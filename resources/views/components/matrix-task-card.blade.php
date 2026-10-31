@props(['task', 'accent' => 'slate'])

@php
    $accentBorder = match($accent) {
        'rose' => 'hover:border-rose-400',
        'sky' => 'hover:border-sky-400',
        'amber' => 'hover:border-amber-400',
        'purple' => 'hover:border-purple-400',
        default => 'hover:border-slate-400',
    };
@endphp

<div class="bg-white rounded-lg p-3 border border-slate-200/90 shadow-2xs {{ $accentBorder }} transition-all group">
    <div class="flex items-start justify-between gap-2">
        <a href="{{ route('panel.tasks.show', $task) }}" class="text-xs font-semibold text-slate-900 hover:text-sky-700 leading-snug line-clamp-2">
            {{ $task->title }}
        </a>
        <x-status-badge :status="$task->priority" />
    </div>

    <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between gap-1">
        <span class="font-medium text-slate-600 truncate max-w-[130px]" title="{{ $task->project->name }}">
            📁 {{ $task->project->name }}
        </span>
        @if ($task->due_date)
            <span class="{{ $task->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-500' }}">
                📅 {{ $task->due_date->format('d/m') }}
                @if ($task->isOverdue()) ⚠️ @endif
            </span>
        @endif
    </div>

    @if ($task->status->value === 'waiting' && $task->waiting_reason)
        <div class="mt-2 p-1.5 bg-purple-50 rounded text-[11px] text-purple-800 border border-purple-200">
            <span class="font-semibold">Menunggu:</span> {{ $task->waiting_reason }}
        </div>
    @endif

    <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
        <span class="text-slate-400 font-mono">#{{ $task->id }}</span>
        <div class="flex items-center gap-1.5 opacity-90 group-hover:opacity-100">
            <a href="{{ route('panel.focus') }}?task_id={{ $task->id }}" class="text-sky-600 hover:text-sky-800 font-medium px-1.5 py-0.5 rounded hover:bg-sky-50" title="Mulai Fokus">
                ⏱️ Fokus
            </a>
            <form action="{{ route('panel.kanban.tasks.move', $task) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="status" value="done">
                <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-medium px-1.5 py-0.5 rounded hover:bg-emerald-50" title="Tandai Selesai">
                    ✓ Selesai
                </button>
            </form>
        </div>
    </div>
</div>
