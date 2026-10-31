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
                        Melindungi akun NARA dengan kode dari Google Authenticator, Aegis, atau 1Password (lapisan keamanan tambahan opsional).
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
                                <button type="button"
                                        @click="open = true"
                                        class="inline-flex items-center justify-center px-4 py-2
                                            rounded-lg border border-blue-300
                                            bg-blue-50 text-blue-800
                                            text-xs font-semibold
                                            hover:bg-blue-100 hover:border-blue-400
                                            active:bg-blue-200
                                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                                            transition-all">
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
                                                <button type="button"
                                                        @click="open = false"
                                                        class="inline-flex items-center justify-center px-4 py-2
                                                            rounded-lg border border-slate-300
                                                            bg-slate-100 text-slate-800
                                                            text-xs font-semibold
                                                            hover:bg-slate-200 hover:text-slate-900
                                                            transition-all">
                                                    Batal
                                                </button>
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center px-4 py-2
                                                            rounded-lg bg-blue-600 text-white
                                                            text-xs font-semibold
                                                            hover:bg-blue-700
                                                            active:bg-blue-800
                                                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                                                            transition-all">
                                                    Generate
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Matikan 2FA --}}
                            <div x-data="{ open: false }">
                                <button type="button"
                                        @click="open = true"
                                        class="inline-flex items-center justify-center
                                            px-3 py-2 rounded-lg
                                            bg-rose-50 text-rose-700
                                            border border-rose-200
                                            text-xs font-semibold
                                            hover:bg-rose-100 hover:border-rose-300
                                            active:bg-rose-200
                                            transition-all">
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
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center
                                                            px-4 py-2 rounded-lg
                                                            bg-rose-600 text-white
                                                            text-xs font-semibold
                                                            border border-rose-700
                                                            hover:bg-rose-700
                                                            active:bg-rose-800
                                                            transition-all">
                                                    Nonaktifkan
                                                </button>
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

        {{-- Bagian 2: Manajemen Integrasi Eksternal & Aturan Dinamis (Fase 2) --}}
        <div id="integrations" class="nara-card p-6 border-l-4 border-sky-600">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                        <span>🔌 Manajemen Integrasi Eksternal (Fase 2)</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Konfigurasi dinamis berbasis database untuk Telegram dan Google Calendar. Atur on/off rule tanpa wajib menyentuh file .env.
                    </p>
                </div>
            </div>

            <div class="pt-5 space-y-6">
                {{-- Integrasi 1: Telegram Bot --}}
                <div class="p-4 rounded-xl border {{ $isTelegramEnabled ? 'border-sky-300 bg-sky-50/30' : 'border-slate-200 bg-slate-50/50' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-200/80">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🤖</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Telegram Bot Integration</h3>
                                <p class="text-xs text-slate-500">Capture cepat inbox, brief harian, reminder jadwal, dan perintah /done.</p>
                            </div>
                        </div>
                        <div>
                            @if ($isTelegramEnabled)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    <span class="h-2 w-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                    Aktif (Rules On)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                                    Nonaktif (Dormant)
                                </span>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('panel.settings.integrations.telegram') }}" class="space-y-4">
                        @csrf
                        <div class="flex items-center gap-2 mb-3">
                            <input type="checkbox" id="telegram_active" name="is_active" value="1"
                                   @checked($isTelegramEnabled)
                                   class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="telegram_active" class="text-xs font-semibold text-slate-800 cursor-pointer">
                                Aktifkan Integrasi Telegram (Rule Switch)
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Bot Token</label>
                                <input type="password" name="bot_token"
                                       value="{{ old('bot_token', $telegramConfig['bot_token']) }}"
                                       placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                                       class="w-full text-xs font-mono rounded-lg border-slate-300 py-1.5 px-2.5 focus:border-sky-500">
                                <span class="text-[11px] text-slate-400">Dari @BotFather di Telegram</span>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Chat ID (User Allowlist)</label>
                                <input type="text" name="chat_id"
                                       value="{{ old('chat_id', $telegramConfig['chat_id']) }}"
                                       placeholder="969666384"
                                       class="w-full text-xs font-mono rounded-lg border-slate-300 py-1.5 px-2.5 focus:border-sky-500">
                                <span class="text-[11px] text-slate-400">ID akun Telegram pribadi Anda</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-[11px] text-slate-500 italic">
                                *Jika dinonaktifkan: bot tidak mengirim briefing/reminder dan webhook luar ditolak dengan aman.
                            </span>
                            <button type="submit" class="nara-btn-primary text-xs py-1.5 px-3">
                                Simpan Telegram Config
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Integrasi 2: Google Calendar (Fase 2) --}}
                <div class="p-4 rounded-xl border {{ $isGoogleEnabled ? 'border-emerald-300 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/50' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-200/80">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📅</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Google Calendar Integration</h3>
                                <p class="text-xs text-slate-500">Sinkronisasi jadwal sesi fokus, deteksi konflik, dan NARA-authoritative mirror.</p>
                            </div>
                        </div>
                        <div>
                            @if ($isGoogleEnabled)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    <span class="h-2 w-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                    Aktif (Rules On)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                                    Mode Lokal (🏠 Off)
                                </span>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('panel.settings.integrations.google') }}" class="space-y-4">
                        @csrf
                        <div class="flex items-center gap-2 mb-3">
                            <input type="checkbox" id="google_active" name="is_active" value="1"
                                   @checked($isGoogleEnabled)
                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="google_active" class="text-xs font-semibold text-slate-800 cursor-pointer">
                                Aktifkan Sinkronisasi Google Calendar (Rule Switch)
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Google Client ID</label>
                                <input type="text" name="client_id"
                                       value="{{ old('client_id', $googleConfig['client_id']) }}"
                                       placeholder="123456...apps.googleusercontent.com"
                                       class="w-full text-xs font-mono rounded-lg border-slate-300 py-1.5 px-2.5 focus:border-sky-500">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Google Client Secret</label>
                                <input type="password" name="client_secret"
                                       value="{{ old('client_secret', $googleConfig['client_secret']) }}"
                                       placeholder="GOCSPX-..."
                                       class="w-full text-xs font-mono rounded-lg border-slate-300 py-1.5 px-2.5 focus:border-sky-500">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Kalender Terdedikasi</label>
                                <input type="text" name="calendar_id"
                                       value="{{ old('calendar_id', $googleConfig['calendar_id']) }}"
                                       placeholder="NARA"
                                       class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5 focus:border-sky-500">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-[11px] text-slate-500 italic">
                                *Jika dinonaktifkan: Kalender berjalan 100% lokal tanpa dependensi eksternal, bebas error.
                            </span>
                            <div class="flex items-center gap-2">
                                @if ($isGoogleEnabled)
                                    <a href="{{ route('panel.calendar.index') }}" class="nara-btn-secondary text-xs py-1.5 px-3">
                                        Buka Kalender →
                                    </a>
                                @endif
                                <button type="submit" class="nara-btn-primary text-xs py-1.5 px-3">
                                    Simpan Google Config
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bagian 3: Uji Coba Notifikasi & Reminder Telegram --}}
        <div class="nara-card p-6 border-l-4 border-indigo-500">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📲</span>
                        <h2 class="text-base font-semibold text-slate-900">Uji Coba Notifikasi Telegram (Fase 1)</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Picu pengiriman notifikasi pengujian langsung ke akun Telegram Anda
                        @if($user->telegram_chat_id)
                            (Chat ID: <code class="bg-indigo-50 text-indigo-700 font-semibold px-1.5 py-0.5 rounded border border-indigo-100">{{ $user->telegram_chat_id }}</code>).
                        @else
                            (<span class="text-amber-600 font-medium">Chat ID belum diisi pada form di bawah</span>).
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('panel.settings.test-notification') }}">
                        @csrf
                        <input type="hidden" name="type" value="briefing">
                        <button type="submit"
                                style="background-color: #d97706 !important; color: #ffffff !important;"
                                class="inline-flex items-center justify-center gap-2
                                    px-4 py-2.5 rounded-lg
                                    text-xs font-bold
                                    shadow-sm
                                    hover:brightness-110 hover:shadow
                                    active:scale-95
                                    disabled:opacity-50
                                    disabled:cursor-not-allowed
                                    transition-all"
                                @disabled(empty($user->telegram_chat_id))>
                            <span class="text-sm">☀️</span>
                            <span style="color: #ffffff !important;">Kirim Briefing Pagi</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('panel.settings.test-notification') }}">
                        @csrf
                        <input type="hidden" name="type" value="reminder">
                        <button type="submit"
                                style="background-color: #4f46e5 !important; color: #ffffff !important;"
                                class="inline-flex items-center justify-center gap-2
                                    px-4 py-2.5 rounded-lg
                                    text-xs font-bold
                                    shadow-sm
                                    hover:brightness-110 hover:shadow
                                    active:scale-95
                                    disabled:opacity-50
                                    disabled:cursor-not-allowed
                                    transition-all"
                                @disabled(empty($user->telegram_chat_id))>
                            <span class="text-sm">⏰</span>
                            <span style="color: #ffffff !important;">Kirim Reminder Jadwal</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bagian 3: Pengaturan Akun, Zona Waktu, dan Aturan Bisnis NARA --}}
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

                <div class="pt-2 border-t border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Jadwal Notifikasi Otomatis (Bagian 7.8)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form-field name="briefing_time" label="Waktu Briefing Pagi (HH:MM)" hint="Default: 07:00 sesuai zona waktu Anda.">
                            <input type="text" name="briefing_time" pattern="^([01]\d|2[0-3]):[0-5]\d$" placeholder="07:00"
                                   value="{{ old('briefing_time', $settings['briefing_time'] ?? '07:00') }}"
                                   class="nara-input font-mono">
                        </x-form-field>

                        <x-form-field name="default_reminder_minutes" label="Pengingat Sebelum Mulai Jadwal (Menit)" hint="Default: 30 menit sebelum sesi dimulai.">
                            <input type="number" name="default_reminder_minutes" min="5" max="120" step="5"
                                   value="{{ old('default_reminder_minutes', $settings['default_reminder_minutes'] ?? 30) }}"
                                   class="nara-input">
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

