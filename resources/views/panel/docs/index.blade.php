<x-panel-layout title="Dokumentasi Sistem" heading="Pusat Panduan & Dokumentasi Sistem NARA">
    <div x-data="{
        openManageModal: false,
        openEditModal: false,
        openCreateCatModal: false,
        editCat: { id: '', name: '', slug: '', icon: '📁', description: '', sort_order: 0 },
        startEditCategory(cat) {
            this.editCat = {
                id: cat.id,
                name: cat.name,
                slug: cat.slug,
                icon: cat.icon || '📁',
                description: cat.description || '',
                sort_order: cat.sort_order || 0
            };
            this.openManageModal = false;
            this.openEditModal = true;
        }
    }" class="max-w-7xl mx-auto space-y-6">

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
                    <a href="{{ route('panel.docs.index', ['category' => $cat->slug]) }}"
                       class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-1.5 {{ $selectedCategory === $cat->slug || $selectedCategory === (string) $cat->id ? 'bg-sky-600 text-white shadow-xs font-bold' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                        <span>{{ $cat->icon }}</span>
                        <span>{{ $cat->name }}</span>
                        <span class="text-[10px] opacity-80">({{ $cat->articles_count }})</span>
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="openManageModal = true" class="nara-btn-secondary text-xs py-1.5 px-3 flex items-center gap-1.5 shadow-xs">
                    <span>🏷️</span>
                    <span>Kelola Kategori</span>
                </button>
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

        {{-- Highlight Banner Kategori Aktif Terpilih dengan Tombol Edit & Hapus --}}
        @if ($activeCategoryObj)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-sky-50/90 border border-sky-200 rounded-2xl text-xs shadow-xs">
                <div class="flex items-center gap-3">
                    <span class="text-3xl p-2 rounded-xl bg-white shadow-xs border border-sky-100 shrink-0">
                        {{ $activeCategoryObj->icon }}
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sky-950 text-sm">{{ $activeCategoryObj->name }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-sky-200/80 text-sky-800 font-semibold">
                                Slug: {{ $activeCategoryObj->slug }}
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-white border border-sky-200 text-slate-600">
                                Urutan: {{ $activeCategoryObj->sort_order }}
                            </span>
                        </div>
                        @if ($activeCategoryObj->description)
                            <p class="text-slate-600 text-xs mt-0.5">{{ $activeCategoryObj->description }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                    <button type="button"
                            @click="startEditCategory({{ json_encode($activeCategoryObj) }})"
                            class="px-3 py-1.5 bg-white border border-slate-300 hover:border-sky-500 hover:text-sky-700 text-slate-700 rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                        <span>✏️</span>
                        <span>Edit Kategori</span>
                    </button>
                    <form method="POST"
                          action="{{ route('panel.docs.categories.destroy', $activeCategoryObj) }}"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori &quot;{{ $activeCategoryObj->name }}&quot;? Artikel di dalamnya akan dipindahkan ke kategori Umum.')"
                          class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-3 py-1.5 bg-white border border-rose-200 hover:bg-rose-50 hover:border-rose-300 text-rose-600 rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                            <span>🗑️</span>
                            <span>Hapus Kategori</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

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
                    @php
                        $matchedCat = $allCategories->firstWhere('name', $groupName);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                    @if ($matchedCat)
                                        <span class="text-base">{{ $matchedCat->icon }}</span>
                                    @endif
                                    <span>{{ $groupName }}</span>
                                    <span class="text-xs font-normal text-slate-500">({{ $groupItems->count() }} artikel)</span>
                                </h3>
                            </div>

                            @if ($matchedCat)
                                <div class="flex items-center gap-1.5 text-xs">
                                    <button type="button"
                                            @click="startEditCategory({{ json_encode($matchedCat) }})"
                                            class="px-2.5 py-1 text-[11px] rounded-lg bg-slate-100 hover:bg-sky-50 hover:text-sky-700 text-slate-600 transition flex items-center gap-1 font-semibold"
                                            title="Edit nama, ikon, atau deskripsi kategori ini">
                                        <span>✏️</span>
                                        <span>Edit Kategori</span>
                                    </button>
                                    <form method="POST"
                                          action="{{ route('panel.docs.categories.destroy', $matchedCat) }}"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori &quot;{{ $matchedCat->name }}&quot;? Artikel di dalamnya akan dipindahkan ke kategori Umum.')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 text-[11px] rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 transition flex items-center gap-1 font-semibold"
                                                title="Hapus kategori ini">
                                            <span>🗑️</span>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        <div class="h-px bg-slate-200 mb-4"></div>

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

        {{-- Modal Kelola Seluruh Kategori --}}
        <div x-cloak x-show="openManageModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6 md:p-8 flex items-start justify-center">
            <div @click.away="openManageModal = false" class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-4xl sm:max-w-5xl w-full p-5 sm:p-7 space-y-5 my-6 sm:my-10">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🏷️</span>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base">Kelola Kategori Dokumentasi</h3>
                            <p class="text-xs text-slate-500">Daftar kategori dokumentasi resmi yang tersimpan di database.</p>
                        </div>
                    </div>
                    <button @click="openManageModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition" title="Tutup Modal">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Tombol Toggle Tambah Kategori Baru --}}
                <div class="flex items-center justify-between bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <span class="text-xs font-medium text-slate-700">Ingin membuat kategori baru?</span>
                    <button type="button"
                            @click="openCreateCatModal = !openCreateCatModal"
                            class="px-3 py-1.5 bg-sky-600 text-white rounded-lg text-xs font-semibold hover:bg-sky-700 transition flex items-center gap-1.5">
                        <span x-text="openCreateCatModal ? '✕ Tutup Form' : '+ Tambah Kategori Baru'"></span>
                    </button>
                </div>

                {{-- Form Tambah Kategori Baru (Collapsible) --}}
                <div x-show="openCreateCatModal" x-cloak class="p-4 bg-sky-50/70 border border-sky-200 rounded-xl space-y-3">
                    <h4 class="text-xs font-bold text-sky-950 uppercase tracking-wider">Formulir Kategori Baru</h4>
                    <form method="POST" action="{{ route('panel.docs.categories.store') }}" class="space-y-3 text-xs">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Ikon (Emoji)</label>
                                <input type="text" name="icon" value="📁" maxlength="8" class="w-full text-center text-base rounded-lg border-slate-300 py-1.5 focus:border-sky-600 focus:ring-sky-500">
                            </div>
                            <div class="sm:col-span-7">
                                <label class="block font-semibold text-slate-700 mb-1">Nama Kategori *</label>
                                <input type="text" name="name" required placeholder="Contoh: Otomatisasi AI & Prompt" class="w-full rounded-lg border-slate-300 py-1.5 focus:border-sky-600 focus:ring-sky-500">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block font-semibold text-slate-700 mb-1">Urutan Tampil</label>
                                <input type="number" name="sort_order" value="10" min="0" class="w-full rounded-lg border-slate-300 py-1.5 focus:border-sky-600 focus:ring-sky-500">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Deskripsi Singkat</label>
                            <input type="text" name="description" placeholder="Penjelasan ruang lingkup panduan kategori ini..." class="w-full rounded-lg border-slate-300 py-1.5 focus:border-sky-600 focus:ring-sky-500">
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="openCreateCatModal = false" class="nara-btn-secondary text-xs py-1.5 px-3">Batal</button>
                            <button type="submit" class="nara-btn-primary text-xs py-1.5 px-4 font-bold">Simpan Kategori Baru</button>
                        </div>
                    </form>
                </div>

                {{-- Tabel Daftar Seluruh Kategori (Bisa Di-scroll Horizontal & Responsif) --}}
                <div class="border border-slate-200 rounded-xl overflow-x-auto bg-white shadow-2xs">
                    <table class="w-full text-left text-xs min-w-[700px]">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="p-3 min-w-[180px] whitespace-nowrap">Kategori</th>
                                <th class="p-3 min-w-[140px] whitespace-nowrap">Slug</th>
                                <th class="p-3 min-w-[200px]">Deskripsi</th>
                                <th class="p-3 min-w-[100px] text-center whitespace-nowrap">Artikel Aktif</th>
                                <th class="p-3 min-w-[70px] text-center whitespace-nowrap">Urutan</th>
                                <th class="p-3 min-w-[140px] text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($allCategories as $c)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-3 font-semibold text-slate-900 whitespace-nowrap">
                                        <span class="mr-1.5">{{ $c->icon }}</span>
                                        <span>{{ $c->name }}</span>
                                    </td>
                                    <td class="p-3 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                        {{ $c->slug }}
                                    </td>
                                    <td class="p-3 text-slate-500 max-w-xs break-words" title="{{ $c->description }}">
                                        {{ $c->description ?: '—' }}
                                    </td>
                                    <td class="p-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ $c->articles_count }} artikel
                                        </span>
                                    </td>
                                    <td class="p-3 text-center text-slate-600 font-mono whitespace-nowrap">
                                        {{ $c->sort_order }}
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    @click="startEditCategory({{ json_encode($c) }})"
                                                    class="px-2.5 py-1 text-[11px] rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 font-semibold transition"
                                                    title="Edit Kategori">
                                                ✏️ Edit
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('panel.docs.categories.destroy', $c) }}"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori &quot;{{ $c->name }}&quot;? Artikel di dalamnya akan dipindahkan ke kategori Umum.')"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2.5 py-1 text-[11px] rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold transition"
                                                        title="Hapus Kategori">
                                                    🗑️ Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-slate-400">
                                        Belum ada kategori yang tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-100">
                    <button type="button" @click="openManageModal = false" class="nara-btn-secondary text-xs py-2 px-5">Tutup</button>
                </div>
            </div>
        </div>

        {{-- Modal Edit Kategori --}}
        <div x-cloak x-show="openEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6 flex items-start sm:items-center justify-center">
            <div @click.away="openEditModal = false" class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full p-6 space-y-4 my-8">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">✏️</span>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base">Edit Kategori</h3>
                            <p class="text-xs text-slate-500">Perbarui nama, ikon, atau urutan kategori.</p>
                        </div>
                    </div>
                    <button @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition" title="Tutup Modal">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" :action="'{{ url('panel/docs/categories') }}/' + editCat.id" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-3">
                            <label class="block font-semibold text-slate-700 mb-1">Ikon (Emoji)</label>
                            <input type="text" name="icon" x-model="editCat.icon" maxlength="8" class="w-full text-center text-base rounded-lg border-slate-300 py-2 focus:border-sky-600 focus:ring-sky-500">
                        </div>
                        <div class="col-span-9">
                            <label class="block font-semibold text-slate-700 mb-1">Nama Kategori *</label>
                            <input type="text" name="name" x-model="editCat.name" required class="w-full rounded-lg border-slate-300 py-2 focus:border-sky-600 focus:ring-sky-500">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Slug URL (Opsional)</label>
                        <input type="text" name="slug" x-model="editCat.slug" placeholder="otomatis dibuat jika dikosongkan" class="w-full rounded-lg border-slate-300 py-2 focus:border-sky-600 focus:ring-sky-500 font-mono text-xs">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Deskripsi Singkat</label>
                        <textarea name="description" x-model="editCat.description" rows="2" class="w-full rounded-lg border-slate-300 py-2 focus:border-sky-600 focus:ring-sky-500 text-xs" placeholder="Penjelasan ruang lingkup..."></textarea>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Urutan Tampil (Sort Order)</label>
                        <input type="number" name="sort_order" x-model="editCat.sort_order" min="0" class="w-full rounded-lg border-slate-300 py-2 focus:border-sky-600 focus:ring-sky-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="openEditModal = false" class="nara-btn-secondary text-xs py-2 px-3">Batal</button>
                        <button type="submit" class="nara-btn-primary text-xs py-2 px-4 font-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-panel-layout>
