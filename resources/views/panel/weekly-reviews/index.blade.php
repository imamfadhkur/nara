<x-panel-layout title="Weekly Review" heading="Weekly Review (Ritual Mingguan)">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-600">
                Ritual evaluasi mingguan singkat (≤ 15 menit) untuk refleksi output, memeriksa kapasitas kerja, dan mengunci 3 prioritas utama minggu depan.
            </p>
            <p class="text-xs text-slate-500 mt-1">
                Minggu ini dimulai: <strong class="text-slate-800">{{ \Illuminate\Support\Carbon::parse($currentWeekStart)->isoFormat('dddd, D MMMM Y') }}</strong>
            </p>
        </div>
        <div>
            <a href="{{ route('panel.weekly-reviews.create') }}" class="nara-btn-primary text-xs py-2 px-4 shadow-xs">
                {{ $currentReview ? '✏️ Edit Review Minggu Ini' : '✨ Mulai Review Minggu Ini' }}
            </a>
        </div>
    </div>

    {{-- Banner Status Review Minggu Ini --}}
    @if ($currentReview)
        <div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50/60 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🎉</span>
                    <div>
                        <h4 class="text-sm font-bold text-emerald-950">Review Minggu Ini Telah Selesai!</h4>
                        <p class="text-xs text-emerald-800 mt-0.5">
                            Kapasitas: {{ $currentReview->planned_load_percent }}% | Task Selesai: {{ $currentReview->completed_task_count }} | Output: {{ $currentReview->output_count }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('panel.weekly-reviews.show', $currentReview) }}" class="text-xs text-emerald-700 font-semibold hover:underline">
                    Lihat Rincian →
                </a>
            </div>
            @if ($currentReview->top_priorities)
                <div class="mt-3 pt-3 border-t border-emerald-200/80">
                    <span class="text-xs font-semibold text-emerald-900 block mb-1">Top 3 Prioritas Minggu Ini:</span>
                    <ul class="list-disc list-inside text-xs text-emerald-800 space-y-0.5">
                        @foreach ($currentReview->top_priorities as $p)
                            <li>{{ $p }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @else
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50/70 p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <h4 class="text-sm font-bold text-amber-950">Review Minggu Ini Belum Dijalankan</h4>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Luangkan waktu 10-15 menit untuk mengunci arah dan memastikan beban minggu depan realistis.
                        </p>
                    </div>
                </div>
                <a href="{{ route('panel.weekly-reviews.create') }}" class="nara-btn-primary text-xs py-1.5 px-3">
                    Mulai Sekarang
                </a>
            </div>
        </div>
    @endif

    {{-- Ringkasan Metrik Minggu Berjalan --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card
            title="Task Selesai Minggu Ini"
            :value="(string) $metrics['completed_tasks']"
            subtitle="Task diselesaikan 7 hari terakhir"
            tone="success"
        />
        <x-kpi-card
            title="Karya / Output Baru"
            :value="(string) $metrics['outputs']"
            subtitle="Dokumen, deliverable, artifact"
            tone="primary"
        />
        <x-kpi-card
            title="Kapasitas Minggu Ini"
            :value="$metrics['capacity_percent'] . '%'"
            :subtitle="strtoupper($metrics['capacity_status']) . ' (' . round($metrics['total_minutes'] / 60, 1) . ' jam)'"
            :tone="$metrics['capacity_percent'] > 100 ? 'danger' : ($metrics['capacity_percent'] > 85 ? 'warning' : 'success')"
        />
        <x-kpi-card
            title="Riwayat Review"
            :value="(string) $reviews->count()"
            subtitle="Total sesi tersimpan"
            tone="neutral"
        />
    </div>

    {{-- Riwayat Weekly Review --}}
    <div class="nara-card p-5">
        <h3 class="text-sm font-bold text-slate-900 mb-4">Arsip Weekly Review Sebelumnya</h3>

        @if ($reviews->isEmpty())
            <div class="text-center py-8 text-slate-400 text-xs">
                Belum ada arsip review mingguan. Klik tombol di atas untuk memulai ritual pertama Anda!
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($reviews as $rev)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50/50 px-2 rounded">
                        <div>
                            <a href="{{ route('panel.weekly-reviews.show', $rev) }}" class="text-sm font-semibold text-slate-900 hover:text-sky-700">
                                Minggu {{ $rev->week_start->isoFormat('D MMMM Y') }}
                            </a>
                            <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                <span>Task: <strong>{{ $rev->completed_task_count }}</strong></span>
                                <span>•</span>
                                <span>Output: <strong>{{ $rev->output_count }}</strong></span>
                                <span>•</span>
                                <span>Beban: <strong>{{ $rev->planned_load_percent }}%</strong></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('panel.weekly-reviews.show', $rev) }}" class="text-xs text-sky-600 hover:text-sky-800 font-medium">
                                Rincian →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-panel-layout>
