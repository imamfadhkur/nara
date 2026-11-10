<x-panel-layout :title="$isEdit ? 'Edit Dokumentasi: ' . $article->title : 'Tulis Dokumentasi Baru'" :heading="$isEdit ? 'Edit Panduan Dokumentasi' : 'Tulis Panduan Dokumentasi Baru'">
    <div class="max-w-4xl mx-auto space-y-6"
         x-data="{
             selectedCategory: '{{ old('documentation_category_id', $article->documentation_category_id ?? '') }}',
             isNewCategory: {{ old('documentation_category_id') === 'new' ? 'true' : 'false' }},
             checkCategoryChange() {
                 this.isNewCategory = (this.selectedCategory === 'new');
             }
         }">

        <div class="flex items-center justify-between text-xs">
            <a href="{{ $isEdit ? route('panel.docs.show', $article->slug) : route('panel.docs.index') }}" class="nara-btn-secondary py-1.5 px-3">
                ← Batal / Kembali
            </a>
            @if ($isEdit)
                <form method="POST" action="{{ route('panel.docs.destroy', $article) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus artikel dokumentasi ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold underline">
                        Hapus Artikel Ini
                    </button>
                </form>
            @endif
        </div>

        <div class="nara-card p-6 sm:p-8">
            <div class="pb-4 mb-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        {{ $isEdit ? 'Perbarui Konten Dokumentasi' : 'Tambah Panduan Baru ke Database' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Dokumentasi disimpan di database dan langsung terbit di website untuk panduan operasional.
                    </p>
                </div>
                <span class="text-2xl">{{ $article->icon ?: '📝' }}</span>
            </div>

            <form method="POST" action="{{ $isEdit ? route('panel.docs.update', $article) : route('panel.docs.store') }}" class="space-y-5 text-xs">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                {{-- Judul & Slug --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Judul Panduan / Masalah *</label>
                        <input
                            type="text"
                            name="title"
                            value="{{ old('title', $article->title) }}"
                            required
                            placeholder="Contoh: Panduan Mengatasi Error 403 access_denied Google OAuth"
                            class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500"
                        >
                        @error('title')
                            <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">URL Slug</label>
                        <input
                            type="text"
                            name="slug"
                            value="{{ old('slug', $article->slug) }}"
                            placeholder="otomatis-dari-judul"
                            class="w-full text-xs font-mono rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500"
                        >
                        <p class="text-[10px] text-slate-400 mt-0.5">Biarkan kosong untuk generate otomatis.</p>
                        @error('slug')
                            <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Pemilihan Kategori Terstruktur (Dropdown Tabel + Opsi Buat Baru) --}}
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/90 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span>🏷️</span>
                            <span>Kategori Dokumentasi (Tabel Terstruktur) *</span>
                        </label>
                        <button
                            type="button"
                            @click="isNewCategory = !isNewCategory; selectedCategory = isNewCategory ? 'new' : ''"
                            class="text-xs font-semibold text-sky-600 hover:text-sky-800 transition"
                            x-text="isNewCategory ? '← Pilih dari Kategori yang Ada' : '+ Buat Kategori Baru'"
                        >
                            + Buat Kategori Baru
                        </button>
                    </div>

                    {{-- Mode 1: Pilih dari Kategori yang Sudah Ada di Database --}}
                    <div x-show="!isNewCategory">
                        <select
                            name="documentation_category_id"
                            x-model="selectedCategory"
                            @change="checkCategoryChange()"
                            class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500 bg-white"
                        >
                            <option value="">-- Pilih Kategori yang Sudah Ada di Database --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('documentation_category_id', $article->documentation_category_id) == $cat->id)>
                                    {{ $cat->icon }} {{ $cat->name }}
                                </option>
                            @endforeach
                            <option value="new">➕ + Buat Kategori Baru Lainnya...</option>
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Pilih salah satu dari <strong>{{ $categories->count() }} kategori resmi</strong> yang sudah terdaftar tanpa perlu menebak-nebak nama kategori.
                        </p>
                    </div>

                    {{-- Mode 2: Form Buat Kategori Baru jika Tidak Cocok --}}
                    <div x-show="isNewCategory" class="p-3 bg-white rounded-lg border border-sky-200 space-y-3" style="display: none;">
                        <span class="text-[11px] font-bold text-sky-800 uppercase tracking-wider block">
                            ✨ Detail Kategori Baru
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Nama Kategori Baru *</label>
                                <input
                                    type="text"
                                    name="new_category_name"
                                    value="{{ old('new_category_name') }}"
                                    placeholder="Contoh: Integrasi WhatsApp / Webhook"
                                    class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-3 focus:border-sky-500"
                                >
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Ikon Kategori</label>
                                <input
                                    type="text"
                                    name="new_category_icon"
                                    value="{{ old('new_category_icon', '📁') }}"
                                    placeholder="📁"
                                    class="w-full text-xs text-center rounded-lg border-slate-300 py-1.5 px-3 focus:border-sky-500"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Deskripsi Singkat Kategori (Opsional)</label>
                            <input
                                type="text"
                                name="new_category_desc"
                                value="{{ old('new_category_desc') }}"
                                placeholder="Penjelasan singkat tujuan kelompok panduan ini..."
                                class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-3 focus:border-sky-500"
                            >
                        </div>
                    </div>
                </div>

                {{-- Ikon Artikel --}}
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Ikon Artikel (Emoji)</label>
                        <input
                            type="text"
                            name="icon"
                            value="{{ old('icon', $article->icon ?: '📖') }}"
                            placeholder="📖"
                            class="w-full text-xs text-center rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500"
                        >
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block font-semibold text-slate-700 mb-1">Ringkasan / Excerpt (Muncul di Kartu Depan) *</label>
                        <textarea
                            name="excerpt"
                            rows="2"
                            required
                            placeholder="Ringkasan 1-2 kalimat mengenai topik ini..."
                            class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500 leading-relaxed"
                        >{{ old('excerpt', $article->excerpt) }}</textarea>
                        @error('excerpt')
                            <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Konten Lengkap Markdown --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-semibold text-slate-700">Isi Lengkap Panduan (Format Markdown) *</label>
                        <span class="text-[11px] text-slate-400">Mendukung heading `#`, list `-`, tabel, blok kode ````</span>
                    </div>
                    <textarea
                        name="content"
                        rows="18"
                        required
                        placeholder="# Judul Artikel&#10;&#10;Tuliskan panduan langkah demi langkah di sini..."
                        class="w-full text-xs font-mono rounded-lg border-slate-300 py-3 px-3.5 focus:border-sky-500 leading-relaxed bg-slate-50/50"
                    >{{ old('content', $article->content) }}</textarea>
                    @error('content')
                        <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Pengaturan Urutan & Publikasi --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-slate-50 rounded-xl border border-slate-200/80 items-center">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Urutan Tampil (Sort Order)</label>
                        <input
                            type="number"
                            name="sort_order"
                            value="{{ old('sort_order', $article->sort_order ?? 0) }}"
                            min="0"
                            max="9999"
                            class="w-32 text-xs rounded-lg border-slate-300 py-1.5 px-3 focus:border-sky-500"
                        >
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-1 sm:mt-0">
                            <input
                                type="checkbox"
                                name="is_published"
                                value="1"
                                @checked(old('is_published', $article->is_published ?? true))
                                class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                            >
                            <span class="font-bold text-slate-800">Publikasikan Artikel Ini</span>
                        </label>
                        <p class="text-[11px] text-slate-500 mt-0.5 ml-6">Jika tidak dicentang, hanya tersimpan sebagai draf.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="{{ $isEdit ? route('panel.docs.show', $article->slug) : route('panel.docs.index') }}" class="nara-btn-secondary text-xs py-2 px-4">
                        Batal
                    </a>
                    <button type="submit" class="nara-btn-primary text-xs py-2 px-5 font-bold">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Terbitkan Dokumentasi' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-panel-layout>
