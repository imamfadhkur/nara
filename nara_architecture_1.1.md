# NARA — Panduan Arsitektur & Pengembangan

## Dokumen Acuan Tunggal · Edisi Solo Developer

> **Versi 1.1** · 30 Oktober 2026 (menggantikan v1.0 tanggal 24 September 2026)
> PT Imfar Manggala Nara · Lini NARA (Personal AI Agent)
> Satu-satunya dokumen acuan pengembangan NARA, dari Fase 0 sampai Fase 6, termasuk rancangan AI (Fase 4) dan Agent (Fase 5).
> Teknologi: **Laravel (core) + MySQL 8**. AI masuk lewat gerbang keputusan Fase 4 (lihat Bagian 11).
> Identitas merek lengkap ada di `pt_imfar.md`.

---

## Ringkasan Perubahan dari v1.0 ke v1.1

Revisi ini lahir dari tinjauan terhadap v1.0 yang dinilai terlalu lebar untuk satu orang. Keputusan Anda dan saran tinjauan digabung sebagai berikut.

| #  | Perubahan                                                                                                                                                                                                 | Alasan singkat                                                                                     |
| -- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| 1  | Data tetap **single source of truth** di database NARA. UI web tetap menyediakan view **Calendar / Timeline / Matrix**, dan semua jadwal **selalu disinkronkan** dengan Google Calendar | Keputusan Anda; Google hanya klien tampilan waktu                                                  |
| 2  | Fase 4 dan 5 **tetap di dokumen ini** (Bagian 11), tidak dipisah ke `roadmap-later.md`                                                                                                            | Satu dokumen acuan; bagian itu diberi label "rancangan, boleh berubah"                             |
| 3  | **Tanpa impor spreadsheet**; data awal lewat `DemoSeeder` berdasarkan contoh lengkap di Bagian 9                                                                                                  | Keputusan Anda                                                                                     |
| 4  | **MySQL 8** dipertahankan. Vektor (Fase 4) memakai **Qdrant**, bukan pgvector                                                                                                                 | Kemudahan developer; pgvector tidak tersedia di MySQL                                              |
| 5  | Skema berkembang **per fase**, bukan semua tabel dibuat di Fase 0                                                                                                                                   | Tabel yang belum dipakai menambah beban pemeliharaan                                               |
| 6  | Status disederhanakan: task 5 status, project 5 status. `blocked` digabung ke `waiting`                                                                                                               | Status tumpang tindih menambah isian                                                               |
| 7  | `impact`/`urgency` hanya di **project**; task hanya punya `priority`. Urgensi dan kepentingan task **diturunkan otomatis** untuk Matrix                                                 | Mengurangi isian wajib                                                                             |
| 8  | Python **bukan komitmen**. Layanan Python hanya dibangun bila lolos gerbang keputusan Fase 4                                                                                                        | LLM bisa dipanggil langsung dari Laravel                                                           |
| 9  | Antarmuka memakai **panel native** buatan sendiri (Blade + Alpine.js, Livewire opsional) untuk **semua** halaman, termasuk CRUD. **Tanpa Filament** atau panel admin siap pakai         | Kontrol penuh atas UI, satu gaya visual, dan tidak bergantung pada siklus versi paket pihak ketiga |
| 10 | Tambahan baru: kapasitas mingguan di 1.0, Telegram capture di Fase 1, backup dan keamanan sejak Fase 0, pencatatan output satu klik, **anggaran waktu dan kriteria gagal**                          | Menjawab keluhan inti: kelebihan beban dan sedikit output                                          |
| 11 | Contoh data diperbanyak (Bagian 9) dan dilengkapi skenario uji                                                                                                                                            | Pengganti impor spreadsheet                                                                        |

---

## Daftar Isi

0. Cara Memakai Dokumen Ini
1. Konsep Produk NARA
2. Keputusan Arsitektur Utama (Ringkas)
3. Arsitektur Sistem
4. Sinkronisasi Google Calendar
5. Desain UI/UX & Konsep Visual
6. Rancangan Database (MySQL 8)
7. Aturan Bisnis
8. Telegram Bot
9. Contoh Data (Seed) & Skenario
10. Panduan Pembangunan (Fase 0–6)
11. Rancangan Lanjutan: AI (Fase 4) & Agent (Fase 5)
12. Pengujian & Kualitas
13. Keamanan, Backup & Operasional
14. Anggaran Waktu, Metrik & Kriteria Gagal
15. Risiko & Mitigasi
16. Parking Lot (Ide yang Sengaja Ditunda)
17. Log Keputusan Arsitektur (ADR) & Changelog
18. Glosarium
19. Prinsip Penutup

---

## 0. Cara Memakai Dokumen Ini

Dokumen ini dipakai sebagai peta, bukan sebagai kontrak. Cara pakainya:

1. **Baca Bagian 2 dan 10 dulu.** Keduanya memuat keputusan dan urutan kerja. Bagian lain adalah rujukan yang dibuka saat dibutuhkan.
2. **Bagian bertanda 🔒 bersifat mengikat** (keputusan, skema inti, aturan bisnis). Mengubahnya harus lewat catatan di ADR (Bagian 17).
3. **Bagian bertanda 🌱 bersifat rancangan** (Fase 4–5, parking lot). Isinya ide jangka panjang dan akan direvisi saat fasenya tiba. Jangan membangun apa pun dari bagian 🌱 sebelum gerbang fasenya terbuka.
4. **Satu fase = satu checklist Definition of Done (DoD).** Jangan pindah fase sebelum checklist selesai dan fitur dipakai nyata.
5. **Ubah dokumen ini lebih dulu, baru ubah kode.** Bila kode menyimpang dari dokumen, salah satunya harus diperbaiki pada hari yang sama.

**Prinsip yang tidak boleh dilanggar di semua fase:**

1. Jangan mulai AI sebelum NARA 1.0 dipakai harian minimal beberapa minggu.
2. Bangun tipis dan dipakai dulu; tambah fitur hanya setelah fitur sebelumnya terbukti dipakai.
3. Setiap fase punya DoD yang jelas.
4. Tidak ada tanggal kaku, tetapi **ada anggaran jam per minggu** (Bagian 14) sebagai pagar pengaman.
5. Bila administrasi NARA melebihi batas di Bagian 14, sistem disederhanakan, bukan dipertahankan.

**Konvensi penulisan.** Nama tabel dan kolom memakai `huruf_kode`. Tanggal contoh memakai format `dd/mm` atau tanggal lengkap. Durasi dalam menit kecuali dinyatakan lain. Kata "pengguna" berarti pemilik tunggal sistem.

---

## 1. Konsep Produk NARA

### 1.1 Positioning

**NARA** adalah lini teknologi AI PT Imfar Manggala Nara yang berfokus pada kecerdasan buatan yang bekerja bersama manusia. NARA bukan sekadar chatbot, melainkan **Personal AI Agent**.

**Kepanjangan:** NARA — *Neural Autonomous Reasoning Agent*. "AI" adalah kategori teknologinya, bukan bagian dari kepanjangan.

> NARA dirancang sebagai Personal AI Agent yang bekerja bersama manusia untuk memahami, merencanakan, mengatur, dan membantu menyelesaikan berbagai aktivitas personal.

Hubungan dengan Manggala (lihat `pt_imfar.md`):

```text
MANGGALA: Learn → Create → Share            → Human Development
NARA:     Understand → Plan → Assist → Act  → Human Augmentation
```

### 1.2 Masalah yang Dijawab

NARA lahir dari tiga keluhan konkret pemiliknya:

| Keluhan                                                                                                  | Wujud dalam sistem                                                                                 |
| -------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| Banyak keinginan dan komitmen (PT, bimbel, riset, freelance, belajar elektronika, robotik) yang tercecer | **Inbox** + hirarki Life Domain → Goal → Project → Task, dengan batas WIP                 |
| Selalu bekerja tetapi terasa tidak menghasilkan apa pun                                                  | **Output** yang tercatat satu klik, laporan output mingguan, dan kartu "Output" di dashboard |
| Beban selalu melebihi waktu                                                                              | **Kapasitas mingguan** (beban vs jam tersedia), Matrix, dan batas WIP                        |

Setiap fitur baru wajib menjawab salah satu dari tiga keluhan ini. Bila tidak, fitur itu masuk Parking Lot (Bagian 16).

### 1.3 Karakter Produk

Enam karakter interaksi yang wajib terasa di setiap layar dan setiap balasan:

| Karakter              | Arti bagi desain dan interaksi                                                 |
| --------------------- | ------------------------------------------------------------------------------ |
| **Intelligent** | Memahami konteks dan melakukan reasoning, bukan sekadar mencocokkan kata kunci |
| **Proactive**   | Tidak hanya menunggu perintah bila memiliki izin dan konteks yang cukup        |
| **Personal**    | Memahami pola kerja pengguna, tidak generik                                    |
| **Reliable**    | Menyampaikan informasi jelas, tidak berpura-pura tahu                          |
| **Calm**        | Interaksi tenang, tidak menambah beban                                         |
| **Supportive**  | Membantu saat pekerjaan terasa terlalu banyak                                  |

Unsur "sahabat" adalah **karakter interaksi**, bukan klaim bahwa NARA menggantikan manusia atau tenaga profesional.

### 1.4 Hirarki Data

```text
Life Domain → Goal → Project → Task → Subtask
```

- **Life Domain**: wilayah hidup (Karier & Akademik, Bisnis, Teknologi & Belajar, Kesehatan).
- **Goal**: hasil jangka menengah yang ingin dicapai.
- **Project**: wadah pekerjaan dengan hasil akhir yang jelas. Boleh berdiri tanpa Goal.
- **Task**: satu langkah yang bisa dikerjakan dan diestimasi.
- **Subtask**: butir checklist di dalam task, tanpa status sendiri.

### 1.5 Fungsi Inti

| Rilis | Fungsi                                                                                                                                    |
| ----- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| 1.0   | Task & project management, capture (Inbox, Telegram), planning (jadwal + kapasitas), reminder, calendar, pencatatan output, weekly review |
| 1.5   | Analitik rule-based: overload, stagnasi, laporan output otomatis                                                                          |
| 2.0   | Natural language command, daily planner, chatbot berbasis data NARA, output intelligence                                                  |
| 3.0   | Agent multi-langkah dengan persetujuan                                                                                                    |

---

## 2. Keputusan Arsitektur Utama (Ringkas) 🔒

| #           | Keputusan                                                                                                                                                    |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| D-01        | **Database NARA = single source of truth**; web, Google Calendar, Telegram, dan AI hanyalah klien                                                      |
| D-02        | **Monolith modular Laravel**; Python hanya lewat gerbang keputusan Fase 4 (lihat juga D-17)                                                            |
| D-03        | **MySQL 8** sebagai database utama (kemudahan developer)                                                                                               |
| D-04        | Vektor di **Qdrant** pada Fase 4 (bukan di MySQL)                                                                                                      |
| D-05        | View **Calendar/Timeline/Matrix** di web dari database NARA; **sinkron selalu** dengan Google Calendar (kalender "NARA" khusus; NARA otoritatif) |
| D-06        | Seluruh rencana (termasuk Fase 4–5) hidup di **satu dokumen** ini                                                                                     |
| D-07        | **Tanpa impor spreadsheet**; data awal lewat `DemoSeeder` berdasar Bagian 9                                                                          |
| D-08        | Skema berkembang **per fase**; tabel non-esensial ditunda dengan pemicu                                                                                |
| D-09 / D-10 | Status disederhanakan; urgency/importance task diturunkan otomatis                                                                                           |
| D-11        | Antarmuka: **panel native** (Blade + Alpine.js, Livewire opsional) untuk seluruh halaman; **tanpa Filament** atau panel admin siap pakai         |
| D-12        | Backup, enkripsi token, 2FA, dan allowlist Telegram sejak **Fase 0**                                                                                   |
| D-13        | **Telegram capture** masuk Fase 1 (bukan Fase 2)                                                                                                       |
| D-14        | **Kapasitas mingguan** sederhana masuk 1.0                                                                                                             |
| D-15        | Anggaran jam/minggu + kriteria gagal sebagai pagar pengaman proyek                                                                                           |
| D-16        | Pencatatan **output satu klik** saat task selesai                                                                                                      |
| D-17        | **Gerbang Python** di Fase 4: layanan Python hanya dibangun bila kebutuhan ML/analitik lokal terbukti                                                  |
| D-18        | Semua waktu disimpan **UTC**, ditampilkan dalam zona waktu pengguna                                                                                    |
| D-19        | Kategori project sebagai **kolom biasa**, bukan tabel terpisah                                                                                         |

Rincian alasan tiap keputusan ada di Bagian 17.

---

## 3. Arsitektur Sistem 🔒

### 3.1 Prinsip

```text
NARA DATABASE = SINGLE SOURCE OF TRUTH
```

Web, Google Calendar, Telegram, dan AI membaca dan menulis **lewat Service Layer Laravel**. Tidak ada klien yang menulis langsung ke tabel. Dengan begitu validasi, activity log, dan aturan bisnis selalu satu pintu.

### 3.2 Diagram Tingkat Tinggi (Fase 0–3)

```text
                        ┌──────────────────────┐
                        │     Pengguna (Web)    │
                        └──────────┬───────────┘
                                   │ HTTPS
                        ┌──────────▼───────────┐
                        │  Nginx (reverse proxy)│
                        └──────────┬───────────┘
                                   │
            ┌──────────────────────▼───────────────────────┐
            │              LARAVEL (monolith modular)       │
            │                                               │
            │  PANEL NATIVE (Blade + Alpine.js)             │
            │  CRUD · Today · Calendar · Timeline · Matrix  │
            │  Weekly Review · Focus · Settings             │
            │  ───────────────────────────────────────────  │
            │  Service Layer (TaskService, ProjectService,  │
            │  ScheduleService, CapacityService, ...)       │
            │  ───────────────────────────────────────────  │
            │  Queue + Scheduler │ Webhook Telegram         │
            └───────┬───────────────────┬──────────────────┘
                    │                   │
             ┌──────▼──────┐     ┌──────▼──────┐
             │   MySQL 8   │     │    Redis    │
             │  (utama)    │     │ queue+cache │
             └─────────────┘     └─────────────┘
                    │
        ┌───────────┼────────────┐
        ▼           ▼            ▼
 Google Calendar  Telegram    Storage lokal
   (kalender      Bot API     (lampiran, backup)
    "NARA")
```

Penambahan di **Fase 4** (hanya bila gerbang keputusan terbuka):

```text
Laravel ──► NaraAIClient ──► LLM API (via Laravel langsung)
        └─► (opsional) Python service lokal ──► Qdrant (vektor)
```

### 3.3 Tanggung Jawab Komponen

| Komponen                      | Tanggung jawab                                                                                                                                            | Catatan                                                                  |
| ----------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| **Laravel**             | Auth, CRUD, view, service layer, reminder, orkestrasi Google dan Telegram, job                                                                            | Satu-satunya penulis data                                                |
| **Panel native**        | Seluruh antarmuka: CRUD (Life Domain, Goal, Project, Task, Output, Settings) dan halaman khusus (Today, Calendar, Timeline, Matrix, Weekly Review, Focus) | Blade + Alpine.js, komponen sendiri (lihat 3.7); tanpa paket panel admin |
| **MySQL 8**             | Penyimpanan utama; kolom JSON untuk payload fleksibel                                                                                                     | InnoDB, `utf8mb4`                                                      |
| **Redis**               | Queue, cache, rate limit                                                                                                                                  | Boleh diganti driver `database` di awal bila ingin lebih sederhana     |
| **Google Calendar API** | Tampilan waktu di HP dan desktop                                                                                                                          | Kalender khusus "NARA"                                                   |
| **Telegram Bot API**    | Capture cepat, reminder, briefing                                                                                                                         | Webhook ke Laravel                                                       |
| **Qdrant** (Fase 4)     | Pencarian semantik                                                                                                                                        | Wadah vektor saja; sumber data tetap MySQL                               |

### 3.4 Pola Modular di Laravel

Satu repo, satu aplikasi, dipisah per modul lewat namespace. Modul tidak boleh saling mengakses model langsung bila bisa lewat service.

```text
app/
├── Models/                     # Eloquent: LifeDomain, Goal, Project, Task, Schedule, ...
├── Domain/                     # aturan bisnis murni (tanpa HTTP)
│   ├── Tasks/                  # TaskService, TaskStatusRules, MatrixClassifier
│   ├── Projects/               # ProjectService, WipGuard, ProgressCalculator
│   ├── Scheduling/             # ScheduleService, ConflictDetector, CapacityService
│   ├── Capture/                # InboxService, TriageService
│   ├── Outputs/                # OutputService
│   └── Activity/               # ActivityLogger
├── Integrations/
│   ├── Google/                 # GoogleCalendarClient, CalendarSyncService, SyncMapper
│   └── Telegram/               # TelegramClient, CommandRouter, Handlers/
├── Http/
│   ├── Controllers/Panel/      # LifeDomain, Goal, Project, Task, Output, Settings (CRUD native)
│   ├── Controllers/            # Today, Calendar, Matrix, Review, Webhook
│   ├── Requests/               # FormRequest per resource (validasi)
│   └── Controllers/Api/        # (Fase 4) AiBridgeController
├── View/Components/            # komponen Blade: DataTable, FormField, Modal, StatusBadge, KpiCard, Toast
├── Jobs/                       # SyncTaskToGoogle, PullGoogleChanges, SendReminder,
│                               # SendDailyBriefing, SendEveningSummary, BackupDatabase
├── Console/Commands/           # nara:sync-google, nara:weekly-report, nara:health-check
├── AI/                         # (Fase 4) NaraAIClient, PromptBuilder, Guardrails
└── Support/                    # helper tanggal, zona waktu, enum
resources/views/
├── layouts/panel.blade.php     # sidebar + topbar + area konten
├── components/                 # tampilan komponen di atas
└── panel/                      # halaman per modul (projects/, tasks/, inbox/, calendar/, ...)
database/
├── migrations/
└── seeders/DemoSeeder.php      # data contoh dari Bagian 9
```

