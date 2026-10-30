<x-panel-layout title="Today (Fokus)" heading="Today (Fokus)">
    @php
        $userTz = auth()->user()->displayTimezone();
        $hour = (int) $today->format('H');
        $greeting = match(true) {
            $hour >= 4 && $hour < 11 => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 18 => 'Selamat Sore',
            default => 'Selamat Malam',
        };

        $attentionCount = $overdueTasks->count() + $stagnantProjects->count() + ($capacity['status'] === 'overload' ? 1 : 0);
        $doneTodayCount = $todaySchedules->where('task.status', \App\Support\Enums\TaskStatus::Done)->count();

        // Persentase segmen kapasitas
        $capTotal = max(1, $capacity['capacity_minutes']);
        $pctRealization = min(100, round(($capacity['realization_minutes'] / $capTotal) * 100, 1));
        $pctScheduled = min(100 - $pctRealization, round(($capacity['planned_scheduled_minutes'] / $capTotal) * 100, 1));
        $pctUnscheduled = min(max(0, 100 - $pctRealization - $pctScheduled), round(($capacity['planned_unscheduled_minutes'] / $capTotal) * 100, 1));
        $freeMinutes = max(0, $capacity['capacity_minutes'] - $capacity['total_minutes']);
    @endphp

    <div x-data="{
        doneModal: false,
        activeTaskId: null,
        activeTaskTitle: '',
        activeTaskEst: 0,
        openDoneModal(id, title, est) {
            this.activeTaskId = id;
            this.activeTaskTitle = title;
            this.activeTaskEst = est || 60;
            this.doneModal = true;
        }
    }" class="space-y-6">

        {{-- 1. Hero Welcome & Focus Header --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-6 text-white shadow-sm border border-slate-800">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-white/10 text-sky-300 text-xs font-medium backdrop-blur-xs">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ $today->translatedFormat('l, d F Y') }}</span>
                        <span class="text-white/40">•</span>
                        <span>{{ $userTz }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                        {{ $greeting }}, {{ auth()->user()->name }}!
                    </h1>
                    <p class="text-sm text-slate-300 max-w-xl leading-relaxed">
                        @if ($todaySchedules->count() > 0)
                            Terdapat <strong>{{ $todaySchedules->count() }} sesi fokus</strong> yang terjadwal untuk hari ini ({{ $doneTodayCount }} selesai).
                        @else
                            Tidak ada sesi jadwal spesifik hari ini. Anda memiliki ruang fleksibel untuk mengerjakan backlog atau triase inbox.
                        @endif
                    </p>
                </div>

                {{-- Tombol Aksi Cepat --}}
                <div class="flex items-center gap-2.5 flex-wrap self-start md:self-auto">
                    <a href="{{ route('panel.tasks.create') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-medium text-xs shadow-xs transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Task Baru</span>
                    </a>
                    <a href="{{ route('panel.kanban.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-xs backdrop-blur-xs transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        <span>Kanban Board</span>
                    </a>
                </div>
            </div>

            {{-- Background decorative shapes --}}
            <div class="absolute -right-8 -bottom-10 w-56 h-56 rounded-full bg-sky-500/10 blur-2xl pointer-events-none"></div>
            <div class="absolute right-40 -top-10 w-48 h-48 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
        </div>

        {{-- 2. Empat Kartu KPI Interaktif --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- KPI 1: Sesi Hari Ini --}}
            <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs hover:shadow-sm transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Jadwal Hari Ini</span>
                    <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-slate-900">{{ $todaySchedules->count() }}</span>
                    <span class="text-xs font-semibold text-slate-500">Sesi Terencana</span>
                </div>
                <div class="mt-2.5 flex items-center gap-1.5 text-xs text-slate-600">
                    <span class="inline-block w-2 h-2 rounded-full {{ $doneTodayCount > 0 ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                    <span><strong>{{ $doneTodayCount }}</strong> selesai</span>
                    <span class="text-slate-300">•</span>
                    <span><strong>{{ max(0, $todaySchedules->count() - $doneTodayCount) }}</strong> tersisa</span>
                </div>
            </div>

            {{-- KPI 2: Task Selesai Minggu Ini --}}
            <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs hover:shadow-sm transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Progres Minggu Ini</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-slate-900">{{ $doneThisWeekCount }}</span>
                    <span class="text-xs font-semibold text-slate-500">Task Selesai</span>
                </div>
                <div class="mt-2.5 flex items-center gap-1.5 text-xs text-indigo-700 font-medium">
                    <span>Realisasi {{ round($capacity['realization_minutes'] / 60, 1) }} jam fokus</span>
                </div>
            </div>

            {{-- KPI 3: Output Nyata --}}
            <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs hover:shadow-sm transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Output Karya</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-slate-900">{{ $outputsThisWeekCount }}</span>
                    <span class="text-xs font-semibold text-slate-500">Hasil Karya</span>
                </div>
                <div class="mt-2.5 flex items-center gap-1 text-xs text-emerald-700 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    <span>Deliverable minggu berjalan</span>
                </div>
            </div>

            {{-- KPI 4: Radar Perhatian --}}
            <div class="bg-white rounded-2xl p-4.5 border {{ $attentionCount > 0 ? ($overdueTasks->count() > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-amber-300 bg-amber-50/20') : 'border-slate-200/80' }} shadow-xs hover:shadow-sm transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider {{ $attentionCount > 0 ? 'text-rose-700' : 'text-slate-500' }}">Radar Perhatian</span>
                    <div class="w-8 h-8 rounded-xl {{ $attentionCount > 0 ? ($overdueTasks->count() > 0 ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600') : 'bg-slate-100 text-slate-500' }} flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold {{ $attentionCount > 0 ? ($overdueTasks->count() > 0 ? 'text-rose-700' : 'text-amber-700') : 'text-slate-900' }}">
                        {{ $attentionCount }}
                    </span>
                    <span class="text-xs font-semibold text-slate-500">{{ $attentionCount > 0 ? 'Peringatan' : 'Semua Beres' }}</span>
                </div>
                <div class="mt-2.5 flex items-center gap-1.5 text-xs {{ $attentionCount > 0 ? 'text-rose-700 font-medium' : 'text-slate-500' }}">
                    @if ($overdueTasks->count() > 0)
                        <span>⚠ {{ $overdueTasks->count() }} terlambat</span>
                    @elseif ($stagnantProjects->count() > 0)
                        <span>{{ $stagnantProjects->count() }} project stagnan</span>
                    @else
                        <span>Ritme kerja terkendali</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. Visual Capacity Meter (Multi-Segmented Bar) --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kapasitas & Beban Mingguan (7.4)</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $capacity['status'] === 'overload' ? 'bg-rose-100 text-rose-800' : ($capacity['status'] === 'padat' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                            {{ ucfirst($capacity['status']) }}
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-extrabold text-slate-900">{{ $capacity['percent'] }}%</span>
                        <span class="text-xs text-slate-500">
                            Terisi <strong>{{ $capacity['total_minutes'] }} mnt</strong> ({{ round($capacity['total_minutes'] / 60, 1) }} jam) dari alokasi <strong>{{ $capacity['capacity_minutes'] }} mnt</strong> (25 jam)
                        </span>
                    </div>
                </div>

                @if ($capacity['status'] === 'overload')
                    <div class="text-xs text-rose-700 bg-rose-50 border border-rose-200 px-3 py-2 rounded-xl">
                        🚨 <strong>Overload!</strong> Saran: geser tenggat task, jadikan 1 project <em>paused</em>, atau turunkan prioritas.
                    </div>
                @else
                    <div class="text-xs text-slate-500 self-start sm:self-auto">
                        Tersedia <strong>{{ $freeMinutes }} mnt</strong> ({{ round($freeMinutes / 60, 1) }} jam) ruang bebas
                    </div>
                @endif
            </div>

            {{-- Multi-Segmented Visual Bar --}}
            <div class="space-y-2">
                <div class="w-full bg-slate-100 rounded-xl h-4 overflow-hidden flex p-0.5 border border-slate-200/60">
                    {{-- 1. Realisasi Selesai --}}
                    @if ($pctRealization > 0)
                        <div class="bg-emerald-500 h-full rounded-l-lg transition-all duration-300"
                             style="width: {{ $pctRealization }}%"
                             title="Realisasi Selesai: {{ $capacity['realization_minutes'] }} mnt ({{ $pctRealization }}%)"></div>
                    @endif
                    {{-- 2. Rencana Terjadwal --}}
                    @if ($pctScheduled > 0)
                        <div class="bg-sky-500 h-full transition-all duration-300"
                             style="width: {{ $pctScheduled }}%"
                             title="Rencana Terjadwal: {{ $capacity['planned_scheduled_minutes'] }} mnt ({{ $pctScheduled }}%)"></div>
                    @endif
                    {{-- 3. Rencana Belum Terjadwal (Jatuh Tempo) --}}
                    @if ($pctUnscheduled > 0)
                        <div class="bg-amber-400 h-full transition-all duration-300"
                             style="width: {{ $pctUnscheduled }}%"
                             title="Belum Dijadwalkan: {{ $capacity['planned_unscheduled_minutes'] }} mnt ({{ $pctUnscheduled }}%)"></div>
                    @endif
                </div>

                {{-- Legend Visual --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-600 pt-1">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span>
                        <span>Realisasi Selesai (<strong>{{ $capacity['realization_minutes'] }} mnt</strong>)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-sky-500 inline-block"></span>
                        <span>Rencana Terjadwal (<strong>{{ $capacity['planned_scheduled_minutes'] }} mnt</strong>)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-amber-400 inline-block"></span>
                        <span>Jatuh Tempo Belum Terjadwal (<strong>{{ $capacity['planned_unscheduled_minutes'] }} mnt</strong>)</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400 ml-auto">
                        <span class="w-3 h-3 rounded bg-slate-200 inline-block"></span>
                        <span>Sisa Bebas ({{ $freeMinutes }} mnt)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Konten Utama: Timeline Sesi Hari Ini & Kolom Samping --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- Kolom Kiri: Vertical Focus Timeline --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between pb-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                            Jadwal Sesi Fokus Hari Ini
                        </h2>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold">
                            {{ $todaySchedules->count() }}
                        </span>
                    </div>
                    <a href="{{ route('panel.tasks.index') }}" class="text-xs text-sky-700 hover:underline font-medium">
                        Tabel Semua Task →
                    </a>
                </div>

                @if ($todaySchedules->isEmpty())
                    <div class="bg-white rounded-2xl p-10 text-center border border-slate-200/80 shadow-xs">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-800">Tidak ada jadwal hari ini</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Gunakan waktu untuk membersihkan Inbox atau jadwalkan task penting dari Kanban view.
                        </p>
                        <div class="mt-4 flex items-center justify-center gap-2">
                            <a href="{{ route('panel.inbox.index') }}" class="nara-btn-secondary text-xs">Periksa Inbox</a>
                            <a href="{{ route('panel.kanban.index') }}" class="nara-btn-primary text-xs">Buka Kanban</a>
                        </div>
                    </div>
                @else
                    {{-- Vertical Timeline List --}}
                    <div class="relative pl-6 sm:pl-8 space-y-4 before:absolute before:left-2.5 sm:before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
                        @foreach ($todaySchedules as $schedule)
                            @php
                                $startTime = $schedule->start_at->setTimezone($userTz);
                                $endTime = $schedule->end_at->setTimezone($userTz);
                                $timeStr = $startTime->format('H:i') . ' – ' . $endTime->format('H:i');
                                $durationMnt = $schedule->durationMinutes();
                                $isDone = $schedule->task->status === \App\Support\Enums\TaskStatus::Done;
                            @endphp

                            <div class="relative group">
                                {{-- Timeline Dot Marker --}}
                                <div class="absolute -left-6 sm:-left-8 top-4 flex items-center justify-center">
                                    @if ($isDone)
                                        <div class="w-5 h-5 rounded-full bg-emerald-500 border-2 border-white shadow-xs text-white flex items-center justify-center">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-5 h-5 rounded-full bg-white border-2 border-sky-500 shadow-xs flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-sky-500"></div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Session Card --}}
                                <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs hover:shadow-sm hover:border-slate-300 transition {{ $isDone ? 'bg-slate-50/50 opacity-80' : '' }}">
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                        <div class="space-y-1.5 flex-1 min-w-0">
                                            {{-- Time & Project Badge --}}
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="inline-flex items-center gap-1 font-mono text-xs font-bold px-2.5 py-0.5 rounded-lg {{ $isDone ? 'bg-slate-200/70 text-slate-600' : 'bg-sky-50 text-sky-800 border border-sky-200/60' }}">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{{ $timeStr }}</span>
                                                    <span class="text-slate-400 font-normal">({{ $durationMnt }} mnt)</span>
                                                </span>

                                                <span class="text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">
                                                    {{ $schedule->task->project->name }}
                                                </span>

                                                @if ($schedule->task->isOverdue() && ! $isDone)
                                                    <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                                        ⚠ Terlambat
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Task Title --}}
                                            <div>
                                                <a href="{{ route('panel.tasks.show', $schedule->task) }}" class="text-base font-bold text-slate-900 hover:text-sky-700 transition leading-snug {{ $isDone ? 'line-through text-slate-400' : '' }}">
                                                    {{ $schedule->task->title }}
                                                </a>
                                            </div>

                                            {{-- Note / Subtasks count --}}
                                            @if ($schedule->note)
                                                <p class="text-xs text-slate-500 italic">"{{ $schedule->note }}"</p>
                                            @endif

                                            @if ($schedule->task->subtasks->isNotEmpty())
                                                @php
                                                    $subDone = $schedule->task->subtasks->where('is_done', true)->count();
                                                    $subTotal = $schedule->task->subtasks->count();
                                                    $subPct = round(($subDone / $subTotal) * 100);
                                                @endphp
                                                <div class="flex items-center gap-2 pt-1 max-w-xs">
                                                    <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                        <div class="bg-sky-500 h-1.5 rounded-full" style="width: {{ $subPct }}%"></div>
                                                    </div>
                                                    <span class="text-[11px] font-medium text-slate-500 whitespace-nowrap">{{ $subDone }}/{{ $subTotal }} subtask</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                            @if ($isDone)
                                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Selesai</span>
                                                </span>
                                            @else
                                                <button
                                                    type="button"
                                                    @click="openDoneModal({{ $schedule->task->id }}, $el.dataset.title, $el.dataset.est)"
                                                    data-title="{{ $schedule->task->title }}"
                                                    data-est="{{ $schedule->task->estimated_minutes ?? 60 }}"
                                                    class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Tandai Selesai</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Kolom Kanan: Radar Perhatian & Bukti Karya Minggu Ini --}}
            <div class="space-y-6">

                {{-- Radar Perlu Perhatian --}}
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <span>Perlu Perhatian</span>
                            @if ($attentionCount > 0)
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                            @endif
                        </h3>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $attentionCount > 0 ? 'bg-rose-100 text-rose-700 font-bold' : 'bg-slate-100 text-slate-500' }}">
                            {{ $attentionCount }}
                        </span>
                    </div>

                    <div class="space-y-2.5">
                        {{-- Task Terlambat --}}
                        @forelse ($overdueTasks as $ot)
                            <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-200/80 text-xs space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] font-bold text-rose-800">
                                    <span>⚠ Terlambat ({{ $ot->due_date?->diffForHumans() }})</span>
                                    <span>#{{ $ot->id }}</span>
                                </div>
                                <a href="{{ route('panel.tasks.show', $ot) }}" class="font-bold text-slate-900 hover:text-rose-700 block text-xs leading-snug">
                                    {{ $ot->title }}
                                </a>
                                <div class="flex items-center justify-between pt-1 text-[11px] text-slate-500">
                                    <span class="truncate max-w-[150px]">{{ $ot->project->name }}</span>
                                    <button
                                        type="button"
                                        @click="openDoneModal({{ $ot->id }}, $el.dataset.title, $el.dataset.est)"
                                        data-title="{{ $ot->title }}"
                                        data-est="{{ $ot->estimated_minutes ?? 60 }}"
                                        class="text-rose-700 hover:underline font-bold"
                                    >
                                        ✓ Selesai
                                    </button>
                                </div>
                            </div>
                        @empty
                        @endforelse

                        {{-- Task Jatuh Tempo Besok Belum Terjadwal --}}
                        @foreach ($dueTomorrowUnscheduled as $dt)
                            <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-200/80 text-xs space-y-1">
                                <div class="text-[11px] font-bold text-amber-800">⏳ Jatuh Tempo Besok (Belum Terjadwal)</div>
                                <a href="{{ route('panel.tasks.show', $dt) }}" class="font-bold text-slate-900 hover:underline block leading-snug">
                                    {{ $dt->title }}
                                </a>
                                <div class="text-[11px] text-slate-500">Project: {{ $dt->project->name }}</div>
                            </div>
                        @endforeach

                        {{-- Project Stagnan --}}
                        @foreach ($stagnantProjects as $sp)
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                                <div class="text-[11px] font-bold text-slate-700">💤 Project Stagnan (> {{ $stagnationDays }} hari)</div>
                                <a href="{{ route('panel.projects.show', $sp) }}" class="font-bold text-slate-900 hover:text-sky-700 block leading-snug">
                                    {{ $sp->name }}
                                </a>
                                <div class="text-[11px] text-slate-500">
                                    Aktivitas terakhir: {{ $sp->last_activity_at?->diffForHumans() ?? 'Belum ada' }}
                                </div>
                            </div>
                        @endforeach

                        @if ($attentionCount === 0 && $dueTomorrowUnscheduled->isEmpty())
                            <div class="py-6 text-center text-xs text-slate-500 space-y-1">
                                <div class="text-lg">✨</div>
                                <p class="font-semibold text-slate-700">Semua Berjalan Lancar</p>
                                <p class="text-[11px] text-slate-400">Tidak ada task tertunda atau project stagnan.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Bukti Karya / Output Terbaru --}}
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                            Bukti Karya Terbaru (Outputs)
                        </h3>
                        <a href="{{ route('panel.outputs.index') }}" class="text-xs text-sky-700 hover:underline font-semibold">
                            Lihat Semua →
                        </a>
                    </div>

                    <div class="space-y-2.5">
                        @forelse ($recentOutputs as $out)
                            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/60 text-xs space-y-1 hover:bg-slate-100/60 transition">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        {{ $out->output_type->label() }}
                                    </span>
                                    <span class="text-[11px] text-slate-400">{{ $out->produced_at?->format('d/m') }}</span>
                                </div>
                                <div class="font-semibold text-slate-900 leading-snug">
                                    {{ $out->title }}
                                </div>
                                @if ($out->evidence_url)
                                    <div class="pt-0.5">
                                        <a href="{{ $out->evidence_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[11px] text-sky-700 hover:underline font-medium">
                                            <span>Buka Berkas Bukti</span>
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic py-3 text-center">Belum ada output tercatat minggu ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Tandai Selesai & Catat Output (D-16) Terintegrasi --}}
        <template x-if="doneModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" x-cloak>
                <div @click.outside="doneModal = false" class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="font-bold text-slate-900 text-base">Tandai Task Selesai</h3>
                        <button @click="doneModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form :action="'{{ url('panel/kanban/tasks') }}/' + activeTaskId + '/move'" method="POST" class="space-y-4 text-sm" x-data="{ wantOutput: true }">
                        @csrf
                        <input type="hidden" name="status" value="done">

                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Judul Task</span>
                            <p class="text-sm font-bold text-slate-900" x-text="activeTaskTitle"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Waktu Realisasi (Menit)</label>
                            <input type="number" name="actual_minutes" :value="activeTaskEst" placeholder="60"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                        </div>

                        <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 space-y-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="record_output" value="1" x-model="wantOutput"
                                       class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-bold text-emerald-950">Catat Bukti Karya / Output Sekaligus (D-16)</span>
                            </label>

                            <div x-show="wantOutput" class="space-y-2.5 pt-1">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-0.5">Judul Output</label>
                                    <input type="text" name="output_title" :value="activeTaskTitle"
                                           class="w-full rounded-lg border-slate-300 bg-white text-xs focus:border-sky-600 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-0.5">Jenis Output</label>
                                    <select name="output_type" class="w-full rounded-lg border-slate-300 bg-white text-xs focus:border-sky-600 focus:ring-sky-500">
                                        @foreach (\App\Support\Enums\OutputType::cases() as $type)
                                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-0.5">Tautan Bukti (URL)</label>
                                    <input type="url" name="evidence_url" placeholder="https://github.com/... atau tautan deliverable"
                                           class="w-full rounded-lg border-slate-300 bg-white text-xs focus:border-sky-600 focus:ring-sky-500">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="doneModal = false" class="nara-btn-secondary text-xs">Batal</button>
                            <button type="submit" class="nara-btn-primary text-xs bg-emerald-600 hover:bg-emerald-700">Simpan & Selesaikan</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-panel-layout>
