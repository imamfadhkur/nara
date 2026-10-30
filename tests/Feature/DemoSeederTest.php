<?php

namespace Tests\Feature;

use App\Domain\Tasks\MatrixClassifier;
use App\Models\Goal;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\WipSlot;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_runs_cleanly_and_yields_exact_architecture_numbers(): void
    {
        $this->seed(DemoSeeder::class);

        $user = User::where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();

        // 1. Verifikasi User & 2FA
        $this->assertSame('Asia/Jakarta', $user->timezone);
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertSame('100000001', $user->telegram_chat_id);

        // 2. Verifikasi WIP Slots (Bagian 9.4: academic=2, business=3, personal=2)
        $this->assertCount(2, $user->projects()->where('status', 'active')->where('wip_slot', WipSlot::Academic)->get());
        $this->assertCount(3, $user->projects()->where('status', 'active')->where('wip_slot', WipSlot::Business)->get());
        $this->assertCount(2, $user->projects()->where('status', 'active')->where('wip_slot', WipSlot::Personal)->get());

        // 3. Verifikasi Progres Project (Bagian 9.11)
        // P1: MSKI Revisi Jurnal = 12%
        $p1 = $user->projects()->where('name', 'MSKI Revisi Jurnal')->firstOrFail();
        $this->assertSame(12, $p1->progress_percent);

        // P2: Riset Joint Paper Robotika = 0%
        $p2 = $user->projects()->where('name', 'Riset Joint Paper Robotika')->firstOrFail();
        $this->assertSame(0, $p2->progress_percent);

        // P3: Pendirian PT Imfar Manggala Nara = 33%
        $p3 = $user->projects()->where('name', 'Pendirian PT Imfar Manggala Nara')->firstOrFail();
        $this->assertSame(33, $p3->progress_percent);

        // P4: Freelance Klien Siakad = 0%
        $p4 = $user->projects()->where('name', 'Freelance Klien Siakad')->firstOrFail();
        $this->assertSame(0, $p4->progress_percent);

        // P5: Landing Page Manggala Academy = 42%
        $p5 = $user->projects()->where('name', 'Landing Page Manggala Academy')->firstOrFail();
        $this->assertSame(42, $p5->progress_percent);

        // P6: NARA 1.0 = 0%
        $p6 = $user->projects()->where('name', 'NARA 1.0')->firstOrFail();
        $this->assertSame(0, $p6->progress_percent);

        // P7: Belajar Kelistrikan Dasar = 0%
        $p7 = $user->projects()->where('name', 'Belajar Kelistrikan Dasar')->firstOrFail();
        $this->assertSame(0, $p7->progress_percent);

        // 4. Verifikasi Progres Goal (Bagian 9.11)
        // G1: Publikasi ilmiah = 6% (rata-rata P1=12 & P2=0)
        $g1 = Goal::where('title', 'Publikasi ilmiah tahun ini')->firstOrFail();
        $this->assertSame(6, $g1->progress_percent);

        // G2: PT berdiri dan memiliki produk pertama = 25% (rata-rata P3=33, P4=0, P5=42)
        $g2 = Goal::where('title', 'PT berdiri dan memiliki produk pertama')->firstOrFail();
        $this->assertSame(25, $g2->progress_percent);

        // G3: Fondasi sistem pribadi dan elektronika = 0% (rata-rata P6=0, P7=0)
        $g3 = Goal::where('title', 'Fondasi sistem pribadi dan elektronika')->firstOrFail();
        $this->assertSame(0, $g3->progress_percent);

        // 5. Verifikasi Matrix Classifier (Bagian 9.11 pada hari Rabu minggu berjalan)
        $monday = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY);
        $wednesday = $monday->copy()->addDays(2); // Acuan Rabu

        $classifier = app(MatrixClassifier::class);

        // Kerjakan: T04, T05
        $t04 = Task::where('title', 'like', 'Revisi bab Metode%')->firstOrFail();
        $t05 = Task::where('title', 'like', 'Kirim laporan bug%')->firstOrFail();
        $this->assertSame(MatrixClassifier::DO, $classifier->classify($t04, $wednesday));
        $this->assertSame(MatrixClassifier::DO, $classifier->classify($t05, $wednesday));

        // Cepatkan / Delegasikan: T09
        $t09 = Task::where('title', 'like', 'Estimasi biaya perubahan%')->firstOrFail();
        $this->assertSame(MatrixClassifier::DELEGATE, $classifier->classify($t09, $wednesday));

        // Waiting: T21
        $t21 = Task::where('title', 'like', 'Menunggu outline%')->firstOrFail();
        $this->assertSame(MatrixClassifier::WAITING, $classifier->classify($t21, $wednesday));
    }
}
