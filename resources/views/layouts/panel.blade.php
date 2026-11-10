<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' · NARA' : 'NARA — Personal AI Agent' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <script>
        window.NaraFocusHelper = {
            STORAGE_KEY: 'nara_focus_session',
            baseTitle: document.title,

            getSession() {
                try {
                    const raw = localStorage.getItem(this.STORAGE_KEY);
                    return raw ? JSON.parse(raw) : null;
                } catch (e) {
                    return null;
                }
            },

            saveSession(data) {
                try {
                    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(data));
                    window.dispatchEvent(new CustomEvent('nara-focus-updated', { detail: data }));
                } catch (e) {}
            },

            clearSession() {
                try {
                    localStorage.removeItem(this.STORAGE_KEY);
                    document.title = this.baseTitle;
                    window.dispatchEvent(new CustomEvent('nara-focus-updated', { detail: null }));
                } catch (e) {}
            },

            updateTitle(timeStr, status) {
                if (status === 'running') {
                    document.title = `(${timeStr}) 🎯 Fokus · NARA`;
                } else if (status === 'paused') {
                    document.title = `(⏸ ${timeStr}) Fokus · NARA`;
                } else if (status === 'completed') {
                    document.title = `🎉 Fokus Selesai! · NARA`;
                } else {
                    document.title = this.baseTitle;
                }
            },

            playChime() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6
                    notes.forEach((freq, idx) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        const startTime = ctx.currentTime + idx * 0.16;
                        osc.frequency.setValueAtTime(freq, startTime);
                        gain.gain.setValueAtTime(0.001, startTime);
                        gain.gain.exponentialRampToValueAtTime(0.25, startTime + 0.04);
                        gain.gain.exponentialRampToValueAtTime(0.0001, startTime + 0.85);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(startTime);
                        osc.stop(startTime + 0.9);
                    });
                } catch (e) {}
            },

            sendDesktopNotification(taskTitle, minutes, focusUrl) {
                this.playChime();
                if (!('Notification' in window)) return;
                if (Notification.permission === 'granted') {
                    try {
                        const notif = new Notification('🎯 Waktu Fokus Selesai! — NARA', {
                            body: `Sesi fokus ${minutes} menit untuk "${taskTitle}" telah selesai. Klik untuk mencatat hasil kerja Anda.`,
                            icon: '{{ asset("images/nara-2.png") }}',
                            tag: 'nara-focus-complete',
                            requireInteraction: true
                        });
                        notif.onclick = function () {
                            window.focus();
                            if (focusUrl) {
                                window.location.href = focusUrl;
                            }
                            notif.close();
                        };
                    } catch (e) {}
                }
            }
        };
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none !important;}</style>
</head>
<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased"
      x-data="{
          sidebarMobileOpen: false,
          sidebarCollapsed: localStorage.getItem('nara_sidebar_collapsed') === 'true',
          toggleSidebar() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              localStorage.setItem('nara_sidebar_collapsed', this.sidebarCollapsed);
          }
      }">