**Aturan struktur:**

1. Controller dan komponen panel **hanya memanggil service**; tidak berisi aturan bisnis.
2. Setiap perubahan data penting lewat `ActivityLogger` (Bagian 6.9).
3. Enum status disimpan sebagai PHP backed enum agar konsisten antara UI, validasi, dan database.
4. Semua waktu disimpan **UTC** dan ditampilkan dalam zona waktu pengguna (`Asia/Jakarta` secara default).

### 3.5 Alur Contoh: Menjadwalkan Task dari Web

```text
Pengguna seret task ke Calendar (Kam 09:00–11:00)
   ▼
CalendarController → ScheduleService::create(task, start, end)
   ├─ ConflictDetector: cek bentrok dengan jadwal lain & event Google (busy)
   ├─ simpan `schedules` + ActivityLogger
   ├─ CapacityService: hitung ulang beban minggu
   └─ dispatch Job SyncTaskToGoogle
          ▼
   GoogleCalendarClient::upsert → `calendar_events` (sync_status = synced)
   ▼
UI memperbarui banner kapasitas ("Minggu ini 90%")
```

### 3.6 Lingkungan

| Lingkungan | Fungsi           | Catatan                                               |
| ---------- | ---------------- | ----------------------------------------------------- |
| Lokal      | Pengembangan     | `DemoSeeder` wajib bisa dijalankan ulang kapan saja |
| Produksi   | Pemakaian harian | Satu VPS kecil cukup; HTTPS wajib; backup harian      |

Tidak ada lingkungan staging selama pemakai hanya satu orang. Migrasi diuji di salinan database lokal sebelum dijalankan di produksi (lihat Bagian 13).

### 3.7 Panel Native 🔒

**Keputusan (D-11):** seluruh antarmuka dibangun sendiri di atas Laravel. Tidak memakai Filament, Nova, Backpack, atau panel admin siap pakai lain. "Native" berarti: rute, controller, Blade, dan komponen milik NARA; Alpine.js untuk interaksi ringan; Livewire boleh dipakai di komponen tertentu bila terbukti menyederhanakan (mis. Kanban), bukan sebagai kerangka panel.

**Satu pola untuk semua resource (CRUD):**

```text
Route::resource('panel/projects', ProjectController)
   ▼
Controller (tipis)  → FormRequest (validasi) → Service (aturan bisnis + ActivityLogger)
   ▼
View Blade: index (DataTable) · create/edit (form) · show (detail)
```

| Elemen                         | Ketentuan                                                                                                                                                                              |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Layout**               | `layouts/panel.blade.php`: sidebar (peta layar Bagian 5.3), topbar (pencarian cepat, tombol `+ Catat`, status sinkron), area konten                                                |
| **Komponen Blade**       | `DataTable` (urut, saring, paginasi server-side, edit sel untuk status/tenggat), `FormField`, `Modal`, `StatusBadge` (warna + teks), `KpiCard`, `Toast`, `ConfirmDialog` |
| **Interaksi**            | Alpine.js untuk modal, dropdown, edit sel, pintasan keyboard; permintaan ke server memakai `fetch` ke endpoint JSON kecil atau Livewire pada komponen tertentu                       |
| **Validasi**             | Selalu `FormRequest` di sisi server; pesan kesalahan berbahasa Indonesia                                                                                                             |
| **Otorisasi**            | Satu pengguna, tetapi tetap melalui **Policy** per model agar siap bila pengguna bertambah (Fase 6)                                                                              |
| **Gaya**                 | Design token (Bagian 5.5) dalam CSS variables; Tailwind boleh dipakai sebagai alat gaya, bukan sebagai paket panel                                                                     |
| **Aksesibilitas dan HP** | Today, Inbox, Task Detail responsif; Table/Kanban/Timeline/Matrix prioritas desktop                                                                                                    |
| **Generator ringan**     | Satu perintah `php artisan nara:make-resource Nama` membuat controller, request, policy, dan view dasar dari stub, agar resource baru tidak ditulis manual berulang                  |

**Konsekuensi yang diterima:** waktu Fase 0 dan Fase 1 lebih panjang dibanding memakai panel siap pakai (perkiraan di Bagian 10 sudah disesuaikan). Sebagai imbalannya: tidak ada ketergantungan pada siklus versi paket panel, satu bahasa desain di seluruh aplikasi, dan komponen yang sama dipakai ulang di CRUD maupun halaman khusus.

**Batas agar tidak melebar:** komponen dibuat **hanya saat dipakai oleh dua halaman** atau lebih; tidak ada pustaka komponen internal yang dibangun "untuk nanti". Bila generator atau komponen mulai menghabiskan anggaran jam fase, jalankan peninjauan lingkup (Bagian 14.4).

---

## 4. Sinkronisasi Google Calendar 🔒

### 4.1 Prinsip

1. **NARA otoritatif.** Bila ada konflik data, NARA menang.
2. NARA hanya menyentuh **satu kalender khusus bernama "NARA"** yang dibuat NARA sendiri. Kalender pribadi dan kalender lain tidak pernah dimodifikasi.
3. Kalender lain (kerja, keluarga) **dibaca saja** sebagai blok "sibuk" agar jadwal tidak bentrok.
4. Sinkronisasi berlaku untuk **jadwal**, bukan seluruh task. Task tanpa jadwal tidak muncul di Google.
5. Kegagalan sinkronisasi **tidak boleh menghalangi** pekerjaan di NARA. Jadwal tetap tersimpan, status sinkron ditandai `failed`, dan dicoba ulang.

### 4.2 Arah Data

| Arah                                             | Cakupan                                                              | Dibangun di        |
| ------------------------------------------------ | -------------------------------------------------------------------- | ------------------ |
| **NARA → Google**                         | Buat, ubah, hapus event dari jadwal NARA                             | Fase 2, langkah 2a |
| **Google → NARA**                         | Perubahan waktu/hapus pada event yang dibuat NARA di kalender "NARA" | Fase 2, langkah 2b |
| **Google → NARA (baca)**                  | Event kalender lain sebagai blok sibuk                               | Fase 2, langkah 2b |
| Event baru yang dibuat manual di kalender "NARA" | Dikonversi menjadi item Inbox (bukan task otomatis)                  | Fase 2, langkah 2b |

Di Fase 1 jadwal sudah bisa dibuat manual di NARA **tanpa** Google. Pembagian 2a dan 2b menjaga agar bagian termahal (arah balik) dibangun **setelah** arah maju stabil dipakai.

### 4.3 Pemetaan Data

| NARA (`schedules`)                             | Google Event                                               |
| ------------------------------------------------ | ---------------------------------------------------------- |
| judul = judul task (+ nama project di deskripsi) | `summary`                                                |
| `start_at`, `end_at`                         | `start.dateTime`, `end.dateTime` (zona waktu pengguna) |
| tautan ke task di NARA                           | `description` (URL)                                      |
| `calendar_events.google_event_id`              | `id`                                                     |
| `schedules.id`                                 | `extendedProperties.private.nara_schedule_id`            |
| status task `done`                             | judul diberi awalan ✓, warna abu                          |
| warna life domain                                | `colorId` terdekat                                       |

`extendedProperties.private.nara_schedule_id` adalah kunci pencocokan. Judul tidak dipakai sebagai kunci karena bisa diubah.

### 4.4 Alur Sinkronisasi Keluar (NARA → Google)

```text
Jadwal dibuat/diubah/dihapus
   ▼
Observer → dispatch SyncTaskToGoogle(schedule_id, operation)
   ▼
Job:
  1. ambil token (dekripsi) → bila kadaluarsa, refresh
  2. create/update/delete event di kalender "NARA"
  3. simpan google_event_id, etag, last_synced_at, sync_status = synced
  gagal → sync_status = failed, retry eksponensial (1m, 5m, 30m, 2j), max 6 kali
  gagal permanen → tampil di panel "Perlu perhatian" + notifikasi Telegram
```

Job bersifat **idempoten**: menjalankan job yang sama dua kali tidak membuat event ganda (dicek lewat `nara_schedule_id`).

### 4.5 Alur Sinkronisasi Masuk (Google → NARA) — Fase 2

Memakai **incremental sync** Google (`syncToken`) dan, bila memungkinkan, **push notification channel (webhook)** agar tidak perlu polling terus-menerus. Cadangan: polling tiap 10 menit lewat Scheduler.

```text
Webhook/polling → PullGoogleChanges
   ▼
untuk setiap event yang berubah di kalender "NARA":
  - punya nara_schedule_id?
      ya + waktu berubah  → perbarui schedule (tandai source = google)
      ya + dihapus        → hapus schedule, task kembali "belum dijadwalkan"
      tidak (event baru)  → buat inbox_item "Event: <judul> (<tanggal>)"
untuk kalender lain (hanya baca):
  - simpan ke tabel busy_blocks (cache) untuk ConflictDetector
```

### 4.6 Aturan Konflik

| Situasi                                                            | Perlakuan                                                                    |
| ------------------------------------------------------------------ | ---------------------------------------------------------------------------- |
| Waktu diubah di NARA dan di Google dalam jendela sinkron yang sama | **NARA menang**; event Google ditimpa; catatan di activity log         |
| Event dihapus di Google, task masih aktif                          | Jadwal dihapus; task muncul di daftar "Belum dijadwalkan"; notifikasi ringan |
| Jadwal bentrok dengan blok sibuk kalender lain                     | Peringatan di UI saat menyimpan; pengguna boleh tetap menyimpan              |
| Token OAuth dicabut/kedaluwarsa permanen                           | Sinkronisasi dijeda, banner merah di UI, notifikasi Telegram; data NARA aman |
| Google API kuota habis (429)                                       | Retry dengan backoff; antrean menumpuk tanpa kehilangan data                 |

### 4.7 Autentikasi dan Izin

- OAuth 2.0 dengan scope **sekecil mungkin**: akses ke kalender (bukan seluruh akun Google).
- Selama aplikasi hanya dipakai pemiliknya, mode OAuth "testing" dengan pengguna uji cukup; verifikasi aplikasi Google baru dipertimbangkan bila ada pengguna lain.
- `access_token` dan `refresh_token` disimpan **terenkripsi** (cast `encrypted` Laravel) di `integration_accounts`.
- Tombol "Putuskan Google" di Settings mencabut token dan menghentikan job. Event yang sudah ada di kalender "NARA" dibiarkan, tidak dihapus otomatis.

### 4.8 Indikator Status di UI

Setiap jadwal menampilkan ikon kecil: ✓ tersinkron, ⟳ menunggu, ⚠ gagal (klik untuk coba ulang). Header kalender menampilkan "Terakhir sinkron: 2 menit lalu". Pengguna selalu bisa melihat **apakah yang dilihatnya di NARA sama dengan di Google**.

### 4.9 Pengujian Sinkronisasi

Skenario uji ada di Bagian 9.15 dan Bagian 12. Wajib lolos sebelum fase 2 dinyatakan selesai.

---

## 5. Desain UI/UX & Konsep Visual 🔒

### 5.1 Fondasi Identitas

| Elemen      | Nilai                                                                                                                                  |
| ----------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| Konsep logo | Human–AI Connection: node, garis koneksi, orbit, ruang negatif; bukan "robot AI"                                                      |
| Warna       | **Deep Blue / Cyan** sebagai dasar + **Violet** sebagai aksen; futuristik tapi manusiawi                                   |
| Tipografi   | Satu keluarga sans-serif modern yang sama dengan ekosistem PT; kesan futuristik dibangun lewat bentuk UI, bukan font yang sulit dibaca |
| Kepribadian | Intelligent · Calm · Personal                                                                                                        |
| Tagline     | *Your Personal AI Agent* / *Intelligence That Works With You*                                                                      |

### 5.2 Prinsip UX

1. **Ketenangan di atas kepadatan.** Antarmuka terasa lapang, bukan spreadsheet yang dipindahkan ke web.
2. **Satu pertanyaan per layar.** Today menjawab "apa yang saya kerjakan sekarang?", Project menjawab "bagaimana progresnya?", Inbox menjawab "apa yang belum diproses?".
3. **Capture lebih mudah daripada mengelola.** Menambah catatan harus memerlukan satu ketukan dan satu ketikan.
4. **Jujur terhadap data.** Setiap angka (progres, beban) bisa diklik untuk melihat dasarnya.
5. **Mobile untuk menangkap, desktop untuk mengelola.** Capture dioptimalkan untuk HP (Telegram + web responsif); Kanban, Timeline, dan Matrix untuk layar lebar.
6. **AI selalu berupa saran** dengan tombol terima/tolak yang jelas (berlaku mulai Fase 4).
7. **Gesekan minimum saat menyelesaikan.** Menandai task selesai menawarkan satu klik untuk melampirkan output (D-16).

### 5.3 Peta Layar

```text
NARA
│
├── Today                  → KPI + jadwal hari ini + perlu perhatian + kapasitas
├── Inbox                  → capture & triase
├── Projects
│   ├── Table              (Fase 1)
│   ├── Kanban             (Fase 1)
│   ├── Calendar           (Fase 2, sinkron Google)
│   ├── Timeline           (Fase 2)
│   └── Matrix             (Fase 2)
├── Task Detail            → subtask, komentar, lampiran, tag, dependensi
├── Outputs                → daftar hasil karya nyata
├── Focus                  → timer sesi fokus per task
├── Weekly Review          → ritual mingguan
├── Assistant              (Fase 4) → percakapan berbasis data NARA
└── Settings               → Google, Telegram, kapasitas, notifikasi, WIP, keamanan
```

Urutan pengiriman view: **Today → Table → Kanban** (Fase 1), baru **Calendar → Timeline → Matrix** (Fase 2). Kolom `impact`/`urgency` project sudah tersimpan sejak Fase 0 sehingga Matrix tinggal "menyala" tanpa migrasi.

### 5.4 Wireframe Layar Kunci

#### Today (contoh data Bagian 9, Rabu 28 Oktober 2026)

```text
NARA — Rabu, 28 Oktober 2026

┌───────────────┬───────────────┬───────────────┬───────────────┐
│    HARI INI   │    PROGRES    │    OUTPUT     │   PERHATIAN   │
│ 3 jadwal      │ 3 task selesai│ 2 output      │ 1 terlambat   │
│ 1 terlambat   │ minggu ini    │ minggu ini    │ beban 90%     │
└───────────────┴───────────────┴───────────────┴───────────────┘

Kapasitas minggu ini  ██████████████████░░  90%  (22,5 dari 25 jam)  ● padat

Jadwal Hari Ini
09:00–12:00  Revisi bab Metode sesuai komentar reviewer      MSKI Revisi Jurnal   ✓
13:00–13:45  Kirim laporan bug Siakad ke klien   ⚠ terlambat Freelance Klien Siakad  ✓
15:30–17:00  Rancang migration Fase 0 NARA                   NARA 1.0             ✓

Perlu Perhatian
⚠ "Estimasi biaya perubahan fitur klien" jatuh tempo besok, belum dijadwalkan
⚠ Riset Joint Paper Robotika: belum ada aktivitas 6 hari
```

Empat kartu KPI bersifat tetap. Kartu "Perhatian" adalah tempat peringatan aturan (kapasitas, stagnasi, terlambat, sinkron gagal) muncul. Ikon ✓ di akhir baris jadwal adalah status sinkron Google.

#### Table View (Fase 1)

```text
Task                                    Project        Status       Prioritas  Tenggat   Est.
Revisi bab Metode sesuai komentar…      MSKI Revisi    in_progress  ● tinggi   30/10     180
Kirim laporan bug Siakad ke klien       Freelance      todo         ● tinggi   27/10 ⚠    45
Perbarui tabel hasil & grafik           MSKI Revisi    todo         ● tinggi   02/11     120
…
[Filter: status ▾] [Project ▾] [Prioritas ▾] [Tenggat ▾]   [+ Task]
```

Table adalah tampilan "pengganti spreadsheet". Kolom bisa diurutkan dan disaring; edit langsung di sel untuk status dan tenggat.

#### Kanban (Fase 1)

```text
TODO               IN PROGRESS        WAITING            DONE
┌──────────────┐   ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│ Perbarui     │   │ Revisi bab   │   │ Outline dari │   │ Baca komentar│
│ tabel hasil  │   │ Metode       │   │ rekan        │   │ reviewer     │
│ ● tinggi     │   │ ● tinggi     │   │ menunggu:    │   │              │
└──────────────┘   └──────────────┘   │ rekan peneliti│  └──────────────┘
                                      └──────────────┘
```

Lima status task menjadi lima kolom (`cancelled` disembunyikan secara default). Prioritas ditampilkan sebagai titik warna kecil, bukan lencana besar. Kartu `waiting` menampilkan **alasan menunggu**.

#### Calendar (Fase 2)

```text
◀ 26 Okt – 1 Nov 2026 ▶   [Hari][Minggu][Bulan]   Terakhir sinkron: 2 menit lalu
Beban minggu ini: 90% ● padat

        Sen        Sel        Rab        Kam        Jum        Sab
08:00  ▇ Baca kom.                                              ▇ Baca 3 paper
09:00                         ▇ Revisi   ▇ Perbarui ▇ Response
                              Metode     tabel       letter
13:00  ▇ Akta       ▇ Wire-   ▇ Laporan  ▇ NIB/OSS  ▇ Copy     ▇ CRUD Task
       checklist    frame     bug Siakad
15:30                         ▇ Migration
        ░░░ blok "sibuk" dari kalender lain (read-only)

Panel kiri: task belum dijadwalkan (seret ke kalender)
```

Fitur utama: seret task dari panel "belum dijadwalkan" ke kalender; seret tepi untuk mengubah durasi; blok abu-abu menunjukkan waktu sibuk dari kalender lain; banner kapasitas selalu terlihat.

