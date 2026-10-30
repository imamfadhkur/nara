<x-panel-layout :title="$project->name" :heading="'Project: '.$project->name">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card
            title="Status & Slot WIP"
            :value="$project->status->label()"
            :subtitle="$project->wip_slot ? 'Slot: ' . $project->wip_slot->label() : 'Tanpa slot WIP'"
            :tone="$project->status->tone()"
        />

        <x-kpi-card
            title="Progres Otomatis"
            :value="$project->progress_percent . '%'"
            subtitle="Dihitung dari bobot task done vs non-cancelled (7.2)"
            tone="accent"
        >
            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden mt-1">
                <div class="bg-violet-600 h-2 transition-all duration-300" style="width: {{ $project->progress_percent }}%"></div>
            </div>
        </x-kpi-card>

        <x-kpi-card
            title="Tenggat Waktu"
            :value="$project->due_date?->format('d/m/Y') ?? 'Tidak ada'"
            :subtitle="'Mulai: ' . ($project->start_date?->format('d/m/Y') ?? '—')"
            :tone="$project->due_date && $project->due_date->isPast() && $project->status->value !== 'done' ? 'danger' : 'neutral'"
        />

        <x-kpi-card
            title="Aktivitas Terakhir"
            :value="$project->last_activity_at?->diffForHumans() ?? 'Belum ada'"
            :subtitle="$project->last_activity_at?->format('d M Y, H:i') ?? '—'"
            :tone="$project->status->value === 'active' && $project->last_activity_at && $project->last_activity_at->diffInDays(now()) >= 5 ? 'warning' : 'neutral'"
        />
    </div>

    {{-- Detail & Metadata Project --}}
    <div class="nara-card p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Goal Induk:</span>
                @if ($project->goal)
                    <a href="{{ route('panel.goals.show', $project->goal) }}" class="ml-1 text-sm font-medium text-sky-700 hover:underline">
                        {{ $project->goal->title }}
                    </a>
                    <span class="text-xs text-slate-500">({{ $project->goal->lifeDomain->name }})</span>
                @else
                    <span class="ml-1 text-sm text-slate-500 italic">Project Mandiri (Tanpa Goal)</span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('panel.tasks.create', ['project_id' => $project->id]) }}" class="nara-btn-primary text-xs">
                    + Task Baru
                </a>
                <a href="{{ route('panel.projects.edit', $project) }}" class="nara-btn-secondary text-xs">
                    Ubah Project
                </a>
                <x-confirm-dialog
                    :action="route('panel.projects.destroy', $project)"
                    title="Hapus / Arsipkan Project"
                    message="Soft-delete project ini beserta seluruh task di dalamnya. Lanjutkan?">
                    <button type="button" class="nara-btn-danger text-xs">Hapus</button>
                </x-confirm-dialog>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Kategori:</span>
                <span class="font-medium text-slate-700">{{ $project->category->label() }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Asal:</span>
                <span class="font-medium text-slate-700">{{ $project->origin->label() }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Dampak (Impact):</span>
                <x-status-badge :status="$project->impact" />
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Urgensi (Urgency):</span>
                <x-status-badge :status="$project->urgency" />
            </div>
        </div>

        @if ($project->description)
            <div class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-700">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Deskripsi:</span>
                {{ $project->description }}
            </div>
        @endif

        @if ($project->definition_of_done)
            <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                <span class="font-semibold text-slate-700 block mb-1">Definition of Done (DoD):</span>
                <pre class="font-sans whitespace-pre-line text-slate-600">{{ $project->definition_of_done }}</pre>
            </div>
        @endif
    </div>

    {{-- Daftar Task di Project Ini --}}
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700">
            Daftar Task ({{ $project->tasks->count() }})
        </h2>
        <a href="{{ route('panel.tasks.create', ['project_id' => $project->id]) }}" class="text-xs text-sky-700 hover:underline font-medium">
            + Tambah Task
        </a>
    </div>

    @php
        $taskColumns = [
            'title' => 'Judul Task',
            'status' => 'Status',
            'priority' => 'Prioritas',
            'estimated_minutes' => 'Estimasi',
            'actual_minutes' => 'Aktual',
            'due_date' => 'Tenggat',
            'subtasks' => 'Subtasks',
            'actions' => ['label' => 'Aksi', 'align' => 'right'],
        ];
    @endphp

    <x-data-table :columns="$taskColumns" empty="Belum ada task di project ini. Klik '+ Task Baru' di atas untuk membuat.">
        @foreach ($project->tasks as $task)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 font-medium text-slate-900">
                    <a href="{{ route('panel.tasks.show', $task) }}" class="hover:text-sky-700 hover:underline">
                        {{ $task->title }}
                    </a>
                    @if ($task->status->value === 'waiting' && $task->waiting_reason)
                        <div class="text-[11px] text-amber-600 italic">
                            ⏳ {{ $task->waiting_reason }}
                        </div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :status="$task->status" />
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :status="$task->priority" />
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">
                    {{ $task->estimated_minutes ? $task->estimated_minutes . ' mnt' : '—' }}
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">
                    {{ $task->actual_minutes ? $task->actual_minutes . ' mnt' : '—' }}
                </td>
                <td class="px-4 py-3 text-xs {{ $task->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-600' }}">
                    {{ $task->due_date?->format('d/m/Y') ?? '—' }}
                    @if ($task->isOverdue())
                        <span class="inline-block text-[10px] bg-rose-100 text-rose-700 px-1 rounded">Terlambat</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    {{ $task->subtasks_count }} item
                </td>
                <td class="px-4 py-3 text-right space-x-2 text-xs">
                    <a href="{{ route('panel.tasks.show', $task) }}" class="text-sky-700 hover:underline font-medium">Detail</a>
                    <a href="{{ route('panel.tasks.edit', $task) }}" class="text-slate-600 hover:underline">Ubah</a>
                </td>
            </tr>
        @endforeach
    </x-data-table>
</x-panel-layout>
