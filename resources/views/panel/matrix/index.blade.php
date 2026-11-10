<x-panel-layout title="Matrix Eisenhower" heading="Matrix Eisenhower (Prioritas Cerdas)">
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-600">
                    Sistem klasifikasi 4 kuadran otomatis deterministik berdasarkan urgensi (tenggat ≤ 3 hari) dan kepentingan (prioritas & dampak project).
                </p>
                <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                    <span>Total task terbuka: <strong class="text-slate-800">{{ $totalOpenTasks }}</strong></span>
                    <span>•</span>
                    <span class="text-rose-600 font-medium">Q1: {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::DO]) }}</span>
                    <span>•</span>
                    <span class="text-sky-600 font-medium">Q2: {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::SCHEDULE]) }}</span>
                    <span>•</span>
                    <span class="text-amber-600 font-medium">Q3: {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::DELEGATE]) }}</span>
                    <span>•</span>
                    <span class="text-slate-600 font-medium">Q4: {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::SOMEDAY]) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('panel.tasks.create') }}" class="nara-btn-primary text-xs py-1.5 px-3">
                    + Task Baru
                </a>
            </div>
        </div>
    </div>

    {{-- Grid 2x2 Kuadran Eisenhower --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Q1: KERJAKAN (Urgen & Penting) --}}
        <div class="rounded-xl border border-rose-300 bg-rose-50/40 p-4 min-h-[380px] flex flex-col shadow-xs">
            <div class="flex items-center justify-between border-b border-rose-200/80 pb-2.5 mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500 ring-4 ring-rose-100"></span>
                    <h3 class="font-bold text-slate-900 text-sm">1. KERJAKAN (Do First)</h3>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-rose-200 text-rose-800">
                    {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::DO]) }}
                </span>
            </div>
            <p class="text-[11px] text-rose-700/80 mb-3">
                ⚡ Mendesak & Berdampak Tinggi. Selesaikan segera hari ini sebelum yang lain.
            </p>

            <div class="space-y-2.5 flex-1 overflow-y-auto">
                @forelse ($quadrants[\App\Domain\Tasks\MatrixClassifier::DO] as $task)
                    <x-matrix-task-card :task="$task" accent="rose" />
                @empty
                    <div class="h-40 flex flex-col items-center justify-center text-center p-4 text-slate-400">
                        <span class="text-2xl mb-1">✨</span>
                        <p class="text-xs">Tidak ada krisis mendesak. Luar biasa!</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Q2: JADWALKAN (Penting, Belum Urgen) --}}
        <div class="rounded-xl border border-sky-300 bg-sky-50/40 p-4 min-h-[380px] flex flex-col shadow-xs">
            <div class="flex items-center justify-between border-b border-sky-200/80 pb-2.5 mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-sky-500 ring-4 ring-sky-100"></span>
                    <h3 class="font-bold text-slate-900 text-sm">2. JADWALKAN (Schedule)</h3>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-sky-200 text-sky-800">
                    {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::SCHEDULE]) }}
                </span>
            </div>
            <p class="text-[11px] text-sky-700/80 mb-3">
                🎯 Zona Pertumbuhan & Fokus Dalam. Beri alokasi waktu di Kalender sebelum mendadak urgen.
            </p>

            <div class="space-y-2.5 flex-1 overflow-y-auto">
                @forelse ($quadrants[\App\Domain\Tasks\MatrixClassifier::SCHEDULE] as $task)
                    <x-matrix-task-card :task="$task" accent="sky" />
                @empty
                    <div class="h-40 flex flex-col items-center justify-center text-center p-4 text-slate-400">
                        <span class="text-2xl mb-1">📅</span>
                        <p class="text-xs">Belum ada task di kuadran penjadwalan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Q3: CEPATKAN / DELEGASIKAN (Urgen, Kurang Penting) --}}
        <div class="rounded-xl border border-amber-300 bg-amber-50/40 p-4 min-h-[380px] flex flex-col shadow-xs">
            <div class="flex items-center justify-between border-b border-amber-200/80 pb-2.5 mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500 ring-4 ring-amber-100"></span>
                    <h3 class="font-bold text-slate-900 text-sm">3. CEPATKAN / DELEGASIKAN</h3>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-amber-200 text-amber-800">
                    {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::DELEGATE]) }}
                </span>
            </div>
            <p class="text-[11px] text-amber-700/80 mb-3">
                ⏱️ Tenggat dekat namun dampak strategis rendah. Selesaikan cepat atau delegasikan jika ada tim.
            </p>

            <div class="space-y-2.5 flex-1 overflow-y-auto">
                @forelse ($quadrants[\App\Domain\Tasks\MatrixClassifier::DELEGATE] as $task)
                    <x-matrix-task-card :task="$task" accent="amber" />
                @empty
                    <div class="h-40 flex flex-col items-center justify-center text-center p-4 text-slate-400">
                        <span class="text-2xl mb-1">🌱</span>
                        <p class="text-xs">Bersih dari distraksi urgen.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Q4: SUATU SAAT / BACKLOG (Tidak Urgen, Tidak Penting) --}}
        <div class="rounded-xl border border-slate-300 bg-slate-100/60 p-4 min-h-[380px] flex flex-col shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5 mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-slate-400 ring-4 ring-slate-100"></span>
                    <h3 class="font-bold text-slate-900 text-sm">4. SUATU SAAT (Someday / Drop)</h3>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-slate-200 text-slate-700">
                    {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::SOMEDAY]) }}
                </span>
            </div>
            <p class="text-[11px] text-slate-600 mb-3">
                💡 Ide atau task bernilai rendah tanpa tenggat. Simpan untuk review berikutnya atau buang jika usang.
            </p>

            <div class="space-y-2.5 flex-1 overflow-y-auto">
                @forelse ($quadrants[\App\Domain\Tasks\MatrixClassifier::SOMEDAY] as $task)
                    <x-matrix-task-card :task="$task" accent="slate" />
                @empty
                    <div class="h-40 flex flex-col items-center justify-center text-center p-4 text-slate-400">
                        <span class="text-2xl mb-1">📁</span>
                        <p class="text-xs">Tidak ada backlog mengendap.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Lajur Khusus: Menunggu (Waiting) --}}
    @if(count($quadrants[\App\Domain\Tasks\MatrixClassifier::WAITING]) > 0)
        <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-4">
            <div class="flex items-center justify-between mb-3 border-b border-purple-200/80 pb-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                    <h3 class="font-bold text-slate-900 text-sm">Lajur Khusus: Menunggu Pihak Lain (Waiting)</h3>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-purple-200 text-purple-900">
                    {{ count($quadrants[\App\Domain\Tasks\MatrixClassifier::WAITING]) }}
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($quadrants[\App\Domain\Tasks\MatrixClassifier::WAITING] as $task)
                    <x-matrix-task-card :task="$task" accent="purple" />
                @endforeach
            </div>
        </div>
    @endif
</x-panel-layout>

