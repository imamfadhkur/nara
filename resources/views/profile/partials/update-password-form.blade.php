<section>
    <div class="border-b border-slate-100 pb-4 mb-5">
        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>🔑 Perbarui Kata Sandi</span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
            Pastikan akun Anda menggunakan kata sandi yang kuat dan acak agar tetap aman.
        </p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4 max-w-xl"
          x-data="{
              showAll: false,
              showCurrent: false,
              showNew: false,
              showConfirm: false,
          }">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-slate-700 mb-1">
                Kata Sandi Saat Ini <span class="text-rose-600">*</span>
            </label>
            <div class="relative">
                <input id="update_password_current_password"
                       name="current_password"
                       :type="(showAll || showCurrent) ? 'text' : 'password'"
                       class="nara-input pr-10"
                       autocomplete="current-password"
                       required>
                <button type="button"
                        @click="showCurrent = !showCurrent"
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer"
                        :title="(showAll || showCurrent) ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :aria-label="(showAll || showCurrent) ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <svg x-show="!(showAll || showCurrent)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showAll || showCurrent" x-cloak class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            @if ($errors->updatePassword->has('current_password'))
                <p class="mt-1 text-xs font-medium text-rose-600" role="alert">
                    {{ $errors->updatePassword->first('current_password') }}
                </p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-medium text-slate-700 mb-1">
                Kata Sandi Baru <span class="text-rose-600">*</span>
            </label>
            <div class="relative">
                <input id="update_password_password"
                       name="password"
                       :type="(showAll || showNew) ? 'text' : 'password'"
                       class="nara-input pr-10"
                       autocomplete="new-password"
                       required>
                <button type="button"
                        @click="showNew = !showNew"
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer"
                        :title="(showAll || showNew) ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :aria-label="(showAll || showNew) ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <svg x-show="!(showAll || showNew)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showAll || showNew" x-cloak class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-xs text-slate-500">Minimal 6 karakter atau lebih.</p>
            @if ($errors->updatePassword->has('password'))
                <p class="mt-1 text-xs font-medium text-rose-600" role="alert">
                    {{ $errors->updatePassword->first('password') }}
                </p>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">
                Ulangi Kata Sandi Baru <span class="text-rose-600">*</span>
            </label>
            <div class="relative">
                <input id="update_password_password_confirmation"
                       name="password_confirmation"
                       :type="(showAll || showConfirm) ? 'text' : 'password'"
                       class="nara-input pr-10"
                       autocomplete="new-password"
                       required>
                <button type="button"
                        @click="showConfirm = !showConfirm"
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer"
                        :title="(showAll || showConfirm) ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :aria-label="(showAll || showConfirm) ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <svg x-show="!(showAll || showConfirm)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showAll || showConfirm" x-cloak class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            @if ($errors->updatePassword->has('password_confirmation'))
                <p class="mt-1 text-xs font-medium text-rose-600" role="alert">
                    {{ $errors->updatePassword->first('password_confirmation') }}
                </p>
            @endif
        </div>

        {{-- Opsi Tampilkan Semua Kata Sandi Sekaligus --}}
        <div class="pt-1">
            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-600 select-none">
                <input type="checkbox" x-model="showAll" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <span>Tampilkan semua kata sandi</span>
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="nara-btn-primary text-xs py-2 px-4">
                Simpan Kata Sandi Baru
            </button>

            @if (session('status') === 'password-updated')
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-xs font-semibold text-emerald-700 flex items-center gap-1"
                >
                    <span>✓</span> Kata sandi berhasil diperbarui.
                </span>
            @endif
        </div>
    </form>
</section>
