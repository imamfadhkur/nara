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
        class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-[#0b192c] text-slate-200 transition-transform duration-200 lg:static lg:translate-x-0"
    >
        <div class="flex items-center justify-between px-5 h-16 border-b border-slate-800">
            <a href="{{ route('panel.projects.index') }}" class="flex items-center gap-2.5">
                {{-- Logo Human–AI Connection: node + garis koneksi + orbit (Bagian 5.1) --}}
                <svg class="h-7 w-7 text-cyan-400" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                    <circle cx="16" cy="16" r="12" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.6"/>
                    <line x1="10" y1="20" x2="22" y2="11" stroke="#a78bfa" stroke-width="2"/>
                    <circle cx="10" cy="20" r="3.5" fill="#38bdf8"/>
                    <circle cx="22" cy="11" r="3.5" fill="#a78bfa"/>
                </svg>
                <div>
                    <span class="text-base font-bold tracking-wide text-white">NARA</span>
                    <span class="block text-[10px] text-cyan-300/80 leading-none">Personal AI Agent · v0.1</span>
                </div>
            </a>
            <button type="button" @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">✕</button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
            <div class="px-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Hirarki Inti</div>

            @php
                $navItems = [
                    ['route' => 'panel.life-domains.*', 'href' => route('panel.life-domains.index'), 'label' => 'Life Domains'],
                    ['route' => 'panel.goals.*', 'href' => route('panel.goals.index'), 'label' => 'Goals'],
                    ['route' => 'panel.projects.*', 'href' => route('panel.projects.index'), 'label' => 'Projects & WIP'],
                    ['route' => 'panel.tasks.*', 'href' => route('panel.tasks.index'), 'label' => 'Tasks (Table)'],
                ];
            @endphp

            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ $item['href'] }}"
                   class="flex items-center justify-between rounded-lg px-3 py-2 font-medium transition {{ $active ? 'bg-sky-600/20 text-cyan-300 ring-1 ring-cyan-400/30' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            <div class="pt-4 px-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Sistem</div>
            <a href="{{ route('panel.settings.index') }}"
               class="flex items-center justify-between rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('panel.settings.*') ? 'bg-sky-600/20 text-cyan-300 ring-1 ring-cyan-400/30' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                <span>Settings & 2FA</span>
                @if (auth()->user() && ! auth()->user()->hasTwoFactorEnabled())
                    <span class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] text-amber-300">!</span>
                @endif
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 text-xs text-slate-400">
            <div class="font-medium text-slate-200 truncate">{{ auth()->user()?->name }}</div>
            <div class="truncate">{{ auth()->user()?->displayTimezone() }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-slate-400 hover:text-white underline">Keluar</button>
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
</body>
</html>

