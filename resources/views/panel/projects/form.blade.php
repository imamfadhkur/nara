<x-panel-layout :title="$project->exists ? 'Ubah Project: '.$project->name : 'Tambah Project Baru'"
                :heading="$project->exists ? 'Ubah Project: '.$project->name : 'Tambah Project Baru'">
    <div class="max-w-3xl nara-card p-6" x-data="{
        status: '{{ old('status', $project->status?->value ?? 'idea') }}',
        wipSlot: '{{ old('wip_slot', $project->wip_slot?->value ?? '') }}'
    }">
        <form method="POST"
              action="{{ $project->exists ? route('panel.projects.update', $project) : route('panel.projects.store') }}"
              class="space-y-5">
            @csrf
            @if ($project->exists)
                @method('PUT')
            @endif

            <x-form-field name="name" label="Nama Project" :required="true">
                <input id="name" name="name" type="text" required
                       value="{{ old('name', $project->name) }}"
                       placeholder="Mis. MSKI Revisi Jurnal, NARA 1.0, Pendirian PT..."
                       class="nara-input">
            </x-form-field>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-field name="goal_id" label="Goal Induk" hint="Opsional: project boleh berdiri sendiri tanpa Goal.">
                    <select id="goal_id" name="goal_id" class="nara-input">
                        <option value="">— Berdiri Sendiri (Tanpa Goal) —</option>
                        @foreach ($goals as $g)
                            <option value="{{ $g->id }}" @selected((int) old('goal_id', $project->goal_id) === $g->id)>
                                {{ $g->title }} ({{ $g->lifeDomain->name }})
                            </option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field name="category" label="Kategori" :required="true" hint="Disimpan sebagai kolom biasa (D-19).">
                    <select id="category" name="category" class="nara-input">
                        @foreach (\App\Support\Enums\ProjectCategory::cases() as $cat)
                            <option value="{{ $cat->value }}" @selected(old('category', $project->category?->value ?? 'feature') === $cat->value)>
                                {{ $cat->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-field name="origin" label="Asal Project" :required="true">
                    <select id="origin" name="origin" class="nara-input">
                        @foreach (\App\Support\Enums\ProjectOrigin::cases() as $orig)
                            <option value="{{ $orig->value }}" @selected(old('origin', $project->origin?->value ?? 'personal') === $orig->value)>
                                {{ $orig->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field name="status" label="Status Project" :required="true" hint="Bila 'Aktif', wajib memilih slot WIP.">
                    <select id="status" name="status" x-model="status" class="nara-input">
                        @foreach (\App\Support\Enums\ProjectStatus::cases() as $st)
                            <option value="{{ $st->value }}" @selected(old('status', $project->status?->value ?? 'idea') === $st->value)>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
            </div>

            {{-- Pemilihan Slot WIP (wajib jika status == active) --}}
            <div x-show="status === 'active'" x-cloak class="p-4 bg-sky-50/70 rounded-xl border border-sky-200 space-y-3">
                <x-form-field name="wip_slot" label="Slot WIP (Batas Max 3 Project Aktif per Slot)" :required="true"
                              hint="Aturan WipGuard: Menolak project aktif ke-4 dalam satu slot.">
                    <select id="wip_slot" name="wip_slot" x-model="wipSlot" class="nara-input bg-white">
                        <option value="">— Pilih Slot WIP —</option>
                        @foreach (\App\Support\Enums\WipSlot::cases() as $slot)
                            @php
                                $usage = $wipUsage[$slot->value] ?? null;
                                $isThisProject = $project->exists && $project->status?->value === 'active' && $project->wip_slot?->value === $slot->value;
                                $activeCount = $usage ? $usage['count'] : 0;
                            @endphp
                            <option value="{{ $slot->value }}" @selected(old('wip_slot', $project->wip_slot?->value) === $slot->value)>
                                {{ $slot->label() }} (Saat ini: {{ $activeCount }}/{{ $usage['limit'] ?? 3 }} aktif)
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
                <p class="text-xs text-sky-800">
                    💡 <em>WIP (Work In Progress) guard adalah pagar pengaman agar beban komitmen tidak melebihi waktu hidup nyata.</em>
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-field name="impact" label="Dampak (Impact)" :required="true" hint="Tersimpan di project, diturunkan otomatis ke Task Matrix (D-10).">
                    <select id="impact" name="impact" class="nara-input">
                        @foreach (\App\Support\Enums\Level::cases() as $lvl)
                            <option value="{{ $lvl->value }}" @selected(old('impact', $project->impact?->value ?? 'medium') === $lvl->value)>
                                {{ $lvl->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field name="urgency" label="Urgensi (Urgency)" :required="true">
                    <select id="urgency" name="urgency" class="nara-input">
                        @foreach (\App\Support\Enums\Level::cases() as $lvl)
                            <option value="{{ $lvl->value }}" @selected(old('urgency', $project->urgency?->value ?? 'medium') === $lvl->value)>
                                {{ $lvl->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-field name="start_date" label="Tanggal Mulai">
                    <input id="start_date" name="start_date" type="date"
                           value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                           class="nara-input">
                </x-form-field>

                <x-form-field name="due_date" label="Tenggat (Due Date)">
                    <input id="due_date" name="due_date" type="date"
                           value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}"
                           class="nara-input">
                </x-form-field>
            </div>

            <x-form-field name="definition_of_done" label="Definition of Done (DoD)" hint="Kriteria konkret kapan project ini dinyatakan selesai.">
                <textarea id="definition_of_done" name="definition_of_done" rows="3"
                          placeholder="- Paper tersubmit ke jurnal&#10;- Kode merge ke main&#10;- Dokumen legal ditandatangani"
                          class="nara-input font-mono text-xs">{{ old('definition_of_done', $project->definition_of_done) }}</textarea>
            </x-form-field>

            <x-form-field name="description" label="Deskripsi Project">
                <textarea id="description" name="description" rows="3" class="nara-input">{{ old('description', $project->description) }}</textarea>
            </x-form-field>

            <div class="pt-3 flex items-center gap-3">
                <button type="submit" class="nara-btn-primary">Simpan Project</button>
                <a href="{{ $project->exists ? route('panel.projects.show', $project) : route('panel.projects.index') }}" class="nara-btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-panel-layout>

