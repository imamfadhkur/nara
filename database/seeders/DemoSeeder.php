<?php

namespace Database\Seeders;

use App\Domain\Activity\ActivityLogger;
use App\Domain\Goals\GoalService;
use App\Domain\Projects\ProjectService;
use App\Domain\Settings\SettingService;
use App\Domain\Tasks\TaskService;
use App\Models\InboxItem;
use App\Models\Output;
use App\Models\Schedule;
use App\Models\User;
use App\Support\Enums\ActivityEvent;
use App\Support\Enums\ActivitySource;
use App\Support\Enums\GoalStatus;
use App\Support\Enums\InboxStatus;
use App\Support\Enums\Level;
use App\Support\Enums\OutputType;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh lengkap dari Bagian 9 dokumen arsitektur (pengganti impor spreadsheet, D-07).
 *
 * Tanggal relatif terhadap Senin minggu berjalan (`now('Asia/Jakarta')->startOfWeek()`).
 * Bila "hari ini" dipasang di Rabu minggu berjalan (mis. Rabu 28 Okt 2026),
 * maka offset terhadap Senin (26 Okt) menghasilkan tanggal yang persis sama dengan tabel 9.3–9.6
 * dan hasil turunan di 9.11:
 *   Progres project: P1=12%, P2=0%, P3=33%, P4=0%, P5=42%, P6=0%, P7=0%
 *   Progres goal   : G1=6%, G2=25%, G3=0%
 *   WIP business   : 3/3 aktif (P3, P4, P5)
 */
class DemoSeeder extends Seeder
{
    public const DEMO_EMAIL = 'imam@nara.local';

    public const DEMO_PASSWORD = 'password-imam-nara-2026';

    public const DEMO_2FA_SECRET = 'JBSWY3DPEHPK3PXP';

