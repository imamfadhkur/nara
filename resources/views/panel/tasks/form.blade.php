<x-panel-layout :title="$task->exists ? 'Ubah Task: '.$task->title : 'Tambah Task Baru'"
                :heading="$task->exists ? 'Ubah Task' : 'Tambah Task Baru'">
    <div class="max-w-2xl nara-card p-6" x-data="{
        status: '{{ old('status', $task->status?->value ?? 'todo') }}'
    }">
        <form method="POST"
              action="{{ $task->exists ? route('panel.tasks.update', $task) : route('panel.tasks.store') }}"
              class="space-y-5">
            @csrf
            @if ($task->exists)
                @method('PUT')
            @endif

            <x-form-field name="project_id" label="Project Wadah" :required="true">
                <select id="project_id" name="project_id" required class="nara-input">
                    @foreach ($projects as $proj)
                        <option value="{{ $proj->id }}" @selected((int) old('project_id', $task->project_id) === $proj->id)>
                            {{ $proj->name }}
                        </option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field name="title" label="Judul Task" :required="true">
                <input id="title" name="title" type="text" required
                       value="{{ old('title', $task->title) }}"
                       placeholder="Satu langkah yang konkret dan bisa dikerjakan..."
                       class="nara-input">
            </x-form-field>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-field name="status" label="Status Task" :required="true">
                    <select id="status" name="status" x-model="status" class="nara-input">
                        @foreach (\App\Support\Enums\TaskStatus::cases() as $st)
                            <option value="{{ $st->value }}" @selected(old('status', $task->status?->value ?? 'todo') === $st->value)>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field name="priority" label="Prioritas Task" :required="true">
                    <select id="priority" name="priority" class="nara-input">
                        @foreach (\App\Support\Enums\TaskPriority::cases() as $pr)
                            <option value="{{ $pr->value }}" @selected(old('priority', $task->priority?->value ?? 'normal') === $pr->value)>
                                {{ $pr->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-form-field>
            </div>

            {{-- Alasan Menunggu (wajib jika status == waiting, Bagian 7.1) --}}
            <div x-show="status === 'waiting'" x-cloak class="p-3 bg-amber-50 rounded-lg border border-amber-200">
                <x-form-field name="waiting_reason" label="Alasan Menunggu (Wajib untuk status Menunggu)" :required="true"
                              hint="Contoh: menunggu outline rekan peneliti, menunggu persetujuan klien, dll.">
                    <input id="waiting_reason" name="waiting_reason" type="text"
                           value="{{ old('waiting_reason', $task->waiting_reason) }}"
                           placeholder="Jelaskan apa atau siapa yang sedang ditunggu..."
                           class="nara-input bg-white">
                </x-form-field>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-form-field name="due_date" label="Tenggat (Due Date)">
                    <input id="due_date" name="due_date" type="date"
                           value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                           class="nara-input">
                </x-form-field>

                <x-form-field name="estimated_minutes" label="Estimasi (Menit)" hint="Dasar perhitungan beban kapasitas.">
                    <input id="estimated_minutes" name="estimated_minutes" type="number" min="1" max="1440" step="15"
                           value="{{ old('estimated_minutes', $task->estimated_minutes) }}"
                           placeholder="Mis. 60"
                           class="nara-input">
                </x-form-field>

                <x-form-field name="actual_minutes" label="Aktual (Menit)" hint="Realisasi waktu fokus.">
                    <input id="actual_minutes" name="actual_minutes" type="number" min="0" max="1440" step="5"
                           value="{{ old('actual_minutes', $task->actual_minutes) }}"
                           placeholder="Mis. 55"
                           class="nara-input">
                </x-form-field>
            </div>

            <x-form-field name="description" label="Deskripsi / Catatan Tambahan">
                <textarea id="description" name="description" rows="3" class="nara-input">{{ old('description', $task->description) }}</textarea>
            </x-form-field>

            <div class="pt-3 flex items-center gap-3">
                <button type="submit" class="nara-btn-primary">Simpan Task</button>
                <a href="{{ $task->exists ? route('panel.tasks.show', $task) : route('panel.tasks.index') }}" class="nara-btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-panel-layout>