#### Timeline (Fase 2)

```text
                 Okt                       Nov
                 26 27 28 29 30 31  1  2 3 4 5 6 ...
MSKI Revisi      ███████████████████████████████ ▲ 06/11
Pendirian PT     ░░░░████████████████████████ ▲ 06/11
Landing Page     ███░░░░░░░░░░░░░░░░░░░░░░░░ ▲ 11/11
NARA 1.0         ████████ …
```

Satu baris per project, rentang dari `start_date` ke `due_date`, diisi sesuai progres. Task bisa dibuka dengan klik. Garis vertikal menandai "hari ini".

#### Matrix (Fase 2)

```text
                  URGEN                         TIDAK URGEN
            ┌──────────────────────┬───────────────────────────┐
 PENTING    │ KERJAKAN              │ JADWALKAN                  │
            │ Revisi bab Metode     │ Perbarui tabel hasil       │
            │ Kirim laporan bug     │ Tulis response letter …    │
            ├──────────────────────┼───────────────────────────┤
 TIDAK      │ CEPATKAN/DELEGASIKAN  │ SUATU SAAT                 │
 PENTING    │ Estimasi biaya klien  │ Copy landing page …        │
            └──────────────────────┴───────────────────────────┘
Task berstatus `waiting` ditampilkan di lajur terpisah di bawah.
```

Kuadran dihitung otomatis oleh aturan di Bagian 7.6. Pengguna tidak mengisi urgency/importance task secara manual.

#### Task Detail

```text
Task: Revisi bab Metode sesuai komentar reviewer
Project: MSKI Revisi Jurnal       Status: in_progress     Prioritas: tinggi
Estimasi: 180 menit   Aktual: 45 menit (dari sesi fokus)  Tenggat: 30/10
Kuadran: KERJAKAN (otomatis)

Subtask
☑ Petakan komentar reviewer
☐ Tulis ulang paragraf desain eksperimen
☐ Perbarui rujukan

Jadwal: Rab 28/10 09:00–12:00 ✓ tersinkron
Komentar · Lampiran · Tag · Dependensi · Riwayat aktivitas
```

#### Saat Menandai Selesai (Pencatatan Output Satu Klik)

```text
✓ Task selesai: "Susun wireframe landing page"

Ada hasil yang bisa dicatat?
[ Tempel tautan / unggah berkas ]   Jenis: [prototype ▾]
[ Catat output ]   [ Lewati ]
```

Dialog ini muncul sekali, singkat, dan **boleh dilewati**. Jenis output diisi otomatis dari kategori task bila memungkinkan. Bila dialog ini sering dilewati (lihat metrik Bagian 14), itu sinyal untuk menyederhanakan lagi, bukan untuk memaksa.

#### Inbox / Quick Capture

```text
+ Tambah catatan cepat…                      [Enter]

Menunggu (4)
─ Cek harga domain baru                         via Telegram   [Task] [Ide] [Buang]
─ Ide: fitur voice note di NARA                 via Telegram   [Task] [Ide] [Buang]
─ Follow up klien X minggu depan                via web        [Task] [Ide] [Buang]
```

Inbox adalah katup pelepas: semua masuk dulu tanpa klasifikasi. Triase dilakukan nanti, idealnya 5 menit per hari (Bagian 7.9).

#### Weekly Review

```text
Weekly Review — 26 Okt – 1 Nov 2026

Ringkasan otomatis
  Selesai: 3 task (285 menit)    Output: 2    Beban terencana: 90%
  Project tanpa aktivitas > 5 hari: Riset Joint Paper Robotika
  Inbox belum diproses: 4

Top 3 prioritas minggu depan   1. …  2. …  3. …
Refleksi (opsional, 1–3 kalimat)  …
Keputusan: [ ] pause project [ ] turunkan WIP [ ] tambah jam
```

Review dirancang selesai dalam **≤ 15 menit**. Ringkasan terisi otomatis; pengguna hanya memilih tiga prioritas dan menulis refleksi singkat.

#### Focus

Timer sederhana yang terhubung ke task: Mulai → Selesai → durasi tersimpan sebagai `focus_sessions` dan menambah `actual_minutes`. Tidak ada gamifikasi.

#### Assistant (Fase 4)

```text
Anda: Besok pagi saya mau kerjakan MSKI 2 jam
NARA: Sudah saya jadwalkan "Perbarui tabel hasil" besok 09:00–11:00 dan
      disinkronkan ke Google Calendar. Ada yang ingin diubah?
```

Nada: singkat, jelas, selalu mengonfirmasi tindakan yang baru dilakukan.

### 5.5 Komponen dan Aksesibilitas

- Satu **design token system** (warna, spasi, radius, tipografi) dipakai di web dan di pesan Telegram (emoji dan format konsisten).
- Status selalu **warna + label teks**, tidak hanya warna.
- Aksi destruktif (hapus project, putuskan integrasi) meminta konfirmasi dan tercatat di `activity_logs`.
- Target responsivitas: aksi lokal (ubah status, seret jadwal) terasa instan, kurang dari 200 ms.
- Mode gelap disediakan sejak awal (token warna sudah mendukung), tetapi bukan prioritas Fase 1.
- Layar kecil: Today, Inbox, dan Task Detail wajib nyaman di HP; Kanban/Timeline/Matrix boleh hanya baca di HP.

### 5.6 Strategi Implementasi UI

| Kebutuhan                                               | Pendekatan                                                                                                                 |
| ------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| CRUD Life Domain, Goal, Project, Task, Output, Settings | **Panel native**: controller + FormRequest + view Blade dengan komponen `DataTable` dan `FormField` (Bagian 3.7) |
| Today, Weekly Review, Focus                             | Halaman Blade kustom + Alpine.js                                                                                           |
| Kanban                                                  | Komponen Blade/Alpine + SortableJS (Livewire bila diperlukan)                                                              |
| Calendar                                                | **FullCalendar** (edisi standar) dengan sumber data dari endpoint NARA                                               |
| Timeline                                                | Komponen gantt ringan (mis. Frappe Gantt) atau implementasi CSS grid sendiri                                               |
| Matrix                                                  | CSS grid 2×2 + drag-and-drop opsional                                                                                     |

Pilihan pustaka final dikunci saat memasuki fase masing-masing, setelah prototipe kecil. Bila pustaka tidak cocok, implementasi sendiri tetap kecil karena datanya sederhana.

---

## 6. Rancangan Database (MySQL 8) 🔒

### 6.1 Prinsip Skema

1. **Per fase.** Hanya tabel yang dipakai fase berjalan yang dimigrasikan (D-08). Tabel fase berikutnya sudah dirancang di sini, tetapi migration-nya ditulis saat fasenya tiba.
2. **Engine dan charset:** InnoDB, `utf8mb4`, collation `utf8mb4_0900_ai_ci`.
3. **Kunci:** `bigint unsigned` auto-increment. Waktu disimpan sebagai `timestamp` UTC; tanggal murni sebagai `date`.
4. **Enum:** disimpan sebagai `varchar` dan dikunci di aplikasi lewat PHP backed enum (lebih mudah diubah daripada `ENUM` MySQL).
5. **Hapus:** `softDeletes` untuk `projects`, `tasks`, `outputs`. Tabel log tidak pernah dihapus.
6. **Indeks:** setiap FK berindeks; indeks gabungan untuk kueri berulang (lihat 6.10).
7. **JSON:** hanya untuk payload fleksibel (`payload`, `before_value`, `after_value`, `settings`). Data yang difilter atau diurutkan harus berupa kolom biasa.

### 6.2 Diagram Relasi

```text
users
  │
  ├── life_domains ──► goals ──► projects ──► tasks ──► task_subtasks
  │                                  │           │
  │                                  │           ├── task_dependencies   (Fase 2)
  │                                  │           ├── task_comments       (Fase 1)
  │                                  │           ├── schedules ──► calendar_events
  │                                  │           ├── focus_sessions
  │                                  │           └── outputs ──► output_files
  │                                  └── project_members                 (ditunda)
  ├── inbox_items
  ├── notifications ──► notification_logs
  ├── integrations (token OAuth terenkripsi)
  ├── busy_blocks         (cache event kalender lain, Fase 2)
  ├── settings
  ├── tags ──► task_tags
  └── activity_logs       (polimorfik)

— Fase 3 —  weekly_reviews, project_health_snapshots
— Fase 4 —  ai_conversations ► ai_messages, ai_commands, ai_recommendations, embeddings_index
— Fase 5 —  agent_runs ► agent_actions, agent_permissions
```

### 6.3 Tabel Inti — Fase 0 sampai 2

**`users`**

| Kolom                                         | Tipe             | Keterangan                                     |
| --------------------------------------------- | ---------------- | ---------------------------------------------- |
| id                                            | bigint PK        |                                                |
| name, email (unique), password                | varchar          |                                                |
| timezone                                      | varchar          | default `Asia/Jakarta`                       |
| telegram_chat_id                              | varchar nullable | allowlist: hanya chat id ini yang dilayani bot |
| two_factor_secret / two_factor_recovery_codes | text nullable    | terenkripsi (2FA sejak Fase 0)                 |
| created_at / updated_at                       | timestamp        |                                                |

**`life_domains`**: id, user_id (FK), name, color, sort_order.

**`goals`**

| Kolom            | Tipe          | Keterangan                        |
| ---------------- | ------------- | --------------------------------- |
| id               | bigint PK     |                                   |
| life_domain_id   | FK            |                                   |
| title            | varchar       |                                   |
| description      | text nullable |                                   |
| target_date      | date nullable | boleh kosong                      |
| status           | varchar       | `active, achieved, abandoned`   |
| progress_percent | tinyint       | turunan dari project (Bagian 7.2) |

**`projects`**

| Kolom                 | Tipe               | Keterangan                                                                                                               |
| --------------------- | ------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| id                    | bigint PK          |                                                                                                                          |
| user_id               | FK                 |                                                                                                                          |
| goal_id               | FK nullable        | project boleh berdiri sendiri                                                                                            |
| name                  | varchar            |                                                                                                                          |
| description           | text nullable      |                                                                                                                          |
| category              | varchar            | `feature, improvement, bug, research, learning, admin, personal, client, idea, maintenance` (kolom biasa, bukan tabel) |
| origin                | varchar            | `personal, external, freelance, research, academic`                                                                    |
| status                | varchar            | `idea, active, paused, done, archived`                                                                                 |
| wip_slot              | varchar nullable   | `academic, business, personal`; wajib bila `active`                                                                  |
| impact                | varchar            | `high, medium, low` (default `medium`)                                                                               |
| urgency               | varchar            | `high, medium, low` (default `medium`)                                                                               |
| start_date / due_date | date nullable      |                                                                                                                          |
| progress_percent      | tinyint            | dihitung otomatis                                                                                                        |
| definition_of_done    | text nullable      | daftar singkat (markdown)                                                                                                |
| last_activity_at      | timestamp nullable | untuk deteksi stagnasi                                                                                                   |
| deleted_at            | timestamp nullable |                                                                                                                          |

**`tasks`**

| Kolom             | Tipe                       | Keterangan                                      |
| ----------------- | -------------------------- | ----------------------------------------------- |
| id                | bigint PK                  |                                                 |
| project_id        | FK                         |                                                 |
| title             | varchar                    |                                                 |
| description       | text nullable              |                                                 |
| status            | varchar                    | `todo, in_progress, waiting, done, cancelled` |
| waiting_reason    | varchar nullable           | wajib bila `waiting`                          |
| priority          | varchar                    | `high, normal, low` (default `normal`)      |
| estimated_minutes | smallint unsigned nullable | dasar kapasitas                                 |
| actual_minutes    | smallint unsigned nullable | dari `focus_sessions` atau manual             |
| due_date          | date nullable              |                                                 |
| completed_at      | timestamp nullable         |                                                 |
| sort_order        | int                        | urutan di Kanban                                |
| deleted_at        | timestamp nullable         |                                                 |

> Task **tidak** menyimpan `impact`, `urgency`, atau `progress_percent`. Kepentingan dan urgensi diturunkan (Bagian 7.6); progres task cukup dari status dan subtask.

**`task_subtasks`**: id, task_id, title, is_done, sort_order.
**`task_comments`** (Fase 1): id, task_id, body, created_at.
**`tags`**: id, user_id, name, color. **`task_tags`**: task_id, tag_id.
**`task_attachments`** (Fase 1): id, task_id, file_path, file_name, mime_type, size_bytes, uploaded_at.
**`task_dependencies`** (Fase 2, pemicu: butuh minimal 3 kasus nyata): id, task_id, depends_on_task_id.

**`schedules`**

| Kolom             | Tipe             | Keterangan                                   |
| ----------------- | ---------------- | -------------------------------------------- |
| id                | bigint PK        |                                              |
| task_id           | FK               | satu task boleh punya beberapa jadwal (sesi) |
| start_at / end_at | timestamp        | UTC                                          |
| source            | varchar          | `nara, google, telegram, ai`               |
| note              | varchar nullable |                                              |

**`calendar_events`** (cermin sisi Google)

| Kolom              | Tipe               | Keterangan                  |
| ------------------ | ------------------ | --------------------------- |
| id                 | bigint PK          |                             |
| schedule_id        | FK unique          |                             |
| google_calendar_id | varchar            | id kalender "NARA"          |
| google_event_id    | varchar            |                             |
| etag               | varchar nullable   |                             |
| sync_status        | varchar            | `pending, synced, failed` |
| last_synced_at     | timestamp nullable |                             |
| last_error         | text nullable      |                             |
| retry_count        | tinyint            |                             |

**`busy_blocks`** (Fase 2): id, user_id, google_calendar_id, google_event_id, start_at, end_at, fetched_at. Cache baca-saja untuk ConflictDetector.

**`outputs`**

| Kolom        | Tipe             | Keterangan                                                                                                                                                                                                   |
| ------------ | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| id           | bigint PK        |                                                                                                                                                                                                              |
| project_id   | FK nullable      |                                                                                                                                                                                                              |
| task_id      | FK nullable      |                                                                                                                                                                                                              |
| title        | varchar          |                                                                                                                                                                                                              |
| output_type  | varchar          | `github, pdf, paper, prototype, website, article, dataset, presentation, video, circuit, pcb, software, documentation, business_doc, legal_doc, physical_prototype, certificate, teaching_material, other` |
| evidence_url | varchar nullable |                                                                                                                                                                                                              |
| produced_at  | date             |                                                                                                                                                                                                              |
| note         | varchar nullable |                                                                                                                                                                                                              |

**`output_files`**: id, output_id, file_path, file_name.

**`inbox_items`**

| Kolom               | Tipe               | Keterangan                                                                               |
| ------------------- | ------------------ | ---------------------------------------------------------------------------------------- |
| id                  | bigint PK          |                                                                                          |
| user_id             | FK                 |                                                                                          |
| content             | text               | catatan mentah                                                                           |
| source              | varchar            | `web, telegram, google, ai`                                                            |
| hint                | varchar nullable   | `task, idea`: petunjuk dari `/add` atau `/idea` untuk default saat triase          |
| telegram_message_id | varchar nullable   | mencegah duplikasi webhook                                                               |
| status              | varchar            | `pending, converted, discarded`                                                        |
| converted_to_type   | varchar nullable   | `project, task, goal` ("Someday" disimpan sebagai project `idea` bertag `someday`) |
| converted_to_id     | bigint nullable    |                                                                                          |
| processed_at        | timestamp nullable |                                                                                          |

**`notifications`** dan **`notification_logs`**

| Tabel                 | Kolom penting                                                                                                                                                                      |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `notifications`     | id, user_id, type (`reminder, daily_briefing, evening_summary, capacity_alert, stagnation_alert, sync_failed, weekly_report`), payload (json), scheduled_at, dedupe_key (unique) |
| `notification_logs` | id, notification_id, channel (`telegram, web`), status (`sent, failed`), error, sent_at                                                                                        |

`dedupe_key` mencegah notifikasi yang sama terkirim dua kali (mis. `reminder:task:12:2026-10-29`).

**`integrations`**: id, user_id, provider (`google_calendar, telegram`), is_active, access_token (terenkripsi), refresh_token (terenkripsi), expires_at, sync_token (untuk incremental sync), meta (json, mis. id kalender "NARA").

**`settings`**: id, user_id, key (unique per user), value (json). Daftar kunci di 6.7.

**`activity_logs`** (polimorfik)

| Kolom                      | Tipe             | Keterangan                                                                                 |
| -------------------------- | ---------------- | ------------------------------------------------------------------------------------------ |
| id                         | bigint PK        |                                                                                            |
| user_id                    | FK               |                                                                                            |
| subject_type / subject_id  | varchar / bigint | model yang berubah                                                                         |
| event                      | varchar          | `created, updated, status_changed, scheduled, completed, output_added, sync_failed, ...` |
| before_value / after_value | json nullable    |                                                                                            |
| source                     | varchar          | `web, telegram, google, system, ai`                                                      |
| created_at                 | timestamp        |                                                                                            |

**`focus_sessions`**: id, task_id, started_at, ended_at, duration_minutes.

### 6.4 Lapisan Fase 3 — Analitik

| Tabel                        | Kolom penting                                                                                                                                                   | Pemicu dibuat                                              |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `weekly_reviews`           | id, user_id, week_start (date, unique per user), top_priorities (json), reflection, completed_task_count, completed_minutes, output_count, planned_load_percent | Fase 2 selesai: review mingguan tertulis dipakai ≥ 3 kali |
| `project_health_snapshots` | id, project_id, snapshot_date, days_since_activity, progress_percent, health (`healthy, slowing, stagnant`)                                                   | Fase 3 dimulai                                             |

Kapasitas mingguan **tidak** memerlukan tabel `workload_estimates` di 1.0; dihitung langsung dari `tasks` dan `schedules` oleh `CapacityService`.

### 6.5 Lapisan Fase 4 — AI 🌱

