<x-panel-layout title="Rincian Weekly Review" heading="Rincian Weekly Review">
    <div class="max-w-3xl">
        <div class="mb-4">
            <a href="{{ route('panel.weekly-reviews.index') }}" class="text-xs text-sky-600 hover:text-sky-800 font-medium">
                ← Kembali ke Arsip Review
            </a>
        </div>

        <div class="nara-card p-6 mb-6">
            <div class="flex items-start justify-between border-b border-slate-200 pb-4 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        Review Minggu {{ $review->week_start->isoFormat('D MMMM Y') }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Dibuat pada {{ $review->created_at->isoFormat('dddd, D MMMM Y [pukul] HH:mm') }}
                    </p>
                </div>
                <a href="{{ route('panel.weekly-reviews.create') }}" class="nara-btn-secondary text-xs py-1.5 px-3">
                    ✏️ Edit
                </a>
            </div>

            {{-- Metrik --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
                    <span class="text-[11px] text-slate-500 block">Task Selesai</span>
                    <strong class="text-slate-900 text-lg">{{ $review->completed_task_count }}</strong>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
                    <span class="text-[11px] text-slate-500 block">Output</span>
                    <strong class="text-slate-900 text-lg">{{ $review->output_count }}</strong>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
                    <span class="text-[11px] text-slate-500 block">Beban Kapasitas</span>
                    <strong class="text-slate-900 text-lg">{{ $review->planned_load_percent }}%</strong>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
                    <span class="text-[11px] text-slate-500 block">Waktu Kerja</span>
                    <strong class="text-slate-900 text-lg">{{ round($review->completed_minutes / 60, 1) }} jam</strong>
                </div>
            </div>

            {{-- Top Priorities --}}
            <div class="mb-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    🎯 Top 3 Prioritas Terkunci:
                </h4>
                @if (!empty($review->top_priorities))
                    <div class="space-y-2">
                        @foreach ($review->top_priorities as $idx => $priority)
                            <div class="flex items-center gap-2 p-2.5 rounded-lg bg-indigo-50/60 border border-indigo-100 text-xs text-indigo-950 font-medium">
                                <span class="w-5 h-5 rounded-full bg-indigo-200 text-indigo-800 font-bold flex items-center justify-center text-[10px]">{{ $idx + 1 }}</span>
                                <span>{{ $priority }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">Tidak ada prioritas dicatat.</p>
                @endif
            </div>

            {{-- Reflection --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    📝 Refleksi & Catatan:
                </h4>
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200 text-xs text-slate-800 leading-relaxed whitespace-pre-line">
                    {{ $review->reflection ?: 'Tidak ada catatan refleksi.' }}
                </div>
            </div>
        </div>
    </div>
</x-panel-layout>

