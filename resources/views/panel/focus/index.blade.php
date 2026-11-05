<x-panel-layout title="Mode Fokus" heading="Mode Fokus (Deep Work Timer)">
    @php
        $tasksMap = $tasks->mapWithKeys(fn ($t) => [
            $t->id => [
                'id' => $t->id,
                'title' => $t->title,
                'project' => $t->project->name,
                'due_date' => $t->due_date ? $t->due_date->format('d M Y') : null,
                'is_overdue' => $t->isOverdue(),
            ],
        ]);
    @endphp

    <script>
        window.naraFocusPage = function () {
            return {
                tasksMap: @js($tasksMap),
                taskId: {{ $selectedTask?->id ?? 'null' }},
                taskTitle: @js($selectedTask?->title ?? ''),
                projectName: @js($selectedTask?->project?->name ?? ''),
                dueDate: @js($selectedTask?->due_date?->format('d M Y')),
                isOverdue: @js($selectedTask?->isOverdue() ?? false),

                presetMinutes: 25,
                secondsRemaining: 1500,
                isRunning: false,
                isPaused: false,
                startedAt: null,
                targetEndAt: null,
                timerInterval: null,
                logModalOpen: false,
                loggedMinutes: 25,
                notifPermission: ('Notification' in window) ? Notification.permission : 'unsupported',

                init() {
                    const params = new URLSearchParams(window.location.search);
                    const saved = window.NaraFocusHelper ? window.NaraFocusHelper.getSession() : null;

                    if (saved && saved.taskId) {
                        const requestedTaskId = params.has('task_id') ? Number(params.get('task_id')) : null;
                        let shouldRestore = true;

                        if (requestedTaskId && requestedTaskId !== Number(saved.taskId) && (saved.status === 'running' || saved.status === 'paused')) {
                            const switchConfirm = confirm(
                                `Sesi fokus untuk task "${saved.taskTitle}" masih berjalan/dijeda.\n\nApakah Anda ingin menghentikan sesi tersebut dan beralih ke task baru ini?`
                            );
                            if (switchConfirm) {
                                window.NaraFocusHelper.clearSession();
                                shouldRestore = false;
                            }
                        }

                        if (shouldRestore) {
                            this.applyTaskData(saved.taskId, saved.taskTitle, saved.projectName);
                            this.presetMinutes = Number(saved.presetMinutes) || 25;
                            this.startedAt = saved.startedAt || null;

                            if (saved.status === 'running' && saved.targetEndAt) {
                                const rem = Math.max(0, Math.ceil((saved.targetEndAt - Date.now()) / 1000));
                                if (rem > 0) {
                                    this.secondsRemaining = rem;
                                    this.targetEndAt = saved.targetEndAt;
                                    this.isRunning = true;
                                    this.isPaused = false;
                                    this.startInterval();
                                } else {
                                    this.secondsRemaining = 0;
                                    this.isRunning = false;
                                    this.isPaused = false;
                                    if (!saved.notified) {
                                        saved.notified = true;
                                        saved.status = 'completed';
                                        window.NaraFocusHelper.saveSession(saved);
                                        window.NaraFocusHelper.sendDesktopNotification(this.taskTitle, this.presetMinutes);
                                    }
                                    this.openLogModal();
                                }
                            } else if (saved.status === 'paused') {
                                this.secondsRemaining = Number(saved.remainingSeconds) || (this.presetMinutes * 60);
                                this.isRunning = false;
                                this.isPaused = true;
                                window.NaraFocusHelper.updateTitle(this.formatTime(), 'paused');
                            } else if (saved.status === 'completed') {
                                this.secondsRemaining = 0;
                                this.isRunning = false;
                                this.isPaused = false;
                                this.openLogModal();
                            }
                        }
                    }

                    if (params.get('open_log') === '1' && !this.logModalOpen) {
                        this.openLogModal();
                    }

                    window.addEventListener('storage', () => this.syncFromStorage());
                    window.addEventListener('nara-focus-updated', () => this.syncFromStorage());
                },

                applyTaskData(id, fallbackTitle = '', fallbackProject = '') {
                    this.taskId = Number(id);
                    if (this.tasksMap && this.tasksMap[id]) {
                        const info = this.tasksMap[id];
                        this.taskTitle = info.title;
                        this.projectName = info.project;
                        this.dueDate = info.due_date;
                        this.isOverdue = info.is_overdue;
                    } else {
                        this.taskTitle = fallbackTitle;
                        this.projectName = fallbackProject;
                        this.dueDate = null;
                        this.isOverdue = false;
                    }
                },

                changeTask(newId) {
                    if ((this.isRunning || this.isPaused) && Number(newId) !== Number(this.taskId)) {
                        if (!confirm('Timer fokus sedang aktif. Ganti task dan mulai ulang timer?')) {
                            this.$refs.taskSelect.value = this.taskId;
                            return;
                        }
                        this.resetTimer();
                    }
                    this.applyTaskData(newId);
                    const url = new URL(window.location.href);
                    url.searchParams.set('task_id', newId);
                    url.searchParams.delete('open_log');
                    window.history.replaceState({}, '', url);
                },

                persistState(status, notified = false) {
                    if (!window.NaraFocusHelper || !this.taskId) return;
                    window.NaraFocusHelper.saveSession({
                        taskId: this.taskId,
                        taskTitle: this.taskTitle,
                        projectName: this.projectName,
                        presetMinutes: this.presetMinutes,
                        status: status,
                        startedAt: this.startedAt,
                        targetEndAt: this.targetEndAt,
                        remainingSeconds: this.secondsRemaining,
                        notified: notified
                    });
                },

                syncFromStorage() {
                    const saved = window.NaraFocusHelper ? window.NaraFocusHelper.getSession() : null;
                    if (!saved) {
                        if (this.isRunning || this.isPaused) {
                            this.pauseIntervalOnly();
                            this.isRunning = false;
                            this.isPaused = false;
                            this.secondsRemaining = this.presetMinutes * 60;
                        }
                        return;
                    }
                    if (saved.status === 'paused' && this.isRunning) {
                        this.pauseIntervalOnly();
                        this.isRunning = false;
                        this.isPaused = true;
                        this.secondsRemaining = saved.remainingSeconds;
                    } else if (saved.status === 'running' && !this.isRunning) {
                        this.targetEndAt = saved.targetEndAt;
                        this.isRunning = true;
                        this.isPaused = false;
                        this.startInterval();
                    }
                },

                formatTime() {
                    const m = Math.floor(this.secondsRemaining / 60);
                    const s = this.secondsRemaining % 60;
                    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                },

                setDuration(minutes) {
                    if (this.isRunning && !confirm('Ubah durasi akan mengulang timer yang sedang berjalan. Lanjutkan?')) {
                        return;
                    }
                    this.pauseIntervalOnly();
                    this.presetMinutes = minutes;
                    this.secondsRemaining = minutes * 60;
                    this.loggedMinutes = minutes;
                    this.isRunning = false;
                    this.isPaused = false;
                    this.startedAt = null;
                    this.targetEndAt = null;
                    if (window.NaraFocusHelper) {
                        window.NaraFocusHelper.clearSession();
                    }
                },

                requestNotifPermission(playTest = false) {
                    if (!('Notification' in window)) return;
                    if (Notification.permission === 'granted') {
                        this.notifPermission = 'granted';
                        if (playTest && window.NaraFocusHelper) {
                            window.NaraFocusHelper.sendDesktopNotification(this.taskTitle || 'Uji Coba Mode Fokus', this.presetMinutes);
                        }
                        return;
                    }
                    Notification.requestPermission().then(permission => {
                        this.notifPermission = permission;
                        if (permission === 'granted' && playTest && window.NaraFocusHelper) {
                            window.NaraFocusHelper.sendDesktopNotification(this.taskTitle || 'Notifikasi Desktop Aktif!', this.presetMinutes);
                        }
                    });
                },

                startTimer() {
                    if (this.notifPermission === 'default') {
                        this.requestNotifPermission(false);
                    }
                    if (this.secondsRemaining <= 0) {
                        this.secondsRemaining = this.presetMinutes * 60;
                    }
                    if (!this.startedAt) {
                        this.startedAt = new Date().toISOString();
                    }
                    this.targetEndAt = Date.now() + (this.secondsRemaining * 1000);
                    this.isRunning = true;
                    this.isPaused = false;
                    this.persistState('running', false);
                    this.startInterval();
                },

                startInterval() {
                    this.pauseIntervalOnly();
                    window.NaraFocusHelper?.updateTitle(this.formatTime(), 'running');
                    this.timerInterval = setInterval(() => {
                        if (!this.targetEndAt) return;
                        const rem = Math.max(0, Math.ceil((this.targetEndAt - Date.now()) / 1000));
                        this.secondsRemaining = rem;
                        if (rem > 0) {
                            window.NaraFocusHelper?.updateTitle(this.formatTime(), 'running');
                        } else {
                            this.handleTimerCompleted();
                        }
                    }, 500);
                },

                pauseIntervalOnly() {
                    if (this.timerInterval) {
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;
                    }
                },

                pauseTimer() {
                    if (this.targetEndAt) {
                        this.secondsRemaining = Math.max(0, Math.ceil((this.targetEndAt - Date.now()) / 1000));
                    }
                    this.pauseIntervalOnly();
                    this.isRunning = false;
                    this.isPaused = true;
                    this.targetEndAt = null;
                    this.persistState('paused', false);
                    window.NaraFocusHelper?.updateTitle(this.formatTime(), 'paused');
                },

                resetTimer() {
                    this.pauseIntervalOnly();
                    this.isRunning = false;
                    this.isPaused = false;
                    this.startedAt = null;
                    this.targetEndAt = null;
                    this.secondsRemaining = this.presetMinutes * 60;
                    if (window.NaraFocusHelper) {
                        window.NaraFocusHelper.clearSession();
                    }
                },

                handleTimerCompleted() {
                    this.pauseIntervalOnly();
                    this.secondsRemaining = 0;
                    this.isRunning = false;
                    this.isPaused = false;
                    this.targetEndAt = null;
                    this.persistState('completed', true);
                    window.NaraFocusHelper?.updateTitle('00:00', 'completed');
                    window.NaraFocusHelper?.sendDesktopNotification(this.taskTitle, this.presetMinutes);
                    this.openLogModal();
                },

                openLogModal() {
                    if (this.isRunning) {
                        this.pauseTimer();
                    }
                    const elapsed = (this.presetMinutes * 60) - this.secondsRemaining;
                    const elapsedMinutes = Math.max(1, Math.round(elapsed / 60));
                    this.loggedMinutes = elapsed > 0 ? elapsedMinutes : this.presetMinutes;
                    this.logModalOpen = true;
                },

                onFormSubmit() {
                    if (window.NaraFocusHelper) {
                        window.NaraFocusHelper.clearSession();
                    }
                }
            };
        };
    </script>

    <div class="max-w-4xl mx-auto" x-data="naraFocusPage()">
        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 flex items-center justify-between shadow-2xs" role="status">
                <div class="flex items-center gap-2">
                    <span class="text-base">✅</span>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        {{-- Header Status & Kontrol Notifikasi Desktop --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <p class="text-sm text-slate-600">
                    Sesi kerja hening terdedikasi untuk satu task tanpa distraksi. Timer <strong>tetap berjalan otomatis</strong> meskipun Anda pindah halaman atau meminimalkan browser.
                </p>
                <div class="flex flex-wrap items-center gap-3 mt-1.5">
                    <p class="text-xs text-slate-500">
                        Total fokus hari ini: <strong class="text-slate-800">{{ $todayTotalMinutes }} menit</strong> ({{ round($todayTotalMinutes / 60, 1) }} jam)
                    </p>
                    <span class="text-slate-300">•</span>

                    {{-- Status & Tombol Izin Notifikasi Desktop --}}
                    <template x-if="notifPermission === 'granted'">
                        <div class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                <span>🔔</span>
                                <span>Notifikasi Desktop & Suara Aktif</span>
                            </span>
                            <button type="button" @click="requestNotifPermission(true)" class="text-[11px] text-sky-700 hover:underline font-medium cursor-pointer">
                                🔊 Uji Suara & Notif
                            </button>
                        </div>
                    </template>

                    <template x-if="notifPermission === 'default'">
                        <button type="button" @click="requestNotifPermission(true)"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 px-2.5 py-0.5 rounded-full border border-amber-300 transition cursor-pointer">
                            <span>🔔</span>
                            <span>Aktifkan Notifikasi Desktop</span>
                        </button>
                    </template>

                    <template x-if="notifPermission === 'denied'">
                        <div class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                <span>🔊</span>
                                <span>Alarm Suara Aktif (Notifikasi OS Diblokir Browser)</span>
                            </span>
                            <button type="button" @click="window.NaraFocusHelper.playChime()" class="text-[11px] text-sky-700 hover:underline font-medium cursor-pointer">
                                Uji Suara
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            @if ($tasks->isNotEmpty())
                <div class="flex items-center gap-2 shrink-0">
                    <label for="taskSelect" class="text-xs font-medium text-slate-700">Pilih Task:</label>
                    <select
                        id="taskSelect"
                        x-ref="taskSelect"
                        :value="taskId"
                        @change="changeTask($event.target.value)"
                        class="text-xs rounded-lg border-slate-300 py-1.5 px-2.5 focus:border-sky-500 max-w-xs"
                    >
                        @foreach ($tasks as $t)
                            <option value="{{ $t->id }}" @selected($selectedTask && $selectedTask->id === $t->id)>
                                #{{ $t->id }} {{ \Illuminate\Support\Str::limit($t->title, 35) }} ({{ $t->project->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if (! $selectedTask)
            <div class="nara-card p-12 text-center">
                <div class="text-4xl mb-3">🎯</div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Tidak Ada Task Aktif</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                    Semua task telah selesai atau Anda belum membuat task baru. Buat task untuk memulai sesi fokus.
                </p>
                <a href="{{ route('panel.tasks.create') }}" class="nara-btn-primary text-xs py-2 px-4">
                    + Buat Task Baru
                </a>
            </div>
        @else
            {{-- Kartu Timer Fokus Utama --}}
            <div class="nara-card p-8 text-center border-2 border-slate-200/80 shadow-md mb-8 relative overflow-hidden bg-gradient-to-b from-white to-slate-50/50">
                {{-- Detail Task Aktif --}}
                <div class="max-w-xl mx-auto mb-6">
                    <span class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full inline-flex items-center gap-1.5 mb-2"
                          :class="isRunning ? 'text-emerald-800 bg-emerald-100' : 'text-sky-700 bg-sky-100'">
                        <span x-show="isRunning" class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span x-text="isRunning ? 'Sedang Berjalan (Tetap Aktif di Latar Belakang)' : 'Sedang Dikerjakan (Focus Mode)'">Sedang Dikerjakan (Focus Mode)</span>
                    </span>
                    <h2 class="text-xl font-bold text-slate-900 mb-1 leading-snug" x-text="taskTitle">
                        {{ $selectedTask->title }}
                    </h2>
                    <p class="text-xs text-slate-500">
                        📁 Proyek: <strong class="text-slate-700" x-text="projectName">{{ $selectedTask->project->name }}</strong>
                        <template x-if="dueDate">
                            <span>
                                • Tenggat:
                                <span :class="isOverdue ? 'text-rose-600 font-bold' : 'text-slate-600'" x-text="dueDate"></span>
                            </span>
                        </template>
                    </p>
                </div>

                {{-- Preset Durasi --}}
                <div class="flex items-center justify-center gap-2 mb-8 flex-wrap">
                    <button
                        type="button"
                        @click="setDuration(25)"
                        :class="presetMinutes === 25 ? 'bg-sky-600 text-white shadow-sm ring-2 ring-sky-300 font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        class="text-xs px-3.5 py-2 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>🍅</span>
                        <span>25 Menit (Pomodoro)</span>
                    </button>
                    <button
                        type="button"
                        @click="setDuration(45)"
                        :class="presetMinutes === 45 ? 'bg-sky-600 text-white shadow-sm ring-2 ring-sky-300 font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        class="text-xs px-3.5 py-2 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>🧠</span>
                        <span>45 Menit (Deep Work)</span>
                    </button>
                    <button
                        type="button"
                        @click="setDuration(60)"
                        :class="presetMinutes === 60 ? 'bg-sky-600 text-white shadow-sm ring-2 ring-sky-300 font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        class="text-xs px-3.5 py-2 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>⚡</span>
                        <span>60 Menit</span>
                    </button>
                    <button
                        type="button"
                        @click="setDuration(15)"
                        :class="presetMinutes === 15 ? 'bg-sky-600 text-white shadow-sm ring-2 ring-sky-300 font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        class="text-xs px-3.5 py-2 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>☕</span>
                        <span>15 Menit (Sprint)</span>
                    </button>
                </div>

                {{-- Tampilan Timer Raksasa --}}
                <div class="mb-8 flex flex-col items-center">
                    <div
                        class="font-mono text-7xl sm:text-8xl font-black tracking-tight select-none transition-colors duration-200 tabular-nums py-1"
                        :class="isRunning ? 'text-sky-600' : (isPaused ? 'text-amber-500' : 'text-slate-800')"
                        x-text="formatTime()"
                    >
                        25:00
                    </div>

                    {{-- Progress bar visual --}}
                    <div class="w-full max-w-sm h-2.5 bg-slate-200 rounded-full overflow-hidden mt-3 shadow-inner">
                        <div
                            class="h-full bg-gradient-to-r from-sky-500 to-indigo-600 transition-all duration-300"
                            :style="'width: ' + Math.min(100, Math.max(0, Math.round(((presetMinutes * 60 - secondsRemaining) / (presetMinutes * 60)) * 100))) + '%'"
                        ></div>
                    </div>

                    <div class="text-xs text-slate-500 font-medium mt-3"
                         x-text="isRunning ? '⚡ Timer sedang berjalan... Anda bebas berpindah halaman, timer akan tampil di pojok kanan bawah.' : (isPaused ? '⏸️ Timer dijeda sementara.' : 'Pilih durasi di atas lalu klik Mulai Fokus.')">
                        Pilih durasi di atas lalu klik Mulai Fokus.
                    </div>
                </div>

                {{-- Kontrol Timer --}}
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <button
                        type="button"
                        x-show="!isRunning"
                        @click="startTimer()"
                        class="nara-btn-primary text-sm py-2.5 px-6 shadow-md flex items-center gap-2 transition transform active:scale-95 cursor-pointer"
                    >
                        <span class="text-base">▶</span>
                        <span x-text="isPaused ? 'Lanjutkan Fokus' : 'Mulai Fokus'">Mulai Fokus</span>
                    </button>

                    <button
                        type="button"
                        x-show="isRunning"
                        @click="pauseTimer()"
                        class="nara-btn-secondary text-sm py-2.5 px-6 shadow-xs flex items-center gap-2 border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100 transition transform active:scale-95 cursor-pointer"
                        style="display: none;"
                    >
                        <span class="text-base">⏸</span>
                        <span>Jeda</span>
                    </button>

                    <button
                        type="button"
                        @click="resetTimer()"
                        class="nara-btn-secondary text-xs py-2.5 px-4 text-slate-600 hover:text-slate-800 transition cursor-pointer"
                        title="Kembalikan timer ke waktu awal preset"
                    >
                        <span>↺</span>
                        <span>Reset</span>
                    </button>

                    <button
                        type="button"
                        @click="openLogModal()"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs py-2.5 px-5 rounded-lg shadow-sm transition flex items-center gap-1.5 transform active:scale-95 cursor-pointer"
                        title="Simpan sesi dan catat actual_minutes"
                    >
                        <span>✓</span>
                        <span>Selesai & Catat</span>
                    </button>
                </div>
            </div>

            {{-- Modal Simpan Sesi Fokus --}}
            <div
                x-show="logModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                style="display: none;"
                @keydown.escape.window="logModalOpen = false"
            >
                <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="logModalOpen = false">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-slate-900">Catat Sesi Fokus</h3>
                        <button type="button" @click="logModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">
                        Simpan capaian fokus Anda untuk task <strong class="text-slate-800" x-text="taskTitle">{{ $selectedTask->title }}</strong>.
                    </p>

                    <form action="{{ route('panel.focus.store') }}" method="POST" class="space-y-4" @submit="onFormSubmit()">
                        @csrf
                        <input type="hidden" name="task_id" :value="taskId">
                        <input type="hidden" name="started_at" :value="startedAt">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Durasi Fokus (Menit)</label>
                            <input
                                type="number"
                                name="duration_minutes"
                                x-model="loggedMinutes"
                                min="1"
                                max="480"
                                class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500 focus:ring-sky-500"
                                required
                            >
                            <p class="text-[11px] text-slate-400 mt-1">Dihitung otomatis dari durasi yang berjalan, dapat disesuaikan manual.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Capaian (Opsional)</label>
                            <input
                                type="text"
                                name="note"
                                placeholder="Contoh: Selesai menyusun draf bab 1"
                                class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500 focus:ring-sky-500"
                            >
                        </div>

                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="mark_done" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-medium text-slate-800">Tandai task ini langsung sebagai <strong class="text-emerald-700">Selesai (Done)</strong></span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="logModalOpen = false" class="nara-btn-secondary text-xs py-2 px-3">
                                Batal
                            </button>
                            <button type="submit" class="nara-btn-primary text-xs py-2 px-4">
                                Simpan Sesi Fokus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Riwayat Sesi Fokus Terbaru --}}
        <div class="nara-card p-5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                🕒 Riwayat Sesi Fokus Terbaru
            </h3>

            @if ($recentSessions->isEmpty())
                <p class="text-xs text-slate-400 italic">Belum ada sesi fokus tercatat.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($recentSessions as $session)
                        <div class="py-2.5 flex items-center justify-between text-xs hover:bg-slate-50/50 px-2 rounded">
                            <div class="truncate max-w-sm sm:max-w-md">
                                <span class="font-semibold text-slate-900">{{ $session->task->title }}</span>
                                <span class="text-slate-400 text-[11px] block sm:inline sm:ml-2">
                                    📁 {{ $session->task->project->name }}
                                    @if ($session->note)
                                        • <i>"{{ $session->note }}"</i>
                                    @endif
                                </span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                    +{{ $session->duration_minutes }} mnt
                                </span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">
                                    {{ $session->started_at->setTimezone(auth()->user()->displayTimezone())->format('d/m H:i') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-panel-layout>