<div class="min-h-full flex">

    {{-- Mobile Backdrop Overlay --}}
    <div
        x-show="sidebarMobileOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarMobileOpen = false"
        x-cloak
        class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-2xs lg:hidden"
    ></div>

    {{-- Sidebar navigasi (Bagian 3.7 & 5.3) --}}
    <aside
        :class="{
            'translate-x-0': sidebarMobileOpen,
            '-translate-x-full': !sidebarMobileOpen,
            'lg:translate-x-0 lg:ml-0': !sidebarCollapsed,
            'lg:-ml-72 lg:-translate-x-full': sidebarCollapsed
        }"
        class="fixed inset-y-0 left-0 z-40 w-72 flex flex-col bg-[#0b192c] text-slate-200 transition-all duration-300 ease-in-out lg:static shrink-0 shadow-2xl lg:shadow-none"
    >
        {{-- Header brand: latar terang agar logo navy kontras dengan sidebar gelap --}}
        <div class="relative px-4 pt-3 pb-3 bg-gradient-to-br from-white via-sky-50 to-indigo-100 border-b-4 border-cyan-400/70 shadow-md">

            <a href="{{ route('panel.today') }}" class="block group" title="NARA AI — Your Personal AI Agent">
                {{-- Crop area transparan PNG (konten logo ±414×114 px dari 492×277 px) --}}
                <div class="relative w-[208px] h-[58px] overflow-hidden mx-auto">
                    <img src="{{ asset('images/nara-2.png') }}" alt="NARA AI"
                         class="absolute max-w-none w-[247px] h-auto left-[-22px] top-[-45px] transition-transform duration-200 group-hover:scale-[1.03]">
                </div>
            </a>

            <p class="mt-3 text-center text-[11px] leading-snug text-slate-600">
                Sistem Lengkap, Deterministik & Integrasi Eksternal.
            </p>
        </div>

        {{-- Sapaan & tanggal hari ini --}}
        @php
            $sidebarNow = now(auth()->user()?->displayTimezone() ?? config('nara.default_timezone'))->locale('id');
            $sidebarHour = (int) $sidebarNow->format('H');
            $sidebarGreeting = match (true) {
                $sidebarHour < 11 => 'Selamat pagi',
                $sidebarHour < 15 => 'Selamat siang',
                $sidebarHour < 18 => 'Selamat sore',
                default => 'Selamat malam',
            };
        @endphp
        <div class="mx-3 mt-3 rounded-xl bg-gradient-to-r from-sky-900/60 to-indigo-900/50 ring-1 ring-sky-700/40 p-3">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="text-xs text-slate-300">{{ $sidebarGreeting }},</div>
                    <div class="text-sm font-semibold text-white truncate max-w-[150px]">{{ auth()->user()?->name ?? 'Tamu' }} 👋</div>
                </div>
                <a href="{{ route('profile.edit') }}"
                   class="shrink-0 p-1.5 rounded-lg bg-sky-800/60 hover:bg-sky-700 text-cyan-200 hover:text-white transition"
                   title="Pengaturan Profil Akun & Kata Sandi">
                    <span class="text-xs">👤</span>
                </a>
            </div>
            <div class="mt-1.5 pt-1.5 border-t border-sky-800/50 flex items-center justify-between text-[11px] text-cyan-300/90">
                <span>{{ $sidebarNow->translatedFormat('l, d F Y') }}</span>
                <span class="text-[10px] text-cyan-200/60 truncate max-w-[90px]">{{ auth()->user()?->email }}</span>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-0.5 text-sm overflow-y-auto">
            @php
                $inboxPendingCount = auth()->user() ? \App\Models\InboxItem::where('user_id', auth()->id())->where('status', 'pending')->count() : 0;
                $needs2fa = auth()->user() && ! auth()->user()->hasTwoFactorEnabled();

                $navGroups = [
                    'Navigasi Harian' => [
                        ['route' => 'panel.today', 'href' => route('panel.today'), 'icon' => '🎯', 'label' => 'Today (Fokus)', 'desc' => 'Jadwal, prioritas & kapasitas', 'badge' => null],
                        ['route' => 'panel.focus', 'href' => route('panel.focus'), 'icon' => '⏱️', 'label' => 'Mode Fokus (Timer)', 'desc' => 'Deep work hening per task', 'badge' => null],
                        ['route' => 'panel.inbox.*', 'href' => route('panel.inbox.index'), 'icon' => '📥', 'label' => 'Inbox (Capture)', 'desc' => 'Tangkap ide & triase cepat', 'badge' => $inboxPendingCount > 0 ? $inboxPendingCount : null],
                        ['route' => 'panel.calendar.*', 'href' => route('panel.calendar.index'), 'icon' => '📅', 'label' => 'Kalender & Sesi', 'desc' => 'Sinkron Google & deteksi konflik', 'badge' => null],
                        ['route' => 'panel.kanban.*', 'href' => route('panel.kanban.index'), 'icon' => '🗂️', 'label' => 'Kanban View', 'desc' => 'Alur kerja visual per status', 'badge' => null],
                        ['route' => 'panel.tasks.*', 'href' => route('panel.tasks.index'), 'icon' => '✅', 'label' => 'Tasks (Table)', 'desc' => 'Daftar lengkap & filter task', 'badge' => null],
                    ],
                    'Perencanaan & Review (Fase 2)' => [
                        ['route' => 'panel.matrix.*', 'href' => route('panel.matrix.index'), 'icon' => '🧭', 'label' => 'Matrix Eisenhower', 'desc' => '4 kuadran prioritas cerdas', 'badge' => null],
                        ['route' => 'panel.timeline.*', 'href' => route('panel.timeline.index'), 'icon' => '🗺️', 'label' => 'Roadmap & Timeline', 'desc' => 'Horizon progres & milestone', 'badge' => null],
                        ['route' => 'panel.weekly-reviews.*', 'href' => route('panel.weekly-reviews.index'), 'icon' => '📊', 'label' => 'Weekly Review', 'desc' => 'Ritual evaluasi mingguan ≤15m', 'badge' => null],
                        ['route' => 'panel.outputs.*', 'href' => route('panel.outputs.index'), 'icon' => '🏆', 'label' => 'Outputs (Karya)', 'desc' => 'Bukti hasil kerja nyata', 'badge' => null],
                    ],
                    'Komitmen & Goal' => [
                        ['route' => 'panel.life-domains.*', 'href' => route('panel.life-domains.index'), 'icon' => '🌱', 'label' => 'Life Domains', 'desc' => 'Area hidup yang dijaga', 'badge' => null],
                        ['route' => 'panel.goals.*', 'href' => route('panel.goals.index'), 'icon' => '🎖️', 'label' => 'Goals', 'desc' => 'Target jangka menengah', 'badge' => null],
                        ['route' => 'panel.projects.*', 'href' => route('panel.projects.index'), 'icon' => '🚀', 'label' => 'Projects & WIP', 'desc' => 'Maks. 3 project aktif per slot', 'badge' => null],
                    ],
                    'Sistem' => [
                        ['route' => 'profile.*', 'href' => route('profile.edit'), 'icon' => '👤', 'label' => 'Profil & Akun', 'desc' => 'Nama, email & kata sandi', 'badge' => null],
                        ['route' => 'panel.settings.*', 'href' => route('panel.settings.index'), 'icon' => '⚙️', 'label' => 'Settings & Integrasi', 'desc' => 'Kapasitas, Telegram & Google Cal', 'badge' => $needs2fa ? '!' : null],
                        ['route' => 'panel.docs.*', 'href' => route('panel.docs.index'), 'icon' => '📚', 'label' => 'Panduan & Dokumentasi', 'desc' => 'Manual API, fitur & troubleshooting', 'badge' => null],
                    ],
                ];
            @endphp

            @foreach ($navGroups as $groupLabel => $items)
                <div class="{{ $loop->first ? '' : 'pt-4' }} px-2 pb-1.5 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    <span>{{ $groupLabel }}</span>
                    <span class="flex-1 h-px bg-slate-700/60"></span>
                </div>

                @foreach ($items as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ $item['href'] }}"
                       class="group flex items-center gap-3 rounded-lg px-2.5 py-2 transition {{ $active ? 'bg-sky-600/20 ring-1 ring-cyan-400/30' : 'hover:bg-slate-800/70' }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-base {{ $active ? 'bg-cyan-400/20' : 'bg-slate-800/80 group-hover:bg-slate-700/80' }}">{{ $item['icon'] }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-medium leading-tight {{ $active ? 'text-cyan-300' : 'text-slate-200 group-hover:text-white' }}">{{ $item['label'] }}</span>
                            <span class="block truncate text-[11px] leading-tight mt-0.5 {{ $active ? 'text-cyan-200/70' : 'text-slate-500 group-hover:text-slate-400' }}">{{ $item['desc'] }}</span>
                        </span>
                        @if ($item['badge'])
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold {{ $item['badge'] === '!' ? 'bg-amber-500/20 text-amber-300' : 'bg-cyan-500/20 text-cyan-300' }}">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="p-3.5 border-t border-slate-800 bg-[#081322] text-xs text-slate-400 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <div class="truncate text-[11px] text-slate-300">🕒 Zona: {{ auth()->user()?->displayTimezone() }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="shrink-0 rounded-lg px-2.5 py-1 bg-slate-800 text-slate-300 hover:bg-rose-600 hover:text-white transition text-[11px] font-medium">Keluar</button>
                </form>
            </div>
            <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between gap-2 text-[10px]">
                <span class="truncate text-slate-500">© {{ date('Y') }} PT Imfar Manggala Nara</span>
                <a href="{{ route('panel.docs.show', 'catatan-rilis-nara-v2-fase-2') }}"
                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-mono font-bold bg-cyan-950/90 text-cyan-300 border border-cyan-800/80 hover:bg-cyan-900 hover:text-cyan-100 hover:border-cyan-400 transition group shrink-0"
                   title="Lihat Apa yang Baru di NARA v2.0 (Fase 2)">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>v2.0 · Fase 2</span>
                    <span class="text-[9px] opacity-70 group-hover:translate-x-0.5 transition">→</span>
                </a>
            </div>
        </div>
    </aside>

    {{-- Area utama --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Topbar (Bagian 3.7): pencarian cepat, tombol + Task, indikator status --}}
        <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between gap-4 sticky top-0 z-20">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                {{-- Tombol Toggle Hide / Show Sidebar (Mobile & Desktop) --}}
                <button
                    type="button"
                    @click="window.innerWidth < 1024 ? sidebarMobileOpen = !sidebarMobileOpen : toggleSidebar()"
                    class="rounded-lg py-1.5 px-2.5 text-slate-700 hover:text-slate-950 hover:bg-slate-100 transition flex items-center gap-1.5 cursor-pointer border border-slate-200 shrink-0 shadow-2xs"
                    :title="sidebarCollapsed ? 'Tampilkan Menu Sidebar' : 'Sembunyikan Menu Sidebar (Mode Fokus)'"
                >
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    <span class="text-xs font-semibold text-slate-700 hidden sm:inline" x-text="sidebarCollapsed ? '' : ''">
                        Sembunyikan Menu
                    </span>
                </button>

                <h1 class="text-base sm:text-lg font-semibold text-slate-900 truncate">
                    {{ $heading ?? 'Panel NARA' }}
                </h1>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <form method="GET" action="{{ route('panel.tasks.index') }}" class="hidden sm:block">
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="Cari task…"
                           class="w-48 lg:w-64 rounded-lg border-slate-300 text-xs py-1.5 px-3 focus:border-sky-600 focus:ring-sky-600">
                </form>

                <a href="{{ route('panel.tasks.create') }}" class="nara-btn-primary text-xs sm:text-sm">
                    + Task
                </a>
                <a href="{{ route('panel.projects.create') }}" class="nara-btn-secondary text-xs sm:text-sm hidden sm:inline-flex">
                    + Project
                </a>

                {{-- Shortcut Tombol Profil Akun --}}
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2 py-1 px-2.5 rounded-lg border border-slate-200 hover:bg-slate-100 transition text-xs font-medium text-slate-700 shadow-2xs"
                   title="Pengaturan Profil Akun & Kata Sandi">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-sky-600 to-indigo-600 text-white flex items-center justify-center font-bold text-[11px] shrink-0">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </div>
                    <span class="hidden md:inline truncate max-w-[110px]">{{ auth()->user()?->name }}</span>
                </a>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
            <x-toast />

            @if ($errors->has('general'))
                <div class="mb-4 rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    {{ $errors->first('general') }}
                </div>
            @endif

            {{ $slot }}
        </main>

        {{-- Footer Bawah Panel (Copyright & Numbering Versi/Fase Tertaut Dokumentasi) --}}
        <footer class="mt-auto py-3.5 px-4 sm:px-6 lg:px-8 border-t border-slate-200/80 bg-white/70 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
                © {{ date('Y') }} PT Imfar Manggala Nara. All rights reserved.
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400">Versi & Rilis:</span>
                <a href="{{ route('panel.docs.show', 'catatan-rilis-nara-v2-fase-2') }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 hover:text-sky-900 hover:border-sky-300 transition group shadow-2xs"
                   title="Klik untuk membaca detail apa yang baru pada versi dan fase ini">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="font-bold">v2.0 · Fase 2</span>
                    <span class="text-slate-400 text-[11px] font-sans group-hover:text-slate-600">(Apa yang baru?)</span>
                    <span class="text-sky-600 group-hover:translate-x-0.5 transition">→</span>
                </a>
            </div>
        </footer>
    </div>
</div>

{{-- Global Floating Focus Timer (Tampil di pojok kanan bawah di semua halaman HANYA saat ada sesi fokus aktif) --}}
<script>
    window.naraFloatingFocusWidget = function () {
        return {
            session: null,
            secondsRemaining: 0,
            minimized: false,
            intervalId: null,

            init() {
                this.loadSession();
                this.intervalId = setInterval(() => this.tick(), 1000);
                window.addEventListener('storage', () => this.loadSession());
                window.addEventListener('nara-focus-updated', () => this.loadSession());
            },

            loadSession() {
                this.session = window.NaraFocusHelper.getSession();
                if (!this.session) {
                    this.secondsRemaining = 0;
                    window.NaraFocusHelper.updateTitle('', 'idle');
                    return;
                }
                this.syncTime();
            },

            syncTime() {
                if (!this.session) return;
                if (this.session.status === 'running' && this.session.targetEndAt) {
                    const diff = Math.max(0, Math.ceil((this.session.targetEndAt - Date.now()) / 1000));
                    this.secondsRemaining = diff;
                    if (diff <= 0) {
                        this.handleComplete();
                    } else {
                        window.NaraFocusHelper.updateTitle(this.formatTime(), 'running');
                    }
                } else if (this.session.status === 'paused') {
                    this.secondsRemaining = this.session.remainingSeconds || 0;
                    window.NaraFocusHelper.updateTitle(this.formatTime(), 'paused');
                } else if (this.session.status === 'completed') {
                    this.secondsRemaining = 0;
                    window.NaraFocusHelper.updateTitle('00:00', 'completed');
                }
            },

            tick() {
                if (this.session && this.session.status === 'running') {
                    this.syncTime();
                }
            },

            handleComplete() {
                if (!this.session) return;
                this.session.status = 'completed';
                this.session.remainingSeconds = 0;
                this.session.targetEndAt = null;
                this.minimized = false;
                if (!this.session.notified) {
                    this.session.notified = true;
                    const focusLogUrl = @js(route('panel.focus')) + '?task_id=' + this.session.taskId + '&open_log=1';
                    window.NaraFocusHelper.sendDesktopNotification(this.session.taskTitle, this.session.presetMinutes, focusLogUrl);
                }
                window.NaraFocusHelper.saveSession(this.session);
                window.NaraFocusHelper.updateTitle('00:00', 'completed');
            },

            formatTime() {
                const m = Math.floor(this.secondsRemaining / 60);
                const s = this.secondsRemaining % 60;
                return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            },

            progressPct() {
                if (!this.session || !this.session.presetMinutes) return 0;
                const total = this.session.presetMinutes * 60;
                return Math.min(100, Math.max(0, Math.round(((total - this.secondsRemaining) / total) * 100)));
            },

            togglePause() {
                if (!this.session) return;
                if (this.session.status === 'running') {
                    const rem = Math.max(0, Math.ceil((this.session.targetEndAt - Date.now()) / 1000));
                    this.session.status = 'paused';
                    this.session.remainingSeconds = rem;
                    this.session.targetEndAt = null;
                    this.secondsRemaining = rem;
                    window.NaraFocusHelper.saveSession(this.session);
                    window.NaraFocusHelper.updateTitle(this.formatTime(), 'paused');
                } else if (this.session.status === 'paused') {
                    const rem = this.session.remainingSeconds || (this.session.presetMinutes * 60);
                    this.session.status = 'running';
                    this.session.targetEndAt = Date.now() + (rem * 1000);
                    window.NaraFocusHelper.saveSession(this.session);
                    window.NaraFocusHelper.updateTitle(this.formatTime(), 'running');
                }
            },

            cancelSession() {
                if (confirm('Batalkan dan reset timer sesi fokus ini?')) {
                    window.NaraFocusHelper.clearSession();
                    this.session = null;
                }
            }
        };
    };
</script>

<div
    x-data="naraFloatingFocusWidget()"
    x-show="session !== null"
    x-cloak
    style="position: fixed; bottom: 24px; right: 24px; z-index: 9999;"
    class="fixed bottom-6 right-6 z-50"
>
    {{-- Mode Ringkas (Minimized Pill di Kanan Bawah) --}}
    <div x-show="minimized"
         @click="minimized = false"
         class="cursor-pointer flex items-center gap-3.5 px-5 py-3.5 rounded-full bg-slate-900 text-white shadow-2xl border-2 border-sky-500/60 hover:border-sky-400 transition">
        <span class="w-3 h-3 rounded-full shrink-0"
              :class="session?.status === 'running' ? 'bg-sky-400 animate-ping' : (session?.status === 'completed' ? 'bg-emerald-400 animate-bounce' : 'bg-amber-400')"></span>
        <span class="font-mono text-lg font-extrabold text-sky-300" x-text="formatTime()">00:00</span>
        <span class="text-sm font-semibold text-slate-200 truncate max-w-[180px]" x-text="session?.taskTitle"></span>
        <span class="text-xs text-slate-400">▲</span>
    </div>

    {{-- Mode Box Penuh Lebih Besar (Floating Focus Card di Kanan Bawah) --}}
    <div x-show="!minimized"
         style="width: min(440px, calc(100vw - 32px));"
         class="rounded-2xl bg-slate-900 text-white shadow-2xl border-2 border-slate-700 p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full shrink-0"
                      :class="session?.status === 'running' ? 'bg-sky-400 animate-pulse' : (session?.status === 'completed' ? 'bg-emerald-400 animate-bounce' : 'bg-amber-400')"></span>
                <span class="text-xs font-bold uppercase tracking-wider"
                      :class="session?.status === 'running' ? 'text-sky-300' : (session?.status === 'completed' ? 'text-emerald-300' : 'text-amber-300')"
                      x-text="session?.status === 'running' ? '⚡ Mode Fokus Sedang Aktif' : (session?.status === 'completed' ? '🎉 Sesi Fokus Selesai!' : '⏸️ Mode Fokus Dijeda')">
                </span>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" @click="minimized = true" class="text-slate-400 hover:text-white px-2.5 py-1 text-xs rounded-lg hover:bg-slate-800 cursor-pointer" title="Kecilkan Widget">—</button>
                <button type="button" @click="cancelSession()" class="text-slate-400 hover:text-rose-400 px-2.5 py-1 text-xs rounded-lg hover:bg-slate-800 cursor-pointer" title="Batalkan Timer">✕</button>
            </div>
        </div>

        <div class="flex items-center justify-between gap-4 py-1">
            <div class="min-w-0 flex-1 space-y-1">
                <div class="text-base sm:text-lg font-bold text-white leading-snug break-words" x-text="session?.taskTitle"></div>
                <div class="text-xs text-slate-300 truncate">📁 Proyek: <strong class="text-white" x-text="session?.projectName"></strong></div>
            </div>
            <div class="font-mono text-4xl sm:text-5xl font-extrabold tracking-tight tabular-nums shrink-0"
                 :class="session?.status === 'running' ? 'text-sky-300' : (session?.status === 'completed' ? 'text-emerald-400' : 'text-amber-300')"
                 x-text="formatTime()">
                25:00
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="space-y-1.5">
            <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-sky-400 to-indigo-500 transition-all duration-300"
                     :style="'width: ' + progressPct() + '%'"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400">
                <span>Target: <strong class="text-slate-200" x-text="(session?.presetMinutes || 25) + ' menit'"></strong></span>
                <span x-text="progressPct() + '% selesai'"></span>
            </div>
        </div>

        {{-- Tombol Aksi Cepat --}}
        <div class="flex items-center justify-between gap-3 pt-2 border-t border-slate-800">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-show="session?.status !== 'completed'"
                    @click="togglePause()"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer"
                    :class="session?.status === 'running' ? 'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/40' : 'bg-sky-600 text-white hover:bg-sky-500'"
                    x-text="session?.status === 'running' ? '⏸ Jeda' : '▶ Lanjut'"
                ></button>

                <a :href="'{{ route('panel.focus') }}?task_id=' + (session?.taskId || '') + '&open_log=1'"
                   class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md transition">
                    ✓ Selesai & Catat
                </a>
            </div>

            <a :href="'{{ route('panel.focus') }}?task_id=' + (session?.taskId || '')"
               class="text-xs text-slate-300 hover:text-sky-300 font-semibold underline shrink-0">
                Buka Timer ↗
            </a>
        </div>
    </div>
</div>

@stack('scripts')
</body>
</html>

