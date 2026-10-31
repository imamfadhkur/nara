<x-panel-layout :title="$article->title" heading="Dokumentasi Sistem NARA">
    <div class="max-w-7xl mx-auto">
        {{-- Breadcrumb & Quick Actions Bar --}}
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <nav class="flex items-center gap-1.5 text-slate-500">
                <a href="{{ route('panel.docs.index') }}" class="hover:text-slate-900 font-medium">📚 Dokumentasi</a>
                <span>/</span>
                <span class="text-slate-600 font-medium">{{ $article->category_name }}</span>
                <span>/</span>
                <span class="text-slate-900 font-semibold truncate max-w-xs">{{ $article->title }}</span>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('panel.docs.index') }}" class="nara-btn-secondary text-xs py-1.5 px-3">
                    ← Daftar Panduan
                </a>
                @if (str_contains($article->category, 'google') || str_contains($article->category, 'telegram') || str_contains($article->category, 'troubleshoot'))
                    <a href="{{ route('panel.settings.index') }}#integrations" class="nara-btn-secondary text-xs py-1.5 px-3 bg-white text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                        ⚙️ Buka Pengaturan Integrasi
                    </a>
                @endif
                <a href="{{ route('panel.docs.edit', $article) }}" class="nara-btn-primary text-xs py-1.5 px-3.5 flex items-center gap-1.5 font-bold">
                    <span>✏️</span>
                    <span>Edit Panduan Ini</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            {{-- Navigasi Samping (TOC / Daftar Panduan Lain) --}}
            <aside class="order-2 lg:order-1 lg:col-span-1 space-y-4">
                <div class="nara-card p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-1.5">
                        <span>📖</span>
                        <span>Daftar Panduan NARA</span>
                    </h3>

                    <nav class="space-y-4 text-xs">
                        @foreach ($allArticles as $catName => $items)
                            <div>
                                <span class="font-bold text-[11px] text-slate-400 uppercase tracking-wider block mb-1.5">
                                    {{ $catName }}
                                </span>
                                <ul class="space-y-1">
                                    @foreach ($items as $item)
                                        @php $isActive = $item->id === $article->id; @endphp
                                        <li>
                                            <a href="{{ route('panel.docs.show', $item->slug) }}"
                                               class="block py-1.5 px-2.5 rounded-lg transition {{ $isActive ? 'bg-sky-50 text-sky-700 font-bold border-l-2 border-sky-600 shadow-2xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                                <span class="mr-1">{{ $item->icon }}</span>
                                                <span>{{ $item->title }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </nav>

                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <a href="{{ route('panel.docs.create') }}" class="text-[11px] font-semibold text-sky-600 hover:text-sky-800 block text-center py-1">
                            + Tulis Topik Baru
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Area Konten Dokumentasi Utama --}}
            <main class="order-1 lg:order-2 lg:col-span-3">
                <article class="nara-card p-6 sm:p-8 border border-slate-200">
                    {{-- Header Artikel --}}
                    <header class="pb-6 mb-6 border-b border-slate-100">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-3xl p-2 rounded-xl bg-slate-100">{{ $article->icon }}</span>
                            <div>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-800">
                                    {{ $article->category_name }}
                                </span>
                                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-3">
                                    <span>Penulis/Editor: <strong class="text-slate-600">{{ $article->last_updated_by ?? 'Admin' }}</strong></span>
                                    <span>•</span>
                                    <span>Pembaruan Terakhir: {{ $article->updated_at->setTimezone(auth()->user()->displayTimezone())->translatedFormat('d F Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug">
                            {{ $article->title }}
                        </h1>

                        <div class="mt-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 text-xs text-slate-700 leading-relaxed italic">
                            💡 {{ $article->excerpt }}
                        </div>
                    </header>

                    {{-- Render Markdown HTML Content --}}
                    <div class="markdown-body text-slate-800 text-sm leading-relaxed space-y-4">
                        {!! $article->rendered_content !!}
                    </div>

                    {{-- Footer Artikel --}}
                    <footer class="mt-10 pt-6 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs text-slate-500">
                        <div>
                            Dokumentasi ini disimpan di database internal NARA.
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('panel.docs.edit', $article) }}" class="nara-btn-secondary text-xs py-1.5 px-3">
                                ✏️ Perbarui Panduan Ini
                            </a>
                            <a href="{{ route('panel.docs.index') }}" class="nara-btn-primary text-xs py-1.5 px-3">
                                Kembali ke Pusat Panduan
                            </a>
                        </div>
                    </footer>
                </article>
            </main>
        </div>
    </div>

    {{-- Styling Khusus Markdown Konten --}}
    <style>
        .markdown-body h1 { font-size: 1.5rem; font-weight: 800; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h2 { font-size: 1.25rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.5rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.25rem; }
        .markdown-body h3 { font-size: 1rem; font-weight: 700; margin-top: 1.25rem; margin-bottom: 0.5rem; color: #334155; }
        .markdown-body h4 { font-size: 0.875rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.25rem; color: #475569; }
        .markdown-body p { margin-bottom: 0.875rem; line-height: 1.625; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body pre { background: #0b192c; color: #e2e8f0; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-family: monospace; font-size: 0.75rem; margin-bottom: 1rem; border: 1px solid #1e293b; }
        .markdown-body code { background: #f1f5f9; color: #0284c7; padding: 0.15rem 0.35rem; border-radius: 0.25rem; font-family: monospace; font-size: 0.8em; }
        .markdown-body pre code { background: transparent; color: inherit; padding: 0; }
        .markdown-body blockquote { border-left: 4px solid #38bdf8; background: #f0f9ff; padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 0 0.5rem 0.5rem 0; color: #0369a1; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin-bottom: 1.25rem; font-size: 0.8rem; }
        .markdown-body th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 0.5rem 0.75rem; text-align: left; font-weight: 600; color: #1e293b; }
        .markdown-body td { border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; color: #334155; }
        .markdown-body tr:nth-child(even) td { background: #f8fafc; }
        .markdown-body a { color: #0284c7; text-decoration: underline; font-weight: 500; }
        .markdown-body hr { margin: 1.5rem 0; border: 0; border-top: 1px solid #e2e8f0; }
    </style>
</x-panel-layout>