| Tabel                  | Kolom penting                                                                                                                               |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `ai_conversations`   | id, user_id, title, created_at                                                                                                              |
| `ai_messages`        | id, conversation_id, role (`user, assistant`), content, token_count, created_at                                                           |
| `ai_commands`        | id, user_id, raw_text, intent, payload (json), status (`parsed, confirmed, executed, rejected`), executed_at                              |
| `ai_recommendations` | id, user_id, type (`daily_plan, capacity, stagnation, output_gap`), payload (json), status (`pending, accepted, dismissed`), created_at |
| `embeddings_index`   | id, subject_type, subject_id, content_hash, vector_id (id di Qdrant), updated_at                                                            |

### 6.6 Lapisan Fase 5 — Agent 🌱

| Tabel                 | Kolom penting                                                                                                                   |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `agent_runs`        | id, user_id, goal_text, status (`planning, waiting_approval, running, completed, failed, cancelled`), started_at, finished_at |
| `agent_actions`     | id, agent_run_id, step_order, action_type, input (json), output (json), requires_approval, approved_at, executed_at, error      |
| `agent_permissions` | id, user_id, action_type (unique per user), auto_allowed (default `false`)                                                    |

### 6.7 Kunci `settings`

| Kunci                            | Default                                                             | Keterangan                                                 |
| -------------------------------- | ------------------------------------------------------------------- | ---------------------------------------------------------- |
| `weekly_capacity_minutes`      | 1500                                                                | 25 jam fokus per minggu                                    |
| `capacity_warn_percent`        | 80                                                                  | ambang "padat"                                             |
| `capacity_over_percent`        | 100                                                                 | ambang "overload"                                          |
| `wip_limit_per_slot`           | 3                                                                   | maksimal project `active` per slot                       |
| `stagnation_days`              | 5                                                                   | ambang stagnan (hari tanpa aktivitas)                      |
| `urgent_within_days`           | 3                                                                   | ambang "urgen" untuk Matrix                                |
| `briefing_time`                | `07:00`                                                           | briefing pagi Telegram                                     |
| `evening_summary_time`         | `20:30`                                                           | ringkasan malam                                            |
| `weekly_review_day_time`       | `Sun 19:00`                                                       | pengingat review                                           |
| `default_reminder_minutes`     | 30                                                                  | pengingat sebelum jadwal                                   |
| `inbox_remind_hours`           | 48                                                                  | pengingat inbox menumpuk                                   |
| `overdue_grace_days`           | 0                                                                   |                                                            |
| `time_of_day_defaults`         | `{"pagi":"09:00","siang":"13:00","sore":"15:30","malam":"19:00"}` | pemetaan kata waktu untuk perintah bahasa natural (Fase 4) |
| `nara_budget_minutes_per_week` | 360                                                                 | anggaran pengembangan NARA (Bagian 14)                     |
| `admin_budget_minutes_per_day` | 10                                                                  | batas administrasi NARA per hari                           |

### 6.8 Contoh Migration (Fase 0, ringkas)

```php
Schema::create('projects', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->constrained()->cascadeOnDelete();
    $t->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
    $t->string('name');
    $t->text('description')->nullable();
    $t->string('category')->default('feature');
    $t->string('origin')->default('personal');
    $t->string('status')->default('idea');
    $t->string('wip_slot')->nullable();
    $t->string('impact')->default('medium');
    $t->string('urgency')->default('medium');
    $t->date('start_date')->nullable();
    $t->date('due_date')->nullable();
    $t->unsignedTinyInteger('progress_percent')->default(0);
    $t->text('definition_of_done')->nullable();
    $t->timestamp('last_activity_at')->nullable();
    $t->softDeletes();
    $t->timestamps();
    $t->index(['user_id', 'status', 'wip_slot']);
});
```

### 6.9 Catatan Pencatatan Aktivitas

`ActivityLogger` dipanggil oleh service (bukan observer di mana-mana) agar `source` dan konteks jelas. Event wajib dicatat: perubahan status, penjadwalan, penyelesaian, output ditambahkan, perubahan WIP, kegagalan sinkron. Tabel ini adalah dasar laporan mingguan dan, kelak, konteks bagi AI.

### 6.10 Indeks dan Kueri Utama

| Kueri                                | Indeks                                                   |
| ------------------------------------ | -------------------------------------------------------- |
| Task per project per status (Kanban) | `tasks (project_id, status, sort_order)`               |
| Task jatuh tempo (Today, Matrix)     | `tasks (status, due_date)`                             |
| Jadwal per rentang waktu (Calendar)  | `schedules (start_at, end_at)`                         |
| Inbox menunggu                       | `inbox_items (user_id, status, created_at)`            |
| Stagnasi                             | `projects (status, last_activity_at)`                  |
| Notifikasi antrean                   | `notifications (scheduled_at)` + unique `dedupe_key` |
| Log per entitas                      | `activity_logs (subject_type, subject_id, created_at)` |
| Busy block                           | `busy_blocks (user_id, start_at, end_at)`              |

### 6.11 Data yang Tidak Disimpan

NARA tidak menyimpan isi email, kata sandi pihak ketiga, atau percakapan Telegram di luar catatan yang Anda kirim ke bot. Pesan Telegram yang bukan perintah diabaikan dan tidak dicatat.

---

## 7. Aturan Bisnis 🔒

### 7.1 Status Task

```text
todo ─► in_progress ─► done
  │          │
  │          └─► waiting ─► in_progress / todo
  └──────────────► cancelled
done ─► todo / in_progress   (dibuka kembali, tercatat di log)
```

- `waiting` **wajib** memiliki `waiting_reason` (mis. "menunggu draft rekan peneliti").
- `done` mengisi `completed_at`; membuka kembali mengosongkannya.
- `cancelled` dikecualikan dari semua perhitungan (beban, progres, Matrix).
- Mengubah task menjadi `in_progress` memperbarui `project.last_activity_at`.

### 7.2 Perhitungan Progres

**Bobot task** = `max(estimated_minutes, 30)`. Task `cancelled` tidak dihitung.

```text
progres project = Σ bobot task done / Σ bobot semua task non-cancelled × 100  (dibulatkan)
progres goal    = rata-rata progres project aktif di bawahnya
```

Project tanpa task berprogres 0%. Progres selalu bisa diklik untuk melihat daftar task penyusunnya. Pengguna tidak mengetik angka progres secara manual.

### 7.3 Status Project dan Batas WIP

| Status       | Arti                                                                   |
| ------------ | ---------------------------------------------------------------------- |
| `idea`     | Gagasan, belum dikerjakan (menggantikan `incubator` dan `planned`) |
| `active`   | Sedang dikerjakan; **wajib** memiliki `wip_slot`               |
| `paused`   | Ditunda dengan sengaja                                                 |
| `done`     | Definition of Done terpenuhi                                           |
| `archived` | Disimpan, tidak tampil di tampilan default                             |

**Aturan WIP (`WipGuard`):** maksimal **3 project `active` per `wip_slot`** (`academic`, `business`, `personal`). Mencoba mengaktifkan project keempat di slot yang sama **ditolak** dengan pesan yang menawarkan: jadikan salah satu `paused`, atau pindahkan project ini ke `idea`. Aturan ini sengaja keras karena ia adalah pagar utama melawan overkomitmen.

### 7.4 Kapasitas Mingguan

```text
Beban terencana minggu = realisasi + rencana
  realisasi = Σ actual_minutes task selesai pada minggu ini (bila kosong, pakai estimasi)
  rencana   = Σ estimated_minutes task (todo / in_progress) yang
              (a) punya jadwal minggu ini, ATAU
              (b) jatuh tempo minggu ini dan belum dijadwalkan
              (setiap task dihitung sekali)
Persentase = beban terencana / weekly_capacity_minutes × 100
```

| Persentase | Label    | Warna  |
| ---------- | -------- | ------ |
| < 80%      | longgar  | hijau  |
| 80–100%   | padat    | kuning |
| > 100%     | overload | merah  |

Saat overload, banner menampilkan tiga tindakan yang disarankan: *geser tenggat*, *jadikan project `paused`*, atau *turunkan prioritas task*. Aturan ini adalah **pengganti sederhana** dari analitik beban Fase 3.

### 7.5 Penjadwalan

- Jadwal tidak boleh berakhir sebelum mulai; durasi minimal 15 menit, kelipatan 15.
- Menyimpan jadwal yang bentrok dengan jadwal NARA lain atau `busy_blocks` menampilkan peringatan; pengguna boleh melanjutkan.
- Satu task boleh dipecah menjadi beberapa jadwal (sesi). Estimasi task tidak berubah.
- Menghapus jadwal tidak mengubah status task.
- Task `done` atau `cancelled` tidak bisa dijadwalkan baru.

### 7.6 Penurunan Urgensi dan Kepentingan (Matrix)

```text
urgen   = overdue ATAU due_date ≤ hari ini + urgent_within_days (default 3)
penting = task.priority = high
          ATAU (project.impact = high DAN task.priority ≠ low)
```

|                         | Urgen                            | Tidak urgen          |
| ----------------------- | -------------------------------- | -------------------- |
| **Penting**       | **Kerjakan**               | **Jadwalkan**  |
| **Tidak penting** | **Cepatkan / Delegasikan** | **Suatu saat** |

Task `waiting`, `done`, `cancelled` tidak masuk Matrix; `waiting` ditampilkan terpisah. Task tanpa tenggat tidak urgen.

### 7.7 Deteksi Stagnasi (Aturan, Fase 1)

Project `active` ditandai **stagnan** bila `now − last_activity_at > stagnation_days` (default 5 hari). `last_activity_at` diperbarui oleh: perubahan status task, task selesai, output ditambahkan, sesi fokus, komentar. Peringatan muncul di kartu "Perhatian" dan tidak dikirim berulang lebih dari sekali per hari.

### 7.8 Reminder dan Notifikasi

| Jenis                | Waktu                    | Isi                                            |
| -------------------- | ------------------------ | ---------------------------------------------- |
| Pengingat jadwal     | 30 menit sebelum mulai   | judul task, project                            |
| Briefing pagi        | `briefing_time`        | jadwal hari ini, tenggat, terlambat, kapasitas |
| Ringkasan malam      | `evening_summary_time` | yang selesai, yang bergeser, inbox menunggu    |
| Pengingat review     | Minggu 19:00             | tautan ke Weekly Review                        |
| Peringatan kapasitas | saat melewati ambang     | persentase + tindakan                          |
| Sinkron gagal        | saat gagal permanen      | tautan ke panel perhatian                      |

**Pembatas:** maksimal **8 notifikasi per hari**; di atas itu digabung ke dalam ringkasan. Jam tenang 22:00–06:30 (kecuali pengingat jadwal yang memang jatuh di jam itu). Setiap notifikasi memiliki `dedupe_key`.

### 7.9 Inbox dan Triase

- Item inbox menjadi **Task**, **Project (idea)**, **Goal**, **Someday** (disimpan sebagai project `idea` berprioritas rendah dengan tag `someday`), atau **Dibuang**.
- Item yang dikirim dengan `/idea` memiliki `hint = idea` dan default triase "Project (idea)"; `/add` memiliki `hint = task`.
- Inbox yang menumpuk lebih dari `inbox_remind_hours` memicu satu pengingat lembut. Triase ditargetkan **maksimal 5 menit per hari**.

### 7.10 Output

- Output boleh terkait project, task, keduanya, atau berdiri sendiri.
- Output memperbarui `last_activity_at` project.
- Kartu "Output" menghitung output minggu berjalan. Weekly Review menampilkan **rasio waktu tercatat terhadap output** (misalnya "6 jam pada Robotika, 0 output") sebagai bahan refleksi, bukan penilaian.

### 7.11 Aturan Waktu dan Zona

Minggu dimulai **Senin**. "Hari ini" mengikuti zona waktu pengguna. Perhitungan "jatuh tempo besok" memakai tanggal lokal, bukan UTC.

### 7.12 Penghapusan dan Arsip

Menghapus project meng-soft-delete task di dalamnya. Output **tidak** ikut terhapus (menjadi output tanpa project). Project `done` dan `archived` tidak dihitung dalam WIP maupun kapasitas.

---

## 8. Telegram Bot 🔒

### 8.1 Peran

Telegram adalah **kanal capture dan notifikasi**, bukan tempat mengelola. Bot dibangun di **Fase 1** karena ia fitur termurah dengan manfaat paling langsung untuk ide yang datang di sela aktivitas (D-13).

### 8.2 Perintah (Fase 1–3)

| Perintah         | Fungsi                                    | Contoh                             |
| ---------------- | ----------------------------------------- | ---------------------------------- |
| `/add <teks>`  | Simpan ke Inbox dengan `hint = task`    | `/add Cek harga domain baru`     |
| `/idea <teks>` | Simpan ke Inbox dengan `hint = idea`    | `/idea Fitur voice note di NARA` |
| teks biasa       | Diperlakukan sebagai `/add`             | `follow up klien X minggu depan` |
| `/today`       | Jadwal hari ini, terlambat, kapasitas     |                                    |
| `/week`        | Ringkasan minggu: beban, selesai, tenggat |                                    |
| `/done <id>`   | Tandai task selesai (id dari `/today`)  | `/done 5`                        |
| `/cap`         | Persentase kapasitas minggu ini           |                                    |
| `/inbox`       | Jumlah dan 5 item terbaru yang menunggu   |                                    |
| `/help`        | Daftar perintah                           |                                    |

Perintah bahasa natural (mis. "jadwalkan MSKI besok pagi 2 jam") **tidak ada di Fase 1–3**; itu masuk Fase 4 (Bagian 11).

### 8.3 Contoh Percakapan

```text
Anda:  /add Cek harga domain baru
NARA:  ✓ Masuk Inbox (#4). 4 item menunggu.

Anda:  /idea Fitur voice note di NARA
NARA:  ✓ Ide disimpan di Inbox (#5).

Anda:  /today
NARA:  📅 Rabu, 28 Okt
       09:00–12:00  Revisi bab Metode sesuai komentar reviewer
       13:00–13:45  Kirim laporan bug Siakad ke klien  ⚠ terlambat
       15:30–17:00  Rancang migration Fase 0 NARA
       Kapasitas minggu ini 90% (padat)

Anda:  /done 5
NARA:  ✓ "Kirim laporan bug Siakad ke klien" selesai.
       Ada hasil yang mau dicatat?  [Catat output] [Lewati]
```

### 8.4 Pesan Terjadwal

**Briefing pagi (07:00)**

```text
☀️ Selamat pagi — Rabu, 28 Okt
Hari ini: 3 jadwal (5 jam 15 menit)
Terlambat: 1 task
Tenggat dekat: Estimasi biaya perubahan fitur klien (besok)
Kapasitas minggu ini: 90% (padat)
```

**Pengingat jadwal (30 menit sebelum)**

```text
⏰ 08:30 — Revisi bab Metode sesuai komentar reviewer (MSKI Revisi Jurnal)
[Mulai fokus] [Geser 30 mnt] [Lewati]
```

**Ringkasan malam (20:30)**

```text
🌙 Ringkasan hari ini
Selesai: 2 task
Belum selesai: 1 → digeser ke besok? [Ya] [Atur sendiri]
Inbox menunggu: 4
```

**Peringatan kapasitas**

```text
⚠️ Beban minggu ini 106% (melebihi kapasitas)
Saran: [Geser tenggat] [Jeda satu project] [Turunkan prioritas]
```

### 8.5 Tombol Inline

Tombol (callback) dipakai untuk aksi cepat: `Mulai fokus`, `Geser`, `Catat output`, `Ya/Tidak`. Semua callback membawa `id` entitas dan **kedaluwarsa setelah 24 jam**. Callback yang kedaluwarsa dijawab "Pesan ini sudah lama; buka NARA untuk melihat keadaan terbaru."

### 8.6 Keamanan

1. Webhook memakai **secret token** Telegram dan URL yang tidak mudah ditebak.
2. **Allowlist:** hanya `users.telegram_chat_id` yang dilayani. Pesan dari chat lain dibalas diam (diabaikan) dan dicatat di log.
3. Duplikasi diblok lewat `telegram_message_id` (webhook bisa terkirim ulang).
4. Rate limit: maksimal 30 pesan masuk per menit; pesan balasan menghormati batas Telegram.
5. Pesan non-perintah panjang (> 1.000 karakter) dipotong saat disimpan ke Inbox, dengan catatan "dipotong".

### 8.7 Penanganan Kegagalan

- Gagal mengirim ke Telegram: retry dengan backoff, `notification_logs.status = failed` setelah percobaan terakhir. Kegagalan Telegram **tidak** memengaruhi data.
- Bot tidak boleh pernah menghapus data lewat perintah. Aksi hapus hanya dari web.

---

## 9. Contoh Data (Seed) & Skenario 🔒

### 9.1 Tujuan

Bagian ini menggantikan impor spreadsheet (D-07). Fungsinya:

1. **Bahan `DemoSeeder`:** database lokal dan uji otomatis bisa dibangun ulang kapan saja.
2. **Tolok ukur perhitungan:** angka progres, Matrix, dan kapasitas di bawah ini adalah *jawaban benar* yang dipakai uji (Bagian 12).
3. **Bahan latihan UI:** setiap layar di Bagian 5 bisa dibangun dengan data nyata.

**Tanggal acuan:** "hari ini" = **Rabu, 28 Oktober 2026**. Minggu berjalan = Senin 26 Okt – Minggu 1 Nov. `DemoSeeder` memakai tanggal **relatif** terhadap `now()` agar data selalu segar; tabel di bawah memakai tanggal absolut hanya agar mudah dibaca.

### 9.2 Pengguna dan Life Domain

| Entitas     | Isi                                                                                                                                           |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| User        | Imam (`Asia/Jakarta`), `telegram_chat_id` terisi                                                                                          |
| Life Domain | **Karier & Akademik** (biru), **Bisnis** (violet), **Teknologi & Belajar** (cyan), **Kesehatan** (hijau, kosong dulu) |

