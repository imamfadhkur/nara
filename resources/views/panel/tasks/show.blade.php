<x-panel-layout :title="$task->title" :heading="'Task: '.$task->title">
    @php
        $matrix = app(\App\Domain\Tasks\MatrixClassifier::class);
        $quadrant = $matrix->classify($task);
    @endphp

    <div x-data="{ openDoneModal: false, openOutputModal: false }">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card
            title="Status & Kuadran"
            :value="$task->status->label()"
            :subtitle="'Kuadran: ' . $matrix->label($quadrant)"
            :tone="$matrix->tone($quadrant)"
        />

        <x-kpi-card
            title="Estimasi vs Realisasi"
            :value="($task->actual_minutes ?? 0) . ' / ' . ($task->estimated_minutes ?? 0) . ' mnt'"
            subtitle="Dasar kapasitas terencana (7.4)"
            tone="primary"
        />

        <x-kpi-card
            title="Tenggat Waktu"
            :value="$task->due_date?->format('d/m/Y') ?? 'Tidak ada'"
            :subtitle="$task->isOverdue() ? '⚠ Telah melewati tenggat!' : ($task->due_date ? $task->due_date->diffForHumans() : 'Fleksibel')"
            :tone="$task->isOverdue() ? 'danger' : 'neutral'"
        />

        <x-kpi-card
            title="Subtask Selesai"
            :value="$task->subtasks->where('is_done', true)->count() . ' / ' . $task->subtasks->count()"
            subtitle="Checklist butir pengerjaan"
            tone="accent"
        />
    </div>

    {{-- Detail Info Task --}}
    <div class="nara-card p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Project:</span>
                <a href="{{ route('panel.projects.show', $task->project) }}" class="ml-1 text-sm font-semibold text-sky-700 hover:underline">
                    {{ $task->project->name }}
                </a>
                @if ($task->project->goal)
                    <span class="text-xs text-slate-500">→ {{ $task->project->goal->title }}</span>
                @endif
            </div>

            <div class="flex items-center flex-wrap gap-2">
                @if ($task->status !== \App\Support\Enums\TaskStatus::Done)
                    <button type="button" @click="openDoneModal = true" class="nara-btn-primary text-xs bg-emerald-600 hover:bg-emerald-700">
                        ✓ Tandai Selesai
                    </button>
                @else
                    <button type="button" @click="openOutputModal = true" class="nara-btn-secondary text-xs text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                        + Catat Bukti Output
                    </button>
                @endif
                <a href="{{ route('panel.tasks.edit', $task) }}" class="nara-btn-secondary text-xs">Ubah Task</a>
                <x-confirm-dialog
                    :action="route('panel.tasks.destroy', $task)"
                    title="Hapus Task"
                    message="Soft-delete task ini. Lanjutkan?">
                    <button type="button" class="nara-btn-danger text-xs">Hapus</button>
                </x-confirm-dialog>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Prioritas:</span>
                <x-status-badge :status="$task->priority" />
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Dampak Project (Impact):</span>
                <x-status-badge :status="$task->project->impact" />
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Selesai Pada:</span>
                <span class="font-medium text-slate-700">{{ $task->completed_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Kuadran Matrix:</span>
                <span class="font-semibold text-sky-800">{{ $matrix->label($quadrant) }}</span>
            </div>
        </div>

        @if ($task->status->value === 'waiting' && $task->waiting_reason)
            <div class="mt-4 p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-900">
                <span class="font-semibold">Alasan Menunggu:</span> {{ $task->waiting_reason }}
            </div>
        @endif

        @if ($task->description)
            <div class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-700">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Deskripsi:</span>
                <p class="whitespace-pre-line">{{ $task->description }}</p>
            </div>
        @endif
    </div>

    {{-- Subtask Checklist (Bagian 1.4 & 9.6) --}}
    <div class="nara-card p-5 mb-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 mb-3">
            Subtask Checklist ({{ $task->subtasks->count() }})
        </h2>

        <div class="space-y-2 mb-4">
            @forelse ($task->subtasks as $subtask)
                <div class="flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 border border-slate-100 transition">
                    <form method="POST" action="{{ route('panel.tasks.subtasks.toggle', [$task, $subtask]) }}" class="inline-flex items-center gap-2.5 flex-1 min-w-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer flex items-center justify-center">
                            @if ($subtask->is_done)
                                <svg class="h-3.5 w-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="h-3.5 w-3.5 border border-slate-400 rounded-sm"></span>
                            @endif
                        </button>
                        <span class="text-sm truncate {{ $subtask->is_done ? 'line-through text-slate-400' : 'text-slate-800' }}">
                            {{ $subtask->title }}
                        </span>
                    </form>

                    <form method="POST" action="{{ route('panel.tasks.subtasks.destroy', [$task, $subtask]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-rose-600 p-1 text-xs" title="Hapus subtask">✕</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">Belum ada subtask checklist.</p>
            @endforelse
        </div>

        {{-- Form Tambah Subtask --}}
        <form method="POST" action="{{ route('panel.tasks.subtasks.store', $task) }}" class="flex items-center gap-2">
            @csrf
            <input type="text" name="title" required placeholder="+ Tambah subtask baru..."
                   class="nara-input text-xs py-1.5 flex-1">
            <button type="submit" class="nara-btn-secondary text-xs py-1.5">Tambah</button>
        </form>
    </div>

    {{-- Bukti Output / Hasil Karya Task (D-16) --}}
    @if ($task->outputs->isNotEmpty())
        <div class="nara-card p-5 mb-6 border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <span>🏆 Bukti Karya / Output Terkait ({{ $task->outputs->count() }})</span>
                </h2>
                <button type="button" @click="openOutputModal = true" class="text-xs text-emerald-700 hover:text-emerald-900 font-semibold">
                    + Tambah Bukti Lain
                </button>
            </div>

            <div class="space-y-3">
                @foreach ($task->outputs as $out)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900">{{ $out->title }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    {{ $out->output_type->label() }}
                                </span>
                            </div>
                            @if ($out->evidence_url)
                                <a href="{{ $out->evidence_url }}" target="_blank" rel="noopener noreferrer" class="text-sky-600 hover:underline block mt-1 truncate max-w-md">
                                    🔗 {{ $out->evidence_url }}
                                </a>
                            @endif
                            @if ($out->note)
                                <p class="text-slate-500 mt-1 italic">{{ $out->note }}</p>
                            @endif

                            @if ($out->files->isNotEmpty())
                                <div class="mt-2 flex items-center gap-2 flex-wrap">
                                    @foreach ($out->files as $file)
                                        <div class="inline-flex items-center gap-1.5 bg-white px-2 py-1 rounded border border-slate-200 text-[11px] shadow-2xs">
                                            <span>📎</span>
                                            <a href="{{ route('panel.outputs.download', [$out, $file]) }}" class="font-medium text-slate-800 hover:text-sky-700 truncate max-w-[150px]" title="Unduh {{ $file->file_name }}">
                                                {{ $file->file_name }}
                                            </a>
                                            <a href="{{ route('panel.outputs.view', [$out, $file]) }}" target="_blank" class="text-sky-600 hover:text-sky-800 ml-1 font-bold" title="Buka berkas di tab baru">
                                                ↗
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="text-right text-[11px] text-slate-400 shrink-0">
                            {{ $out->produced_at->format('d M Y') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Riwayat Aktivitas Task (Bagian 6.9 & 6.10) --}}
    <div class="nara-card p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 mb-3">
            Riwayat Aktivitas Task
        </h2>

        <div class="space-y-3">
            @forelse ($logs as $log)
                <div class="flex items-start gap-3 text-xs text-slate-600 pb-2.5 border-b border-slate-50 last:border-b-0">
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-500 uppercase">
                        {{ $log->source->value }}
                    </span>
                    <div class="flex-1">
                        <span class="font-medium text-slate-800">{{ $log->event->label() }}</span>
                        @if ($log->after_value)
                            <span class="text-slate-500">
                                @if (isset($log->after_value['status']))
                                    menjadi <strong class="text-sky-700">{{ $log->after_value['status'] }}</strong>
                                @endif
                                @if (isset($log->after_value['waiting_reason']))
                                    ({{ $log->after_value['waiting_reason'] }})
                                @endif
                            </span>
                        @endif
                    </div>
                    <time class="text-slate-400 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">Belum ada riwayat aktivitas tercatat.</p>
            @endforelse
        </div>
    </div>

    {{-- Modal Tandai Selesai + Catat Output (D-16) --}}
    <div x-cloak x-show="openDoneModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div @click.away="openDoneModal = false" class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-semibold text-slate-900 text-base">Tandai Task Selesai</h3>
                <button @click="openDoneModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('panel.kanban.tasks.move', $task) }}" class="space-y-4 text-sm" x-data="{ wantOutput: true }" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="status" value="done">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Realisasi Waktu (Menit, Opsional)</label>
                    <input type="number" name="actual_minutes" value="{{ $task->estimated_minutes }}" placeholder="Misal: 60"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                </div>

                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 space-y-3">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="record_output" value="1" x-model="wantOutput"
                               class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-semibold text-emerald-950">Catat Hasil Karya / Bukti Output Sekaligus (D-16)</span>
                    </label>

                    <div x-show="wantOutput" class="space-y-2.5 pt-1">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-0.5">Judul Output</label>
                            <input type="text" name="output_title" value="{{ $task->title }}"
                                   class="w-full rounded-lg border-slate-300 bg-white text-xs focus:border-sky-600 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-0.5">Jenis Output</label>
                            <select name="output_type" class="w-full rounded-lg border-slate-300 bg-white text-xs focus:border-sky-600 focus:ring-sky-500">
                                @foreach (\App\Support\Enums\OutputType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-0.5">Tautan Bukti (URL)</label>
                            <input type="url" name="evidence_url" placeholder="https://github.com/... atau deliverable"
                                   class="w-full rounded-lg border-slate-300 bg-white text-xs focus:border-sky-600 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-0.5">Unggah Berkas Bukti (File Upload)</label>
                            <input type="file" name="output_file"
                                   class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-white file:text-sky-700 hover:file:bg-slate-100 border border-slate-300 rounded-lg p-1 bg-white cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-0.5">Mendukung PDF, Word/Excel, gambar, ZIP, dll. (Maks. 25 MB).</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="openDoneModal = false" class="nara-btn-secondary text-xs">Batal</button>
                    <button type="submit" class="nara-btn-primary text-xs bg-emerald-600 hover:bg-emerald-700">Simpan & Selesaikan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Catat Bukti Output untuk Task Selesai --}}
    <div x-cloak x-show="openOutputModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div @click.away="openOutputModal = false" class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-semibold text-slate-900 text-base">Catat Bukti Output untuk Task Ini</h3>
                <button @click="openOutputModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('panel.outputs.store') }}" class="space-y-4 text-sm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                <input type="hidden" name="task_id" value="{{ $task->id }}">
                <input type="hidden" name="produced_at" value="{{ now()->toDateString() }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Judul Output *</label>
                    <input type="text" name="title" value="{{ $task->title }}" required
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Output *</label>
                    <select name="output_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                        @foreach (\App\Support\Enums\OutputType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tautan Bukti / URL</label>
                    <input type="url" name="evidence_url" placeholder="https://github.com/... atau deliverable"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Unggah Berkas Bukti (File Upload)</label>
                    <input type="file" name="file"
                           class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 border border-slate-300 rounded-lg p-1.5 cursor-pointer bg-slate-50/50">
                    <p class="text-[11px] text-slate-500 mt-1">Mendukung PDF, Word/Excel, gambar, arsip ZIP, kode, dll. (Maks. 25 MB).</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Catatan</label>
                    <textarea name="note" rows="2" placeholder="Catatan singkat..."
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="openOutputModal = false" class="nara-btn-secondary text-xs">Batal</button>
                    <button type="submit" class="nara-btn-primary text-xs">Simpan Output</button>
                </div>
            </form>
        </div>
    </div>
    </div>
</x-panel-layout>

