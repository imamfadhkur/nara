<x-panel-layout title="Pengaturan & Keamanan (2FA)" heading="Pengaturan Sistem & Keamanan">
    <div class="space-y-6 max-w-4xl">

        {{-- Box Kode Pemulihan 2FA jika baru dibuat --}}
        @if ($recoveryCodes)
            <div class="p-5 bg-amber-50 rounded-xl border border-amber-300 shadow-sm" role="alert">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🔐</span>
                    <div>
                        <h3 class="text-sm font-bold text-amber-900">Simpan Kode Pemulihan Anda Sekarang!</h3>
                        <p class="text-xs text-amber-800 mt-1">
                            Simpan kode di bawah ini di tempat offline yang aman (seperti password manager). Setiap kode hanya bisa dipakai satu kali bila Anda kehilangan akses ke aplikasi autentikator.
                        </p>
                        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 bg-white p-3 rounded-lg border border-amber-200 font-mono text-xs text-slate-800 font-bold select-all">
                            @foreach ($recoveryCodes as $code)
                                <div class="p-1 text-center bg-slate-50 rounded">{{ $code }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bagian 1: Autentikasi Dua Langkah (2FA TOTP) (D-12, Bagian 13.1) --}}
        <div class="nara-card p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Autentikasi Dua Langkah (2FA TOTP)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Melindungi akun NARA dengan kode dari Google Authenticator, Aegis, atau 1Password (wajib sejak Fase 0).
                    </p>
                </div>
                <div>
                    @if ($user->hasTwoFactorEnabled())
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                            2FA Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Belum Aktif
                        </span>
                    @endif
                </div>
            </div>

            <div class="pt-5">
                @if (! $user->hasTwoFactorEnabled() && ! $twoFactorSetup)
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-slate-600 max-w-xl">
                            Klik tombol di sebelah kanan untuk memunculkan kode QR dan mulai memasangkan aplikasi autentikator ke perangkat Anda.
                        </p>
                        <form method="POST" action="{{ route('panel.settings.two-factor.enable') }}">
                            @csrf
                            <button type="submit" class="nara-btn-primary text-xs">
                                Mulai Pasang 2FA
                            </button>
                        </form>
                    </div>
                @elseif (! $user->hasTwoFactorEnabled() && $twoFactorSetup)
                    {{-- Langkah Konfirmasi Penyetelan 2FA --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <div class="flex flex-col items-center p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="bg-white p-2 rounded-lg shadow-sm">
                                {!! $twoFactorSetup['qr_svg'] !!}
                            </div>
                            <span class="mt-3 text-[11px] text-slate-500 font-mono select-all">
                                Kunci Manual: <strong>{{ $twoFactorSetup['secret'] }}</strong>
                            </span>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Langkah 2: Masukkan Kode Konfirmasi</h3>
                            <p class="text-xs text-slate-600 mt-1 mb-4">
                                Pindai QR di samping menggunakan aplikasi autentikator Anda, lalu masukkan 6 digit kode yang muncul untuk mengonfirmasi.
                            </p>

                            <form method="POST" action="{{ route('panel.settings.two-factor.confirm') }}" class="space-y-4">
                                @csrf
                                <x-form-field name="code" label="Kode 6 Digit" :required="true">
                                    <input type="text" name="code" maxlength="6" inputmode="numeric" required
                                           placeholder="123456"
                                           class="nara-input font-mono tracking-widest text-base w-48 text-center">
                                </x-form-field>
                                <button type="submit" class="nara-btn-primary text-xs">
                                    Konfirmasi & Aktifkan 2FA
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    {{-- 2FA Sedang Aktif --}}
                    <div class="space-y-4">
                        <p class="text-xs text-slate-600">
                            Akun Anda saat ini terlindungi dengan verifikasi dua langkah. Saat login, sistem akan meminta 6 digit kode TOTP atau salah satu kode pemulihan.
                        </p>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            {{-- Buat ulang kode pemulihan --}}
                            <div x-data="{ open: false }">
                                <button type="button" @click="open = true" class="nara-btn-secondary text-xs">
                                    Buat Ulang Kode Pemulihan
                                </button>
                                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="open = false" class="bg-white rounded-xl p-5 max-w-sm w-full">
                                        <h4 class="font-semibold text-sm text-slate-900">Konfirmasi Kata Sandi</h4>
                                        <p class="text-xs text-slate-600 mt-1 mb-3">Masukkan kata sandi saat ini untuk membuat ulang kode pemulihan:</p>
                                        <form method="POST" action="{{ route('panel.settings.two-factor.recovery') }}" class="space-y-3">
                                            @csrf
                                            <input type="password" name="password" required placeholder="Kata sandi..." class="nara-input text-xs">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="open = false" class="nara-btn-secondary text-xs">Batal</button>
                                                <button type="submit" class="nara-btn-primary text-xs">Generate</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Matikan 2FA --}}
                            <div x-data="{ open: false }">
                                <button type="button" @click="open = true" class="text-xs text-rose-600 hover:underline">
                                    Nonaktifkan 2FA
                                </button>
                                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="open = false" class="bg-white rounded-xl p-5 max-w-sm w-full">
                                        <h4 class="font-semibold text-sm text-rose-700">Matikan 2FA Akun</h4>
                                        <p class="text-xs text-slate-600 mt-1 mb-3">Masukkan kata sandi saat ini untuk menonaktifkan autentikasi dua langkah:</p>
                                        <form method="POST" action="{{ route('panel.settings.two-factor.disable') }}" class="space-y-3">
                                            @csrf
                                            @method('DELETE')
                                            <input type="password" name="password" required placeholder="Kata sandi..." class="nara-input text-xs">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="open = false" class="nara-btn-secondary text-xs">Batal</button>
                                                <button type="submit" class="nara-btn-danger text-xs">Nonaktifkan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bagian 2: Pengaturan Akun, Zona Waktu, dan Aturan Bisnis NARA --}}
        <div class="nara-card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-1">Pengaturan Umum & Batas Sistem (Bagian 6.7)</h2>
            <p class="text-xs text-slate-500 mb-5">
                Konfigurasi zona waktu, batasan WIP guard, dan kapasitas jam mingguan personal Anda.
            </p>

            <form method="POST" action="{{ route('panel.settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-field name="timezone" label="Zona Waktu Tampilan" :required="true"
                                  hint="D-18: Semua waktu disimpan UTC dan ditampilkan sesuai zona ini.">
                        <select name="timezone" class="nara-input">
                            @foreach (['Asia/Jakarta' => 'WIB (Asia/Jakarta)', 'Asia/Makassar' => 'WITA (Asia/Makassar)', 'Asia/Jayapura' => 'WIT (Asia/Jayapura)', 'UTC' => 'UTC'] as $tzVal => $tzLabel)
                                <option value="{{ $tzVal }}" @selected(old('timezone', $user->timezone) === $tzVal)>
                                    {{ $tzLabel }}
                                </option>
                            @endforeach
                        </select>
                    </x-form-field>

                    <x-form-field name="telegram_chat_id" label="Telegram Chat ID (Allowlist)"
                                  hint="Hanya Chat ID ini yang dilayani oleh bot (D-12, Bagian 8.6).">
                        <input type="text" name="telegram_chat_id"
                               value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}"
                               placeholder="Mis. 100000001"
                               class="nara-input font-mono">
                    </x-form-field>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Aturan Kapasitas & Batas WIP</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-form-field name="weekly_capacity_minutes" label="Kapasitas Mingguan (Menit)" :required="true"
                                      hint="1500 menit = 25 jam fokus kerja nyata.">
                            <input type="number" name="weekly_capacity_minutes" min="60" max="6000" step="30"
                                   value="{{ old('weekly_capacity_minutes', $settings['weekly_capacity_minutes']) }}"
                                   class="nara-input">
                        </x-form-field>

                        <x-form-field name="capacity_warn_percent" label="Ambang Padat (%)" :required="true" hint="Default: 80%">
                            <input type="number" name="capacity_warn_percent" min="50" max="100"
                                   value="{{ old('capacity_warn_percent', $settings['capacity_warn_percent']) }}"
                                   class="nara-input">
                        </x-form-field>

                        <x-form-field name="capacity_over_percent" label="Ambang Overload (%)" :required="true" hint="Default: 100%">
                            <input type="number" name="capacity_over_percent" min="80" max="200"
                                   value="{{ old('capacity_over_percent', $settings['capacity_over_percent']) }}"
                                   class="nara-input">
                        </x-form-field>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <x-form-field name="wip_limit_per_slot" label="Batas Max WIP per Slot" :required="true" hint="Default: 3 project">
                            <input type="number" name="wip_limit_per_slot" min="1" max="10"
                                   value="{{ old('wip_limit_per_slot', $settings['wip_limit_per_slot']) }}"
                                   class="nara-input">
                        </x-form-field>

                        <x-form-field name="stagnation_days" label="Ambang Stagnan (Hari)" :required="true" hint="Tanpa aktivitas > N hari (7.7)">
                            <input type="number" name="stagnation_days" min="1" max="60"
                                   value="{{ old('stagnation_days', $settings['stagnation_days']) }}"
                                   class="nara-input">
                        </x-form-field>

                        <x-form-field name="urgent_within_days" label="Ambang Urgen (Hari)" :required="true" hint="Jatuh tempo dlm N hari (7.6)">
                            <input type="number" name="urgent_within_days" min="0" max="30"
                                   value="{{ old('urgent_within_days', $settings['urgent_within_days']) }}"
                                   class="nara-input">
                        </x-form-field>

                        <x-form-field name="nara_budget_minutes_per_week" label="Anggaran Dev NARA (Mnt/Mgg)" :required="true" hint="Default: 360 (6 jam)">
                            <input type="number" name="nara_budget_minutes_per_week" min="60" max="2400"
                                   value="{{ old('nara_budget_minutes_per_week', $settings['nara_budget_minutes_per_week']) }}"
                                   class="nara-input">
                        </x-form-field>
                    </div>

                    <div class="mt-4">
                        <x-form-field name="admin_budget_minutes_per_day" label="Batas Administrasi NARA (Menit/Hari)" :required="true"
                                      hint="Target: ≤ 10 menit/hari untuk capture & triase (Bagian 14.2).">
                            <input type="number" name="admin_budget_minutes_per_day" min="1" max="60"
                                   value="{{ old('admin_budget_minutes_per_day', $settings['admin_budget_minutes_per_day']) }}"
                                   class="nara-input w-36">
                        </x-form-field>
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" class="nara-btn-primary text-xs">Simpan Semua Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</x-panel-layout>

