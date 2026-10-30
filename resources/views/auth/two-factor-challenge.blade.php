<x-guest-layout>
    <div class="mb-4">
        <h1 class="text-lg font-semibold text-gray-900">Verifikasi Dua Langkah (2FA)</h1>
        <p class="mt-1 text-sm text-gray-600">
            Masukkan kode 6 digit dari aplikasi autentikator Anda, atau salah satu kode pemulihan offline.
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Kode Autentikasi / Kode Pemulihan" />
            <x-text-input
                id="code"
                class="block mt-1 w-full tracking-widest font-mono"
                type="text"
                name="code"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-gray-600 hover:text-gray-900 underline" href="{{ route('login') }}">
                Kembali ke login
            </a>

            <x-primary-button>
                Verifikasi & Masuk
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

