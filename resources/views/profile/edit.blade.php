<x-panel-layout title="Profil & Akun Pengguna" heading="Pengaturan Profil & Akun">
    <div class="space-y-6 max-w-4xl">
        {{-- Banner navigasi cepat ke Pengaturan Umum & Integrasi --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-3">
            <div>
                <p class="text-xs text-slate-500">
                    Kelola nama profil, alamat email login, serta kata sandi akun NARA Anda.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('panel.settings.index') }}" class="nara-btn-secondary text-xs py-1.5 px-3">
                    <span>⚙️</span>
                    <span>Pengaturan Sistem & Kapasitas →</span>
                </a>
            </div>
        </div>

        {{-- Kartu 1: Informasi Profil Akun --}}
        <div class="nara-card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- Kartu 2: Ganti Kata Sandi --}}
        <div class="nara-card p-6">
            @include('profile.partials.update-password-form')
        </div>

        {{-- Kartu 3: Hapus Akun (Zona Berbahaya) --}}
        <div class="nara-card p-6 border-rose-200 bg-rose-50/20">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-panel-layout>
