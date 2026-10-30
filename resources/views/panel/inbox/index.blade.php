<x-panel-layout title="Inbox (Quick Capture)" heading="Inbox — Penangkapan Ide & Triase Cepat">
    {{-- Form Quick Capture (Ketik dan Enter) --}}
    <div class="nara-card p-4 mb-6">
        <form method="POST" action="{{ route('panel.inbox.store') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            @csrf
            <div class="relative flex-1">
                <input
                    type="text"
                    name="content"
                    required
                    autofocus
                    placeholder="+ Tambah catatan cepat atau ide baru… (tekan Enter untuk menyimpan)"
                    class="nara-input text-sm py-2 px-3 pr-20"
                >
                <div class="absolute right-2 top-2">
                    <select name="hint" class="text-[11px] py-1 border-0 bg-slate-100 rounded text-slate-600 focus:ring-0">
                        <option value="task">Sebagai Task</option>
                        <option value="idea">Sebagai Ide</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="nara-btn-primary whitespace-nowrap text-xs sm:text-sm py-2">
                Simpan ke Inbox
            </button>
        </form>
        <p class="text-[11px] text-slate-500 mt-2">
            💡 <em>Inbox adalah katup pelepas. Tangkap semua hal tanpa perlu langsung mengelompokkannya. Triase cukup 5 menit sehari.</em>
        </p>
    </div>

    {{-- Daftar Item Menunggu Triase --}}
    <div class="space-y-3" x-data="{
        activeModal: null,
        activeId: null,
        activeContent: '',
        openTaskModal(id, content) {
            this.activeId = id;
            this.activeContent = content;
            this.activeModal = 'task';
        },
        closeModal() {
            this.activeModal = null;
        }
    }">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700">
                Menunggu Triase ({{ $items->total() }})
            </h2>
        </div>

        <div class="nara-card divide-y divide-slate-100 overflow-hidden">
            @forelse ($items as $item)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 transition">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <span class="text-slate-400 font-mono text-xs mt-0.5">#{{ $item->id }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-900 font-medium whitespace-pre-line">{{ $item->content }}</p>
                            <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-1">
                                <span>via {{ ucfirst($item->source->value) }}</span>
                                <span>•</span>
                                <time>{{ $item->created_at->diffForHumans() }}</time>
                                @if ($item->hint)
                                    <span class="rounded bg-sky-50 text-sky-700 px-1.5 py-0.2 font-medium">hint: {{ $item->hint }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Triase Cepat --}}
                    <div class="flex items-center gap-2 self-end sm:self-center flex-wrap">
                        {{-- Tombol Konversi ke Task --}}
                        <button
                            type="button"
                            @click.stop="openTaskModal({{ $item->id }}, $el.getAttribute('data-content'))"
                            data-content="{{ $item->content }}"
                            class="nara-btn-primary text-xs py-1 px-2.5"
                        >
                            → Task
                        </button>

                        {{-- Tombol Konversi ke Project Ide --}}
                        <form method="POST" action="{{ route('panel.inbox.triage', $item) }}" class="inline">
                            @csrf
                            <input type="hidden" name="target" value="project">
                            <button type="submit" class="nara-btn-secondary text-xs py-1 px-2.5">
                                → Project (Ide)
                            </button>
                        </form>

                        {{-- Tombol Someday --}}
                        <form method="POST" action="{{ route('panel.inbox.triage', $item) }}" class="inline">
                            @csrf
                            <input type="hidden" name="target" value="someday">
                            <button type="submit" class="nara-btn-secondary text-xs py-1 px-2.5 text-slate-500">
                                Suatu Saat
                            </button>
                        </form>

                        {{-- Tombol Buang / Discard --}}
                        <form method="POST" action="{{ route('panel.inbox.triage', $item) }}" class="inline">
                            @csrf
                            <input type="hidden" name="target" value="discard">
                            <button type="submit" class="text-xs text-rose-600 hover:underline p-1">
                                Buang
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">
                    🎉 Inbox Anda kosong! Seluruh catatan sudah diproses ke Project/Task.
                </div>
            @endforelse
        </div>

        @if ($items->hasPages())
            <div class="mt-4">
                {{ $items->links() }}
            </div>
        @endif

        {{-- Modal Triase ke Task --}}
        <div
            x-cloak
            x-show="activeModal === 'task'"
            @keydown.escape.window="closeModal()"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
        >
            {{-- Backdrop click to close --}}
            <div class="fixed inset-0" @click="closeModal()"></div>

            {{-- Modal Box --}}
            <div class="relative bg-white rounded-2xl shadow-xl border border-slate-200 max-w-lg w-full p-6 space-y-4 z-10">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900 text-base">Triase ke Task</h3>
                    <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <p class="text-xs text-slate-600 font-medium bg-slate-50 p-2.5 rounded-lg border border-slate-200/60 leading-relaxed whitespace-pre-line" x-text="activeContent"></p>

                <form :action="'{{ url('panel/inbox') }}/' + activeId + '/triage'" method="POST" class="space-y-4 text-sm">
                    @csrf
                    <input type="hidden" name="target" value="task">

                    <x-form-field name="project_id" label="Pilih Project Wadah" :required="true">
                        <select name="project_id" required class="nara-input">
                            <option value="">— Pilih Project —</option>
                            @foreach ($projects as $proj)
                                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </x-form-field>

                    <x-form-field name="title" label="Judul Task" :required="true">
                        <input type="text" name="title" :value="activeContent" required class="nara-input">
                    </x-form-field>

                    <div class="grid grid-cols-2 gap-3">
                        <x-form-field name="estimated_minutes" label="Estimasi (Menit)">
                            <input type="number" name="estimated_minutes" placeholder="60" class="nara-input">
                        </x-form-field>

                        <x-form-field name="due_date" label="Tenggat">
                            <input type="date" name="due_date" class="nara-input">
                        </x-form-field>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="closeModal()" class="nara-btn-secondary text-xs">Batal</button>
                        <button type="submit" class="nara-btn-primary text-xs">Jadikan Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-panel-layout>