### 9.3 Goal

| ID | Domain              | Goal                                   | Target     | Project        |
| -- | ------------------- | -------------------------------------- | ---------- | -------------- |
| G1 | Karier & Akademik   | Publikasi ilmiah tahun ini             | 31/12/2026 | P1, P2         |
| G2 | Bisnis              | PT berdiri dan memiliki produk pertama | 30/06/2027 | P3, P4, P5, P8 |
| G3 | Teknologi & Belajar | Fondasi sistem pribadi dan elektronika | —         | P6, P7, P9     |

### 9.4 Project

| ID | Project                          | Kategori | Status | Slot WIP | Impact | Urgency | Mulai | Tenggat | Aktivitas terakhir |
| -- | -------------------------------- | -------- | ------ | -------- | ------ | ------- | ----- | ------- | ------------------ |
| P1 | MSKI Revisi Jurnal               | research | active | academic | high   | high    | 05/10 | 06/11   | 28/10              |
| P2 | Riset Joint Paper Robotika       | research | active | academic | high   | medium  | 12/10 | 30/11   | **22/10**    |
| P3 | Pendirian PT Imfar Manggala Nara | admin    | active | business | high   | medium  | 01/10 | 30/11   | 26/10              |
| P4 | Freelance Klien Siakad           | client   | active | business | medium | high    | 14/10 | 13/11   | 27/10              |
| P5 | Landing Page Manggala Academy    | feature  | active | business | medium | medium  | 19/10 | 11/11   | 27/10              |
| P6 | NARA 1.0                         | feature  | active | personal | high   | medium  | 20/10 | 31/12   | 28/10              |
| P7 | Belajar Kelistrikan Dasar        | learning | active | personal | low    | low     | 10/10 | —      | 25/10              |
| P8 | Bimbel Sendiri                   | idea     | idea   | —       | high   | low     | —    | —      | 20/10              |
| P9 | Robot Line Follower              | idea     | idea   | —       | medium | low     | —    | —      | 18/10              |

Jumlah project `active` per slot: academic 2, **business 3 (penuh)**, personal 2. Ini dipakai oleh skenario WIP di 9.12.

### 9.5 Task

Seluruh 21 task di bawah dipakai oleh Today, Table, Kanban, dan Matrix.

| ID  | Project | Task                                       | Status          | Prioritas | Est. (mnt) | Aktual (mnt) | Tenggat |
| --- | ------- | ------------------------------------------ | --------------- | --------- | ---------- | ------------ | ------- |
| T01 | P1      | Baca komentar reviewer & petakan perubahan | `done`        | tinggi    | 60         | 55           | 26/10   |
| T02 | P3      | Siapkan checklist dokumen akta pendirian   | `done`        | tinggi    | 90         | 100          | 27/10   |
| T03 | P5      | Susun wireframe landing page               | `done`        | normal    | 120        | 130          | 28/10   |
| T04 | P1      | Revisi bab Metode sesuai komentar reviewer | `in_progress` | tinggi    | 180        | —           | 30/10   |
| T05 | P4      | Kirim laporan bug Siakad ke klien          | `todo`        | tinggi    | 45         | —           | 27/10   |
| T06 | P6      | Rancang migration Fase 0 NARA              | `in_progress` | tinggi    | 90         | —           | —      |
| T07 | P1      | Perbarui tabel hasil & grafik              | `todo`        | tinggi    | 120        | —           | 02/11   |
| T08 | P3      | Daftar NIB/OSS & tentukan KBLI             | `todo`        | normal    | 120        | —           | 06/11   |
| T09 | P4      | Estimasi biaya perubahan fitur klien       | `todo`        | rendah    | 60         | —           | 29/10   |
| T10 | P1      | Tulis response letter ke reviewer          | `todo`        | tinggi    | 90         | —           | 06/11   |
| T11 | P5      | Tulis copy halaman landing                 | `todo`        | normal    | 90         | —           | 06/11   |
| T12 | P2      | Baca 3 paper pembanding                    | `todo`        | normal    | 150        | —           | 01/11   |
| T13 | P6      | Bangun CRUD Task (panel native)            | `todo`        | tinggi    | 120        | —           | —      |
| T14 | P1      | Proofread final & submit ke jurnal         | `todo`        | tinggi    | 60         | —           | 06/11   |
| T15 | P2      | Susun kerangka metodologi riset            | `todo`        | normal    | 120        | —           | 11/11   |
| T16 | P3      | Cek syarat bank untuk rekening perusahaan  | `todo`        | tinggi    | 60         | —           | 06/11   |
| T17 | P6      | Siapkan DemoSeeder                         | `todo`        | normal    | 45         | —           | —      |
| T18 | P5      | Implementasi layout responsif              | `todo`        | normal    | 75         | —           | 11/11   |
| T19 | P7      | Catat konsep seri-paralel                  | `todo`        | rendah    | 60         | —           | —      |
| T20 | P7      | Praktik breadboard LED & resistor          | `todo`        | rendah    | 90         | —           | —      |
| T21 | P2      | Menunggu outline dari rekan peneliti       | `waiting`     | normal    | —         | —           | —      |

Catatan data:

- T01–T03 selesai minggu ini. T05 **terlambat** (tenggat 27/10, belum selesai). T21 `waiting` dengan alasan "menunggu outline dari rekan peneliti".
- T04 dan T06 berstatus `in_progress`.
- T09, T14, T15, T16, T17, T18, T19, T20 **belum dijadwalkan**.

### 9.6 Contoh Subtask

| Task                  | Subtask                                                                                          |
| --------------------- | ------------------------------------------------------------------------------------------------ |
| T04 Revisi bab Metode | ☑ Petakan komentar reviewer · ☐ Tulis ulang paragraf desain eksperimen · ☐ Perbarui rujukan |
| T08 Daftar NIB/OSS    | ☐ Siapkan data pemilik · ☐ Tentukan KBLI · ☐ Isi formulir OSS                               |
| T13 Bangun CRUD Task  | ☐ Halaman index/form Task · ☐ Validasi status · ☐ Uji `WipGuard`                          |

### 9.7 Jadwal Minggu Ini

| Task | Hari      | Waktu        | Durasi (mnt) | Keadaan   |
| ---- | --------- | ------------ | ------------ | --------- |
| T01  | Sen 26/10 | 08:00–09:00 | 60           | selesai   |
| T02  | Sen 26/10 | 13:00–14:30 | 90           | selesai   |
| T03  | Sel 27/10 | 13:00–15:00 | 120          | selesai   |
| T04  | Rab 28/10 | 09:00–12:00 | 180          | terjadwal |
| T05  | Rab 28/10 | 13:00–13:45 | 45           | terjadwal |
| T06  | Rab 28/10 | 15:30–17:00 | 90           | terjadwal |
| T07  | Kam 29/10 | 09:00–11:00 | 120          | terjadwal |
| T08  | Kam 29/10 | 13:00–15:00 | 120          | terjadwal |
| T10  | Jum 30/10 | 09:00–10:30 | 90           | terjadwal |
| T11  | Jum 30/10 | 13:00–14:30 | 90           | terjadwal |
| T12  | Sab 31/10 | 08:00–10:30 | 150          | terjadwal |
| T13  | Sab 31/10 | 13:00–15:00 | 120          | terjadwal |

Total durasi per hari (menit): Sen 150, Sel 120, Rab 315, Kam 240, Jum 180, Sab 270. (Senin dan Selasa memakai durasi rencana; angka aktual ada di tabel task.)

**Sinkron Google:** seluruh jadwal berstatus `synced` kecuali satu contoh `failed` yang sengaja disisipkan untuk uji UI (jadwal T11, `last_error = "429 rateLimitExceeded"`).

**Blok sibuk (kalender lain):** Rabu 12:00–13:00 (makan siang keluarga), Kamis 15:30–17:00 (rapat kampus), Jumat 19:00–20:00 (acara keluarga).

### 9.8 Output

| ID | Judul                                   | Jenis         | Project | Task | Tanggal | Bukti                |
| -- | --------------------------------------- | ------------- | ------- | ---- | ------- | -------------------- |
| O1 | Checklist dokumen akta pendirian        | `legal_doc` | P3      | T02  | 26/10   | berkas PDF           |
| O2 | Wireframe landing page Academy          | `prototype` | P5      | T03  | 27/10   | tautan berkas desain |
| O3 | Repo `nara-core` (commit awal Fase 0) | `github`    | P6      | —   | 21/10   | tautan repositori    |

Kartu "Output" minggu berjalan = **2** (O1, O2).

### 9.9 Inbox

| ID | Isi                                        | Sumber   | Hint | Status              |
| -- | ------------------------------------------ | -------- | ---- | ------------------- |
| I1 | Cek harga domain baru                      | telegram | task | pending             |
| I2 | Ide: fitur voice note di NARA              | telegram | idea | pending             |
| I3 | Follow up klien X minggu depan             | web      | task | pending             |
| I4 | Diajak join riset dosen Y — pikirkan dulu | telegram | idea | pending             |
| I5 | Beli kabel jumper                          | telegram | task | converted → T-lama |
| I6 | Ide aplikasi jadwal piket                  | web      | idea | discarded           |

### 9.10 Notifikasi dan Log (contoh)

| Waktu       | Jenis             | Saluran  | Status |
| ----------- | ----------------- | -------- | ------ |
| 28/10 07:00 | daily_briefing    | telegram | sent   |
| 28/10 08:30 | reminder (T04)    | telegram | sent   |
| 27/10 20:30 | evening_summary   | telegram | sent   |
| 28/10 06:00 | sync_failed (T11) | web      | sent   |

Beberapa baris `activity_logs`: `T03 status_changed todo→done (web, 27/10 15:02)`, `O2 created (web, 27/10 15:03)`, `T21 status_changed in_progress→waiting (web, 22/10)`.

### 9.11 Hasil Turunan (Jawaban Benar untuk Uji)

**Progres project** (rumus 7.2, bobot = max(estimasi, 30)):

| Project               | Task non-cancelled | Selesai | Progres       |
| --------------------- | ------------------ | ------- | ------------- |
| P1 MSKI Revisi Jurnal | 5                  | 1 (T01) | **12%** |
| P2 Riset Joint Paper  | 3                  | 0       | **0%**  |
| P3 Pendirian PT       | 3                  | 1 (T02) | **33%** |
| P4 Freelance Siakad   | 2                  | 0       | **0%**  |
| P5 Landing Page       | 3                  | 1 (T03) | **42%** |
| P6 NARA 1.0           | 3                  | 0       | **0%**  |
| P7 Kelistrikan        | 2                  | 0       | **0%**  |

Progres goal (rata-rata project aktif): **G1 = 6%**, **G2 = 25%** (P3, P4, P5), **G3 = 0%** (P6, P7).

**Matrix** (rumus 7.6, hari ini 28/10, ambang urgen ≤ 3 hari):

| Kuadran                          | Task                                             |
| -------------------------------- | ------------------------------------------------ |
| **Kerjakan**               | T04, T05                                         |
| **Jadwalkan**              | T06, T07, T08, T10, T12, T13, T14, T15, T16, T17 |
| **Cepatkan / Delegasikan** | T09                                              |
| **Suatu saat**             | T11, T18, T19, T20                               |
| *Waiting (terpisah)*           | T21                                              |

**Kapasitas minggu ini** (rumus 7.4, kapasitas 25 jam = 1.500 menit):

| Komponen                                                       | Menit                    |
| -------------------------------------------------------------- | ------------------------ |
| Realisasi: T01 (55) + T02 (100) + T03 (130)                    | 285                      |
| Rencana terjadwal: T04, T05, T06, T07, T08, T10, T11, T12, T13 | 1.005                    |
| Rencana belum dijadwalkan tapi jatuh tempo minggu ini: T09     | 60                       |
| **Total beban terencana**                                | **1.350**          |
| Persentase                                                     | **90% → "padat"** |

Perhatikan bahwa T09 (tenggat 29/10) dihitung karena jatuh tempo minggu ini walau belum dijadwalkan, dan tidak dihitung dua kali.

**Stagnasi:** hanya **P2** (6 hari tanpa aktivitas, ambang 5). P7 (3 hari) tidak.

**Kartu Today:** 3 jadwal hari ini; 1 terlambat (T05); 3 task selesai; 2 output; peringatan kapasitas 90%.

### 9.12 Skenario: Batas WIP

*Awal: seed 9.4 (slot business sudah 3 project aktif).*

1. Pengguna mengubah **P8 Bimbel Sendiri** menjadi `active` dengan `wip_slot = business`.
2. **Hasil:** ditolak oleh `WipGuard`. Pesan: "Slot Bisnis sudah penuh (3/3): Pendirian PT, Freelance Siakad, Landing Page. Jeda salah satu, atau biarkan Bimbel tetap sebagai ide."
3. Pengguna menjeda **P5** (`paused`) lalu mencoba lagi → **berhasil**; business tetap 3/3 aktif.
4. Efek samping: T11, T18 (task P5) keluar dari kapasitas dan Matrix; beban minggu turun menjadi 1.260 menit = **84%** (tetap "padat").

### 9.13 Skenario: Overload Mingguan

*Awal: seed 9.11 (90%).*

1. Klien meminta presentasi mendadak. Pengguna menambah task **"Persiapan presentasi ke klien"**, project P4, prioritas `high`, estimasi **240 menit**, tenggat **Jumat 30/10**.
2. **Hasil:** beban 1.350 + 240 = **1.590 menit = 106% → overload (merah)**. Telegram mengirim peringatan kapasitas; banner di Calendar memunculkan tiga saran.
3. Pengguna memilih *jeda satu project* → P5 dijeda. T11 (90 menit terjadwal minggu ini) keluar dari beban → 1.500 menit = **100% → "padat"** (batas atas, bukan overload).
4. Task baru masuk kuadran **Kerjakan** (penting + urgen), dan karena belum dijadwalkan, muncul di panel "belum dijadwalkan" Calendar.

### 9.14 Skenario: Capture, Triase, dan Selesai + Output

1. Dari HP: `/add Cek harga domain baru` → Inbox I1 (`hint = task`).
2. Malam hari, triase 5 menit: I1 → **Task** di project P3; I2 → **Project (idea)** "Voice note NARA"; I4 → **Someday**; I3 tetap menunggu. Task baru dari I1 belum memiliki estimasi dan tenggat, sehingga tidak memengaruhi beban.
3. Pengguna menandai **T05 selesai** (aktual 40 menit). Dialog output muncul; pengguna **melewati**.
4. **Hasil:** `T05.completed_at` terisi; progres **P4 = 45/105 = 43%**; beban terencana = 1.350 − 45 + 40 = 1.345 menit = **90%**; T05 keluar dari Kerjakan; `P4.last_activity_at` = sekarang; activity log mencatat `status_changed (source: telegram atau web)`.

### 9.15 Skenario Uji Sinkronisasi Google

*Setiap skenario berdiri sendiri dan dimulai dari data seed di Bagian 9.7.*

| Kode  | Skenario                      | Langkah                                                  | Hasil yang diharapkan                                                                       |
| ----- | ----------------------------- | -------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| GS-01 | Jadwal baru                   | Jadwalkan T14 Jumat 11:00–12:00                         | Event muncul di kalender "NARA"; `sync_status = synced`; `nara_schedule_id` terisi      |
| GS-02 | Ubah waktu di NARA            | Geser T07 ke 10:00–12:00                                | Event Google diperbarui; tidak ada event ganda                                              |
| GS-03 | Hapus jadwal                  | Hapus jadwal T08                                         | Event dihapus di Google; task T08 kembali "belum dijadwalkan"                               |
| GS-04 | Google error sementara        | Simulasikan 500 saat membuat event                       | Jadwal tetap tersimpan; `failed` → retry (1m, 5m) → `synced`                          |
| GS-05 | Job ganda                     | Jalankan job sinkron yang sama dua kali                  | Hanya satu event di Google (idempoten)                                                      |
| GS-06 | Ubah waktu di Google          | Pindahkan event T06 ke 16:00 di Google                   | Jadwal NARA ikut berubah (`source = google`) setelah sinkron masuk                        |
| GS-07 | Konflik dua arah              | Ubah T06 di NARA dan di Google dalam jendela yang sama   | **NARA menang**; event Google ditimpa; log mencatat konflik                           |
| GS-08 | Hapus di Google               | Hapus event T10 di Google                                | Jadwal T10 terhapus; T10 muncul di "belum dijadwalkan"; notifikasi ringan                   |
| GS-09 | Event manual di kalender NARA | Buat event "Tanya notaris" langsung di Google            | Muncul sebagai item Inbox (bukan task)                                                      |
| GS-10 | Kalender lain                 | Event "Rapat kampus" Kamis 15:30–17:00 di kalender lain | `busy_blocks` terisi; menjadwalkan di jam itu memunculkan peringatan                      |
| GS-11 | Token dicabut                 | Cabut akses aplikasi dari akun Google                    | Sinkron dijeda; banner merah; notifikasi Telegram; data NARA utuh                           |
| GS-12 | Zona waktu                    | Jadwal Sabtu 23:30–00:30 WIB                            | Tanggal dan jam benar di NARA dan Google (batas UTC tidak menggeser hari)                   |
| GS-13 | Task selesai                  | Tandai T04 selesai                                       | Judul event diberi awalan ✓                                                                |
| GS-14 | Kuota (429)                   | Simulasikan 429 berturut-turut                           | Backoff berjalan; antrean tidak kehilangan data; kartu Perhatian muncul bila gagal permanen |

### 9.16 `DemoSeeder`

Ringkasan rancangan:

