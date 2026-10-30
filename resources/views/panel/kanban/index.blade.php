<x-panel-layout title="Kanban View" heading="Kanban Board">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <p class="text-sm text-slate-600">
            Tampilan alur kerja task 4 kolom utama. Geser atau ubah status langsung dari kartu.
        </p>

        {{-- Filter Project --}}
        <form method="GET" class="flex items-center gap-2">
            <select name="project_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5 focus:border-sky-600">
                <option value="">Semua Project</option>
                @foreach ($projects as $proj)
                    <option value="{{ $proj->id }}" @selected((int) request('project_id') === $proj->id)>{{ $proj->name }}</option>
                @endforeach
            </select>
            @if(request()->filled('project_id'))
                <a href="{{ route('panel.kanban.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    {{-- Board 4 Kolom --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-start" x-data="{ moveModal: false, activeTaskId: null, activeTaskTitle: '', targetStatus: 'todo' }">
        @foreach ($columns as $colKey => $col)
            <div class="bg-slate-100/80 rounded-xl p-3 border border-slate-200 min-h-[500px] flex flex-col">
                <div class="flex items-center justify-between px-2 py-1 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                        {{ $col['title'] }}
                    </span>
                    <span class="rounded-full bg-slate-200 text-slate-700 text-[11px] font-semibold px-2 py-0.5">
                        {{ $col['tasks']->count() }}
                    </span>
                </div>

                <div class="space-y-2.5 flex-1 overflow-y-auto">
                    @forelse ($col['tasks'] as $task)
                        <div class="nara-card p-3.5 shadow-xs hover:shadow-sm transition bg-white group">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('panel.tasks.show', $task) }}" class="text-sm font-medium text-slate-900 hover:text-sky-700 leading-snug">
                                    {{ $task->title }}
                                </a>
                                <x-status-badge :status="$task->priority" />
                            </div>

                            <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                                <span class="font-medium text-slate-600 truncate max-w-[120px]" title="{{ $task->project->name }}">
                                    {{ $task->project->name }}
                                </span>
                                @if ($task->due_date)
                                    <span class="{{ $task->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                        {{ $task->due_date->format('d/m') }}
                                        @if ($task->isOverdue()) ⚠ @endif
                                    </span>
                                @endif
                            </div>

                            @if ($task->status->value === 'waiting' && $task->waiting_reason)
                                <div class="mt-2 p-1.5 bg-amber-50 rounded text-[11px] text-amber-800 border border-amber-200">
                                    <span class="font-semibold">menunggu:</span> {{ $task->waiting_reason }}
                                </div>
                            @endif

                            {{-- Tombol Aksi Cepat Pindah Status --}}
                            <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between opacity-80 group-hover:opacity-100 text-xs">
                                <a href="{{ route('panel.tasks.show', $task) }}" class="text-slate-400 hover:text-sky-700 text-[11px]">
                                    Detail
                                </a>
                                <button
                                    type="button"
                                    @click.stop="moveModal = true; activeTaskId = {{ $task->id }}; activeTaskTitle = $el.dataset.title; targetStatus = $el.dataset.status"
                                    data-title="{{ $task->title }}"
                                    data-status="{{ $task->status->value }}"
                                    class="text-sky-700 hover:underline font-medium text-[11px]"
                                >
                                    Ubah Status →
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="h-28 flex items-center justify-center text-xs text-slate-400 border border-dashed border-slate-300 rounded-lg">
                            Kosong
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{-- Modal Ubah Status --}}
        <div
            x-cloak
            x-show="moveModal"
            @keydown.escape.window="moveModal = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
        >
            {{-- Backdrop click to close --}}
            <div class="fixed inset-0" @click="moveModal = false"></div>

            {{-- Modal box --}}
            <div class="relative bg-white rounded-2xl p-6 max-w-md w-full shadow-xl border border-slate-200 z-10 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Pindahkan Status Task</h3>
                    <button type="button" @click="moveModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <p class="text-xs text-slate-600 font-medium bg-slate-50 p-2 rounded-lg border border-slate-200/60" x-text="activeTaskTitle"></p>

                <form :action="'{{ url('panel/kanban/tasks') }}/' + activeTaskId + '/move'" method="POST" class="space-y-4 text-sm">
                    @csrf
                    <x-form-field name="status" label="Pilih Status Baru" :required="true">
                        <select name="status" x-model="targetStatus" class="nara-input">
                            @foreach (\App\Support\Enums\TaskStatus::cases() as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </x-form-field>

                    <div x-show="targetStatus === 'waiting'" x-cloak class="p-3 bg-amber-50 rounded-lg border border-amber-200">
                        <x-form-field name="waiting_reason" label="Alasan Menunggu (Wajib)">
                            <input type="text" name="waiting_reason" placeholder="Contoh: menunggu draft dari rekan..." class="nara-input bg-white text-xs">
                        </x-form-field>
                    </div>

                    <div x-show="targetStatus === 'done'" x-cloak class="p-3 bg-emerald-50 rounded-lg border border-emerald-200 space-y-3">
                        <x-form-field name="actual_minutes" label="Waktu Realisasi (Menit, Opsional)">
                            <input type="number" name="actual_minutes" placeholder="Misal: 45" class="nara-input bg-white text-xs">
                        </x-form-field>

                        <div class="pt-2 border-t border-emerald-200/60" x-data="{ wantOutput: false }">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="record_output" value="1" x-model="wantOutput"
                                       class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-semibold text-emerald-900">Catat Hasil Karya / Output Sekarang? (D-16)</span>
                            </label>

                            <div x-show="wantOutput" class="mt-2 space-y-2">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-700 mb-0.5">Judul Output</label>
                                    <input type="text" name="output_title" :value="activeTaskTitle"
                                           class="nara-input bg-white text-xs py-1">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-700 mb-0.5">Jenis Output</label>
                                    <select name="output_type" class="nara-input bg-white text-xs py-1">
                                        @foreach (\App\Support\Enums\OutputType::cases() as $ot)
                                            <option value="{{ $ot->value }}">{{ $ot->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-700 mb-0.5">Tautan Bukti (URL)</label>
                                    <input type="url" name="evidence_url" placeholder="https://github.com/... atau deliverable"
                                           class="nara-input bg-white text-xs py-1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="moveModal = false" class="nara-btn-secondary text-xs">Batal</button>
                        <button type="submit" class="nara-btn-primary text-xs">Perbarui Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-panel-layout>
