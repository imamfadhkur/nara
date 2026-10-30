<x-panel-layout :title="$task->title" :heading="'Task: '.$task->title">
    @php
        $matrix = app(\App\Domain\Tasks\MatrixClassifier::class);
        $quadrant = $matrix->classify($task);
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card
            title="Status & Kuadran"
            :value="$task->status->label()"
            :subtitle="'Kuadran: ' . $matrix->label($quadrant)"
            :tone="$matrix->tone($quadrant)"
        />

        <x-kpi-card
            title="Estimasi vs Realisasi"
            :value="($task->actual_minutes ?? 0) . ' / ' . ($task->estimated_minutes ?? 0) . ' mnt'"
            subtitle="Dasar kapasitas terencana (7.4)"
            tone="primary"
        />

        <x-kpi-card
            title="Tenggat Waktu"
            :value="$task->due_date?->format('d/m/Y') ?? 'Tidak ada'"
            :subtitle="$task->isOverdue() ? '⚠ Telah melewati tenggat!' : ($task->due_date ? $task->due_date->diffForHumans() : 'Fleksibel')"
            :tone="$task->isOverdue() ? 'danger' : 'neutral'"
        />

        <x-kpi-card
            title="Subtask Selesai"
            :value="$task->subtasks->where('is_done', true)->count() . ' / ' . $task->subtasks->count()"
            subtitle="Checklist butir pengerjaan"
            tone="accent"
        />
    </div>

    {{-- Detail Info Task --}}
    <div class="nara-card p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Project:</span>
                <a href="{{ route('panel.projects.show', $task->project) }}" class="ml-1 text-sm font-semibold text-sky-700 hover:underline">
                    {{ $task->project->name }}
                </a>
                @if ($task->project->goal)
                    <span class="text-xs text-slate-500">→ {{ $task->project->goal->title }}</span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('panel.tasks.edit', $task) }}" class="nara-btn-secondary text-xs">Ubah Task</a>
                <x-confirm-dialog
                    :action="route('panel.tasks.destroy', $task)"
                    title="Hapus Task"
                    message="Soft-delete task ini. Lanjutkan?">
                    <button type="button" class="nara-btn-danger text-xs">Hapus</button>
                </x-confirm-dialog>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Prioritas:</span>
                <x-status-badge :status="$task->priority" />
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Dampak Project (Impact):</span>
                <x-status-badge :status="$task->project->impact" />
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Selesai Pada:</span>
                <span class="font-medium text-slate-700">{{ $task->completed_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Kuadran Matrix:</span>
                <span class="font-semibold text-sky-800">{{ $matrix->label($quadrant) }}</span>
            </div>
        </div>

        @if ($task->status->value === 'waiting' && $task->waiting_reason)
            <div class="mt-4 p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-900">
                <span class="font-semibold">Alasan Menunggu:</span> {{ $task->waiting_reason }}
            </div>
        @endif

        @if ($task->description)
            <div class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-700">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Deskripsi:</span>
                <p class="whitespace-pre-line">{{ $task->description }}</p>
            </div>
        @endif
    </div>

    {{-- Subtask Checklist (Bagian 1.4 & 9.6) --}}
    <div class="nara-card p-5 mb-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 mb-3">
            Subtask Checklist ({{ $task->subtasks->count() }})
        </h2>

        <div class="space-y-2 mb-4">
            @forelse ($task->subtasks as $subtask)
                <div class="flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 border border-slate-100 transition">
                    <form method="POST" action="{{ route('panel.tasks.subtasks.toggle', [$task, $subtask]) }}" class="inline-flex items-center gap-2.5 flex-1 min-w-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer flex items-center justify-center">
                            @if ($subtask->is_done)
                                <svg class="h-3.5 w-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="h-3.5 w-3.5 border border-slate-400 rounded-sm"></span>
                            @endif
                        </button>
                        <span class="text-sm truncate {{ $subtask->is_done ? 'line-through text-slate-400' : 'text-slate-800' }}">
                            {{ $subtask->title }}
                        </span>
                    </form>

                    <form method="POST" action="{{ route('panel.tasks.subtasks.destroy', [$task, $subtask]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-rose-600 p-1 text-xs" title="Hapus subtask">✕</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">Belum ada subtask checklist.</p>
            @endforelse
        </div>

        {{-- Form Tambah Subtask --}}
        <form method="POST" action="{{ route('panel.tasks.subtasks.store', $task) }}" class="flex items-center gap-2">
            @csrf
            <input type="text" name="title" required placeholder="+ Tambah subtask baru..."
                   class="nara-input text-xs py-1.5 flex-1">
            <button type="submit" class="nara-btn-secondary text-xs py-1.5">Tambah</button>
        </form>
    </div>

    {{-- Riwayat Aktivitas Task (Bagian 6.9 & 6.10) --}}
    <div class="nara-card p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 mb-3">
            Riwayat Aktivitas Task
        </h2>

        <div class="space-y-3">
            @forelse ($logs as $log)
                <div class="flex items-start gap-3 text-xs text-slate-600 pb-2.5 border-b border-slate-50 last:border-b-0">
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-500 uppercase">
                        {{ $log->source->value }}
                    </span>
                    <div class="flex-1">
                        <span class="font-medium text-slate-800">{{ $log->event->label() }}</span>
                        @if ($log->after_value)
                            <span class="text-slate-500">
                                @if (isset($log->after_value['status']))
                                    menjadi <strong class="text-sky-700">{{ $log->after_value['status'] }}</strong>
                                @endif
                                @if (isset($log->after_value['waiting_reason']))
                                    ({{ $log->after_value['waiting_reason'] }})
                                @endif
                            </span>
                        @endif
                    </div>
                    <time class="text-slate-400 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">Belum ada riwayat aktivitas tercatat.</p>
            @endforelse
        </div>
    </div>
</x-panel-layout>
