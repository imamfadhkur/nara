<x-panel-layout title="Roadmap & Timeline" heading="Timeline & Roadmap Proyek">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-600">
                Visualisasi horizon proyek, progres penyelesaian task, dan pencapaian milestone strategis.
            </p>
            <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                <span>Total Proyek: <strong class="text-slate-800">{{ $totalProjects }}</strong></span>
                <span>•</span>
                <span class="text-emerald-600 font-medium">Aktif (WIP): {{ $activeProjects }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('panel.projects.create') }}" class="nara-btn-primary text-xs py-1.5 px-3">
                + Proyek Baru
            </a>
        </div>
    </div>

    @if ($roadmap->isEmpty())
        <div class="nara-card p-12 text-center">
            <div class="text-4xl mb-3">🗺️</div>
            <h3 class="text-base font-semibold text-slate-900 mb-1">Belum Ada Proyek</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                Buat proyek pertama Anda untuk memetakan roadmap, tonggak pencapaian, dan deadline horizon.
            </p>
            <a href="{{ route('panel.projects.create') }}" class="nara-btn-primary text-xs py-2 px-4">
                Buat Proyek Sekarang
            </a>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($roadmap as $item)
                @php
                    $proj = $item['project'];
                    $pct = $item['progress_percent'];
                    $barColor = match(true) {
                        $pct >= 100 => 'bg-emerald-500',
                        $pct >= 60 => 'bg-sky-500',
                        $pct >= 30 => 'bg-amber-500',
                        default => 'bg-indigo-500',
                    };
                @endphp
                <div class="nara-card p-5 border border-slate-200/90 shadow-2xs hover:shadow-xs transition">
                    {{-- Header Proyek --}}
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-3">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <a href="{{ route('panel.projects.show', $proj) }}" class="text-base font-bold text-slate-900 hover:text-sky-700">
                                    {{ $proj->name }}
                                </a>
                                <x-status-badge :status="$proj->status" />
                                <x-status-badge :status="$proj->impact" />
                                @if ($proj->lifeDomain)
                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium border border-slate-200">
                                        {{ $proj->lifeDomain->name }}
                                    </span>
                                @endif
                            </div>
                            @if ($proj->outcome)
                                <p class="text-xs text-slate-600 line-clamp-1 italic">
                                    🎯 Target Output: {{ $proj->outcome }}
                                </p>
                            @endif
                        </div>

                        {{-- Tanggal Horizon --}}
                        <div class="text-right text-xs text-slate-500 shrink-0">
                            @if ($proj->target_date)
                                <div>Tenggat: <strong class="text-slate-800">{{ $proj->target_date->format('d M Y') }}</strong></div>
                                @php
                                    $diff = (int) now()->startOfDay()->diffInDays($proj->target_date->startOfDay(), false);
                                @endphp
                                <div class="text-[11px] {{ $diff < 0 ? 'text-rose-600 font-bold' : ($diff <= 7 ? 'text-amber-600 font-medium' : 'text-slate-400') }}">
                                    @if ($diff < 0)
                                        Terlewat {{ abs($diff) }} hari
                                    @elseif ($diff === 0)
                                        Hari ini!
                                    @else
                                        {{ $diff }} hari tersisa
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400">Tanpa target tanggal</span>
                            @endif
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs mb-1.5 font-medium">
                            <span class="text-slate-700">Penyelesaian Task</span>
                            <span class="text-slate-900 font-bold">{{ $item['completed_tasks'] }} / {{ $item['total_tasks'] }} Task ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200/80">
                            <div class="h-full {{ $barColor }} rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    {{-- Milestone / Task List Breakdown --}}
                    <div class="bg-slate-50/70 rounded-lg p-3 border border-slate-200/60" x-data="{ expanded: false }">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">
                                📌 Task Terbuka ({{ $item['open_tasks']->count() }})
                            </span>
                            <button @click="expanded = !expanded" type="button" class="text-sky-600 hover:text-sky-800 text-[11px] font-medium flex items-center gap-1">
                                <span x-text="expanded ? 'Sembunyikan' : 'Lihat Rincian Milestone'"></span>
                                <span x-text="expanded ? '▲' : '▼'"></span>
                            </button>
                        </div>

                        <div x-show="expanded" class="mt-3 space-y-2 pt-2 border-t border-slate-200/70" style="display: none;">
                            @forelse ($proj->tasks as $task)
                                <div class="flex items-center justify-between text-xs py-1 px-2 rounded hover:bg-white bg-slate-100/50">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="{{ $task->status->value === 'done' ? 'text-emerald-600' : 'text-slate-400' }}">
                                            {{ $task->status->value === 'done' ? '✓' : '☐' }}
                                        </span>
                                        <a href="{{ route('panel.tasks.show', $task) }}" class="{{ $task->status->value === 'done' ? 'line-through text-slate-400' : 'text-slate-800 font-medium hover:text-sky-700' }} truncate">
                                            {{ $task->title }}
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 text-[11px] text-slate-500">
                                        <x-status-badge :status="$task->priority" />
                                        @if ($task->due_date)
                                            <span class="{{ $task->isOverdue() ? 'text-rose-600 font-bold' : '' }}">
                                                {{ $task->due_date->format('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-[11px] text-slate-400 italic py-1">Belum ada task pada proyek ini.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-panel-layout>
