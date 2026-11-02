<x-panel-layout :title="$goal->exists ? 'Ubah Goal' : 'Tambah Goal'"
                :heading="$goal->exists ? 'Ubah Goal' : 'Tambah Goal'">
    <div class="max-w-xl nara-card p-6">
        <form method="POST"
              action="{{ $goal->exists ? route('panel.goals.update', $goal) : route('panel.goals.store') }}"
              class="space-y-4">
            @csrf
            @if ($goal->exists)
                @method('PUT')
            @endif

            <x-form-field name="life_domain_id" label="Life Domain" :required="true">
                <select id="life_domain_id" name="life_domain_id" required class="nara-input">
                    @foreach ($domains as $d)
                        <option value="{{ $d->id }}" @selected((int) old('life_domain_id', $goal->life_domain_id) === $d->id)>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field name="title" label="Judul Goal" :required="true">
                <input id="title" name="title" type="text" required
                       value="{{ old('title', $goal->title) }}"
                       class="nara-input">
            </x-form-field>

            <x-form-field name="description" label="Deskripsi">
                <textarea id="description" name="description" rows="3" class="nara-input">{{ old('description', $goal->description) }}</textarea>
            </x-form-field>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-field name="target_date" label="Tanggal Target" hint="Boleh dikosongkan.">
                    <input id="target_date" name="target_date" type="date"
                           value="{{ old('target_date', $goal->target_date?->format('Y-m-d')) }}"
                           class="nara-input">
                </x-form-field>

                <x-form-field name="status" label="Status" :required="true">
                    <select id="status" name="status" class="nara-input">
                        @foreach (\App\Support\Enums\GoalStatus::cases() as $s)
                            <option value="{{ $s->value }}" @selected(old('status', $goal->status?->value ?? 'active') === $s->value)>
                                {{ $s->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
            </div>

            <x-form-field name="wip_limit" label="Batas Maksimal Project Aktif (WIP Limit)" hint="Jumlah project aktif bersamaan yang diizinkan di bawah Goal ini. Kosongkan untuk mengikuti batas default sistem.">
                <input id="wip_limit" name="wip_limit" type="number" min="1" max="20" placeholder="Bawaan sistem (misal: 3)"
                       value="{{ old('wip_limit', $goal->wip_limit) }}"
                       class="nara-input">
            </x-form-field>

            <div class="pt-2 flex items-center gap-2">
                <button type="submit" class="nara-btn-primary">Simpan</button>
                <a href="{{ route('panel.goals.index') }}" class="nara-btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-panel-layout>