```php
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $today = now('Asia/Jakarta')->startOfDay();      // acuan "hari ini"
        $monday = $today->copy()->startOfWeek();         // Senin minggu berjalan

        $user = User::factory()->create([...]);
        // 1. Life domain & goal      (9.2, 9.3)
        // 2. Project P1–P9           (9.4)  → panggil ProjectService agar WipGuard ikut diuji
        // 3. Task T01–T21 + subtask  (9.5, 9.6) → tanggal relatif: $monday->addDays(n)
        // 4. Jadwal                  (9.7)  → sync_status diset manual (tanpa memanggil Google)
        // 5. Output, inbox, notifikasi, activity log (9.8–9.10)
        // 6. Hitung ulang progres & last_activity_at lewat service
    }
}
```

Aturan seeder: dapat dijalankan berulang (`migrate:fresh --seed`), **tidak** memanggil API Google atau Telegram, dan **selalu** menghasilkan angka di 9.11 bila "hari ini" dipasang di Rabu minggu berjalan. Ada uji otomatis yang memastikan hal ini (Bagian 12).

---

## 10. Panduan Pembangunan (Fase 0–6) 🔒

### 10.1 Cara Membaca Fase

Setiap fase memuat: **tujuan**, **cakupan**, **di luar cakupan** (sengaja ditunda), **DoD**, dan **gerbang** ke fase berikutnya. Perkiraan jam hanya untuk menjaga kewajaran, **bukan tenggat**. Dengan anggaran 6 jam/minggu (Bagian 14), 30 jam kira-kira lima minggu kalender.

```text
Fase 0  NARA 0.1   Fondasi (DB, auth, CRUD, keamanan)
Fase 1  NARA 0.5   Dipakai harian (Today, Table, Kanban, Inbox, Telegram, kapasitas)
Fase 2  NARA 1.0   Kalender + sinkron Google, Timeline, Matrix, Weekly Review   ⟵ siap pakai penuh
Fase 3  NARA 1.5   Analitik rule-based & laporan otomatis
Fase 4  NARA 2.0   AI Assistant (gerbang keputusan Python)           🌱
Fase 5  NARA 3.0   Agent dengan persetujuan                           🌱
Fase 6  NARA X     Hardening & scaling (kondisional)                  🌱
```

**Aturan lintas fase:**

1. Mulai fase hanya bila DoD fase sebelumnya selesai **dan** fitur fase sebelumnya sudah dipakai nyata (lihat gerbang).
2. Tiap fitur dikerjakan sebagai **irisan vertikal** (migration → service → UI → uji) agar selalu ada sesuatu yang bisa dipakai.
3. Bila anggaran jam fase terlampaui dua kali lipat, berhenti dan jalankan peninjauan lingkup (Bagian 14.4).

---

### FASE 0 — Fondasi (NARA 0.1)

**Tujuan:** database dan CRUD dasar berjalan **dengan aman**, tanpa peduli tampilan.

**Perkiraan:** 20–30 jam (lebih panjang karena panel dibangun sendiri).

**Cakupan:**

- Repo git, project Laravel, environment (`.env`), CI sederhana (jalankan uji + linter), MySQL 8 lokal dan produksi.
- Migration **tabel inti Fase 0**: `users`, `life_domains`, `goals`, `projects`, `tasks`, `task_subtasks`, `activity_logs`, `settings` (+ tabel framework queue).
- Auth dengan **2FA** (Laravel Fortify/Breeze + TOTP); registrasi publik dimatikan.
- **Panel native** (Bagian 3.7): layout panel, komponen dasar (`DataTable`, `FormField`, `Modal`, `StatusBadge`, `Toast`), dan CRUD lengkap untuk Life Domain, Goal, Project, Task dengan `FormRequest` dan Policy.
- Struktur `app/Domain/...` (Bagian 3.4) dengan `ProjectService`, `TaskService`, `ActivityLogger`, `WipGuard`, `ProgressCalculator`.
- Enum PHP untuk status, prioritas, dll.
- **Keamanan dasar sejak awal (D-12):** HTTPS di produksi, `APP_KEY` aman, **backup database harian terenkripsi** (di luar server yang sama), uji restore sekali.
- `DemoSeeder` versi awal (Life Domain → Task) dan uji unit untuk progres dan WIP.

**Di luar cakupan:** halaman khusus (Today, Kanban, Calendar, dst.), generator `nara:make-resource` (baru dibuat bila CRUD kelima akan ditulis), Telegram, Google, inbox, jadwal, output.

**Definition of Done:**

```text
[ ] Login dengan 2FA berfungsi; registrasi publik mati
[ ] Migration Fase 0 bersih dijalankan di lokal dan produksi
[ ] Dapat membuat life_domain → goal → project → task lewat panel native (index, form, hapus lembut)
[ ] WipGuard menolak project aktif ke-4 per slot (diuji otomatis)
[ ] Progres project dihitung benar sesuai 7.2 (diuji otomatis)
[ ] Backup harian berjalan; satu kali uji restore berhasil
[ ] HTTPS aktif; repo di git dengan README awal
```

**Gerbang ke Fase 1:** DoD terpenuhi. Tidak perlu masa pakai.

---

### FASE 1 — Dipakai Harian (NARA 0.5)

**Tujuan:** NARA mulai dipakai **setiap hari** untuk menangkap dan melihat pekerjaan. Fase ini mengatasi keluhan "ide membludak" dan "beban tak terlihat".

**Perkiraan:** 35–50 jam.

**Cakupan (urutan irisan yang disarankan):**

1. **Inbox web** (`inbox_items`) + triase ke Task / Project (idea) / Goal / Someday / Buang.
2. **Telegram capture (D-13):** webhook, allowlist, `/add`, `/idea`, teks biasa, `/inbox`. Mulai dipakai dari HP segera.
3. **Today** (empat kartu KPI, jadwal hari ini, perlu perhatian).
4. **Table View** (memakai komponen `DataTable` dari Fase 0) dan **Kanban View**; **Task Detail** (subtask, komentar, lampiran, tag).
5. **Jadwal manual** (`schedules`) lewat form; belum ada Google.
6. **Kapasitas mingguan (D-14):** `CapacityService` dan banner persentase di Today.
7. **Output satu klik (D-16):** tabel `outputs`, dialog saat task selesai, halaman daftar Outputs.
8. **Reminder dan briefing** Telegram: pengingat jadwal, briefing pagi, `/today`, `/done`.
9. **Stagnasi sederhana** (7.7) tampil di kartu Perhatian.
10. `DemoSeeder` lengkap (Bagian 9) dan uji hasil turunan (9.11).

**Di luar cakupan:** Calendar view, Timeline, Matrix, Google, Weekly Review tertulis, Focus mode, dependensi.

**Definition of Done:**

```text
[ ] Task dan project nyata saya sudah ada di NARA (dimasukkan manual, tanpa impor)
[ ] Inbox dipakai harian; /add dari HP berfungsi
[ ] Today, Table, Kanban menampilkan data nyata
[ ] Banner kapasitas menampilkan angka yang sesuai 9.11 pada data seed
[ ] Output bisa dicatat satu klik dan muncul di daftar
[ ] Reminder dan briefing pagi diterima di Telegram
[ ] Seluruh uji skenario 9.12–9.14 lolos
[ ] Administrasi NARA ≤ 10 menit/hari selama 2 minggu terakhir (diukur manual)
```

**Gerbang ke Fase 2:** DoD terpenuhi **dan** NARA dipakai hampir setiap hari selama **minimal 3 minggu**.

---

### FASE 2 — NARA 1.0 (Sistem Lengkap, Deterministik)

**Tujuan:** semua pandangan yang Anda inginkan hadir di web dan **sinkron dengan Google Calendar** (D-05). Spreadsheet lama tidak lagi diperlukan.

**Perkiraan:** 40–60 jam.

**Langkah 2a — Calendar + sinkron keluar:**

- OAuth Google (scope minimal), pembuatan kalender "NARA", `integrations`, `calendar_events`.
- Calendar view (FullCalendar), seret dan lepas, panel "belum dijadwalkan", banner kapasitas.
- Job `SyncTaskToGoogle` idempoten dengan retry; indikator ✓ ⟳ ⚠ (Bagian 4.8).
- Skenario GS-01 s.d. GS-05, GS-12 s.d. GS-14 lolos.

**Langkah 2b — Sinkron masuk:**

- Pull perubahan (syncToken + webhook/polling), `busy_blocks`, `ConflictDetector`, aturan konflik (4.6).
- Skenario GS-06 s.d. GS-11 lolos.

**Langkah 2c — Pandangan dan ritual:**

- **Matrix** (otomatis dari 7.6) dan **Timeline** per project.
- **Weekly Review** (`weekly_reviews`) dengan ringkasan otomatis; pengingat Minggu 19:00.
- **Focus mode** (`focus_sessions`) dan pengisian `actual_minutes`.
- `task_dependencies` **hanya bila** pemicu terpenuhi (≥ 3 kasus nyata).
- Ringkasan malam Telegram, `/week`, `/cap`.

**Definition of Done (NARA 1.0):**

```text
[ ] Semua project dan task aktif saya ada di NARA; spreadsheet tidak dibuka lagi
[ ] Calendar, Timeline, dan Matrix menampilkan data nyata
[ ] Jadwal di NARA dan Google Calendar konsisten (uji GS-01…GS-14 lolos)
[ ] Perubahan di Google tercermin di NARA; konflik diselesaikan sesuai 4.6
[ ] Weekly Review dilakukan ≥ 3 kali berturut-turut, masing-masing ≤ 15 menit
[ ] Telegram: reminder, briefing, ringkasan malam diterima
[ ] Output tercatat setiap minggu; kartu Output tidak kosong 3 minggu berturut-turut
[ ] Backup + uji restore dijalankan ulang (setiap fase minor)
[ ] NARA dipakai setiap hari
```

**Gerbang ke Fase 3:** DoD terpenuhi dan NARA 1.0 dipakai stabil **minimal 4 minggu**. Pada titik ini jalankan **audit fitur** (Bagian 14.5): fitur yang tidak terpakai dimatikan sebelum menambah yang baru.

---

### FASE 3 — NARA 1.5 (Analitik Rule-Based, Masih Laravel)

**Tujuan:** sistem memberi insight tanpa LLM; menjawab "apakah waktu saya menghasilkan sesuatu?".

**Perkiraan:** 15–25 jam.

**Cakupan:**

- `project_health_snapshots` harian (sehat / melambat / stagnan).
- Kapasitas lanjutan: tren 4 minggu, rasio estimasi vs aktual per kategori.
- **Laporan output mingguan otomatis** (via Telegram dan halaman): jam tercatat per project vs output.
- Pengingat otomatis untuk task `waiting` yang terlalu lama (> 7 hari).
- Rekomendasi tertulis berbasis aturan ("Project X: 6 jam, 0 output dalam 14 hari").

**DoD:**

```text
[ ] Snapshot kesehatan project berjalan harian
[ ] Laporan output mingguan terkirim otomatis ≥ 4 minggu
[ ] Peringatan overload/stagnan memberi saran tindakan konkret
[ ] Fitur 1.5 dipakai ≥ 1 bulan sebelum membahas AI
```

**Gerbang ke Fase 4:** DoD terpenuhi **dan** Gerbang Keputusan AI (Bagian 11.2) dijalankan.

---

### FASE 4 — NARA 2.0 (AI Assistant) 🌱

Rincian ada di **Bagian 11**. Ringkasnya: perintah bahasa natural, daily planner, chatbot berbasis data NARA, narasi insight. Perkiraan 40–60 jam. Layanan Python hanya dibangun bila lolos gerbang (D-17).

### FASE 5 — NARA 3.0 (Agent) 🌱

Rincian ada di **Bagian 11.8**. Ringkasnya: alur multi-langkah dengan persetujuan wajib dan jejak audit lengkap. Perkiraan 40–60 jam. Hanya dimulai setelah AI 2.0 dipakai rutin ≥ 2 bulan.

### FASE 6 — Hardening & Scaling (Kondisional) 🌱

Dilakukan hanya bila NARA dipakai intensif dan muncul masalah nyata (lambat, sering gagal, atau ada pengguna kedua):

- Tinjauan keamanan menyeluruh, rotasi token, rate limit lanjutan.
- Observabilitas: log terpusat, pemantauan uptime, pelacakan error.
- Container dengan batas sumber daya (Docker Compose cukup; Kubernetes tidak diperlukan).
- Evaluasi apakah ada bagian monolith yang memang layak dipisah. Tidak ada pemisahan "karena rapi".
- Bila pengguna kedua dipertimbangkan: kaji ulang model `user_id`, verifikasi aplikasi OAuth Google, dan kebijakan privasi.

---

### 10.2 Ringkasan Tabel per Fase

| Fase | Tabel baru                                                                                                                                                                |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0    | `users`, `life_domains`, `goals`, `projects`, `tasks`, `task_subtasks`, `activity_logs`, `settings`                                                       |
| 1    | `inbox_items`, `schedules`, `outputs`, `output_files`, `notifications`, `notification_logs`, `task_comments`, `task_attachments`, `tags`, `task_tags` |
| 2    | `integrations`, `calendar_events`, `busy_blocks`, `weekly_reviews`, `focus_sessions`, (`task_dependencies` bila terpenuhi)                                    |
| 3    | `project_health_snapshots`                                                                                                                                              |
| 4    | `ai_conversations`, `ai_messages`, `ai_commands`, `ai_recommendations`, `embeddings_index`                                                                      |
| 5    | `agent_runs`, `agent_actions`, `agent_permissions`                                                                                                                  |

### 10.3 Strategi Cabang dan Rilis

- Satu cabang `main` yang selalu bisa dideploy; cabang pendek per irisan (`feat/inbox`, `feat/calendar-sync`).
- Pesan commit singkat dan bermakna; tag rilis per fase (`v0.1`, `v0.5`, `v1.0`).
- Deploy manual terskrip (`deploy.sh`): tarik kode → migrasi → cache → restart queue. Otomasi CI/CD penuh tidak diperlukan untuk satu orang.

---

## 11. Rancangan Lanjutan: AI (Fase 4) & Agent (Fase 5) 🌱

> **Status: rancangan, boleh berubah.** Bagian ini disimpan di dokumen yang sama agar arah jangka panjang tetap satu (D-06), tetapi **tidak ada yang dibangun dari sini sebelum gerbangnya terbuka**. Saat Fase 4 dimulai, bagian ini ditinjau ulang lebih dulu dan perubahannya dicatat di ADR.

### 11.1 Prinsip AI di NARA

1. **AI menyarankan, pengguna memutuskan.** Setiap perubahan data oleh AI melewati konfirmasi (kecuali yang diizinkan eksplisit di Fase 5).
2. **Satu pintu tulis.** AI tidak pernah menulis ke database langsung; ia memanggil service Laravel yang sama dengan UI.
3. **Berbasis data NARA sendiri.** Jawaban harus bersumber dari task, jadwal, output, dan log; bila tidak ada datanya, AI mengatakan tidak tahu.
4. **Transparan.** Setiap rekomendasi menyertakan "dasar" (data apa yang dipakai) yang bisa dibuka.
5. **Hemat dan tenang.** Tidak ada notifikasi AI baru di luar batas harian (7.8).
6. **Privasi dulu.** Hanya data minimum yang dikirim ke penyedia LLM (lihat 11.7).

### 11.2 Gerbang Keputusan Fase 4 (D-17)

Fase 4 dimulai bila **semua** syarat berikut terpenuhi:

```text
[ ] NARA 1.5 dipakai stabil ≥ 1 bulan
[ ] Ada ≥ 3 kasus nyata di mana aturan (rule-based) tidak cukup, tercatat di Parking Lot
[ ] Anggaran jam dan batas administrasi (Bagian 14) masih terpenuhi 4 minggu terakhir
[ ] Sudah memilih penyedia LLM dan membaca kebijakan penyimpanan datanya
[ ] Anggaran biaya bulanan untuk LLM ditetapkan (batas keras)
```

**Keputusan arsitektur di gerbang ini — pilih satu:**

| Opsi                                  | Kapan dipilih                                                                                           | Konsekuensi                                                                                                  |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| **A. Laravel-only** (default)   | Kebutuhan = parsing perintah, narasi insight, chatbot, retrieval                                        | Laravel memanggil API LLM lewat `NaraAIClient`; Qdrant diakses lewat HTTP dari Laravel; tanpa layanan baru |
| **B. Laravel + layanan Python** | Ada kebutuhan nyata: analitik data lanjutan, model lokal, pipeline embedding berat, pustaka ML tertentu | Satu layanan FastAPI internal (bukan publik), dipanggil Laravel; kontrak API didefinisikan lebih dulu        |

Opsi A adalah default karena tidak menambah titik gagal. Opsi B hanya dipilih dengan alasan tertulis di ADR.

### 11.3 Arsitektur Fase 4

```text
Pengguna (Web / Telegram)
        ▼
   Laravel ── AiCommandController ──► Guardrails (validasi input, batas, redaksi)
        │                                   ▼
        │                           NaraAIClient ──► LLM API
        │                                   ▲
        │              PromptBuilder (konteks terpilih dari MySQL, bukan seluruh DB)
        │                                   ▲
        │              Retrieval ──► Qdrant (embedding task/project/output)
        ▼
   ScheduleService / TaskService / ...   (satu pintu tulis, sama seperti UI)
```

**Opsi B** menambahkan layanan di antara `NaraAIClient` dan LLM/Qdrant:

```text
nara-ai-service/            (FastAPI, hanya jaringan internal)
├── main.py
├── api/        routes_nlp.py · routes_planner.py · routes_insight.py · routes_chat.py
├── core/       config.py · security.py (token antar-layanan) · nara_client.py (tulis balik via API Laravel)
├── services/   nlp_engine.py · planner_engine.py · insight_engine.py
├── embeddings/ vector_store.py
└── requirements.txt
```

Aturan Opsi B: Python boleh **membaca** (pengguna database read-only atau API), tetapi **setiap penulisan** lewat `AiBridgeController` di Laravel.

### 11.4 Fitur Fase 4

**a. Perintah bahasa natural**

