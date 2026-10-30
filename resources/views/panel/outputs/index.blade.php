<x-panel-layout title="Outputs (Hasil Karya)" heading="Outputs (Hasil Karya)">
    <div x-data="{ openCreateModal: false }" class="space-y-6">
        <!-- Top bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-slate-600">
                    Koleksi hasil karya nyata yang selesai (D-16, Bagian 5.4 & 7.10). Setiap output menjadi bukti konkret kemajuan project.
                </p>
            </div>
            <button @click="openCreateModal = true" class="nara-btn-primary self-start sm:self-auto inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Catat Output</span>
            </button>
        </div>

        <!-- Filter bar -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs flex flex-wrap items-center gap-3 text-xs">
            <form method="GET" class="flex flex-wrap items-center gap-3 w-full">
                <div class="flex items-center gap-2">
                    <span class="text-slate-500 font-medium">Filter:</span>
                    <select name="project_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5 focus:border-sky-600 focus:ring-sky-500">
                        <option value="">Semua Project</option>
                        @foreach ($projects as $proj)
                            <option value="{{ $proj->id }}" @selected((int) request('project_id') === $proj->id)>{{ $proj->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <select name="output_type" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5 focus:border-sky-600 focus:ring-sky-500">
                        <option value="">Semua Jenis Output</option>
                        @foreach (\App\Support\Enums\OutputType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(request('output_type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                @if(request()->anyFilled(['project_id', 'output_type']))
                    <a href="{{ route('panel.outputs.index') }}" class="text-rose-600 hover:underline ml-auto font-medium">Reset Saringan</a>
                @endif
            </form>
        </div>

        <!-- Outputs list -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            @if ($outputs->isEmpty())
                <div class="p-10 text-center">
                    <div class="inline-flex p-3 rounded-full bg-slate-100 text-slate-400 mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-700">Belum ada output hasil karya yang tercatat.</p>
                    <p class="text-xs text-slate-500 mt-1">Selesaikan task penting atau catat hasil deliverable langsung dari sini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs text-slate-600 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="py-3 px-4">Output / Judul</th>
                                <th class="py-3 px-4">Jenis</th>
                                <th class="py-3 px-4">Project & Task</th>
                                <th class="py-3 px-4">Tanggal</th>
                                <th class="py-3 px-4">Tautan Bukti</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($outputs as $out)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-900">{{ $out->title }}</div>
                                        @if ($out->note)
                                            <div class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $out->note }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $out->output_type->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-600">
                                        @if ($out->project)
                                            <a href="{{ route('panel.projects.show', $out->project) }}" class="font-medium text-sky-700 hover:underline">
                                                {{ $out->project->name }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                        @if ($out->task)
                                            <div class="text-slate-400 mt-0.5">Task: {{ $out->task->title }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-xs text-slate-600">
                                        {{ $out->produced_at?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-xs">
                                        @if ($out->evidence_url)
                                            <a href="{{ $out->evidence_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-sky-600 hover:text-sky-800 font-medium">
                                                <span>Buka Tautan</span>
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('panel.outputs.destroy', $out) }}" onsubmit="return confirm('Hapus catatan output ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($outputs->hasPages())
                    <div class="p-3 border-t border-slate-200">
                        {{ $outputs->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Modal Tambah Output -->
        <div x-cloak x-show="openCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
            <div @click.away="openCreateModal = false" class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-semibold text-slate-900 text-base">Catat Output Baru (Deliverable)</h3>
                    <button @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('panel.outputs.store') }}" class="space-y-4 text-sm">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Judul Output *</label>
                        <input type="text" name="title" required placeholder="Contoh: Modul Auth Laravel 12 siap review"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Output *</label>
                            <select name="output_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                                @foreach (\App\Support\Enums\OutputType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Project</label>
                            <select name="project_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                                <option value="">— Tanpa Project —</option>
                                @foreach ($projects as $proj)
                                    <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal Selesai *</label>
                            <input type="date" name="produced_at" value="{{ now()->toDateString() }}" required
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tautan Bukti / URL</label>
                            <input type="url" name="evidence_url" placeholder="https://github.com/..."
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                        <textarea name="note" rows="2" placeholder="Keterangan singkat hasil pengerjaan..."
                                  class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-600 focus:ring-sky-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="openCreateModal = false" class="nara-btn-secondary text-xs">Batal</button>
                        <button type="submit" class="nara-btn-primary text-xs">Simpan Output</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-panel-layout>
