<x-panel-layout title="Tasks (Table View)" heading="Tasks (Table View)">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <p class="text-sm text-slate-600">
            Tampilan tabel task lengkap (pengganti spreadsheet). Kolom dapat disaring, diurutkan, dan dicari.
        </p>
        <a href="{{ route('panel.tasks.create') }}" class="nara-btn-primary self-start sm:self-auto">+ Task</a>
    </div>

    @php
        $columns = [
            'title' => ['label' => 'Task', 'sortable' => true],
            'project' => ['label' => 'Project'],
            'status' => ['label' => 'Status', 'sortable' => true],
            'priority' => ['label' => 'Prioritas', 'sortable' => true],
            'due_date' => ['label' => 'Tenggat', 'sortable' => true],
            'estimated_minutes' => ['label' => 'Est. (Mnt)', 'sortable' => true],
            'subtasks' => ['label' => 'Subtask'],
            'actions' => ['label' => 'Aksi', 'align' => 'right'],
        ];
    @endphp

    <x-data-table :columns="$columns" :paginator="$tasks" :sort="$sort" :dir="$dir" empty="Tidak ada task yang cocok dengan saringan.">
        <x-slot name="filters">
            <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul task…"
                       class="rounded-lg border-slate-300 text-xs py-1.5 px-2.5 w-48 focus:border-sky-600">

                <select name="project_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Project</option>
                    @foreach ($projects as $proj)
                        <option value="{{ $proj->id }}" @selected((int) request('project_id') === $proj->id)>{{ $proj->name }}</option>
                    @endforeach
                </select>

                <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Status</option>
                    @foreach (\App\Support\Enums\TaskStatus::cases() as $ts)
                        <option value="{{ $ts->value }}" @selected(request('status') === $ts->value)>{{ $ts->label() }}</option>
                    @endforeach
                </select>

                <select name="priority" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Prioritas</option>
                    @foreach (\App\Support\Enums\TaskPriority::cases() as $tp)
                        <option value="{{ $tp->value }}" @selected(request('priority') === $tp->value)>{{ $tp->label() }}</option>
                    @endforeach
                </select>

                <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 ml-1">
                    <input type="checkbox" name="with_cancelled" value="1" @checked(request('with_cancelled'))
                           onchange="this.form.submit()" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    Sertakan Batal
                </label>

                @if(request()->anyFilled(['q', 'project_id', 'status', 'priority', 'with_cancelled']))
                    <a href="{{ route('panel.tasks.index') }}" class="text-xs text-rose-600 hover:underline ml-auto">Reset Filter</a>
                @endif
            </form>
        </x-slot>

        @foreach ($tasks as $task)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 font-medium text-slate-900 max-w-xs sm:max-w-md">
                    <a href="{{ route('panel.tasks.show', $task) }}" class="hover:text-sky-700 hover:underline block truncate">
                        {{ $task->title }}
                    </a>
                    @if ($task->status->value === 'waiting' && $task->waiting_reason)
                        <div class="text-[11px] text-amber-600 italic truncate" title="{{ $task->waiting_reason }}">
                            ⏳ {{ $task->waiting_reason }}
                        </div>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-slate-700">
                    <a href="{{ route('panel.projects.show', $task->project) }}" class="hover:underline text-sky-700 font-medium">
                        {{ $task->project->name }}
                    </a>
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :status="$task->status" />
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :status="$task->priority" />
                </td>
                <td class="px-4 py-3 text-xs {{ $task->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-600' }}">
                    {{ $task->due_date?->format('d/m') ?? '—' }}
                    @if ($task->isOverdue())
                        <span class="inline-block text-[10px] bg-rose-100 text-rose-700 px-1 rounded ml-0.5">⚠</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">
                    {{ $task->estimated_minutes ?? '—' }}
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    @if ($task->subtasks_count > 0)
                        <span>{{ $task->done_subtasks_count }}/{{ $task->subtasks_count }}</span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right space-x-2 text-xs">
                    <a href="{{ route('panel.tasks.show', $task) }}" class="text-sky-700 hover:underline font-medium">Detail</a>
                    <a href="{{ route('panel.tasks.edit', $task) }}" class="text-slate-600 hover:underline">Ubah</a>
                </td>
            </tr>
        @endforeach
    </x-data-table>
</x-panel-layout>