```text
"Besok pagi saya mau kerjakan MSKI 2 jam"
   ▼ parse
{ intent: "create_schedule", project: "MSKI Revisi Jurnal", task: null,
  date: <besok>, start: "09:00", duration: 120 }       ← "pagi" dipetakan oleh setting time_of_day_defaults
   ▼
Laravel memvalidasi → mencari task terbuka paling relevan di project itu → meminta konfirmasi
   ▼
Konfirmasi ("Jadwalkan 'Perbarui tabel hasil' besok 09:00–11:00?" [Ya] [Ubah])
   ▼
ScheduleService::create → sinkron Google (alur Bagian 3.5)
```

Daftar intent awal: `create_task`, `create_schedule`, `reschedule`, `add_inbox`, `query_today`, `query_capacity`, `mark_done`. Intent di luar daftar dijawab "belum bisa" dan tidak ditebak.

**b. Daily Planner**

Masukan: tugas terbuka, tenggat, prioritas, kuadran Matrix, jadwal dan `busy_blocks`, kapasitas harian. Keluaran: **usulan** blok jadwal harian dengan alasan singkat. Tombol [Terima semua] [Ubah] [Tolak]. Planner tidak boleh menjadwalkan di luar jam kerja yang ditetapkan, tidak menimpa jadwal yang sudah ada, dan tidak pernah melebihi kapasitas harian.

**c. Insight naratif**

Mengubah angka aturan Fase 3 menjadi penjelasan: "Robotika menerima 6 jam dalam 14 hari tanpa output; dua task `waiting` menunggu rekan peneliti." Angka tetap dihitung oleh aturan; LLM hanya menyusun kalimat dari angka tersebut (mencegah halusinasi angka).

**d. Chatbot berbasis data NARA**

Alurnya retrieval: pertanyaan → pencarian semantik (Qdrant) + kueri terstruktur (MySQL) → konteks terpilih → jawaban dengan rujukan ("berdasarkan T04, T10"). Tidak ada jawaban umum dari "pengetahuan LLM" tanpa konteks NARA untuk pertanyaan tentang data pengguna.

### 11.5 Kontrak API Jembatan (`AiBridgeController`)

Hanya dibuat bila Opsi B atau bila Laravel perlu API internal untuk layanan lain.

| Endpoint                                          | Fungsi                           | Catatan                                    |
| ------------------------------------------------- | -------------------------------- | ------------------------------------------ |
| `POST /internal/ai/schedules`                   | Buat jadwal dari hasil parse     | Lewat `ScheduleService`; `source = ai` |
| `POST /internal/ai/tasks`                       | Buat task                        | Divalidasi seperti UI                      |
| `POST /internal/ai/inbox`                       | Tambah item inbox                |                                            |
| `GET /internal/ai/context/today`                | Konteks hari ini                 | Data minimum                               |
| `POST /internal/ai/recommendations`             | Simpan rekomendasi (`pending`) | Menunggu keputusan                         |
| `POST /internal/ai/recommendations/{id}/accept` | Terapkan rekomendasi             | Dipanggil dari UI, bukan oleh AI           |

Keamanan: token antar-layanan, hanya jaringan internal, rate limit, dan **semua panggilan tercatat** di `activity_logs` dengan `source = ai`.

### 11.6 Guardrails

| Risiko                                                    | Pengaman                                                                                                                  |
| --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| Perintah ambigu                                           | Minta klarifikasi atau tampilkan pilihan; jangan menebak project/task                                                     |
| Tanggal salah (mis. "Jumat" saat hari Jumat)              | Aturan eksplisit: kata hari = kemunculan berikutnya; selalu tampilkan tanggal lengkap pada konfirmasi                     |
| Aksi destruktif                                           | Tidak tersedia untuk AI di Fase 4 (hapus, arsip, batalkan massal)                                                         |
| Halusinasi angka                                          | Angka selalu dari kueri/aturan, bukan dari LLM                                                                            |
| Biaya membengkak                                          | Batas token harian, batas biaya bulanan keras, cache untuk permintaan berulang                                            |
| Injeksi prompt dari data (judul task, hasil event Google) | Isi data diperlakukan sebagai **data**, bukan instruksi; AI tidak menjalankan perintah yang ditemukan di dalam data |
| Kegagalan penyedia LLM                                    | NARA tetap berfungsi penuh tanpa AI; fitur AI turun dengan pesan jelas                                                    |

### 11.7 Privasi Data untuk LLM

1. Kirim **hanya** data yang dibutuhkan permintaan (mis. 10 task relevan, bukan seluruh database).
2. Catatan sensitif (Inbox dengan kata kunci tertentu, komentar pribadi) dapat ditandai `private` dan **tidak pernah** dikirim ke LLM eksternal.
3. Setting `ai_enabled` dan `ai_send_private` (default: mati) memberi kendali penuh.
4. Pilih penyedia yang kebijakannya tidak melatih model dari data API Anda, dan catat pilihan itu di ADR.
5. Catatan tentang kondisi pribadi, kesehatan, atau keuangan tidak diprioritaskan untuk dikirim; bila diperlukan, diminta konfirmasi tiap kali.

### 11.8 Fase 5 — Agent (Rancangan)

**Definisi:** alur multi-langkah yang direncanakan AI, dijalankan lewat service Laravel, dengan **persetujuan** pada langkah yang mengubah data.

```text
Tujuan: "Jadwalkan semua task MSKI minggu depan"
  1. baca task + tenggat           (baca, otomatis)
  2. baca kapasitas + busy_blocks  (baca, otomatis)
  3. susun usulan slot             (hitung, otomatis)
  4. TAMPILKAN usulan              ▶ menunggu persetujuan
  5. buat jadwal + sinkron Google  (tulis, setelah disetujui)
  6. kirim ringkasan Telegram
```

**Aturan:**

- `agent_permissions.auto_allowed = false` sebagai default untuk semua tipe aksi tulis.
- Setiap langkah tercatat di `agent_actions` (input, output, siapa menyetujui, kapan).
- **Batas keras per run:** jumlah langkah, jumlah perubahan data, waktu eksekusi.
- **Tombol henti** di UI dan di Telegram (`/stop`).
- Aksi tidak dapat dibatalkan (hapus, kirim ke pihak luar) **tidak boleh** otomatis, apa pun izinnya.

**Alur contoh yang dirancang:** (1) siapkan laporan mingguan; (2) jadwalkan task satu project untuk minggu depan; (3) triase Inbox massal dengan usulan klasifikasi.

### 11.9 DoD Ringkas Fase 4 dan 5

```text
Fase 4
[ ] Gerbang 11.2 lolos; keputusan A/B tercatat di ADR
[ ] Perintah bahasa natural membuat jadwal yang benar pada ≥ 90% dari 30 kalimat uji
[ ] Daily Planner dipakai rutin ≥ 4 minggu (saran diterima > 50%)
[ ] Chatbot menjawab dengan rujukan; tidak ada jawaban data tanpa sumber
[ ] Biaya LLM di bawah batas bulanan

Fase 5
[ ] ≥ 2 alur agent berjalan end-to-end dengan persetujuan
[ ] Semua langkah tercatat di agent_actions; tombol henti berfungsi
[ ] Tidak ada aksi tulis tanpa jejak audit
```

---

## 12. Pengujian & Kualitas 🔒

### 12.1 Prinsip

Uji dipusatkan pada **aturan bisnis dan sinkronisasi** (tempat bug paling mahal), bukan pada kosmetik UI. Cakupan 100% bukan tujuan.

### 12.2 Lapisan Uji

| Lapisan                     | Alat                                      | Fokus                                                                                                                |
| --------------------------- | ----------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| Unit                        | Pest/PHPUnit                              | `ProgressCalculator`, `WipGuard`, `MatrixClassifier`, `CapacityService`, `ConflictDetector`, aturan status |
| Fitur                       | Pest + database uji                       | Service dan endpoint: membuat jadwal, mengubah status, triase inbox, webhook Telegram                                |
| Integrasi (dengan *fake*) | Fake HTTP untuk Google dan Telegram       | Job sinkron: sukses, gagal, retry, idempotensi                                                                       |
| Browser                     | Laravel Dusk atau Playwright (secukupnya) | 3–5 alur kritis: tambah task, seret jadwal, selesaikan + output                                                     |
| Seeder                      | Uji khusus                                | `DemoSeeder` menghasilkan angka Bagian 9.11                                                                        |

### 12.3 Uji Wajib (Daftar Minimum)

1. **Progres** (7.2): tujuh project di 9.11 menghasilkan 12/0/33/0/42/0/0%.
2. **WIP** (7.3): project aktif ke-4 per slot ditolak; project `done`/`archived` tidak dihitung.
3. **Matrix** (7.6): klasifikasi 17 task terbuka sesuai tabel 9.11; batas tepat 3 hari urgen.
4. **Kapasitas** (7.4): 1.350 menit = 90%; skenario 9.13 menghasilkan 106% lalu 100%; task tidak dihitung dua kali.
5. **Status** (7.1): `waiting` tanpa alasan ditolak; membuka kembali `done` mengosongkan `completed_at`.
6. **Telegram:** chat tidak dikenal diabaikan; pesan duplikat (`telegram_message_id`) tidak membuat inbox ganda; `/done` pada id yang salah ditolak ramah.
7. **Notifikasi:** `dedupe_key` mencegah kirim ganda; batas 8 per hari; jam tenang.
8. **Sinkronisasi Google:** GS-01 s.d. GS-14 (Bagian 9.15) dengan Google yang di-*fake*.
9. **Zona waktu:** "besok" dan batas hari pada 23:30 WIB benar.
10. **Seeder:** `migrate:fresh --seed` berulang menghasilkan keadaan yang identik.
11. **Backup:** uji restore (manual terjadwal, tercatat).

### 12.4 Uji Rilis (Checklist Manual)

Sebelum men-deploy fase minor: `migrate:fresh --seed` di lokal lolos; salinan database produksi dimigrasikan di lokal; seluruh uji hijau; backup terbaru ada; rollback dipahami (Bagian 13.5).

### 12.5 Kualitas Kode

- Linter/format otomatis (Pint) dan analisis statis ringan (Larastan level sedang) di CI.
- Satu pull request atau satu commit bermakna per irisan; tidak ada kode mati "untuk nanti".
- Migration tidak diedit setelah dijalankan di produksi; perubahan = migration baru.

### 12.6 Uji AI (Fase 4) 🌱

Kumpulan **kalimat uji emas** (30–50 kalimat Indonesia: berbagai hari, durasi, nama project, kata "pagi/sore/malam") dengan hasil parse yang diharapkan. Dijalankan tiap kali prompt atau model diganti. Hasil di bawah ambang akurasi menghalangi rilis perubahan.

---

## 13. Keamanan, Backup & Operasional 🔒

### 13.1 Keamanan Sejak Fase 0 (D-12)

| Area             | Ketentuan                                                                                              |
| ---------------- | ------------------------------------------------------------------------------------------------------ |
| Akses            | Satu pengguna; registrasi publik dimatikan; **2FA TOTP** wajib; kode pemulihan disimpan offline  |
| Transport        | HTTPS saja (HSTS); cookie `secure`, `httpOnly`, `sameSite=lax`                                   |
| Kata sandi       | Hash bawaan Laravel (bcrypt/argon2); panjang minimal 14 karakter                                       |
| Token integrasi  | Token OAuth Google dan rahasia lain **terenkripsi** (cast `encrypted`); tidak pernah masuk log |
| Rahasia          | `.env` di luar repo; kunci API dirotasi bila bocor; tidak ada rahasia di kode                        |
| Telegram         | Webhook secret, **allowlist chat id**, rate limit (Bagian 8.6)                                   |
| Rate limit login | Pembatasan percobaan; pemberitahuan Telegram bila login dari perangkat baru                            |
| Header           | CSP dasar, `X-Frame-Options`, `X-Content-Type-Options`                                             |
| Dependensi       | `composer audit` dan `npm audit` rutin; pembaruan keamanan tidak ditunda                           |
| Berkas unggahan  | Disimpan di luar *web root*; validasi tipe dan ukuran; nama berkas diacak                            |

### 13.2 Backup (D-12)

| Item        | Ketentuan                                                                        |
| ----------- | -------------------------------------------------------------------------------- |
| Frekuensi   | **Harian** (dump MySQL), simpanan 7 harian + 4 mingguan + 3 bulanan        |
| Lokasi      | **Di luar server produksi** (penyimpanan objek atau perangkat lain)        |
| Enkripsi    | Terenkripsi sebelum dikirim; kunci tidak disimpan bersama cadangan               |
| Cakupan     | Database + folder unggahan; konfigurasi `.env` (aman) disimpan terpisah        |
| Uji restore | **Sekali per fase minor** dan sekali setiap 3 bulan; dicatat tanggalnya    |
| Sasaran     | **RPO** ≤ 24 jam, **RTO** ≤ 4 jam (cukup untuk pemakaian personal) |

Backup yang tidak pernah diuji dianggap tidak ada.

### 13.3 Pemantauan Sederhana

- **Health check** (`nara:health-check`) tiap 5 menit: database, queue hidup, usia sinkron Google terakhir, usia backup terakhir.
- Kegagalan memicu pesan Telegram ke pemilik (sekali per jam per jenis).
- Pemantauan uptime eksternal ringan untuk URL aplikasi (bisa memakai layanan monitoring yang sudah dimiliki).
- Log aplikasi dirotasi; tidak menyimpan isi catatan pribadi di log kesalahan.

### 13.4 Operasi Rutin

| Frekuensi | Tindakan                                                                              |
| --------- | ------------------------------------------------------------------------------------- |
| Harian    | Backup otomatis; triase Inbox (≤ 5 menit)                                            |
| Mingguan  | Weekly Review; cek ringkasan kegagalan sinkron                                        |
| Bulanan   | Pembaruan dependensi; tinjauan anggaran waktu dan fitur (Bagian 14.5); cek ruang disk |
| 3 bulanan | Uji restore; tinjau token dan izin integrasi; rotasi kunci bila perlu                 |

### 13.5 Deploy dan Rollback

1. Backup manual tepat sebelum deploy yang menyertakan migration.
2. `deploy.sh`: mode pemeliharaan → tarik kode → `composer install --no-dev` → `migrate --force` → cache config/route/view → restart queue → keluar mode pemeliharaan.
3. **Rollback:** kembali ke tag sebelumnya; bila migration bersifat merusak, pulihkan dari backup (karena itu migration destruktif harus langsung didahului backup dan diuji di salinan lokal).
4. Migration hanya **menambah** sebisa mungkin (kolom nullable, tabel baru); perubahan merusak dilakukan dua langkah.

### 13.6 Privasi dan Data Pribadi

NARA menyimpan data pribadi (rencana, catatan, jadwal). Prinsip: data tetap di server Anda; integrasi pihak ketiga memakai izin minimum; tidak ada analitik pihak ketiga di UI; data AI mengikuti Bagian 11.7. Bila kelak ada pengguna lain, Fase 6 mewajibkan kebijakan privasi dan penelaahan ulang model data.

### 13.7 Perencanaan Kapasitas Server

Beban satu pengguna sangat kecil: satu VPS kecil (1–2 vCPU, 2 GB RAM, disk 20–40 GB) cukup untuk Laravel + MySQL + Redis. Qdrant (Fase 4, Opsi A/B) ditambahkan hanya bila fasenya tiba; bila memori terbatas, tunda atau pakai opsi terkelola.

---

## 14. Anggaran Waktu, Metrik & Kriteria Gagal 🔒

Dokumen ini menolak tanggal kaku, tetapi proyek tanpa batas adalah risiko bagi seseorang yang sudah kelebihan beban. Karena itu NARA diberi **anggaran**, bukan tenggat (D-15).

### 14.1 Anggaran Pengembangan

| Parameter                        | Nilai awal                                               | Keterangan                                                                                                 |
| -------------------------------- | -------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Jam pengembangan NARA per minggu | **6 jam** (`nara_budget_minutes_per_week = 360`) | Ditempatkan di slot WIP `personal`; NARA 1.0 adalah salah satu dari maksimal 3 project aktif di slot itu |
| Slot waktu tetap                 | 2 sesi × 3 jam, dijadwalkan di NARA sendiri             | "Memakai NARA untuk membangun NARA"                                                                        |
| Perkiraan total sampai NARA 1.0  | 95–140 jam                                              | Penjumlahan Fase 0–2 di Bagian 10 (termasuk panel native); jadi sekitar 16–24 minggu bila disiplin       |
| Pemicu peninjauan                | Fase melampaui **2×** perkiraan jam               | Lihat 14.4                                                                                                 |

Angka ini adalah titik awal yang **boleh disesuaikan** lewat ADR setelah Fase 0, saat jam nyata sudah terukur.

### 14.2 Anggaran Pemakaian (Administrasi)

| Kegiatan                                                         | Batas                                                    |
| ---------------------------------------------------------------- | -------------------------------------------------------- |
| Administrasi NARA total per hari (capture, triase, ubah status)  | **≤ 10 menit** (`admin_budget_minutes_per_day`) |
| Triase Inbox                                                     | ≤ 5 menit per hari                                      |
| Weekly Review                                                    | ≤ 15 menit per minggu                                   |
| Tugas "merapikan sistem" tanpa menghasilkan sesuatu di luar NARA | 0 jam terjadwal                                          |

### 14.3 Metrik Keberhasilan

Metrik dipilih sedikit dan dapat dibaca dari data NARA sendiri.

