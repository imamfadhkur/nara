<x-panel-layout title="Ritual Weekly Review" heading="Ritual Weekly Review (≤ 15 Menit)">
    <div class="max-w-4xl">
        <p class="text-sm text-slate-600 mb-6">
            Luangkan 10-15 menit untuk menutup minggu ini secara teratur, mengaudit kapasitas, dan mengunci arah minggu berikutnya.
        </p>

        <form action="{{ route('panel.weekly-reviews.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
            <input type="hidden" name="completed_task_count" value="{{ $completedTasks->count() }}">
            <input type="hidden" name="completed_minutes" value="{{ $capacityInfo['total_minutes'] }}">
            <input type="hidden" name="output_count" value="{{ $outputCount }}">
            <input type="hidden" name="planned_load_percent" value="{{ $capacityInfo['percent'] }}">

            {{-- Langkah 1: Audit Capaian Minggu Ini --}}
            <div class="nara-card p-5 border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <span>1. Audit Capaian Minggu Ini</span>
                    </h3>
                    <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">
                        {{ $completedTasks->count() }} Task Selesai
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 text-xs">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-slate-500 block">Task Diselesaikan:</span>
                        <strong class="text-slate-900 text-base">{{ $completedTasks->count() }}</strong>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-slate-500 block">Output Nyata:</span>
                        <strong class="text-slate-900 text-base">{{ $outputCount }} file/dokumen</strong>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-slate-500 block">Kapasitas Kerja:</span>
                        <strong class="text-slate-900 text-base">{{ $capacityInfo['percent'] }}% ({{ $capacityInfo['status'] }})</strong>
                    </div>
                </div>

                @if ($completedTasks->isNotEmpty())
                    <div class="text-xs text-slate-600 bg-emerald-50/50 p-3 rounded-lg border border-emerald-200/60 max-h-40 overflow-y-auto">
                        <div class="font-semibold text-emerald-900 mb-1.5">Task yang berhasil Anda selesaikan:</div>
                        <ul class="space-y-1">
                            @foreach ($completedTasks as $ct)
                                <li class="flex items-center gap-1.5">
                                    <span class="text-emerald-600">✓</span>
                                    <span>{{ $ct->title }}</span>
                                    <span class="text-[11px] text-slate-400">({{ $ct->project->name }})</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Langkah 2: Refleksi Singkat --}}
            <div class="nara-card p-5 border-l-4 border-l-sky-500">
                <h3 class="font-bold text-slate-900 text-sm mb-1">
                    2. Refleksi & Pembelajaran
                </h3>
                <p class="text-xs text-slate-500 mb-3">
                    Apa yang berjalan baik? Apa hambatan terbesar minggu ini? Apa yang perlu disesuaikan minggu depan?
                </p>

                <textarea
                    name="reflection"
                    rows="4"
                    class="w-full text-xs rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500 p-3"
                    placeholder="Contoh: Berhasil menyelesaikan draf proposal sebelum Rabu. Kendala: ada interupsi mendadak pada Kamis sore. Penyesuaian: alokasikan blok fokus hening 90 menit di pagi hari."
                >{{ old('reflection', $existing?->reflection) }}</textarea>
            </div>

            {{-- Langkah 3: Kunci Top 3 Prioritas Minggu Depan --}}
            <div class="nara-card p-5 border-l-4 border-l-indigo-500">
                <h3 class="font-bold text-slate-900 text-sm mb-1">
                    3. Kunci Top 3 Prioritas Minggu Depan
                </h3>
                <p class="text-xs text-slate-500 mb-3">
                    Tentukan 3 target hasil paling berdampak tinggi (Must-Wins) untuk minggu depan.
                </p>

                @php
                    $p1 = old('top_priority_1', $existing?->top_priorities[0] ?? '');
                    $p2 = old('top_priority_2', $existing?->top_priorities[1] ?? '');
                    $p3 = old('top_priority_3', $existing?->top_priorities[2] ?? '');
                @endphp

                <div class="space-y-2.5">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">1</span>
                        <input
                            type="text"
                            name="top_priority_1"
                            value="{{ $p1 }}"
                            placeholder="Prioritas Utama #1 (Paling krusial)"
                            class="flex-1 text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                            required
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">2</span>
                        <input
                            type="text"
                            name="top_priority_2"
                            value="{{ $p2 }}"
                            placeholder="Prioritas #2"
                            class="flex-1 text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">3</span>
                        <input
                            type="text"
                            name="top_priority_3"
                            value="{{ $p3 }}"
                            placeholder="Prioritas #3"
                            class="flex-1 text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                        >
                    </div>
                </div>
            </div>

            {{-- Tombol Simpan --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('panel.weekly-reviews.index') }}" class="nara-btn-secondary text-xs py-2 px-4">
                    Batal
                </a>
                <button type="submit" class="nara-btn-primary text-xs py-2 px-5">
                    ✓ Simpan Weekly Review
                </button>
            </div>
        </form>
    </div>
</x-panel-layout>
