<x-panel-layout title="Dokumentasi Sistem" heading="Pusat Panduan & Dokumentasi Sistem NARA">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Header & Pencarian --}}
        <div class="nara-card p-6 bg-gradient-to-r from-sky-900 via-indigo-950 to-slate-900 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10 max-w-3xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-cyan-400/20 text-cyan-200 ring-1 ring-cyan-400/30 mb-3">
                    📚 Knowledge Base & System Manual
                </span>
                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white mb-2">
                    Dokumentasi Lengkap, Integrasi API & Solusi Troubleshooting
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed mb-6">
                    Panduan resmi konfigurasi Google Calendar (lokal & production), integrasi Bot Telegram, alur kerja seluruh fitur NARA, hingga prosedur pemecahan masalah. Seluruh dokumentasi tersimpan di database dan dapat dikelola secara mandiri oleh admin.
                </p>

                {{-- Form Pencarian Cepat --}}
                <form method="GET" action="{{ route('panel.docs.index') }}" class="flex items-center gap-2 max-w-xl">
                    @if ($selectedCategory)
                        <input type="hidden" name="category" value="{{ $selectedCategory }}">
                    @endif
                    <div class="relative flex-1">
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Cari kata kunci (contoh: '403 access_denied', 'telegram', 'pomodoro')..."
                            class="w-full text-xs sm:text-sm rounded-xl border-0 bg-white/10 px-4 py-2.5 text-white placeholder-slate-400 backdrop-blur-md focus:bg-white focus:text-slate-900 focus:ring-2 focus:ring-cyan-400"
                        >
                    </div>
                    <button type="submit" class="nara-btn-primary text-xs sm:text-sm py-2.5 px-5 shrink-0 bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold">
                        Cari
                    </button>
                    @if ($search || $selectedCategory)
                        <a href="{{ route('panel.docs.index') }}" class="px-3 py-2 text-xs text-slate-300 hover:text-white underline shrink-0">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- Decorative pattern --}}
            <div class="absolute right-0 top-0 bottom-0 w-80 bg-gradient-to-l from-cyan-500/10 to-transparent pointer-events-none hidden md:block"></div>
        </div>

        {{-- Bar Tombol Aksi & Kategori --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            {{-- Filter Kategori --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 text-xs">
                <a href="{{ route('panel.docs.index') }}"
                   class="px-3 py-1.5 rounded-lg font-medium transition {{ empty($selectedCategory) ? 'bg-sky-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    Semua Kategori ({{ $totalCount }})
                </a>
                @foreach ($allCategories as $cat)
                    <a href="{{ route('panel.docs.index', ['category' => $cat->category]) }}"
                       class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition {{ $selectedCategory === $cat->category ? 'bg-sky-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                        {{ $cat->category_name }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('panel.settings.index') }}#integrations" class="nara-btn-secondary text-xs py-1.5 px-3 flex items-center gap-1.5">
                    <span>⚙️</span>
                    <span>Pengaturan Integrasi</span>
                </a>
                <a href="{{ route('panel.docs.create') }}" class="nara-btn-primary text-xs py-1.5 px-3.5 flex items-center gap-1.5 font-bold">
                    <span>+</span>
                    <span>Tulis Panduan Baru</span>
                </a>
            </div>
        </div>

        {{-- Daftar Artikel Dokumentasi --}}
        @if ($articles->isEmpty())
            <div class="nara-card p-12 text-center">
                <div class="text-4xl mb-3">🔍</div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Tidak Ada Panduan Ditemukan</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                    Tidak ada artikel yang cocok dengan pencarian <em>"{{ $search }}"</em>. Coba kata kunci lain atau tambah dokumentasi baru.
                </p>
                <a href="{{ route('panel.docs.index') }}" class="nara-btn-secondary text-xs py-2 px-4">
                    Tampilkan Semua Panduan
                </a>
            </div>
        @else
            <div class="space-y-8">
                @foreach ($groupedArticles as $groupName => $groupItems)
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                                {{ $groupName }} ({{ $groupItems->count() }})
                            </h3>
                            <div class="flex-1 h-px bg-slate-200"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($groupItems as $item)
                                <div class="nara-card p-5 flex flex-col justify-between hover:shadow-md transition-shadow border border-slate-200/90 group">
                                    <div>
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <span class="text-2xl p-2 rounded-xl bg-slate-100 group-hover:bg-sky-50 transition">
                                                {{ $item->icon }}
                                            </span>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                                {{ $item->category_name }}
                                            </span>
                                        </div>

                                        <h4 class="text-sm font-bold text-slate-900 mb-2 leading-snug group-hover:text-sky-600 transition">
                                            <a href="{{ route('panel.docs.show', $item->slug) }}">
                                                {{ $item->title }}
                                            </a>
                                        </h4>

                                        <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed mb-4">
                                            {{ $item->excerpt }}
                                        </p>
                                    </div>

                                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                        <span class="truncate">
                                            Diperbarui: {{ $item->updated_at->format('d M Y') }}
                                        </span>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('panel.docs.edit', $item) }}" class="text-slate-400 hover:text-slate-700 font-medium" title="Edit Artikel">
                                                ✏️ Edit
                                            </a>
                                            <a href="{{ route('panel.docs.show', $item->slug) }}" class="text-sky-600 font-semibold hover:text-sky-800 flex items-center gap-0.5">
                                                <span>Baca</span>
                                                <span>→</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-panel-layout>
