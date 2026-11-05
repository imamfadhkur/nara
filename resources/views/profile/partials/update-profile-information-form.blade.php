<section>
    <div class="border-b border-slate-100 pb-4 mb-5">
        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>👤 Informasi Profil Akun</span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
            Perbarui nama lengkap dan alamat email yang Anda gunakan untuk masuk (login) ke sistem NARA.
        </p>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4 max-w-xl">
        @csrf
        @method('patch')

        @if (isset($redirectBackToSettings) && $redirectBackToSettings)
            <input type="hidden" name="redirect_to" value="{{ route('panel.settings.index') }}#account">
        @endif

        <x-form-field name="name" label="Nama Lengkap" :required="true"
                      hint="Nama yang ditampilkan di panel dan pada sapaan NARA.">
            <input id="name" name="name" type="text"
                   class="nara-input"
                   value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
        </x-form-field>

        <x-form-field name="email" label="Alamat Email (Username Login)" :required="true"
                      hint="Alamat email unik yang digunakan untuk autentikasi dan masuk ke sistem.">
            <input id="email" name="email" type="email"
                   class="nara-input font-mono text-xs"
                   value="{{ old('email', $user->email) }}" required autocomplete="username">
        </x-form-field>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-800">
                <p>
                    Alamat email Anda belum diverifikasi.
                    <button form="send-verification" class="underline font-semibold hover:text-amber-950 ml-1">
                        Kirim ulang email verifikasi.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-semibold text-emerald-700">
                        ✓ Tautan verifikasi baru telah dikirimkan ke alamat email Anda.
                    </p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="nara-btn-primary text-xs py-2 px-4">
                Simpan Perubahan Profil
            </button>

            @if (session('status') === 'profile-updated')
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-xs font-semibold text-emerald-700 flex items-center gap-1"
                >
                    <span>✓</span> Profil berhasil diperbarui.
                </span>
            @endif
        </div>
    </form>
</section>
