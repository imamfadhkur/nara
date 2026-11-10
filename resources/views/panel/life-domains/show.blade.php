<x-panel-layout :title="$domain->name" :heading="'Domain: '.$domain->name">
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-2">
            <x-status-badge :label="$domain->colorLabel()" tone="primary" />
            <span class="text-xs text-slate-500">Urutan #{{ $domain->sort_order }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('panel.goals.create', ['life_domain_id' => $domain->id]) }}" class="nara-btn-primary">+ Goal di Domain Ini</a>
            <a href="{{ route('panel.life-domains.edit', $domain) }}" class="nara-btn-secondary">Ubah</a>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($domain->goals as $goal)
            <div class="nara-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('panel.goals.show', $goal) }}" class="text-base font-semibold text-slate-900 hover:text-sky-700">
                            {{ $goal->title }}
                        </a>
                        <div class="mt-1 flex items-center gap-3 text-xs text-slate-500">
                            <x-status-badge :status="$goal->status" />
                            <span>Target: {{ $goal->target_date?->format('d/m/Y') ?? '—' }}</span>
                            <span>Progres rata-rata project aktif: <strong>{{ $goal->progress_percent }}%</strong></span>
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-sky-800">{{ $goal->projects->count() }} project</span>
                </div>
            </div>
        @empty
            <div class="nara-card p-6 text-center text-sm text-slate-500">
                Belum ada goal di domain ini.
            </div>
        @endforelse
    </div>
</x-panel-layout>

