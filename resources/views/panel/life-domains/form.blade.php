<x-panel-layout :title="$domain->exists ? 'Ubah Life Domain' : 'Tambah Life Domain'"
                :heading="$domain->exists ? 'Ubah Life Domain' : 'Tambah Life Domain'">
    <div class="max-w-xl nara-card p-6">
        <form method="POST"
              action="{{ $domain->exists ? route('panel.life-domains.update', $domain) : route('panel.life-domains.store') }}"
              class="space-y-4">
            @csrf
            @if ($domain->exists)
                @method('PUT')
            @endif

            <x-form-field name="name" label="Nama Domain" :required="true">
                <input id="name" name="name" type="text" required
                       value="{{ old('name', $domain->name) }}"
                       class="nara-input">
            </x-form-field>

            <x-form-field name="color" label="Warna Identitas" :required="true">
                <select id="color" name="color" class="nara-input">
                    @foreach (\App\Models\LifeDomain::COLORS as $key => $label)
                        <option value="{{ $key }}" @selected(old('color', $domain->color ?: 'blue') === $key)>
                            {{ $label }} ({{ $key }})
                        </option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field name="sort_order" label="Urutan" hint="Angka kecil tampil lebih dulu.">
                <input id="sort_order" name="sort_order" type="number" min="0" max="999"
                       value="{{ old('sort_order', $domain->sort_order ?? 1) }}"
                       class="nara-input w-32">
            </x-form-field>

            <div class="pt-2 flex items-center gap-2">
                <button type="submit" class="nara-btn-primary">Simpan</button>
                <a href="{{ route('panel.life-domains.index') }}" class="nara-btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-panel-layout>