| Metrik                                               | Sehat bila                  | Sumber                              |
| ---------------------------------------------------- | --------------------------- | ----------------------------------- |
| Hari aktif (ada aktivitas) per minggu                | ≥ 5 dari 7                 | `activity_logs`                   |
| Output tercatat per minggu                           | ≥ 1                        | `outputs`                         |
| Rasio jam tercatat pada project tanpa output 14 hari | menurun dari bulan ke bulan | Fase 3                              |
| Kapasitas "overload" berturut-turut                  | ≤ 2 minggu                 | `CapacityService`                 |
| Inbox menunggu > 7 hari                              | ≤ 3 item                   | `inbox_items`                     |
| Weekly Review dilakukan                              | ≥ 3 dari 4 minggu          | `weekly_reviews`                  |
| Administrasi harian                                  | ≤ 10 menit                 | catatan manual 2 minggu per kuartal |
| Sinkron Google gagal permanen                        | 0 per bulan                 | `calendar_events`                 |

Metrik bukan untuk menilai diri; ia sinyal untuk menyederhanakan sistem atau mengurangi komitmen.

### 14.4 Kriteria Gagal dan Tindakan

| Kriteria gagal                                                       | Tindakan                                                                                                        |
| -------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Administrasi > 10 menit/hari rata-rata selama 2 minggu               | **Sederhanakan:** hapus satu isian wajib, matikan satu notifikasi, atau sembunyikan satu view             |
| NARA tidak dibuka ≥ 5 hari berturut-turut padahal ada pekerjaan     | Periksa gesekan: apakah capture masih satu langkah? Perbaiki gesekan, jangan tambah fitur                       |
| Fase melampaui 2× perkiraan jam                                     | **Peninjauan lingkup:** pangkas cakupan fase ke irisan terkecil yang bisa dipakai; sisanya ke Parking Lot |
| Output nol selama 3 minggu berturut-turut padahal jam kerja tercatat | Bukan kegagalan NARA, tetapi sinyal inti proyek: bahas di Weekly Review; kurangi jumlah project aktif           |
| Overload merah > 3 minggu                                            | Wajib mengurangi komitmen (jeda project / turunkan WIP) sebelum menambah apa pun                                |
| Mengerjakan fitur yang tidak menjawab tiga keluhan di 1.2            | Hentikan; pindahkan ke Parking Lot                                                                              |

### 14.5 Ritual Peninjauan

**Audit fitur bulanan (≤ 30 menit)** pada Weekly Review pertama tiap bulan:

1. Daftar fitur yang dipakai dan tidak dipakai bulan ini (dari `activity_logs`).
2. Fitur tidak terpakai dua bulan berturut-turut → **dimatikan** (disembunyikan, bukan langsung dihapus).
3. Satu fitur yang ingin ditambah bulan depan, dengan jawaban atas "keluhan mana yang ia jawab?".
4. Cek anggaran jam NARA bulan itu; sesuaikan bila perlu.

**Aturan perubahan lingkup:** menambah fitur ke fase berjalan hanya bila ada fitur lain yang dikeluarkan dengan bobot jam setara. Dokumen diubah dulu (Bagian 0).

---

## 15. Risiko & Mitigasi 🔒

| #   | Risiko                                                                           | Dampak                                 | Mitigasi                                                                                       |
| --- | -------------------------------------------------------------------------------- | -------------------------------------- | ---------------------------------------------------------------------------------------------- |
| R1  | **Membangun NARA mengorbankan hal lain** (proyek tentang mengelola proyek) | Kelebihan beban bertambah              | Anggaran jam, WIP, kriteria gagal (Bagian 14); NARA dianggap project biasa                     |
| R2  | Sinkron Google rumit: konflik, token kedaluwarsa, kuota                          | Jadwal tidak cocok; kepercayaan hilang | Kalender khusus, NARA otoritatif, sinkron dua langkah (2a/2b), indikator status, uji GS-01…14 |
| R3  | Verifikasi aplikasi OAuth Google memakan waktu                                   | Penundaan integrasi                    | Mode pengujian untuk pemilik tunggal; verifikasi hanya bila pengguna bertambah                 |
| R4  | Data hilang (disk rusak, kesalahan migrasi)                                      | Aset jangka panjang hilang             | Backup harian di luar server, uji restore, migration aman                                      |
| R5  | Kebocoran kredensial atau akses tidak sah                                        | Data pribadi terbuka                   | 2FA, HTTPS, token terenkripsi, allowlist Telegram, pemberitahuan login                         |
| R6  | **Over-engineering** (fitur terlalu banyak, skema terlalu luas)            | Tidak pernah dipakai                   | Skema per fase, DoD, audit fitur, Parking Lot                                                  |
| R7  | Tidak memakai NARA (gesekan capture)                                             | Proyek gagal diam-diam                 | Telegram capture di Fase 1; metrik hari aktif; perbaikan gesekan lebih dulu                    |
| R8  | AI memberi saran keliru atau mengubah data tak diinginkan                        | Kepercayaan hilang                     | Konfirmasi wajib, satu pintu tulis, guardrails (11.6), audit                                   |
| R9  | Biaya LLM tak terkendali                                                         | Tagihan tak terduga                    | Batas harian dan bulanan keras (11.2, 11.6)                                                    |
| R10 | Privasi: data pribadi terkirim ke penyedia LLM                                   | Paparan data sensitif                  | Data minimum, tanda `private`, `ai_enabled` default mati (11.7)                            |
| R11 | Ketergantungan pada satu penyedia (Google, Telegram, LLM)                        | Layanan berubah atau berhenti          | NARA tetap sumber data; klien bisa diganti; NARA berfungsi tanpa AI                            |
| R12 | Satu orang = satu titik gagal (sakit, sibuk)                                     | Proyek berhenti berbulan-bulan         | Dokumen ini sebagai memori; tag rilis; sistem tetap berguna di tiap fase                       |
| R13 | Perubahan teknologi (versi Laravel, API Google)                                  | Pekerjaan pemeliharaan                 | Pembaruan bulanan kecil; hindari paket niche; uji otomatis pada aturan inti                    |
| R14 | Perfeksionisme: memoles UI sebelum dipakai                                       | Penundaan manfaat                      | Urutan Today → Table → Kanban; polesan setelah dipakai harian                                |

---

## 16. Parking Lot (Ide yang Sengaja Ditunda) 🌱

Ide di sini **tidak** dikerjakan sampai memenuhi pemicunya. Tulis ide baru di sini, bukan langsung ke kode.

| Ide                                             | Pemicu untuk dipertimbangkan                         | Catatan                                                              |
| ----------------------------------------------- | ---------------------------------------------------- | -------------------------------------------------------------------- |
| Sinkron dua arah untuk **semua** kalender | Ada kebutuhan nyata melihat/menulis di kalender lain | Sekarang hanya baca (busy blocks)                                    |
| `task_dependencies`                           | ≥ 3 kasus nyata task yang terhalang task lain       | Tabel sudah dirancang                                                |
| `project_members` dan kolaborasi              | Mulai bekerja bersama orang lain di NARA             | Perlu peninjauan model pengguna                                      |
| Aplikasi mobile native / PWA                    | Web responsif terasa tidak cukup setelah 2 bulan     | PWA dulu sebelum native                                              |
| Voice note (Inbox lewat suara)                  | Capture teks terbukti tidak cukup                    | Perlu transkripsi (Fase 4)                                           |
| Integrasi email (email → Inbox)                | ≥ 5 kali per bulan menyalin email ke Inbox manual   | Hanya meneruskan ke alamat khusus; tidak membaca seluruh kotak masuk |
| Knowledge base / catatan pribadi                | Catatan di luar task makin menumpuk                  | Bisa berupa tipe item baru atau alat lain                            |
| Time tracking penuh                             | `actual_minutes` dari Focus terbukti tidak cukup   | Jangan menambah administrasi                                         |
| Template project berulang                       | Membuat project serupa ≥ 3 kali                     | Template task                                                        |
| Task berulang (recurring)                       | ≥ 5 task rutin dibuat manual tiap minggu            | Hati-hati kompleksitas aturan                                        |
| Dashboard keuangan/kesehatan (domain Kesehatan) | Domain Kesehatan mulai dipakai nyata                 | Sekarang domain kosong                                               |
| Berbagi laporan (tautan baca-saja)              | Perlu melaporkan progres ke klien/mitra              | Perlu keamanan tautan                                                |
| Multi-pengguna / SaaS                           | Keputusan bisnis terpisah, bukan teknis              | Lihat Fase 6                                                         |
| Mode gelap penuh dan tema                       | Setelah NARA 1.0 stabil                              | Token warna sudah siap                                               |
| Integrasi ke Manggala (ekosistem)               | Ada kebutuhan tukar data nyata                       | Lewat API, bukan basis data bersama                                  |

---

## 17. Log Keputusan Arsitektur (ADR) & Changelog 🔒

### 17.1 Format

Setiap keputusan: **konteks → keputusan → alasan → konsekuensi → pemicu tinjau ulang**. Keputusan lama tidak dihapus; bila berubah, tambahkan ADR baru yang menggantikan.

### 17.2 Daftar ADR

| ID             | Keputusan                                                                                                                                   | Alasan                                                                        | Konsekuensi / pemicu tinjau ulang                                                                       |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **D-01** | Database NARA = single source of truth                                                                                                      | Menghindari data tercecer; Google/Telegram bisa diganti                       | Semua klien lewat service layer. Tinjau bila ada kebutuhan offline-first                                |
| **D-02** | Monolith modular Laravel                                                                                                                    | Satu orang; satu deployment; mudah dirawat                                    | Pisahkan layanan hanya di Fase 6 bila ada masalah nyata                                                 |
| **D-03** | MySQL 8                                                                                                                                     | Kemudahan developer saat ini (keputusan Anda)                                 | Vektor tidak di MySQL (D-04). Tinjau bila fitur memerlukan kemampuan yang MySQL tidak punya             |
| **D-04** | Vektor di Qdrant (Fase 4)                                                                                                                   | MySQL tidak punya pencarian vektor yang memadai; menghindari migrasi database | Qdrant hanya indeks; sumber data tetap MySQL                                                            |
| **D-05** | View Calendar/Timeline/Matrix di web, **selalu sinkron** dengan Google Calendar; kalender "NARA" khusus; NARA otoritatif              | Keputusan Anda; kalender tetap terlihat di HP                                 | Dibangun bertahap 2a/2b; arah balik diuji ketat (GS-01…14)                                             |
| **D-06** | Semua rencana (termasuk Fase 4–5) dalam satu dokumen                                                                                       | Keputusan Anda; satu acuan                                                    | Bagian 🌱 diberi label rancangan                                                                        |
| **D-07** | Tanpa impor spreadsheet; `DemoSeeder` + data contoh                                                                                       | Keputusan Anda                                                                | Data nyata dimasukkan dari nol lewat UI/Telegram                                                        |
| **D-08** | Skema per fase                                                                                                                              | Tabel yang belum dipakai hanya menambah beban                                 | Migration dibuat saat fasenya tiba                                                                      |
| **D-09** | Status disederhanakan (task 5, project 5); `blocked` digabung ke `waiting` dengan alasan                                                | Status tumpang tindih; kurangi isian                                          | Tinjau bila muncul kebutuhan membedakan secara nyata                                                    |
| **D-10** | `impact`/`urgency` hanya di project; urgensi/kepentingan task diturunkan otomatis                                                       | Mengurangi isian; Matrix tetap ada                                            | Ambang `urgent_within_days` dapat disetel                                                             |
| **D-11** | **Panel native** (Blade + Alpine.js, Livewire opsional) untuk seluruh antarmuka; **tanpa Filament** atau panel admin siap pakai | Kontrol penuh, satu bahasa desain, tidak bergantung siklus versi paket panel  | Fase 0–1 lebih lama (±8–10 jam). Tinjau ulang bila komponen/generator melampaui anggaran fase (14.4) |
| **D-12** | Backup, enkripsi token, 2FA, allowlist Telegram sejak Fase 0                                                                                | Data adalah aset jangka panjang                                               | Uji restore berkala                                                                                     |
| **D-13** | Telegram capture di Fase 1                                                                                                                  | Fitur termurah dengan manfaat langsung                                        | Hanya capture + reminder dasar; natural language ditunda                                                |
| **D-14** | Kapasitas mingguan sederhana di 1.0                                                                                                         | Menjawab langsung masalah overload                                            | Tanpa tabel khusus; analitik lanjut di Fase 3                                                           |
| **D-15** | Anggaran jam + kriteria gagal                                                                                                               | Proyek tanpa batas berbahaya bagi yang sudah kelebihan beban                  | Disetel ulang setelah Fase 0                                                                            |
| **D-16** | Pencatatan output satu klik saat selesai                                                                                                    | Fitur tentang output harus tanpa gesekan agar terisi                          | Bila sering dilewati, sederhanakan                                                                      |
| **D-17** | Gerbang Python di Fase 4 (default Laravel-only)                                                                                             | Python tidak diperlukan untuk memanggil LLM                                   | Opsi B hanya dengan alasan tertulis                                                                     |
| **D-18** | Seluruh waktu disimpan UTC; tampilan zona waktu pengguna                                                                                    | Menghindari bug lintas hari                                                   | Uji GS-12                                                                                               |
| **D-19** | Kolom `category` project sebagai kolom biasa, bukan tabel `project_categories`                                                          | Daftar kecil, jarang berubah                                                  | Jadikan tabel bila pengguna perlu kategori kustom                                                       |

### 17.3 Changelog Dokumen

| Versi         | Tanggal                   | Ringkasan                                                                                                                                                                                                                                                                                             |
| ------------- | ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1.0           | 24 September 2026         | Panduan arsitektur final pertama: konsep, arsitektur Laravel + Python, UI/UX, skema 4 lapisan, fase 0–6                                                                                                                                                                                              |
| **1.1** | **30 Oktober 2026** | Tinjauan pemangkasan: panel native (tanpa Filament), sinkron Google bertahap, MySQL + Qdrant, status disederhanakan, skema per fase, gerbang Python, tanpa impor spreadsheet, data contoh lengkap dan skenario uji, keamanan sejak Fase 0, kapasitas mingguan, anggaran waktu dan kriteria gagal, ADR |

Entri berikutnya ditambahkan di sini setiap kali dokumen berubah bermakna.

---

## 18. Glosarium

| Istilah                          | Arti                                                                                         |
| -------------------------------- | -------------------------------------------------------------------------------------------- |
| **ADR**                    | Architecture Decision Record: catatan satu keputusan arsitektur beserta alasannya            |
| **Agent**                  | Alur multi-langkah yang direncanakan AI dan dijalankan lewat service dengan persetujuan      |
| **Allowlist**              | Daftar identitas yang diizinkan (di sini: chat Telegram milik pemilik)                       |
| **Backoff**                | Jeda yang makin panjang antar percobaan ulang                                                |
| **Beban terencana**        | Jumlah menit pekerjaan yang direalisasikan atau dijadwalkan/jatuh tempo pada minggu berjalan |
| **Busy block**             | Rentang waktu sibuk dari kalender lain yang hanya dibaca                                     |
| **Capture**                | Menangkap ide/tugas secepat mungkin tanpa mengklasifikasikan                                 |
| **DoD**                    | Definition of Done: daftar periksa penyelesaian sebuah fase                                  |
| **Embedding**              | Representasi angka dari teks untuk pencarian semantik                                        |
| **Gerbang (gate)**         | Syarat yang harus dipenuhi sebelum memulai fase atau keputusan berikutnya                    |
| **Idempoten**              | Operasi yang dijalankan berulang tetap menghasilkan keadaan yang sama                        |
| **Inbox**                  | Tempat penampungan catatan mentah sebelum ditriase                                           |
| **Incremental sync**       | Sinkronisasi hanya perubahan sejak titik terakhir (`syncToken`)                            |
| **Irisan vertikal**        | Satu fitur utuh dari database sampai UI sehingga bisa langsung dipakai                       |
| **Kapasitas**              | Jumlah jam fokus yang tersedia per minggu (`weekly_capacity_minutes`)                      |
| **Kuadran (Matrix)**       | Pengelompokan task: Kerjakan, Jadwalkan, Cepatkan/Delegasikan, Suatu saat                    |
| **Monolith modular**       | Satu aplikasi yang dipisah rapi per modul dalam kode, tanpa layanan terpisah                 |
| **NARA otoritatif**        | Dalam konflik data, versi NARA yang berlaku                                                  |
| **Output**                 | Hasil nyata di luar NARA (repo, dokumen, prototipe, artikel, dsb.)                           |
| **Parking Lot**            | Daftar ide yang sengaja ditunda sampai pemicunya terpenuhi                                   |
| **Qdrant**                 | Basis data vektor untuk pencarian semantik                                                   |
| **RPO / RTO**              | Batas kehilangan data / batas waktu pemulihan                                                |
| **Service layer**          | Lapisan kode yang menjadi satu-satunya pintu aturan bisnis dan penulisan data                |
| **Single source of truth** | Satu tempat data yang dianggap benar; lainnya klien                                          |
| **Slot WIP**               | Kelompok `academic`, `business`, `personal` tempat batas project aktif diterapkan      |
| **Stagnan**                | Project aktif tanpa aktivitas lebih dari ambang hari                                         |
| **Triase**                 | Memutuskan nasib item Inbox (task, ide, someday, buang)                                      |
| **WIP**                    | Work In Progress: jumlah pekerjaan yang berjalan bersamaan                                   |

---

## 19. Prinsip Penutup

> **NARA tidak boleh membuat penggunanya sibuk mengelola NARA. NARA harus membuat penggunanya lebih banyak menghasilkan sesuatu di luar NARA.**

Prinsip ini berlaku di setiap fase dan setiap keputusan desain. Bila sebuah fitur atau tampilan menambah beban administratif tanpa menambah kejelasan atau tindakan nyata, fitur itu ditunda, disederhanakan, atau dimatikan.

Tiga pertanyaan penguji untuk setiap perubahan:

1. Keluhan mana (di Bagian 1.2) yang dijawab oleh perubahan ini?
2. Apakah ia menambah atau mengurangi administrasi harian?
3. Apakah ia membuat saya lebih banyak menghasilkan sesuatu di luar NARA?

Bila jawabannya tidak jelas, tulis di Parking Lot dan kembali bekerja.

*— Akhir dokumen —*
