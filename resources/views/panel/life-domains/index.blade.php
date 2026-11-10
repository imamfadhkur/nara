<x-panel-layout title="Life Domains" heading="Life Domains">
    <div class="flex items-center justify-between mb-5">
        <p class="text-sm text-slate-600">
            Wilayah hidup tingkat tertinggi: Karier & Akademik, Bisnis, Teknologi & Belajar, Kesehatan.
        </p>
        <a href="{{ route('panel.life-domains.create') }}" class="nara-btn-primary">+ Life Domain</a>
    </div>

    @php
        $columns = [
            'sort_order' => '#',
            'name' => 'Nama Domain',
            'color' => 'Warna',
            'goals' => 'Jumlah Goal',
            'actions' => ['label' => 'Aksi', 'align' => 'right'],
        ];
    @endphp

    <x-data-table :columns="$columns" empty="Belum ada Life Domain.">
        @foreach ($domains as $domain)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 text-slate-500 w-16">{{ $domain->sort_order }}</td>
                <td class="px-4 py-3 font-medium text-slate-900">
                    <a href="{{ route('panel.life-domains.show', $domain) }}" class="hover:text-sky-700 hover:underline">
                        {{ $domain->name }}
                    </a>
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :label="$domain->colorLabel()" :tone="$domain->color === 'violet' ? 'accent' : ($domain->color === 'green' ? 'success' : 'primary')" />
                </td>
                <td class="px-4 py-3 text-slate-600">{{ $domain->goals_count }} goal</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <a href="{{ route('panel.life-domains.edit', $domain) }}" class="text-sky-700 hover:underline text-xs font-medium">Ubah</a>
                    @if ($domain->goals_count === 0)
                        <x-confirm-dialog
                            :action="route('panel.life-domains.destroy', $domain)"
                            title="Hapus Life Domain"
                            :message="'Hapus domain '.$domain->name.'?'">
                            <button type="button" class="text-rose-600 hover:underline text-xs font-medium">Hapus</button>
                        </x-confirm-dialog>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-data-table>
</x-panel-layout>

