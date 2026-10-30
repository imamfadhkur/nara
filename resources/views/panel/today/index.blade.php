<x-panel-layout title="Today" heading="Today — {{ $today->translatedFormat('l, d F Y') }}">
    {{-- Empat Kartu KPI Tetap (Bagian 5.4 Wireframe) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card
            title="HARI INI"
            :value="$todaySchedules->count() . ' Jadwal'"
            :subtitle="$overdueTasks->count() > 0 ? '⚠ ' . $overdueTasks->count() . ' task terlambat' : 'Tidak ada terlambat'"
            :tone="$overdueTasks->count() > 0 ? 'warning' : 'primary'"
        />

        <x-kpi-card
            title="PROGRES MINGGU INI"
            :value="$doneThisWeekCount . ' Selesai'"
            subtitle="Task selesai minggu berjalan"
            tone="accent"
        />

        <x-kpi-card
            title="OUTPUT MINGGU INI"
            :value="$outputsThisWeekCount . ' Output'"
            subtitle="Hasil karya nyata tercatat"
            tone="success"
        />

        @php
            $attentionCount = $overdueTasks->count() + $stagnantProjects->count() + ($capacity['status'] === 'overload' ? 1 : 0);
        @endphp
        <x-kpi-card
            title="PERHATIAN"
            :value="$attentionCount > 0 ? $attentionCount . ' Peringatan' : 'Semua Aman'"
            :subtitle="'Beban: ' . $capacity['percent'] . '% (' . $capacity['status'] . ')'"
            :tone="$attentionCount > 0 ? ($capacity['status'] === 'overload' ? 'danger' : 'warning') : 'neutral'"
        />
    </div>

    {{-- Banner Kapasitas Mingguan (D-14 & Bagian 7.4) --}}
    <div class="nara-card p-5 mb-6 border-l-4 {{ $capacity['status'] === 'overload' ? 'border-l-rose-500 bg-rose-50/30' : ($capacity['status'] === 'padat' ? 'border-l-amber-500 bg-amber-50/20' : 'border-l-emerald-500 bg-emerald-50/20') }}">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kapasitas Minggu Ini</span>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xl font-bold text-slate-900">{{ $capacity['percent'] }}%</span>
                    <x-status-badge :label="ucfirst($capacity['status'])" :tone="$capacity['tone']" />
                    <span class="text-xs text-slate-500">
                        ({{ round($capacity['total_minutes'] / 60, 1) }} dari {{ round($capacity['capacity_minutes'] / 60, 1) }} jam fokus)
                    </span>
                </div>
            </div>

            @if ($capacity['status'] === 'overload')
                <div class="text-xs text-rose-700 bg-rose-100/70 px-3 py-1.5 rounded-lg">
                    <strong>Saran Penyesuaian:</strong> Geser tenggat task, jadikan salah satu project <em>paused</em>, atau turunkan prioritas.
                </div>
            @endif
        </div>

        <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
            <div class="{{ $capacity['status'] === 'overload' ? 'bg-rose-600' : ($capacity['status'] === 'padat' ? 'bg-amber-500' : 'bg-emerald-600') }} h-2.5 transition-all duration-300"
                 style="width: {{ min(100, $capacity['percent']) }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Jadwal Hari Ini --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700">
                    Jadwal Hari Ini ({{ $todaySchedules->count() }})
                </h2>
                <a href="{{ route('panel.tasks.index') }}" class="text-xs text-sky-700 hover:underline">
                    Lihat Semua Task →
                </a>
            </div>

            <div class="nara-card divide-y divide-slate-100 overflow-hidden">
                @forelse ($todaySchedules as $schedule)
                    @php
                        $userTz = auth()->user()->displayTimezone();
                        $timeStr = $schedule->start_at->setTimezone($userTz)->format('H:i') . '–' . $schedule->end_at->setTimezone($userTz)->format('H:i');
                    @endphp
                    <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="font-mono text-xs font-semibold px-2 py-1 rounded bg-slate-100 text-slate-700 whitespace-nowrap">
                                {{ $timeStr }}
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('panel.tasks.show', $schedule->task) }}" class="text-sm font-medium text-slate-900 hover:text-sky-700 truncate block">
                                    {{ $schedule->task->title }}
                                </a>
                                <span class="text-xs text-slate-500">
                                    {{ $schedule->task->project->name }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($schedule->task->isOverdue())
                                <span class="text-xs text-rose-600 font-semibold bg-rose-50 px-2 py-0.5 rounded">⚠ Terlambat</span>
                            @endif
                            <x-status-badge :status="$schedule->task->status" />
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-400">
                        Tidak ada sesi jadwal untuk hari ini.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Panel Perlu Perhatian (Bagian 5.4 Wireframe) --}}
        <div class="space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700">
                Perlu Perhatian ({{ $attentionCount }})
            </h2>

            <div class="space-y-3">
                {{-- Task Terlambat --}}
                @foreach ($overdueTasks as $ot)
                    <div class="nara-card p-3 border-l-4 border-l-rose-500 text-xs">
                        <div class="font-semibold text-rose-800">⚠ Task Terlambat</div>
                        <a href="{{ route('panel.tasks.show', $ot) }}" class="font-medium text-slate-900 hover:underline block mt-0.5">
                            {{ $ot->title }}
                        </a>
                        <div class="text-[11px] text-slate-500 mt-1">
                            Project: {{ $ot->project->name }} · Tenggat: {{ $ot->due_date?->format('d/m') }}
                        </div>
                    </div>
                @endforeach

                {{-- Task Jatuh Tempo Besok Belum Terjadwal --}}
                @foreach ($dueTomorrowUnscheduled as $dt)
                    <div class="nara-card p-3 border-l-4 border-l-amber-500 text-xs">
                        <div class="font-semibold text-amber-800">⏳ Jatuh Tempo Besok (Belum Dijadwalkan)</div>
                        <a href="{{ route('panel.tasks.show', $dt) }}" class="font-medium text-slate-900 hover:underline block mt-0.5">
                            {{ $dt->title }}
                        </a>
                        <div class="text-[11px] text-slate-500 mt-1">
                            Project: {{ $dt->project->name }}
                        </div>
                    </div>
                @endforeach

                {{-- Project Stagnan --}}
                @foreach ($stagnantProjects as $sp)
                    <div class="nara-card p-3 border-l-4 border-l-slate-400 text-xs">
                        <div class="font-semibold text-slate-700">💤 Project Stagnan (> {{ $stagnationDays }} hari)</div>
                        <a href="{{ route('panel.projects.show', $sp) }}" class="font-medium text-slate-900 hover:underline block mt-0.5">
                            {{ $sp->name }}
                        </a>
                        <div class="text-[11px] text-slate-500 mt-1">
                            Aktivitas terakhir: {{ $sp->last_activity_at?->diffForHumans() ?? 'Belum ada' }}
                        </div>
                    </div>
                @endforeach

                @if ($attentionCount === 0 && $dueTomorrowUnscheduled->isEmpty())
                    <div class="nara-card p-5 text-center text-xs text-slate-500">
                        ✨ Semua project dan task berada dalam ritme yang baik. Tidak ada peringatan stagnasi atau keterlambatan.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-panel-layout>
