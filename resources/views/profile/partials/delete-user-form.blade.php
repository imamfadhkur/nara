<section class="space-y-4">
    <div class="border-b border-rose-100 pb-3">
        <h2 class="text-base font-bold text-rose-800 flex items-center gap-2">
            <span>⚠️ Zona Berbahaya: Hapus Akun</span>
        </h2>
        <p class="text-xs text-rose-600 mt-0.5">
            Setelah akun dihapus, seluruh data sistem, proyek, dan tugas akan dihapus secara permanen.
        </p>
    </div>

    <div x-data="{ openModal: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
        <button
            type="button"
            @click="openModal = true"
            class="nara-btn-danger text-xs py-2 px-3.5"
        >
            Hapus Akun NARA
        </button>

        <div x-show="openModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-2xs">
            <div @click.outside="openModal = false" class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-rose-200">
                <form method="post" action="{{ route('profile.destroy') }}" class="space-y-4">
                    @csrf
                    @method('delete')

                    <h3 class="text-base font-bold text-rose-900">
                        Apakah Anda yakin ingin menghapus akun?
                    </h3>

                    <p class="text-xs text-slate-600">
                        Tindakan ini tidak dapat dibatalkan. Masukkan kata sandi akun Anda untuk mengonfirmasi penghapusan permanen.
                    </p>

                    <div>
                        <label for="password_delete" class="block text-xs font-semibold text-slate-700 mb-1">
                            Kata Sandi Akun <span class="text-rose-600">*</span>
                        </label>
                        <div x-data="{ show: false }" class="relative">
                            <input
                                id="password_delete"
                                name="password"
                                :type="show ? 'text' : 'password'"
                                class="nara-input text-xs pr-10"
                                placeholder="Masukkan kata sandi untuk konfirmasi..."
                                required
                            >
                            <button
                                type="button"
                                @click="show = !show"
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer"
                                :title="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            >
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="show" x-cloak class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        @if ($errors->userDeletion->has('password'))
                            <p class="mt-1 text-xs font-medium text-rose-600" role="alert">
                                {{ $errors->userDeletion->first('password') }}
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="openModal = false" class="nara-btn-secondary text-xs py-1.5 px-3">
                            Batal
                        </button>
                        <button type="submit" class="nara-btn-danger text-xs py-1.5 px-3.5">
                            Ya, Hapus Akun Saya
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
