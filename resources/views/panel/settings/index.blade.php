<x-panel-layout title="Pengaturan & Integrasi" heading="Pengaturan Sistem & Integrasi">
    <div
        class="space-y-6 max-w-4xl"
        x-data="{
            activeTab: (
                window.location.hash === '#account' || '{{ session('status') }}' === 'profile-updated' || '{{ session('status') }}' === 'password-updated' || {{ ($errors->has('name') || $errors->has('email') || $errors->updatePassword->isNotEmpty()) ? 'true' : 'false' }}
                    ? 'account'
                    : (window.location.hash === '#integrations' ? 'integrations' : (window.location.hash === '#security' ? 'security' : 'general'))
            ),
            setTab(tab) {
                this.activeTab = tab;
                window.location.hash = tab;
            }
        }"
    >
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

        {{-- Navigasi Tab Pengelompokan Pengaturan --}}
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
            <button
                type="button"
                @click="setTab('account')"
                :class="activeTab === 'account' ? 'bg-sky-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 font-medium'"
                class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-2 shrink-0 border border-slate-200"
            >
                <span>👤</span>
                <span>Profil & Akun</span>
            </button>

            <button
                type="button"
                @click="setTab('general')"
                :class="activeTab === 'general' ? 'bg-sky-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 font-medium'"
                class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-2 shrink-0 border border-slate-200"
            >
                <span>⚙️</span>
                <span>Pengaturan Umum & Kapasitas</span>
            </button>

            <button
                type="button"
                @click="setTab('integrations')"
                :class="activeTab === 'integrations' ? 'bg-sky-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 font-medium'"
                class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-2 shrink-0 border border-slate-200 relative"
            >
                <span>🔌</span>
                <span>Integrasi Eksternal (Fase 2)</span>
                @if ($isTelegramEnabled || $isGoogleEnabled)
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                @endif
            </button>

            <button
                type="button"
                @click="setTab('security')"
                :class="activeTab === 'security' ? 'bg-sky-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 font-medium'"
                class="px-4 py-2 rounded-lg text-xs transition flex items-center gap-2 shrink-0 border border-slate-200"
            >
                <span>🔐</span>
                <span>Keamanan & 2FA</span>
                @if (! $user->hasTwoFactorEnabled())
                    <span class="px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Opsional</span>
                @endif
            </button>
        </div>

        {{-- ======================================================== --}}
        {{-- TAB 0: PROFIL & AKUN PENGGUNA                            --}}
        {{-- ======================================================== --}}
        <div x-show="activeTab === 'account'" class="space-y-6" style="display: none;">
            {{-- Kartu 1: Informasi Profil Akun --}}
            <div class="nara-card p-6">
                @include('profile.partials.update-profile-information-form', ['redirectBackToSettings' => true])
            </div>

            {{-- Kartu 2: Ganti Kata Sandi (Password) --}}
            <div class="nara-card p-6">
                @include('profile.partials.update-password-form')
            </div>

            {{-- Kartu 3: Tautan Pintas ke 2FA --}}
            <div class="p-4 bg-sky-50/60 rounded-xl border border-sky-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🔐</span>
                    <div>
                        <strong class="text-slate-800">Autentikasi Dua Langkah (2FA TOTP)</strong>
                        <p class="text-slate-500 text-[11px] mt-0.5">
                            Status saat ini:
                            <span class="font-semibold {{ $user->hasTwoFactorEnabled() ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $user->hasTwoFactorEnabled() ? 'Aktif' : 'Belum Aktif' }}
                            </span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="setTab('security')" class="nara-btn-secondary text-xs py-1.5 px-3 self-start sm:self-auto">
                    Kelola 2FA →
                </button>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- TAB 1: PENGATURAN UMUM & KAPASITAS                       --}}
        {{-- ======================================================== --}}
        <div x-show="activeTab === 'general'" class="space-y-6">
            <div class="nara-card p-6">
                <div class="border-b border-slate-100 pb-4 mb-5">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>⚙️ Pengaturan Umum & Batas Sistem (Bagian 6.7)</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Konfigurasi zona waktu tampilan, batas kuota WIP guard per slot, dan kapasitas fokus mingguan.
                    </p>
                </div>

                <form method="POST" action="{{ route('panel.settings.update') }}" class="space-y-6">
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
                                   placeholder="Mis. 969666384"
                                   class="nara-input font-mono">
                        </x-form-field>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
                            <span>📊 Aturan Kapasitas Kerja Mingguan</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <x-form-field name="weekly_capacity_minutes" label="Kapasitas Mingguan (Menit)" :required="true"
                                          hint="1.500 menit = 25 jam fokus kerja nyata.">
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

                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
                            <span>🛡️ Batas WIP & Pagar Pengaman</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <x-form-field name="wip_limit_per_slot" label="Batas Max WIP per Slot" :required="true" hint="Default: 3 project aktif (7.3)">
                                <input type="number" name="wip_limit_per_slot" min="1" max="10"
                                       value="{{ old('wip_limit_per_slot', $settings['wip_limit_per_slot']) }}"
                                       class="nara-input">
                            </x-form-field>

                            <x-form-field name="stagnation_days" label="Ambang Stagnan (Hari)" :required="true" hint="Tanpa aktivitas > N hari (7.7)">
                                <input type="number" name="stagnation_days" min="1" max="60"
                                       value="{{ old('stagnation_days', $settings['stagnation_days']) }}"
                                       class="nara-input">
                            </x-form-field>

                            <x-form-field name="urgent_within_days" label="Ambang Urgen Matrix (Hari)" :required="true" hint="Jatuh tempo dlm N hari (7.6)">
                                <input type="number" name="urgent_within_days" min="0" max="30"
                                       value="{{ old('urgent_within_days', $settings['urgent_within_days']) }}"
                                       class="nara-input">
                            </x-form-field>
                        </div>

                        {{-- Pengaturan Kuota Batas WIP per Goal (Opsi B & C: WIP Slot berbasis Goal dinamis) --}}
                        <div class="mt-6 p-4 rounded-xl border border-sky-200/90 bg-sky-50/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-sky-950 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>🎯</span>
                                        <span>Batas Maksimal Project Aktif (WIP) per Goal</span>
                                    </h4>
                                    <p class="text-[11px] text-slate-600 mt-0.5">
                                        Setiap Goal berfungsi sebagai wadah WIP dinamis. Anda dapat menentukan batas maksimal project aktif secara spesifik per Goal (1–10 project), atau kosongkan untuk mengikuti batas default sistem ({{ $settings['wip_limit_per_slot'] }} project).
                                    </p>
                                </div>
                                <a href="{{ route('panel.goals.create') }}" class="nara-btn-secondary text-[11px] py-1 px-2.5 shrink-0">
                                    + Goal Baru
                                </a>
                            </div>

                            @if ($goals->isEmpty())
                                <p class="text-xs text-slate-500 italic py-2">
                                    Belum ada Goal. <a href="{{ route('panel.goals.create') }}" class="text-sky-700 underline font-semibold">Buat goal pertama Anda</a> untuk mulai mengelompokkan project dan batas WIP.
                                </p>
                            @else
                                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                            <tr>
                                                <th class="p-2.5">Life Domain & Judul Goal</th>
                                                <th class="p-2.5 text-center">Status Project Aktif</th>
                                                <th class="p-2.5 text-right w-44">Batas Max WIP</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($goals as $g)
                                                <tr class="hover:bg-slate-50/70">
                                                    <td class="p-2.5">
                                                        <div class="font-semibold text-slate-800">{{ $g->title }}</div>
                                                        <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                                            <span class="inline-block w-2 h-2 rounded-full" style="background-color: var(--color-{{ $g->lifeDomain->color ?? 'blue' }}-500, #0284c7);"></span>
                                                            <span>{{ $g->lifeDomain?->name ?? 'Tanpa Domain' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="p-2.5 text-center whitespace-nowrap">
                                                        @php
                                                            $effectiveLimit = $g->wip_limit ?? $settings['wip_limit_per_slot'];
                                                            $isFull = $g->active_projects_count >= $effectiveLimit;
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $isFull ? 'bg-rose-100 text-rose-700' : ($g->active_projects_count > 0 ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600') }}">
                                                            {{ $g->active_projects_count }} / {{ $effectiveLimit }} aktif
                                                        </span>
                                                    </td>
                                                    <td class="p-2.5 text-right">
                                                        <div class="flex items-center justify-end gap-1.5">
                                                            <input
                                                                type="number"
                                                                name="goal_wip_limits[{{ $g->id }}]"
                                                                min="1"
                                                                max="20"
                                                                value="{{ $g->wip_limit }}"
                                                                placeholder="{{ $settings['wip_limit_per_slot'] }} (Default)"
                                                                class="w-28 text-xs py-1 px-2 rounded-lg border-slate-300 text-right focus:border-sky-500"
                                                            >
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <x-form-field name="nara_budget_minutes_per_week" label="Anggaran Dev NARA (Mnt/Mgg)" :required="true" hint="Default: 360 (6 jam)">
                                <input type="number" name="nara_budget_minutes_per_week" min="60" max="2400"
                                       value="{{ old('nara_budget_minutes_per_week', $settings['nara_budget_minutes_per_week']) }}"
                                       class="nara-input">
                            </x-form-field>

                            <x-form-field name="admin_budget_minutes_per_day" label="Batas Administrasi NARA (Menit/Hari)" :required="true"
                                          hint="Target: ≤ 10 menit/hari untuk capture & triase (Bagian 14.2).">
                                <input type="number" name="admin_budget_minutes_per_day" min="1" max="60"
                                       value="{{ old('admin_budget_minutes_per_day', $settings['admin_budget_minutes_per_day']) }}"
                                       class="nara-input">
                            </x-form-field>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
                            <span>⏰ Jadwal Notifikasi Otomatis (Bagian 7.8)</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-form-field name="briefing_time" label="Waktu Briefing Pagi (HH:MM)" hint="Default: 07:00 sesuai zona waktu Anda.">
                                <input type="text" name="briefing_time" pattern="^([01]\d|2[0-3]):[0-5]\d$" placeholder="07:00"
                                       value="{{ old('briefing_time', $settings['briefing_time'] ?? '07:00') }}"
                                       class="nara-input font-mono">
                            </x-form-field>

                            <x-form-field name="default_reminder_minutes" label="Pengingat Sebelum Sesi Mulai (Menit)" hint="Default: 30 menit sebelum jadwal.">
                                <input type="number" name="default_reminder_minutes" min="5" max="120" step="5"
                                       value="{{ old('default_reminder_minutes', $settings['default_reminder_minutes'] ?? 30) }}"
                                       class="nara-input">
                            </x-form-field>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="nara-btn-primary text-xs py-2.5 px-5">
                            Simpan Semua Pengaturan Umum
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- TAB 2: INTEGRASI EKSTERNAL (TELEGRAM & GOOGLE CALENDAR) --}}
        {{-- ======================================================== --}}
        <div x-show="activeTab === 'integrations'" class="space-y-6" style="display: none;">
            {{-- Header Integrasi --}}
            <div class="nara-card p-6 border-l-4 border-l-sky-600">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>🔌 Manajemen Integrasi Eksternal (Fase 2)</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Aturan on/off dinamis dan konfigurasi kredensial langsung dari database tanpa wajib mengubah berkas <code>.env</code>.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $isTelegramEnabled ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-600' }}">
                            Telegram: {{ $isTelegramEnabled ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $isGoogleEnabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                            Google Cal: {{ $isGoogleEnabled ? 'Aktif' : 'Lokal (Off)' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- 1. KARTU INTEGRASI TELEGRAM --}}
            <div class="nara-card p-6 border-l-4 {{ $isTelegramEnabled ? 'border-l-sky-500' : 'border-l-slate-300' }}">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🤖</span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Telegram Bot Integration</h3>
                            <p class="text-xs text-slate-500">Capture cepat inbox, brief pagi, reminder jadwal, dan perintah /done dari HP.</p>
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

                {{-- Form Konfigurasi Telegram --}}
                <form method="POST" action="{{ route('panel.settings.integrations.telegram') }}" class="space-y-4 mb-6">
                    @csrf
                    <div class="p-3 bg-sky-50/60 rounded-xl border border-sky-200/80">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="telegram_active" name="is_active" value="1"
                                   @checked($isTelegramEnabled)
                                   class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span class="text-xs font-bold text-slate-800">
                                Aktifkan Integrasi Telegram (Rule Switch)
                            </span>
                        </label>
                        <p class="text-[11px] text-slate-500 mt-1 ml-6">
                            Jika dimatikan, semua fitur outbound (briefing/reminder) berhenti sementara dan webhook masuk ditolak dengan aman.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bot Token</label>
                            <input type="password" name="bot_token"
                                   value="{{ old('bot_token', $telegramConfig['bot_token']) }}"
                                   placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                                   class="w-full text-xs font-mono rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500">
                            <span class="text-[11px] text-slate-400">Didapatkan dari @BotFather di Telegram</span>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Chat ID (User Allowlist)</label>
                            <input type="text" name="chat_id"
                                   value="{{ old('chat_id', $telegramConfig['chat_id']) }}"
                                   placeholder="969666384"
                                   class="w-full text-xs font-mono rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500">
                            <span class="text-[11px] text-slate-400">ID numerik akun Telegram Anda (dari @userinfobot)</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-[11px] text-slate-400">
                            Tersimpan aman di database tabel <code>integrations</code>.
                        </span>
                        <button type="submit" class="nara-btn-primary text-xs py-1.5 px-4">
                            Simpan Telegram Config
                        </button>
                    </div>
                </form>

                {{-- Panduan Menghubungkan Telegram Bot --}}
                <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4 mb-4" x-data="{ openGuide: false }">
                    <button type="button" @click="openGuide = !openGuide" class="w-full flex items-center justify-between text-left text-xs font-bold text-sky-900">
                        <span class="flex items-center gap-2">
                            <span>📖</span>
                            <span>Panduan Lengkap Menghubungkan Bot Telegram (Langkah demi Langkah)</span>
                        </span>
                        <span x-text="openGuide ? 'Tutup ▲' : 'Buka Panduan ▼'" class="text-[11px] text-sky-700"></span>
                    </button>

                    <div x-show="openGuide" class="mt-3 pt-3 border-t border-sky-200/70 text-xs text-slate-700 space-y-3" style="display: none;">
                        <div class="flex items-start gap-2">
                            <span class="font-bold text-sky-700 shrink-0">1.</span>
                            <div>
                                <strong>Buat Bot Baru di Telegram:</strong> Buka aplikasi Telegram, cari akun <a href="https://t.me/BotFather" target="_blank" class="text-sky-700 underline font-semibold">@BotFather</a>, kirim pesan <code>/newbot</code>. Ikuti instruksi memberi nama bot dan salin <strong>HTTP API Token</strong> yang diberikan ke kolom <em>Bot Token</em> di atas.
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-sky-700 shrink-0">2.</span>
                            <div>
                                <strong>Dapatkan Chat ID Anda:</strong> Buka bot <a href="https://t.me/userinfobot" target="_blank" class="text-sky-700 underline font-semibold">@userinfobot</a> di Telegram. Bot akan membalas dengan ID numerik Anda (misal: <code>969666384</code>). Masukkan angka tersebut ke kolom <em>Chat ID</em> di atas.
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-sky-700 shrink-0">3.</span>
                            <div>
                                <strong>Simpan dan Aktifkan:</strong> Centang pilihan <em>"Aktifkan Integrasi Telegram"</em> lalu klik <strong>Simpan Telegram Config</strong>.
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-sky-700 shrink-0">4.</span>
                            <div>
                                <strong>Menerima Pesan Masuk (Webhook):</strong> Untuk pengujian di komputer lokal, jalankan tunnel HTTPS (seperti Ngrok):
                                <div class="bg-slate-900 text-slate-100 p-2.5 rounded font-mono text-[11px] my-1 select-all">
                                    ngrok http 8000
                                </div>
                                Daftarkan URL webhook ke Telegram menggunakan curl:
                                <div class="bg-slate-900 text-slate-100 p-2.5 rounded font-mono text-[11px] my-1 select-all">
                                    curl -F "url=https://&lt;DOMAIN-NGROK-ANDA&gt;/api/telegram/webhook" https://api.telegram.org/bot&lt;BOT_TOKEN&gt;/setWebhook
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-sky-700 shrink-0">5.</span>
                            <div>
                                <strong>Daftar Perintah Mobile:</strong>
                                Ketik <code>/today</code> untuk cek jadwal hari ini, <code>/tasks</code> untuk daftar task aktif, <code>/done_&lt;id&gt;</code> untuk menyelesaikan task, <code>/idea &lt;teks&gt;</code> untuk mencatat ide, atau ketik teks apa saja untuk otomatis masuk ke Inbox.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Panel Uji Coba Pengiriman Notifikasi --}}
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <h4 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                        <span>📲</span>
                        <span>Uji Coba Pengiriman Pesan Langsung ke Telegram</span>
                    </h4>
                    <p class="text-[11px] text-slate-500 mb-3">
                        Picu pengiriman instan tanpa menunggu jam scheduler untuk memverifikasi bot dapat mengirim pesan ke akun Anda.
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('panel.settings.test-notification') }}">
                            @csrf
                            <input type="hidden" name="type" value="briefing">
                            <button type="submit"
                                    style="background-color: #d97706 !important; color: #ffffff !important;"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold shadow-xs hover:brightness-110 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                    @disabled(! $isTelegramEnabled || empty($telegramConfig['chat_id']))>
                                <span>☀️</span>
                                <span>Kirim Briefing Pagi</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('panel.settings.test-notification') }}">
                            @csrf
                            <input type="hidden" name="type" value="reminder">
                            <button type="submit"
                                    style="background-color: #4f46e5 !important; color: #ffffff !important;"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold shadow-xs hover:brightness-110 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                    @disabled(! $isTelegramEnabled || empty($telegramConfig['chat_id']))>
                                <span>⏰</span>
                                <span>Kirim Reminder Jadwal</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- 2. KARTU INTEGRASI GOOGLE CALENDAR --}}
            <div class="nara-card p-6 border-l-4 {{ $isGoogleEnabled ? 'border-l-emerald-500' : 'border-l-slate-300' }}">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📅</span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Google Calendar Integration</h3>
                            <p class="text-xs text-slate-500">Sinkronisasi sesi jadwal NARA dua arah, deteksi konflik waktu, dan isolasi kalender terdedikasi.</p>
                        </div>
                    </div>
                    <div>
                        @if ($googleConfig['is_connected'])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                <span class="h-2 w-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                Terhubung & Aktif (Google Calendar)
                            </span>
                        @elseif ($isGoogleEnabled && ! $googleConfig['is_authorized'])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                Belum Diotorisasi (OAuth Diperlukan)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                                Mode Lokal (🏠 Off)
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Form Konfigurasi Google Calendar --}}
                <form method="POST" action="{{ route('panel.settings.integrations.google') }}" class="space-y-4 mb-5">
                    @csrf
                    <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-200/80">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="google_active" name="is_active" value="1"
                                   @checked($isGoogleEnabled)
                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">
                                Aktifkan Sinkronisasi Google Calendar (Rule Switch)
                            </span>
                        </label>
                        <p class="text-[11px] text-slate-500 mt-1 ml-6">
                            Jika dinonaktifkan, Kalender berjalan 100% dalam <strong>Mode Lokal (🏠)</strong> tanpa error dan tanpa dependensi eksternal.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Google Client ID</label>
                            <input type="text" name="client_id"
                                   value="{{ old('client_id', $googleConfig['client_id']) }}"
                                   placeholder="123456...apps.googleusercontent.com"
                                   class="w-full text-xs font-mono rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Google Client Secret</label>
                            <input type="password" name="client_secret"
                                   value="{{ old('client_secret', $googleConfig['client_secret']) }}"
                                   placeholder="GOCSPX-..."
                                   class="w-full text-xs font-mono rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Kalender Terdedikasi</label>
                            <input type="text" name="calendar_id"
                                   value="{{ old('calendar_id', $googleConfig['calendar_id']) }}"
                                   placeholder="NARA"
                                   class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('panel.calendar.index') }}" class="nara-btn-secondary text-xs py-1.5 px-3">
                                📅 Buka Kalender NARA →
                            </a>
                        </div>
                        <button type="submit" class="nara-btn-primary text-xs py-1.5 px-4">
                            Simpan Google Config
                        </button>
                    </div>
                </form>

                {{-- Status Koneksi & Tombol Otorisasi OAuth --}}
                @if ($googleConfig['is_connected'])
                    <div class="mb-5 p-4 bg-emerald-50 rounded-xl border border-emerald-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                        <div>
                            <p class="font-bold text-emerald-900 flex items-center gap-1.5">
                                <span>✅</span>
                                <span>Akun Google Terhubung & Siap Sinkronisasi</span>
                            </p>
                            <p class="text-emerald-700 text-[11px] mt-0.5">
                                Kalender Terdedikasi: <strong>{{ $googleConfig['calendar_id'] }}</strong>
                                @if ($googleConfig['google_calendar_id'])
                                    <span class="text-emerald-600 font-mono text-[10px]">({{ $googleConfig['google_calendar_id'] }})</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('panel.calendar.sync') }}">
                                @csrf
                                <button type="submit" class="nara-btn-secondary text-xs py-1.5 px-3 bg-white font-medium hover:bg-emerald-50">
                                    🔄 Sinkronkan Semua Jadwal
                                </button>
                            </form>
                            <form method="POST" action="{{ route('panel.settings.integrations.google.disconnect') }}">
                                @csrf
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 hover:underline px-2 py-1 font-medium" onclick="return confirm('Apakah Anda yakin ingin memutuskan akun Google Calendar?')">
                                    Putuskan Akun
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="mb-5 p-4 bg-amber-50/80 rounded-xl border border-amber-200 text-xs space-y-3">
                        <div class="flex items-start gap-2.5">
                            <span class="text-xl shrink-0">⚠️</span>
                            <div>
                                <h4 class="font-bold text-amber-900 text-sm">Otorisasi Akun Google Diperlukan</h4>
                                <p class="text-amber-800 text-xs mt-0.5">
                                    Kredensial Client ID & Secret telah disimpan, namun Anda <strong>belum memberikan izin OAuth</strong> ke akun Google Anda. Agar tugas dan jadwal yang Anda alokasikan di NARA otomatis muncul di <code>calendar.google.com</code>, lakukan otorisasi di bawah ini.
                                </p>
                            </div>
                        </div>

                        <div class="p-3 bg-white rounded-lg border border-amber-200/80 text-[11px] text-slate-700 space-y-1.5">
                            <span class="font-bold text-slate-900 block">
                                📌 Langkah Penting di Google Cloud Console:
                            </span>
                            <p>
                                Pastikan URL pengalihan (Authorized Redirect URI) di bawah telah didaftarkan pada kredensial <strong>"NARA Web client"</strong> di Google Cloud Console:
                            </p>
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    readonly
                                    value="{{ route('panel.settings.integrations.google.callback') }}"
                                    class="bg-slate-50 text-slate-800 px-3 py-1.5 rounded text-xs select-all font-mono border border-slate-300 flex-1"
                                >
                            </div>
                            <span class="text-[10px] text-slate-500 block">
                                (Menu: <em>Google Cloud Console</em> &gt; <em>Credentials</em> &gt; Klik <em>OAuth 2.0 Client ID</em> &gt; Tambah ke <em>Authorized redirect URIs</em> &gt; Klik <em>Save</em>).
                            </span>
                        </div>

                        <div class="pt-1 flex items-center gap-2">
                            <a href="{{ route('panel.settings.integrations.google.redirect') }}" class="nara-btn-primary text-xs py-2 px-4 shadow-sm flex items-center gap-2 font-bold bg-amber-600 hover:bg-amber-700 text-white">
                                <span>🔑</span>
                                <span>Hubungkan Akun Google (Otorisasi Sekarang) →</span>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Panduan Menghubungkan Google Calendar --}}
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4" x-data="{ openGuide: false }">
                    <button type="button" @click="openGuide = !openGuide" class="w-full flex items-center justify-between text-left text-xs font-bold text-emerald-900">
                        <span class="flex items-center gap-2">
                            <span>📖</span>
                            <span>Panduan Lengkap Menghubungkan Google Calendar (Langkah demi Langkah)</span>
                        </span>
                        <span x-text="openGuide ? 'Tutup ▲' : 'Buka Panduan ▼'" class="text-[11px] text-emerald-700"></span>
                    </button>

                    <div x-show="openGuide" class="mt-3 pt-3 border-t border-emerald-200/70 text-xs text-slate-700 space-y-3" style="display: none;">
                        <div class="flex items-start gap-2">
                            <span class="font-bold text-emerald-700 shrink-0">1.</span>
                            <div>
                                <strong>Prinsip Keamanan & Isolasi (NARA Authoritative):</strong> NARA hanya membuat event pada kalender terdedikasi (misal bernama <code>NARA</code>) sehingga kalender pribadi Anda tidak akan pernah diubah atau dihapus tanpa izin. Event kalender lain hanya dibaca sebagai <em>busy blocks</em> untuk mendeteksi bentrok jadwal.
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-emerald-700 shrink-0">2.</span>
                            <div>
                                <strong>Buka Google Cloud Console:</strong> Kunjungi <a href="https://console.cloud.google.com/" target="_blank" class="text-emerald-700 underline font-semibold">console.cloud.google.com</a>, buat project baru (misal: <em>NARA Personal Agent</em>).
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-emerald-700 shrink-0">3.</span>
                            <div>
                                <strong>Aktifkan Google Calendar API:</strong> Masuk ke menu <em>APIs & Services</em> &gt; <em>Library</em>. Cari <code>Google Calendar API</code> lalu klik tombol <strong>Enable</strong>.
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-emerald-700 shrink-0">4.</span>
                            <div>
                                <strong>Buat Kredensial OAuth 2.0 & Masukkan Redirect URI:</strong>
                                Buka menu <em>APIs & Services</em> &gt; <em>Credentials</em> &gt; <strong>Create Credentials</strong> &gt; <strong>OAuth Client ID</strong>.
                                Pilih tipe <em>Web Application</em>. Pada bagian <strong>Authorized redirect URIs</strong> (URI pengalihan yang diizinkan), masukkan:
                                <div class="my-1.5 p-2 bg-slate-100 rounded text-[11px] font-mono text-slate-800 break-all select-all border border-slate-200">
                                    {{ route('panel.settings.integrations.google.callback') }}
                                </div>
                                Simpan di Google Console, lalu salin <strong>Client ID</strong> dan <strong>Client Secret</strong> ke formulir di atas dan klik <strong>Simpan Google Config</strong>.
                            </div>
                        </div>

                        <div class="flex items-start gap-2 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                            <span class="font-bold text-amber-700 shrink-0">5.</span>
                            <div>
                                <strong class="text-amber-900">Tambahkan Email ke "Test Users" (Wajib untuk Akun Pribadi):</strong>
                                <p class="mt-0.5 text-amber-800">
                                    Karena aplikasi berada dalam status *Testing* (Pengujian), Google memblokir login (Error 403 access_denied) kecuali email Anda didaftarkan sebagai tester:
                                </p>
                                <ol class="list-decimal ml-4 mt-1 space-y-0.5 text-amber-900 text-[11px]">
                                    <li>Buka menu <strong>APIs & Services</strong> &gt; <strong>OAuth consent screen</strong> (Layar Persetujuan OAuth).</li>
                                    <li>Scroll ke bagian <strong>Test users</strong> (Pengguna uji), klik <strong>+ ADD USERS</strong>.</li>
                                    <li>Ketik email Anda (misal: <code>imamfadhkur1@gmail.com</code>) lalu klik <strong>Save</strong>.</li>
                                </ol>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-emerald-700 shrink-0">6.</span>
                            <div>
                                <strong>Otorisasi Akun Google (Langkah Terakhir):</strong>
                                Klik tombol <strong>Hubungkan Akun Google (Otorisasi Sekarang)</strong>. Jika muncul peringatan <em>"Google belum memverifikasi aplikasi ini"</em>, klik <strong>Lanjutan (Advanced)</strong> di kiri bawah &gt; klik <strong>Buka NARA Personal Agent (tidak aman)</strong> &gt; centang izin kalender lalu klik <strong>Lanjutkan</strong>. NARA akan otomatis membuat kalender <code>NARA</code> dan menyinkronkan seluruh jadwal Anda.
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="font-bold text-emerald-700 shrink-0">7.</span>
                            <div>
                                <strong>Mode Simulasi / Tanpa Kredensial:</strong> Jika Anda belum menghubungkan akun Google atau menonaktifkan sakelar integrasi, NARA secara cerdas beralih ke Mode Kalender Lokal (`🏠`), sehingga Anda tetap dapat menjadwalkan task tanpa hambatan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- TAB 3: KEAMANAN & AUTENTIKASI 2FA TOTP                   --}}
        {{-- ======================================================== --}}
        <div x-show="activeTab === 'security'" class="space-y-6" style="display: none;">
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
                                            <form method="POST" action="{{ route('panel.settings.two-factor.recovery') }}" class="space-y-3" x-data="{ showPass: false }">
                                                @csrf
                                                <div class="relative">
                                                    <input :type="showPass ? 'text' : 'password'" name="password" required placeholder="Kata sandi..." class="nara-input text-xs pr-9">
                                                    <button type="button" @click="showPass = !showPass" tabindex="-1" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer" :title="showPass ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                                        <svg x-show="!showPass" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        <svg x-show="showPass" x-cloak class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                                    </button>
                                                </div>
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
                                            <form method="POST" action="{{ route('panel.settings.two-factor.disable') }}" class="space-y-3" x-data="{ showPass: false }">
                                                @csrf
                                                @method('DELETE')
                                                <div class="relative">
                                                    <input :type="showPass ? 'text' : 'password'" name="password" required placeholder="Kata sandi..." class="nara-input text-xs pr-9">
                                                    <button type="button" @click="showPass = !showPass" tabindex="-1" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer" :title="showPass ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                                        <svg x-show="!showPass" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        <svg x-show="showPass" x-cloak class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                                    </button>
                                                </div>
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
        </div>
    </div>
</x-panel-layout>