    public function run(): void
    {
        $tz = 'Asia/Jakarta';
        $today = Carbon::now($tz)->startOfDay();
        // Senin minggu berjalan sebagai titik nol relatif (26/10 pada contoh 28/10/2026).
        $monday = $today->copy()->startOfWeek(Carbon::MONDAY);
        $day = fn (int $offsetFromMonday): string => $monday->copy()->addDays($offsetFromMonday)->toDateString();
        $stamp = fn (int $offsetFromMonday): Carbon => $monday->copy()->addDays($offsetFromMonday)->setTime(12, 0)->utc();

        $user = User::query()->updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Imam',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'timezone' => $tz,
                'telegram_chat_id' => '100000001',
                'two_factor_secret' => self::DEMO_2FA_SECRET,
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => [
                    'NARA1-RECOV', 'NARA2-RECOV', 'NARA3-RECOV', 'NARA4-RECOV',
                    'NARA5-RECOV', 'NARA6-RECOV', 'NARA7-RECOV', 'NARA8-RECOV',
                ],
            ],
        );

        // Bersihkan data milik user demo agar seeder idempoten saat dijalankan ulang.
        $user->projects()->withTrashed()->get()->each(function ($p) {
            $p->tasks()->withTrashed()->forceDelete();
            $p->forceDelete();
        });
        $user->inboxItems()->delete();
        $user->outputs()->withTrashed()->forceDelete();
        $user->lifeDomains()->delete();
        $user->settings()->delete();

        $settings = app(SettingService::class);
        foreach (SettingService::defaults() as $key => $value) {
            $settings->set($user, $key, $value);
        }

        $logger = app(ActivityLogger::class);
        $goalService = app(GoalService::class);
        $projectService = app(ProjectService::class);
        $taskService = app(TaskService::class);

        $logger->withSource(ActivitySource::System, function () use (
            $user, $goalService, $projectService, $taskService, $day, $stamp, $monday, $tz, $logger
        ) {
            // 9.2 Life Domain
            $dAcademic = $goalService->createDomain($user, ['name' => 'Karier & Akademik', 'color' => 'blue', 'sort_order' => 1]);
            $dBusiness = $goalService->createDomain($user, ['name' => 'Bisnis', 'color' => 'violet', 'sort_order' => 2]);
            $dTech = $goalService->createDomain($user, ['name' => 'Teknologi & Belajar', 'color' => 'cyan', 'sort_order' => 3]);
            $goalService->createDomain($user, ['name' => 'Kesehatan', 'color' => 'green', 'sort_order' => 4]);

            // 9.3 Goal
            $endOfYear = $monday->copy()->endOfYear()->toDateString();
            $midNextYear = $monday->copy()->addYear()->month(6)->endOfMonth()->toDateString();

            $g1 = $goalService->createGoal($dAcademic, [
                'title' => 'Publikasi ilmiah tahun ini',
                'target_date' => $endOfYear,
                'status' => GoalStatus::Active,
            ]);
            $g2 = $goalService->createGoal($dBusiness, [
                'title' => 'PT berdiri dan memiliki produk pertama',
                'target_date' => $midNextYear,
                'status' => GoalStatus::Active,
            ]);
            $g3 = $goalService->createGoal($dTech, [
                'title' => 'Fondasi sistem pribadi dan elektronika',
                'target_date' => null,
                'status' => GoalStatus::Active,
            ]);

            // 9.4 Project P1–P9 (lewat ProjectService agar WipGuard ikut teruji).
            // Offset terhadap Senin 26/10: 01/10=-25, 05/10=-21, 10/10=-16, 12/10=-14,
            // 14/10=-12, 18/10=-8, 19/10=-7, 20/10=-6, 22/10=-4, 25/10=-1, 26/10=0,
            // 27/10=+1, 28/10=+2, 06/11=+11, 11/11=+16, 13/11=+18, 30/11=+35.
            $p1 = $projectService->create($user, [
                'goal_id' => $g1->id,
                'name' => 'MSKI Revisi Jurnal',
                'category' => ProjectCategory::Research,
                'origin' => ProjectOrigin::Academic,
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Academic,
                'impact' => Level::High,
                'urgency' => Level::High,
                'start_date' => $day(-21),
                'due_date' => $day(11),
            ]);
            $p2 = $projectService->create($user, [
                'goal_id' => $g1->id,
                'name' => 'Riset Joint Paper Robotika',
                'category' => ProjectCategory::Research,
                'origin' => ProjectOrigin::Research,
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Academic,
                'impact' => Level::High,
                'urgency' => Level::Medium,
                'start_date' => $day(-14),
                'due_date' => $day(35),
            ]);
            $p3 = $projectService->create($user, [
                'goal_id' => $g2->id,
                'name' => 'Pendirian PT Imfar Manggala Nara',
                'category' => ProjectCategory::Admin,
                'origin' => ProjectOrigin::Personal,
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Business,
                'impact' => Level::High,
                'urgency' => Level::Medium,
                'start_date' => $day(-25),
                'due_date' => $day(35),
            ]);
            $p4 = $projectService->create($user, [
                'goal_id' => $g2->id,
                'name' => 'Freelance Klien Siakad',
                'category' => ProjectCategory::Client,
                'origin' => ProjectOrigin::Freelance,
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Business,
                'impact' => Level::Medium,
                'urgency' => Level::High,
                'start_date' => $day(-12),
                'due_date' => $day(18),
            ]);
            $p5 = $projectService->create($user, [
                'goal_id' => $g2->id,
                'name' => 'Landing Page Manggala Academy',
                'category' => ProjectCategory::Feature,
                'origin' => ProjectOrigin::Personal,
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Business,
                'impact' => Level::Medium,
                'urgency' => Level::Medium,
                'start_date' => $day(-7),
                'due_date' => $day(16),
            ]);
            $p6 = $projectService->create($user, [
                'goal_id' => $g3->id,
                'name' => 'NARA 1.0',
                'category' => ProjectCategory::Feature,
                'origin' => ProjectOrigin::Personal,
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Personal,
                'impact' => Level::High,
                'urgency' => Level::Medium,
                'start_date' => $day(-6),
                'due_date' => $endOfYear,
            ]);
            $p7 = $projectService->create($user, [
                'goal_id' => $g3->id,
                'name' => 'Belajar Kelistrikan Dasar',
                'category' => ProjectCategory::Learning,
                'origin' => ProjectOrigin::Personal,
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Personal,
                'impact' => Level::Low,
                'urgency' => Level::Low,
                'start_date' => $day(-16),
                'due_date' => null,
            ]);
            $p8 = $projectService->create($user, [
                'goal_id' => $g2->id,
                'name' => 'Bimbel Sendiri',
                'category' => ProjectCategory::Idea,
                'origin' => ProjectOrigin::Personal,
                'status' => ProjectStatus::Idea,
                'wip_slot' => null,
                'impact' => Level::High,
                'urgency' => Level::Low,
            ]);
            $p9 = $projectService->create($user, [
                'goal_id' => $g3->id,
                'name' => 'Robot Line Follower',
                'category' => ProjectCategory::Idea,
                'origin' => ProjectOrigin::Personal,
                'status' => ProjectStatus::Idea,
                'wip_slot' => null,
                'impact' => Level::Medium,
                'urgency' => Level::Low,
            ]);

            // 9.5 Task T01–T21
            $tasks = [
                ['p' => $p1, 'title' => 'Baca komentar reviewer & petakan perubahan', 'status' => TaskStatus::Done, 'priority' => TaskPriority::High, 'est' => 60, 'act' => 55, 'due' => $day(0), 'done_at' => $stamp(0)],
                ['p' => $p3, 'title' => 'Siapkan checklist dokumen akta pendirian', 'status' => TaskStatus::Done, 'priority' => TaskPriority::High, 'est' => 90, 'act' => 100, 'due' => $day(1), 'done_at' => $stamp(1)],
                ['p' => $p5, 'title' => 'Susun wireframe landing page', 'status' => TaskStatus::Done, 'priority' => TaskPriority::Normal, 'est' => 120, 'act' => 130, 'due' => $day(2), 'done_at' => $stamp(1)],
                ['p' => $p1, 'title' => 'Revisi bab Metode sesuai komentar reviewer', 'status' => TaskStatus::InProgress, 'priority' => TaskPriority::High, 'est' => 180, 'act' => null, 'due' => $day(4), 'subtasks' => [
                    ['Petakan komentar reviewer', true],
                    ['Tulis ulang paragraf desain eksperimen', false],
                    ['Perbarui rujukan', false],
                ]],
                ['p' => $p4, 'title' => 'Kirim laporan bug Siakad ke klien', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'est' => 45, 'act' => null, 'due' => $day(1)],
                ['p' => $p6, 'title' => 'Rancang migration Fase 0 NARA', 'status' => TaskStatus::InProgress, 'priority' => TaskPriority::High, 'est' => 90, 'act' => null, 'due' => null],
                ['p' => $p1, 'title' => 'Perbarui tabel hasil & grafik', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'est' => 120, 'act' => null, 'due' => $day(7)],
                ['p' => $p3, 'title' => 'Daftar NIB/OSS & tentukan KBLI', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Normal, 'est' => 120, 'act' => null, 'due' => $day(11), 'subtasks' => [
                    ['Siapkan data pemilik', false],
                    ['Tentukan KBLI', false],
                    ['Isi formulir OSS', false],
                ]],
                ['p' => $p4, 'title' => 'Estimasi biaya perubahan fitur klien', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Low, 'est' => 60, 'act' => null, 'due' => $day(3)],
                ['p' => $p1, 'title' => 'Tulis response letter ke reviewer', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'est' => 90, 'act' => null, 'due' => $day(11)],
                ['p' => $p5, 'title' => 'Tulis copy halaman landing', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Normal, 'est' => 90, 'act' => null, 'due' => $day(11)],
                ['p' => $p2, 'title' => 'Baca 3 paper pembanding', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Normal, 'est' => 150, 'act' => null, 'due' => $day(6)],
                ['p' => $p6, 'title' => 'Bangun CRUD Task (panel native)', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'est' => 120, 'act' => null, 'due' => null, 'subtasks' => [
                    ['Halaman index/form Task', false],
                    ['Validasi status', false],
                    ['Uji WipGuard', false],
                ]],
                ['p' => $p1, 'title' => 'Proofread final & submit ke jurnal', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'est' => 60, 'act' => null, 'due' => $day(11)],
                ['p' => $p2, 'title' => 'Susun kerangka metodologi riset', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Normal, 'est' => 120, 'act' => null, 'due' => $day(16)],
                ['p' => $p3, 'title' => 'Cek syarat bank untuk rekening perusahaan', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'est' => 60, 'act' => null, 'due' => $day(11)],
                ['p' => $p6, 'title' => 'Siapkan DemoSeeder', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Normal, 'est' => 45, 'act' => null, 'due' => null],
                ['p' => $p5, 'title' => 'Implementasi layout responsif', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Normal, 'est' => 75, 'act' => null, 'due' => $day(16)],
                ['p' => $p7, 'title' => 'Catat konsep seri-paralel', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Low, 'est' => 60, 'act' => null, 'due' => null],
                ['p' => $p7, 'title' => 'Praktik breadboard LED & resistor', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Low, 'est' => 90, 'act' => null, 'due' => null],
                ['p' => $p2, 'title' => 'Menunggu outline dari rekan peneliti', 'status' => TaskStatus::Waiting, 'waiting_reason' => 'menunggu outline dari rekan peneliti', 'priority' => TaskPriority::Normal, 'est' => null, 'act' => null, 'due' => null],
            ];

            $tModels = [];
            foreach ($tasks as $idx => $row) {
                $t = $taskService->create($row['p'], [
                    'title' => $row['title'],
                    'status' => $row['status'],
                    'waiting_reason' => $row['waiting_reason'] ?? null,
                    'priority' => $row['priority'],
                    'estimated_minutes' => $row['est'],
                    'actual_minutes' => $row['act'],
                    'due_date' => $row['due'],
                    'completed_at' => $row['done_at'] ?? null,
                    'sort_order' => $idx + 1,
                ]);

                $tModels[$idx + 1] = $t;

                foreach ($row['subtasks'] ?? [] as [$subTitle, $subDone]) {
                    $sub = $taskService->addSubtask($t, $subTitle);
                    if ($subDone) {
                        $taskService->updateSubtask($sub, ['is_done' => true]);
                    }
                }
            }

            // 9.7 Jadwal Minggu Ini
            $schedTime = fn (int $offsetFromMonday, string $hi): Carbon => Carbon::parse($monday->copy()->addDays($offsetFromMonday)->toDateString() . ' ' . $hi, $tz)->utc();

            $scheduleDefs = [
                // [Task Number, dayOffset, start, end, note]
                [1,  0, '08:00', '09:00', 'Sesi revisi komentar'],
                [2,  0, '13:00', '14:30', 'Sesi akta pendirian'],
                [3,  1, '13:00', '15:00', 'Sesi wireframe'],
                [4,  2, '09:00', '12:00', 'Sesi revisi bab metode'],
                [5,  2, '13:00', '13:45', 'Sesi laporan bug'],
                [6,  2, '15:30', '17:00', 'Sesi migration Fase 0'],
                [7,  3, '09:00', '11:00', 'Sesi tabel hasil'],
                [8,  3, '13:00', '15:00', 'Sesi OSS/NIB'],
                [10, 4, '09:00', '10:30', 'Sesi response letter'],
                [11, 4, '13:00', '14:30', 'Sesi copy landing page'],
                [12, 5, '08:00', '10:30', 'Sesi baca paper pembanding'],
                [13, 5, '13:00', '15:00', 'Sesi CRUD Task'],
            ];

            foreach ($scheduleDefs as [$tNum, $dOffset, $startStr, $endStr, $note]) {
                Schedule::create([
                    'task_id' => $tModels[$tNum]->id,
                    'start_at' => $schedTime($dOffset, $startStr),
                    'end_at' => $schedTime($dOffset, $endStr),
                    'source' => 'nara',
                    'note' => $note,
                ]);
            }

            // 9.8 Output
            Output::create([
                'user_id' => $user->id,
                'project_id' => $p3->id,
                'task_id' => $tModels[2]->id,
                'title' => 'Checklist dokumen akta pendirian',
                'output_type' => OutputType::LegalDoc,
                'evidence_url' => 'https://storage.nara.local/evidence/akta-pendirian-checklist.pdf',
                'produced_at' => $day(0),
                'note' => 'Berkas PDF checklist persyaratan notaris',
            ]);

            Output::create([
                'user_id' => $user->id,
                'project_id' => $p5->id,
                'task_id' => $tModels[3]->id,
                'title' => 'Wireframe landing page Academy',
                'output_type' => OutputType::Prototype,
                'evidence_url' => 'https://figma.com/file/demo-academy-wireframe',
                'produced_at' => $day(1),
                'note' => 'Tautan berkas desain Figma 5 section utama',
            ]);

            Output::create([
                'user_id' => $user->id,
                'project_id' => $p6->id,
                'task_id' => null,
                'title' => 'Repo nara-core (commit awal Fase 0)',
                'output_type' => OutputType::Github,
                'evidence_url' => 'https://github.com/nara/nara-core',
                'produced_at' => $day(-5), // 21/10
                'note' => 'Tautan repositori git foundation architecture',
            ]);

            // 9.9 Inbox
            InboxItem::create([
                'user_id' => $user->id,
                'content' => 'Cek harga domain baru',
                'source' => 'telegram',
                'hint' => 'task',
                'status' => InboxStatus::Pending,
            ]);

            InboxItem::create([
                'user_id' => $user->id,
                'content' => 'Ide: fitur voice note di NARA',
                'source' => 'telegram',
                'hint' => 'idea',
                'status' => InboxStatus::Pending,
            ]);

            InboxItem::create([
                'user_id' => $user->id,
                'content' => 'Follow up klien X minggu depan',
                'source' => 'web',
                'hint' => 'task',
                'status' => InboxStatus::Pending,
            ]);

            InboxItem::create([
                'user_id' => $user->id,
                'content' => 'Diajak join riset dosen Y — pikirkan dulu',
                'source' => 'telegram',
                'hint' => 'idea',
                'status' => InboxStatus::Pending,
            ]);

            InboxItem::create([
                'user_id' => $user->id,
                'content' => 'Beli kabel jumper',
                'source' => 'telegram',
                'hint' => 'task',
                'status' => InboxStatus::Converted,
                'converted_to_type' => 'task',
                'converted_to_id' => $tModels[20]->id,
                'processed_at' => $stamp(-1),
            ]);

            InboxItem::create([
                'user_id' => $user->id,
                'content' => 'Ide aplikasi jadwal piket',
                'source' => 'web',
                'hint' => 'idea',
                'status' => InboxStatus::Discarded,
                'processed_at' => $stamp(-1),
            ]);

            // 9.10 Log Aktivitas Tambahan
            $logger->log($tModels[3], ActivityEvent::StatusChanged, ['status' => 'todo'], ['status' => 'done']);
            $logger->log($tModels[21], ActivityEvent::StatusChanged, ['status' => 'in_progress'], ['status' => 'waiting', 'waiting_reason' => 'menunggu outline dari rekan peneliti']);

            // Setel ulang last_activity_at sesuai tabel 9.4 (terutama P2 stagnan 6 hari: 22/10).
            foreach ([
                [$p1, 2],
                [$p2, -4],
                [$p3, 0],
                [$p4, 1],
                [$p5, 1],
                [$p6, 2],
                [$p7, -1],
                [$p8, -6],
                [$p9, -8],
            ] as [$project, $offset]) {
                $project->forceFill(['last_activity_at' => $stamp($offset)])->saveQuietly();
            }
        });
    }
}

