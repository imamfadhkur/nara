<x-panel-layout title="Mode Fokus" heading="Mode Fokus (Deep Work Timer)">
    <div class="max-w-4xl mx-auto" x-data="{
        presetMinutes: 25,
        secondsRemaining: 1500,
        isRunning: false,
        isPaused: false,
        timerInterval: null,
        logModalOpen: false,
        loggedMinutes: 25,

        formatTime() {
            const m = Math.floor(this.secondsRemaining / 60);
            const s = this.secondsRemaining % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },

        setDuration(minutes) {
            this.pauseTimer();
            this.presetMinutes = minutes;
            this.secondsRemaining = minutes * 60;
            this.loggedMinutes = minutes;
            this.isPaused = false;
        },

        startTimer() {
            this.isRunning = true;
            this.isPaused = false;
            clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                if (this.secondsRemaining > 0) {
                    this.secondsRemaining--;
                } else {
                    this.pauseTimer();
                    this.openLogModal();
                }
            }, 1000);
        },

        pauseTimer() {
            this.isRunning = false;
            this.isPaused = true;
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },

        resetTimer() {
            this.pauseTimer();
            this.isPaused = false;
            this.secondsRemaining = this.presetMinutes * 60;
        },

        openLogModal() {
            this.pauseTimer();
            const elapsed = (this.presetMinutes * 60) - this.secondsRemaining;
            const elapsedMinutes = Math.max(1, Math.round(elapsed / 60));
            this.loggedMinutes = elapsedMinutes > 0 ? elapsedMinutes : this.presetMinutes;
            this.logModalOpen = true;
        }
    }">
        {{-- Header Status --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <p class="text-sm text-slate-600">
                    Sesi kerja hening terdedikasi untuk satu task tanpa distraksi. Waktu tercatat otomatis ke <code>actual_minutes</code> task.
                </p>
                <p class="text-xs text-slate-500 mt-1">
                    Total fokus hari ini: <strong class="text-slate-800">{{ $todayTotalMinutes }} menit</strong> ({{ round($todayTotalMinutes / 60, 1) }} jam)
                </p>
            </div>
            @if ($tasks->isNotEmpty())
                <div class="flex items-center gap-2">
                    <label for="taskSelect" class="text-xs font-medium text-slate-700">Pilih Task:</label>
                    <select
                        id="taskSelect"
                        @change="window.location.href = '{{ route('panel.focus') }}?task_id=' + $event.target.value"
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
                    <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700 bg-sky-100 px-2.5 py-0.5 rounded-full inline-block mb-2">
                        Sedang Dikerjakan (Focus Mode)
                    </span>
                    <h2 class="text-xl font-bold text-slate-900 mb-1 leading-snug">
                        {{ $selectedTask->title }}
                    </h2>
                    <p class="text-xs text-slate-500">
                        📁 Proyek: <strong class="text-slate-700">{{ $selectedTask->project->name }}</strong>
                        @if ($selectedTask->due_date)
                            • Tenggat: <span class="{{ $selectedTask->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-600' }}">{{ $selectedTask->due_date->format('d M Y') }}</span>
                        @endif
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
                         x-text="isRunning ? '⚡ Timer sedang berjalan... Tetap fokus pada task ini.' : (isPaused ? '⏸️ Timer dijeda sementara.' : 'Pilih durasi di atas lalu klik Mulai Fokus.')">
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
                        Simpan capaian fokus Anda untuk task <strong>{{ $selectedTask->title }}</strong>.
                    </p>

                    <form action="{{ route('panel.focus.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="task_id" value="{{ $selectedTask->id }}">

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
