<x-panel-layout :title="$goal->title" :heading="'Goal: '.$goal->title">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card title="Life Domain" :value="$goal->lifeDomain->name" tone="primary" />
        <x-kpi-card title="Kapasitas WIP" :value="$goal->activeProjects()->count() . ' / ' . ($goal->wip_limit ?: 'Default (3)') . ' aktif'"
                    :subtitle="$goal->wip_limit ? 'Batas WIP Kustom Goal' : 'Mengikuti default sistem'"
                    :tone="$goal->activeProjects()->count() >= ($goal->wip_limit ?: 3) ? 'danger' : 'neutral'" />
        <x-kpi-card title="Progres (Project Aktif)" :value="$goal->progress_percent.'%'"
                    subtitle="Rata-rata progres project aktif" tone="accent" />
        <x-kpi-card title="Target" :value="$goal->target_date?->format('d/m/Y') ?? 'Tanpa tanggal'"
                    :subtitle="'Status: '.$goal->status->label()" />
    </div>

    @if ($goal->description)
        <div class="nara-card p-4 mb-6 text-sm text-slate-700">{{ $goal->description }}</div>
    @endif

    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Project Penyusun</h2>
        <div class="flex gap-2">
            <a href="{{ route('panel.projects.create', ['goal_id' => $goal->id]) }}" class="nara-btn-primary text-xs">+ Project</a>
            <a href="{{ route('panel.goals.edit', $goal) }}" class="nara-btn-secondary text-xs">Ubah Goal</a>
        </div>
    </div>

    @php
        $columns = [
            'name' => 'Project',
            'status' => 'Status',
            'wip_slot' => 'Slot WIP',
            'progress' => 'Progres',
            'tasks' => 'Task',
        ];
    @endphp
    <x-data-table :columns="$columns" empty="Belum ada project di bawah goal ini.">
        @foreach ($goal->projects as $project)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 font-medium text-slate-900">
                    <a href="{{ route('panel.projects.show', $project) }}" class="hover:text-sky-700 hover:underline">
                        {{ $project->name }}
                    </a>
                </td>
                <td class="px-4 py-3"><x-status-badge :status="$project->status" /></td>
                <td class="px-4 py-3 text-slate-600">{{ $project->wip_slot?->label() ?? '—' }}</td>
                <td class="px-4 py-3 font-medium">{{ $project->progress_percent }}%</td>
                <td class="px-4 py-3 text-slate-600">{{ $project->tasks_count }} task</td>
            </tr>
        @endforeach
    </x-data-table>
</x-panel-layout>

