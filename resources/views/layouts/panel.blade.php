<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' · NARA' : 'NARA — Personal AI Agent' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

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
            {{-- Tombol Sembunyikan Sidebar --}}
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Navigasi NARA</span>
                <button
                    type="button"
                    @click="window.innerWidth < 1024 ? sidebarMobileOpen = false : toggleSidebar()"
                    class="flex items-center gap-1 text-[11px] font-semibold text-slate-600 hover:text-slate-950 px-2 py-0.5 rounded-md hover:bg-slate-200/70 transition cursor-pointer"
                    title="Sembunyikan Sidebar agar fokus ke konten"
                >
                    <span class="text-xs">⇤</span>
                    <span class="hidden sm:inline">Sembunyikan</span>
                </button>
            </div>

            <a href="{{ route('panel.today') }}" class="block group" title="NARA AI — Your Personal AI Agent">
                {{-- Crop area transparan PNG (konten logo ±414×114 px dari 492×277 px) --}}
                <div class="relative w-[208px] h-[58px] overflow-hidden mx-auto">
                    <img src="{{ asset('images/nara-2.png') }}" alt="NARA AI"
                         class="absolute max-w-none w-[247px] h-auto left-[-22px] top-[-45px] transition-transform duration-200 group-hover:scale-[1.03]">
                </div>
            </a>

            <div class="mt-2 flex items-center justify-center gap-1.5 text-[10px] font-semibold">
                <span class="px-2 py-0.5 rounded-full bg-[#0b192c] text-cyan-300 tracking-wide">v1.0</span>
                <span class="px-2 py-0.5 rounded-full bg-white text-indigo-700 ring-1 ring-indigo-200">Fase 2 · NARA 1.0</span>
            </div>
            <p class="mt-1.5 text-center text-[11px] leading-snug text-slate-600">
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
        <div class="mx-3 mt-3 rounded-xl bg-gradient-to-r from-sky-900/60 to-indigo-900/50 ring-1 ring-sky-700/40 px-3 py-2.5">
            <div class="text-xs text-slate-300">{{ $sidebarGreeting }},</div>
            <div class="text-sm font-semibold text-white truncate">{{ auth()->user()?->name ?? 'Tamu' }} 👋</div>
            <div class="mt-0.5 text-[11px] text-cyan-300/90">{{ $sidebarNow->translatedFormat('l, d F Y') }}</div>
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
                        ['route' => 'panel.projects.*', 'href' => route('panel.projects.index'), 'icon' => '🚀', 'label' => 'Projects & WIP', 'desc' => 'Maks. 3 project aktif per slot', 'badge' => null],
                        ['route' => 'panel.goals.*', 'href' => route('panel.goals.index'), 'icon' => '🎖️', 'label' => 'Goals', 'desc' => 'Target jangka menengah', 'badge' => null],
                        ['route' => 'panel.life-domains.*', 'href' => route('panel.life-domains.index'), 'icon' => '🌱', 'label' => 'Life Domains', 'desc' => 'Area hidup yang dijaga', 'badge' => null],
                    ],
                    'Sistem' => [
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
                    <span class="text-xs font-semibold text-slate-700 hidden sm:inline" x-text="sidebarCollapsed ? 'Tampilkan Menu' : 'Sembunyikan Menu'">
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
@stack('scripts')
</body>
</html>

