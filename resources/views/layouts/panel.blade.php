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
<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-full flex">

    {{-- Sidebar navigasi (Bagian 3.7 & 5.3) --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-40 w-72 flex flex-col bg-[#0b192c] text-slate-200 transition-transform duration-200 lg:static lg:translate-x-0"
    >
        {{-- Header brand: latar terang agar logo navy kontras dengan sidebar gelap --}}
        <div class="relative px-4 pt-4 pb-3 bg-gradient-to-br from-white via-sky-50 to-indigo-100 border-b-4 border-cyan-400/70 shadow-md">
            <button type="button" @click="sidebarOpen = false" class="lg:hidden absolute top-2 right-3 text-slate-500 hover:text-slate-900">✕</button>

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

        <div class="p-4 border-t border-slate-800 bg-[#081322] text-xs text-slate-400 flex items-center justify-between gap-2">
            <div class="min-w-0">
                <div class="truncate">🕒 Zona: {{ auth()->user()?->displayTimezone() }}</div>
                <div class="truncate text-[10px] text-slate-500 mt-0.5">© {{ date('Y') }} PT Imfar Manggala Nara</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="shrink-0 rounded-lg px-2.5 py-1.5 bg-slate-800 text-slate-200 hover:bg-rose-600 hover:text-white transition">Keluar</button>
            </form>
        </div>
    </aside>

    {{-- Area utama --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Topbar (Bagian 3.7): pencarian cepat, tombol + Task, indikator status --}}
        <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <button type="button" @click="sidebarOpen = true" class="lg:hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100">
                    ☰
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
    </div>
</div>
@stack('scripts')
</body>
</html>

