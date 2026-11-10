<x-panel-layout title="Goals" heading="Goals">
    <div class="flex items-center justify-between mb-5">
        <p class="text-sm text-slate-600">
            Hasil jangka menengah. Progres goal dihitung otomatis dari rata-rata progres project <em>aktif</em> di bawahnya.
        </p>
        <a href="{{ route('panel.goals.create') }}" class="nara-btn-primary">+ Goal</a>
    </div>

    @php
        $columns = [
            'title' => 'Goal',
            'domain' => 'Life Domain',
            'status' => 'Status',
            'target_date' => 'Target',
            'progress' => 'Progres',
            'projects' => 'Project',
            'actions' => ['label' => 'Aksi', 'align' => 'right'],
        ];
    @endphp

    <x-data-table :columns="$columns" :paginator="$goals" empty="Belum ada goal.">
        <x-slot name="filters">
            <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
                <select name="life_domain_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Domain</option>
                    @foreach ($domains as $d)
                        <option value="{{ $d->id }}" @selected((int) request('life_domain_id') === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5">
                    <option value="">Semua Status</option>
                    @foreach (\App\Support\Enums\GoalStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot>

        @foreach ($goals as $goal)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 font-medium text-slate-900">
                    <a href="{{ route('panel.goals.show', $goal) }}" class="hover:text-sky-700 hover:underline">
                        {{ $goal->title }}
                    </a>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ $goal->lifeDomain->name }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$goal->status" /></td>
                <td class="px-4 py-3 text-slate-600">{{ $goal->target_date?->format('d/m/Y') ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('panel.goals.show', $goal) }}" class="inline-flex items-center gap-2" title="Klik untuk melihat project penyusun">
                        <div class="w-24 bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-sky-600 h-2" style="width: {{ $goal->progress_percent }}%"></div>
                        </div>
                        <span class="text-xs font-medium text-slate-700">{{ $goal->progress_percent }}%</span>
                    </a>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ $goal->projects_count }}</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <a href="{{ route('panel.goals.edit', $goal) }}" class="text-sky-700 hover:underline text-xs font-medium">Ubah</a>
                    <x-confirm-dialog :action="route('panel.goals.destroy', $goal)" title="Hapus Goal"
                                      message="Project di bawah goal ini tidak ikut terhapus (menjadi project tanpa goal). Lanjutkan?">
                        <button type="button" class="text-rose-600 hover:underline text-xs font-medium">Hapus</button>
                    </x-confirm-dialog>
                </td>
            </tr>
        @endforeach
    </x-data-table>
</x-panel-layout>

