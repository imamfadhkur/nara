<x-panel-layout title="Projects & WIP" heading="Projects & Batas WIP">
    {{-- Ringkasan Slot WIP (Bagian 7.3: maksimal N per slot) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @foreach ($wipUsage as $slotKey => $data)
            @php
                $isFull = $data['count'] >= $data['limit'];
                $tone = $isFull ? 'danger' : ($data['count'] > 0 ? 'primary' : 'neutral');
            @endphp
            <x-kpi-card
                :title="'Slot WIP: ' . $data['slot']->label()"
                :value="$data['count'] . ' / ' . $data['limit'] . ' aktif'"
                :subtitle="$isFull ? '● Slot Penuh (WipGuard aktif)' : 'Kapasitas slot aman'"
                :tone="$tone"
            >
                @if ($data['projects']->isNotEmpty())
                    <div class="text-xs text-slate-500 truncate" title="{{ $data['projects']->pluck('name')->implode(', ') }}">
                        {{ $data['projects']->pluck('name')->implode(', ') }}
                    </div>
                @else
                    <div class="text-xs text-slate-400 italic">Belum ada project aktif di slot ini</div>
                @endif
            </x-kpi-card>
        @endforeach
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <p class="text-sm text-slate-600">
            Wadah pekerjaan dengan hasil akhir terukur. Project aktif dibatasi 3 per slot untuk mencegah beban berlebih.
        </p>
        <a href="{{ route('panel.projects.create') }}" class="nara-btn-primary self-start sm:self-auto">+ Project</a>
    </div>

    @php
        $columns = [
            'name' => ['label' => 'Nama Project', 'sortable' => true],
            'status' => ['label' => 'Status', 'sortable' => true],
            'wip_slot' => ['label' => 'Slot WIP', 'sortable' => true],
            'goal' => ['label' => 'Goal / Domain'],
            'progress_percent' => ['label' => 'Progres', 'sortable' => true],
            'tasks' => ['label' => 'Tasks (Open/Done)'],
            'due_date' => ['label' => 'Tenggat', 'sortable' => true],
            'actions' => ['label' => 'Aksi', 'align' => 'right'],
        ];
    @endphp

    <x-data-table :columns="$columns" :paginator="$projects" :sort="$sort" :dir="$dir" empty="Tidak ada project yang cocok.">
        <x-slot name="filters">
            <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama project…"
                       class="rounded-lg border-slate-300 text-xs py-1.5 px-2.5 w-44 focus:border-sky-600">

                <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Status</option>
                    @foreach (\App\Support\Enums\ProjectStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>

                <select name="wip_slot" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Slot WIP</option>
                    @foreach (\App\Support\Enums\WipSlot::cases() as $ws)
                        <option value="{{ $ws->value }}" @selected(request('wip_slot') === $ws->value)>{{ $ws->label() }}</option>
                    @endforeach
                </select>

                <select name="category" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Kategori</option>
                    @foreach (\App\Support\Enums\ProjectCategory::cases() as $cat)
                        <option value="{{ $cat->value }}" @selected(request('category') === $cat->value)>{{ $cat->label() }}</option>
                    @endforeach
                </select>

                <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 ml-1">
                    <input type="checkbox" name="with_archived" value="1" @checked(request('with_archived'))
                           onchange="this.form.submit()" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    Sertakan Arsip
                </label>

                @if(request()->anyFilled(['q', 'status', 'wip_slot', 'category', 'with_archived']))
                    <a href="{{ route('panel.projects.index') }}" class="text-xs text-rose-600 hover:underline ml-auto">Reset Filter</a>
                @endif
            </form>
        </x-slot>

        @foreach ($projects as $project)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 font-medium text-slate-900">
                    <a href="{{ route('panel.projects.show', $project) }}" class="hover:text-sky-700 hover:underline">
                        {{ $project->name }}
                    </a>
                    <div class="text-[11px] text-slate-500">
                        Kategori: {{ $project->category->label() }} · Asal: {{ $project->origin->label() }}
                    </div>
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :status="$project->status" />
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">
                    {{ $project->wip_slot?->label() ?? '—' }}
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">
                    @if ($project->goal)
                        <a href="{{ route('panel.goals.show', $project->goal) }}" class="hover:underline text-sky-700">
                            {{ $project->goal->title }}
                        </a>
                        <div class="text-[10px] text-slate-400">({{ $project->goal->lifeDomain->name }})</div>
                    @else
                        <span class="text-slate-400">Mandiri (Tanpa Goal)</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="inline-flex items-center gap-2">
                        <div class="w-16 bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-sky-600 h-2" style="width: {{ $project->progress_percent }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-slate-700">{{ $project->progress_percent }}%</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">
                    <span class="font-medium text-amber-700">{{ $project->open_tasks_count }} buka</span> /
                    <span class="text-emerald-700">{{ $project->done_tasks_count }} selesai</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">
                    {{ $project->due_date?->format('d/m/Y') ?? '—' }}
                </td>
                <td class="px-4 py-3 text-right space-x-2 text-xs">
                    <a href="{{ route('panel.projects.show', $project) }}" class="text-sky-700 hover:underline font-medium">Buka</a>
                    <a href="{{ route('panel.projects.edit', $project) }}" class="text-slate-600 hover:underline">Ubah</a>
                    <x-confirm-dialog
                        :action="route('panel.projects.destroy', $project)"
                        title="Arsipkan Project"
                        :message="'Soft delete project &quot;'.$project->name.'&quot; dan seluruh task di dalamnya?'">
                        <button type="button" class="text-rose-600 hover:underline">Hapus</button>
                    </x-confirm-dialog>
                </td>
            </tr>
        @endforeach
    </x-data-table>
</x-panel-layout>
