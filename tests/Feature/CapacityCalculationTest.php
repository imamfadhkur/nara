<?php

namespace Tests\Feature;

use App\Domain\Scheduling\CapacityService;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CapacityCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_capacity_on_seed_data_is_exactly_90_percent(): void
    {
        $this->seed(DemoSeeder::class);

        $user = User::where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $capacityService = app(CapacityService::class);

        $cap = $capacityService->calculateWeekly($user);

        // Angka acuan Bagian 9.11
        $this->assertSame(285, $cap['realization_minutes'], 'Realisasi harus 285 mnt (T01=55, T02=100, T03=130)');
        $this->assertSame(1005, $cap['planned_scheduled_minutes'], 'Rencana terjadwal harus 1.005 mnt');
        $this->assertSame(60, $cap['planned_unscheduled_minutes'], 'Rencana belum terjadwal harus 60 mnt (T09)');
        $this->assertSame(1350, $cap['total_minutes'], 'Total beban harus 1.350 mnt');
        $this->assertSame(1500, $cap['capacity_minutes'], 'Kapasitas mingguan harus 1.500 mnt (25 jam)');
        $this->assertSame(90, $cap['percent'], 'Persentase beban harus tepat 90%');
        $this->assertSame('padat', $cap['status']);
        $this->assertSame('warning', $cap['tone']);
    }

    public function test_scenario_9_13_overload_and_pause_recovery(): void
    {
        $this->seed(DemoSeeder::class);

        $user = User::where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $capacityService = app(CapacityService::class);

        $p4 = Project::where('user_id', $user->id)->where('name', 'Freelance Klien Siakad')->firstOrFail();
        $p5 = Project::where('user_id', $user->id)->where('name', 'Landing Page Manggala Academy')->firstOrFail();

        $fridayStr = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString();

        // 1. Tambah task baru ke P4 (240 menit, jatuh tempo Jumat 30/10)
        Task::create([
            'project_id' => $p4->id,
            'title' => 'Persiapan presentasi ke klien',
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::High,
            'estimated_minutes' => 240,
            'due_date' => $fridayStr,
        ]);

        // Verifikasi hasil: 1.350 + 240 = 1.590 menit = 106% -> Overload (merah)
        $capAfterNewTask = $capacityService->calculateWeekly($user);
        $this->assertSame(1590, $capAfterNewTask['total_minutes']);
        $this->assertSame(106, $capAfterNewTask['percent']);
        $this->assertSame('overload', $capAfterNewTask['status']);
        $this->assertSame('danger', $capAfterNewTask['tone']);

        // 2. Pengguna memilih jeda satu project -> P5 dijeda
        $p5->update(['status' => ProjectStatus::Paused]);

        // Verifikasi hasil pemulihan: T11 (90 menit jadwal) keluar -> 1.500 menit = 100% -> "padat"
        $capAfterPause = $capacityService->calculateWeekly($user);
        $this->assertSame(1500, $capAfterPause['total_minutes']);
        $this->assertSame(100, $capAfterPause['percent']);
        $this->assertSame('padat', $capAfterPause['status']);
        $this->assertSame('warning', $capAfterPause['tone']);
    }
}

