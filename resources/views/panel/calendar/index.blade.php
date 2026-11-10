<x-panel-layout title="Kalender Jadwal" heading="Kalender & Sesi Fokus Terjadwal">
    <div x-data="{ scheduleModal: {{ $errors->any() ? 'true' : 'false' }}, selectedTask: null, modalTaskId: '{{ old('task_id', '') }}', modalTaskTitle: '', modalDate: '{{ old('start_date', $currentDate->toDateString()) }}', modalTime: '{{ old('start_time', '09:00') }}', modalDuration: {{ (int) old('duration_minutes', 60) }} }">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-600">
                Alokasi waktu kerja deterministik terintegrasi dua arah dengan Google Calendar dan deteksi konflik.
            </p>
            <div class="flex items-center gap-3 mt-1.5 text-xs flex-wrap">
                @if ($isGoogleEnabled && $googleConfig['is_authorized'])
                    <span class="inline-flex items-center gap-1.5 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Google Calendar Aktif ({{ $googleConfig['calendar_id'] }})
                    </span>
                    <form action="{{ route('panel.calendar.sync') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-sky-600 hover:text-sky-800 underline font-medium cursor-pointer">
                            ⟳ Sinkronkan Sekarang
                        </button>
                    </form>
                @elseif ($isGoogleEnabled && ! $googleConfig['is_authorized'])
                    <span class="inline-flex items-center gap-1.5 text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200 font-medium">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Google Calendar Belum Diotorisasi
                    </span>
                    <a href="{{ route('panel.settings.index') }}#integrations" class="text-xs text-amber-800 font-bold hover:underline">
                        🔑 Hubungkan Akun Google di Pengaturan →
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5 text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-300 font-medium">
                        🏠 Mode Kalender Lokal (Integrasi Google dinonaktifkan di Pengaturan)
                    </span>
                    <a href="{{ route('panel.settings.index') }}#integrations" class="text-xs text-sky-600 hover:text-sky-800 underline">
                        Atur di Pengaturan
                    </a>
                @endif
            </div>
        </div>

        {{-- Navigasi Tanggal & View Mode --}}
        <div class="flex items-center gap-2 flex-wrap">
            @php
                $tz = auth()->user()->displayTimezone();
                $prevDate = match($viewMode) {
                    'month' => $currentDate->copy()->subMonth()->toDateString(),
                    'day' => $currentDate->copy()->subDay()->toDateString(),
                    default => $currentDate->copy()->subWeek()->toDateString(),
                };
                $nextDate = match($viewMode) {
                    'month' => $currentDate->copy()->addMonth()->toDateString(),
                    'day' => $currentDate->copy()->addDay()->toDateString(),
                    default => $currentDate->copy()->addWeek()->toDateString(),
                };
                $todayDate = \Illuminate\Support\Carbon::now($tz)->toDateString();
            @endphp

            <div class="flex items-center bg-white border border-slate-200 rounded-lg shadow-2xs p-0.5">
                <a href="{{ route('panel.calendar.index', ['view' => $viewMode, 'date' => $prevDate]) }}" class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded text-xs" title="Sebelumnya">
                    ◀
                </a>
                <a href="{{ route('panel.calendar.index', ['view' => $viewMode, 'date' => $todayDate]) }}" class="px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded">
                    Hari Ini
                </a>
                <a href="{{ route('panel.calendar.index', ['view' => $viewMode, 'date' => $nextDate]) }}" class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded text-xs" title="Berikutnya">
                    ▶
                </a>
            </div>

            <div class="flex items-center bg-white border border-slate-200 rounded-lg shadow-2xs p-0.5 text-xs font-medium">
                <a href="{{ route('panel.calendar.index', ['view' => 'day', 'date' => $currentDate->toDateString()]) }}" class="px-2.5 py-1 rounded {{ $viewMode === 'day' ? 'bg-sky-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Hari
                </a>
                <a href="{{ route('panel.calendar.index', ['view' => 'week', 'date' => $currentDate->toDateString()]) }}" class="px-2.5 py-1 rounded {{ $viewMode === 'week' ? 'bg-sky-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Minggu
                </a>
                <a href="{{ route('panel.calendar.index', ['view' => 'month', 'date' => $currentDate->toDateString()]) }}" class="px-2.5 py-1 rounded {{ $viewMode === 'month' ? 'bg-sky-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Bulan
                </a>
            </div>

            <button type="button" @click="scheduleModal = true; selectedTask = null" class="nara-btn-primary text-xs py-1.5 px-3">
                + Jadwalkan Sesi
            </button>
        </div>
    </div>

    {{-- Layout Utama: Grid Kalender + Sidebar Unscheduled Tasks --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
        {{-- Konten Kalender (3 Kolom) --}}
        <div class="lg:col-span-3 space-y-4">
            {{-- Header Rentang Tanggal --}}
            <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-2xs">
                <h3 class="text-sm font-bold text-slate-900">
                    @if ($viewMode === 'month')
                        {{ $currentDate->isoFormat('MMMM Y') }}
                    @elseif ($viewMode === 'day')
                        {{ $currentDate->isoFormat('dddd, D MMMM Y') }}
                    @else
                        Minggu {{ $rangeStart->isoFormat('D MMM') }} – {{ $rangeEnd->isoFormat('D MMM Y') }}
                    @endif
                </h3>
                <div class="flex items-center gap-3 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span> Sesi NARA</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Blok Sibuk</span>
                </div>
            </div>

            @if ($viewMode === 'month')
                {{-- Tampilan Bulan --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200 text-center py-2 text-xs font-bold text-slate-700">
                        <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div>
                    </div>
                    @php
                        $calDay = $rangeStart->copy();
                    @endphp
                    <div class="grid grid-cols-7 divide-x divide-y divide-slate-100">
                        @while ($calDay->lessThanOrEqualTo($rangeEnd))
                            @php
                                $dayStr = $calDay->toDateString();
                                $daySchedules = $schedules->filter(fn($s) => $s->start_at->setTimezone($tz)->toDateString() === $dayStr);
                                $isToday = $calDay->toDateString() === \Illuminate\Support\Carbon::now($tz)->toDateString();
                                $isCurrentMonth = $calDay->month === $currentDate->month;
                            @endphp
                            <div class="min-h-[90px] p-1.5 {{ $isCurrentMonth ? 'bg-white' : 'bg-slate-50/50 text-slate-400' }} hover:bg-slate-50/80 transition">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold {{ $isToday ? 'w-5 h-5 rounded-full bg-sky-600 text-white flex items-center justify-center font-bold' : 'text-slate-700' }}">
                                        {{ $calDay->day }}
                                    </span>
                                    @if ($daySchedules->count() > 0)
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $daySchedules->count() }} sesi</span>
                                    @endif
                                </div>
                                <div class="space-y-1">
                                    @foreach ($daySchedules->take(3) as $s)
                                        @php
                                            $syncStatus = $s->calendarEvent?->sync_status ?? 'synced';
                                            $isLocal = ($s->calendarEvent?->google_calendar_id ?? '') === 'local';
                                            $syncIcon = match(true) {
                                                $isLocal => '🏠',
                                                $syncStatus === 'synced' => '✓',
                                                $syncStatus === 'failed' => '⚠',
                                                default => '⟳',
                                            };
                                        @endphp
                                        <div class="text-[10px] p-1 rounded bg-sky-50 text-sky-900 border border-sky-200 truncate font-medium flex items-center justify-between" title="{{ $s->task->title }} ({{ $s->start_at->setTimezone($tz)->format('H:i') }})">
                                            <span class="truncate">{{ $s->start_at->setTimezone($tz)->format('H:i') }} {{ $s->task->title }}</span>
                                            <span class="text-[9px] shrink-0 ml-1 text-slate-500">{{ $syncIcon }}</span>
                                        </div>
                                    @endforeach
                                    @if ($daySchedules->count() > 3)
                                        <div class="text-[9px] text-slate-400 text-center font-medium">+{{ $daySchedules->count() - 3 }} lagi</div>
                                    @endif
                                </div>
                            </div>
                            @php $calDay->addDay(); @endphp
                        @endwhile
                    </div>
                </div>

            @elseif ($viewMode === 'day')
                {{-- Tampilan Hari --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4">
                    @php
                        $dayStr = $currentDate->toDateString();
                        $daySchedules = $schedules->filter(fn($s) => $s->start_at->setTimezone($tz)->toDateString() === $dayStr);
                        $dayBusy = $busyBlocks->filter(fn($b) => $b->start_at->setTimezone($tz)->toDateString() === $dayStr);
                    @endphp

                    @if ($daySchedules->isEmpty() && $dayBusy->isEmpty())
                        <div class="text-center py-12 text-slate-400">
                            <span class="text-3xl block mb-2">🏖️</span>
                            <p class="text-xs">Tidak ada jadwal atau komitmen untuk hari ini.</p>
                            <button
                                type="button"
                                @click="modalDate = '{{ $dayStr }}'; scheduleModal = true;"
                                class="mt-3 nara-btn-primary text-xs py-1.5 px-3"
                            >
                                + Tambah Jadwal Hari Ini
                            </button>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($daySchedules as $s)
                                @php
                                    $syncStatus = $s->calendarEvent?->sync_status ?? 'synced';
                                    $isLocal = ($s->calendarEvent?->google_calendar_id ?? '') === 'local';
                                    $syncBadge = match(true) {
                                        $isLocal => ['label' => '🏠 Lokal', 'cls' => 'bg-slate-100 text-slate-600 border-slate-200'],
                                        $syncStatus === 'synced' => ['label' => '✓ Google Synced', 'cls' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                        $syncStatus === 'failed' => ['label' => '⚠ Sync Error', 'cls' => 'bg-rose-50 text-rose-700 border-rose-200'],
                                        default => ['label' => '⟳ Pending', 'cls' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                    };
                                @endphp
                                <div class="flex items-center justify-between p-3.5 rounded-lg border border-sky-200 bg-sky-50/40 hover:bg-sky-50 transition">
                                    <div class="flex items-start gap-3">
                                        <div class="text-center shrink-0">
                                            <span class="font-mono font-bold text-xs text-sky-800">
                                                {{ $s->start_at->setTimezone($tz)->format('H:i') }}
                                            </span>
                                            <span class="block text-[10px] text-slate-400">
                                                {{ $s->end_at->setTimezone($tz)->format('H:i') }}
                                            </span>
                                        </div>
                                        <div>
                                            <a href="{{ route('panel.tasks.show', $s->task) }}" class="text-xs font-bold text-slate-900 hover:text-sky-700">
                                                {{ $s->task->title }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500">
                                                <span>📁 {{ $s->task->project->name }}</span>
                                                <span>•</span>
                                                <span>{{ $s->durationMinutes() }} menit</span>
                                                <span class="text-[10px] px-1.5 py-0.2 rounded border font-medium {{ $syncBadge['cls'] }}">
                                                    {{ $syncBadge['label'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('panel.focus') }}?task_id={{ $s->task->id }}" class="nara-btn-secondary text-[11px] py-1 px-2.5 text-sky-700">
                                            ⏱️ Fokus
                                        </a>
                                        <form action="{{ route('panel.calendar.destroy', $s) }}" method="POST" onsubmit="return confirm('Hapus sesi jadwal ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 text-[11px] p-1 font-medium">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($dayBusy as $b)
                                <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-slate-50/60 text-slate-600">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono text-xs text-slate-500">
                                            {{ $b->start_at->setTimezone($tz)->format('H:i') }} – {{ $b->end_at->setTimezone($tz)->format('H:i') }}
                                        </span>
                                        <span class="text-xs font-medium">{{ $b->title }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded">Blok Kalender Eksternal</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            @else
                {{-- Tampilan Minggu (Default 7 Kolom) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    @php
                        $dayPtr = $rangeStart->copy();
                    @endphp
                    @for ($i = 0; $i < 7; $i++)
                        @php
                            $dayStr = $dayPtr->toDateString();
                            $daySchedules = $schedules->filter(fn($s) => $s->start_at->setTimezone($tz)->toDateString() === $dayStr);
                            $dayBusy = $busyBlocks->filter(fn($b) => $b->start_at->setTimezone($tz)->toDateString() === $dayStr);
                            $isToday = $dayStr === \Illuminate\Support\Carbon::now($tz)->toDateString();
                        @endphp
                        <div class="bg-white rounded-xl border {{ $isToday ? 'border-sky-400 ring-2 ring-sky-100' : 'border-slate-200' }} p-3 min-h-[300px] flex flex-col shadow-2xs">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                <div>
                                    <div class="text-[11px] font-bold text-slate-500 uppercase">{{ $dayPtr->isoFormat('ddd') }}</div>
                                    <div class="text-sm font-extrabold {{ $isToday ? 'text-sky-600' : 'text-slate-800' }}">
                                        {{ $dayPtr->day }} {{ $dayPtr->isoFormat('MMM') }}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="modalDate = '{{ $dayStr }}'; scheduleModal = true;"
                                    class="text-xs text-slate-400 hover:text-sky-600 p-1"
                                    title="Tambah Sesi"
                                >
                                    +
                                </button>
                            </div>

                            <div class="space-y-2 flex-1 overflow-y-auto">
                                @forelse ($daySchedules as $s)
                                    @php
                                        $syncStatus = $s->calendarEvent?->sync_status ?? 'synced';
                                        $isLocal = ($s->calendarEvent?->google_calendar_id ?? '') === 'local';
                                        $syncIcon = match(true) {
                                            $isLocal => '🏠',
                                            $syncStatus === 'synced' => '✓',
                                            $syncStatus === 'failed' => '⚠',
                                            default => '⟳',
                                        };
                                    @endphp
                                    <div class="bg-sky-50/70 border border-sky-200 rounded-lg p-2 text-xs group hover:bg-sky-100/60 transition">
                                        <div class="flex items-center justify-between text-[10px] text-slate-500 mb-1">
                                            <span class="font-mono font-bold text-sky-800">
                                                {{ $s->start_at->setTimezone($tz)->format('H:i') }}
                                            </span>
                                            <span class="text-[9px] font-semibold text-slate-500" title="Sync Status: {{ $syncStatus }}">{{ $syncIcon }}</span>
                                        </div>
                                        <a href="{{ route('panel.tasks.show', $s->task) }}" class="font-semibold text-slate-900 hover:text-sky-700 block line-clamp-2 leading-tight">
                                            {{ $s->task->title }}
                                        </a>
                                        <div class="mt-1 text-[10px] text-slate-400 truncate">
                                            {{ $s->task->project->name }}
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-sky-100 flex items-center justify-between text-[10px] opacity-0 group-hover:opacity-100 transition">
                                            <a href="{{ route('panel.focus') }}?task_id={{ $s->task->id }}" class="text-sky-700 font-medium hover:underline">
                                                ⏱️ Fokus
                                            </a>
                                            <form action="{{ route('panel.calendar.destroy', $s) }}" method="POST" onsubmit="return confirm('Hapus sesi?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:underline">
                                                    ×
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    @if ($dayBusy->isEmpty())
                                        <div class="h-24 flex items-center justify-center text-center text-[11px] text-slate-300">
                                            Kosong
                                        </div>
                                    @endif
                                @endforelse

                                @foreach ($dayBusy as $b)
                                    <div class="bg-slate-100/80 border border-slate-200 rounded p-1.5 text-[11px] text-slate-600 truncate">
                                        <span class="font-mono text-[9px] block text-slate-400">
                                            {{ $b->start_at->setTimezone($tz)->format('H:i') }}–{{ $b->end_at->setTimezone($tz)->format('H:i') }}
                                        </span>
                                        <span class="truncate block font-medium">{{ $b->title }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @php $dayPtr->addDay(); @endphp
                    @endfor
                </div>
            @endif
        </div>

        {{-- Sidebar: Task Belum Dijadwalkan (Unscheduled Tasks) --}}
        <div class="nara-card p-4 border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                    📌 Menunggu Jadwal ({{ $unscheduledTasks->count() }})
                </h4>
                <a href="{{ route('panel.tasks.create') }}" class="text-sky-600 hover:text-sky-800 text-xs font-medium">
                    + Baru
                </a>
            </div>
            <p class="text-[11px] text-slate-500 mb-3">
                Task aktif yang belum dialokasikan ke slot waktu kalender.
            </p>

            <div class="space-y-2 max-h-[550px] overflow-y-auto">
                @forelse ($unscheduledTasks as $ut)
                    <div class="p-2.5 rounded-lg border border-slate-200 hover:border-sky-300 bg-white transition group text-xs">
                        <div class="flex items-start justify-between gap-1 mb-1">
                            <span class="font-semibold text-slate-900 group-hover:text-sky-700 line-clamp-2">
                                {{ $ut->title }}
                            </span>
                            <x-status-badge :status="$ut->priority" />
                        </div>
                        <div class="text-[11px] text-slate-400 mb-2 truncate">
                            📁 {{ $ut->project->name }}
                        </div>
                        <button
                            type="button"
                            @click="modalTaskId = '{{ $ut->id }}'; modalTaskTitle = '{{ addslashes($ut->title) }}'; scheduleModal = true;"
                            class="w-full text-center py-1 rounded bg-sky-50 text-sky-700 hover:bg-sky-100 font-semibold text-[11px] transition"
                        >
                            + Jadwalkan ke Kalender
                        </button>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400 italic">
                        Semua task aktif telah terjadwal!
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Modal Jadwalkan Sesi --}}
        <div
            x-show="scheduleModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            style="display: none;"
            @keydown.escape.window="scheduleModal = false"
        >
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200" @click.outside="scheduleModal = false">
                <h3 class="text-base font-bold text-slate-900 mb-1">Jadwalkan Sesi Kerja</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Alokasikan blok waktu fokus untuk task pada kalender.
                </p>

                <form action="{{ route('panel.calendar.store') }}" method="POST" class="space-y-4">
                    @csrf

                    @if ($errors->any())
                        <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 text-xs text-rose-800 space-y-1">
                            @foreach ($errors->all() as $err)
                                <div class="font-medium">⚠️ {{ $err }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Task</label>
                        <select name="task_id" x-model="modalTaskId" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500" required>
                            <option value="">-- Pilih Task --</option>
                            @foreach ($unscheduledTasks as $ut)
                                <option value="{{ $ut->id }}">#{{ $ut->id }} {{ $ut->title }} ({{ $ut->project->name }})</option>
                            @endforeach
                            @foreach ($schedules->pluck('task')->unique('id') as $st)
                                @if (! $unscheduledTasks->contains('id', $st->id))
                                    <option value="{{ $st->id }}">#{{ $st->id }} {{ $st->title }} ({{ $st->project->name }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal</label>
                            <input type="date" name="start_date" x-model="modalDate" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Mulai</label>
                            <input type="time" name="start_time" x-model="modalTime" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Durasi</label>
                        <select name="duration_minutes" x-model="modalDuration" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500" required>
                            <option value="30">30 Menit</option>
                            <option value="45">45 Menit</option>
                            <option value="60">60 Menit (1 Jam)</option>
                            <option value="90">90 Menit (1.5 Jam)</option>
                            <option value="120">120 Menit (2 Jam)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="note" placeholder="Misal: Blok hening tanpa notifikasi" class="w-full text-xs rounded-lg border-slate-300 py-2 px-3 focus:border-sky-500">
                    </div>

                    <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-200">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="force" value="1" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs text-amber-900">Abaikan peringatan jika ada jadwal bentrok (Force)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="scheduleModal = false" class="nara-btn-secondary text-xs py-2 px-3">
                            Batal
                        </button>
                        <button type="submit" class="nara-btn-primary text-xs py-2 px-4">
                            Simpan ke Kalender
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</x-panel-layout>

