# NARA — Panduan Arsitektur & Pengembangan Final

## Edisi Solo Developer

> Versi Final 1.0 · 24 September 2026
> PT Imfar Manggala Nara · Lini NARA AI (Personal AI Agent)
> Menggabungkan hasil diskusi arsitektur/database (`nara.md`) dengan identitas & filosofi produk (`rancangan.md`), ditambah rancangan UI/UX yang sebelumnya belum dituliskan secara eksplisit. Identitas merek lengkap ada di `pt_imfar.md`.
> Preferensi teknologi: **Laravel** (core system) + **Python** (AI/Intelligence layer).

---

## 0. Cara Memakai Dokumen Ini

NARA dibangun sebagai *monolith modular* Laravel murni untuk versi 1.0, dengan Python/AI baru masuk penuh di versi 2.0 — namun **Laravel dan Python disiapkan berdampingan sejak awal**:

- **Laravel = Core System** (auth, CRUD, dashboard, kanban/table/calendar view, inbox, notification, integrasi Google/Telegram). Ini *single source of truth* untuk semua data.
- **Python = Intelligence Service**, berjalan sebagai satu service tambahan (bukan microservices penuh), dipanggil Laravel lewat internal API. Python **tidak** menjadi tempat penyimpanan data utama — ia membaca via API/DB read-only dan menulis balik lewat API Laravel agar validasi & aturan bisnis tetap satu pintu.
- Prinsip *"jangan bangun microservices dulu"* tetap dipegang — hanya **dua service**. Python baru "hidup" penuh mulai NARA 2.0, tapi fondasinya (kontrak API, folder, deployment) disiapkan sejak awal.

**Prinsip yang tidak boleh dilanggar di semua fase:**

1. **Jangan mulai AI sebelum NARA 1.0 dipakai harian minimal beberapa minggu.**
2. **Maksimal fondasi dulu, fitur belakangan** — migrasi database dan struktur folder dibuat lengkap sejak Fase 0 walau belum semua tabel dipakai.
3. **Setiap fase punya Definition of Done yang jelas** — jangan pindah fase sebelum checklist selesai.
4. Tidak ada target tanggal kaku — ini proyek personal solo developer; durasi tiap fase bisa beberapa minggu sampai beberapa bulan. Yang penting urutannya, bukan kecepatannya.

---

## 1. Konsep Produk NARA

### 1.1 Positioning

**NARA** adalah lini teknologi AI PT Imfar Manggala Nara yang berfokus pada kecerdasan buatan yang bekerja bersama manusia — bukan sekadar chatbot, melainkan **Personal AI Agent**.

**Kepanjangan:** NARA — *Neural Autonomous Reasoning Agent* ("AI" adalah kategori teknologinya, bukan bagian dari kepanjangan).

> **NARA dirancang sebagai Personal AI Agent yang bekerja bersama manusia untuk memahami, merencanakan, mengatur, dan membantu menyelesaikan berbagai aktivitas personal.**

Filosofi hubungan dengan Manggala (lihat `pt_imfar.md`):

```text
MANGGALA: Learn → Create → Share            → Human Development
NARA:     Understand → Plan → Assist → Act  → Human Augmentation
```

**Prinsip penutup yang berlaku di setiap fase pengembangan:**

> NARA tidak boleh membuat penggunanya sibuk mengelola NARA. NARA harus membuat penggunanya lebih banyak menghasilkan sesuatu di luar NARA.

### 1.2 Karakter Produk

NARA tidak digambarkan sebagai robot yang hanya memberi perintah. Enam karakter interaksi yang wajib terasa di setiap layar dan setiap balasan:

| Karakter | Arti bagi desain & interaksi |
|---|---|
| **Intelligent** | Memahami konteks dan melakukan reasoning — bukan sekadar mencocokkan kata kunci |
| **Proactive** | Tidak hanya menunggu perintah ketika memiliki izin dan konteks yang cukup |
| **Personal** | Memahami kebutuhan dan pola kerja pengguna, bukan generik untuk semua orang |
| **Reliable** | Menyampaikan informasi jelas, tidak berpura-pura tahu sesuatu yang tidak diketahui |
| **Calm** | Interaksi terasa tenang, tidak menambah beban pengguna |
| **Supportive** | Membantu ketika pekerjaan terasa terlalu banyak atau kompleks |

Unsur "sahabat" hadir sebagai **karakter interaksi**, bukan klaim bahwa NARA menggantikan manusia atau tenaga profesional.

### 1.3 Fungsi Inti

Task management · Planning · Personal organization · Reminder · Calendar management · Information retrieval · Context management · Decision support · Workflow automation · Personal knowledge management · Computer/app interaction · Conversational assistance.

Domain data yang dikelola membentuk hirarki:

```text
Life Domain → Goal → Project → Task → Subtask
```

Ini menjadi tulang punggung seluruh tampilan dan skema database di dokumen ini.

---

## 2. Ringkasan Arsitektur Sistem

### 2.1 Prinsip Arsitektur

```text
NARA DATABASE = SINGLE SOURCE OF TRUTH
```

Semua modul lain (web, calendar, Telegram, AI) hanya **klien** dari database ini — bukan penyimpanan datanya sendiri.

### 2.2 Diagram Arsitektur Tingkat Tinggi

```text
                                   ┌──────────────────────────┐
                                   │        USERS (Web)       │
                                   └─────────────┬─────────────┘
                                                 │
                                   ┌─────────────▼─────────────┐
                                   │     NGINX (Reverse Proxy) │
                                   └───────┬─────────────┬─────┘
                                           │             │
                         ┌─────────────────▼───┐   ┌─────▼─────────────────┐
                         │   LARAVEL CORE APP   │   │  PYTHON AI SERVICE    │
                         │   (Monolith Modular) │◄──┤  (FastAPI, internal)  │
                         │                       │──►│                       │
                         │  - Auth               │   │  - NLP command parser │
                         │  - Projects/Tasks/CRUD│   │  - Daily/Weekly       │
                         │  - Dashboard           │   │    Planner engine     │
                         │  - Kanban/Table/Timeline│   │  - Overload &         │
                         │  - Inbox               │   │    Stagnation detector│
                         │  - Notification dispatcher│ │  - Output Intelligence│
                         │  - Integration orchestr.│   │  - Chatbot & Agent    │
                         │  - Laravel Queue/Scheduler│  │    (2.0 / 3.0)        │
                         └───────┬───────────┬────┘   └─────┬──────────────────┘
                                 │           │                │
                         ┌───────▼───┐ ┌─────▼─────┐   ┌──────▼───────┐
                         │  MySQL /  │ │   Redis    │   │  Vector Store │
                         │ PostgreSQL│ │(cache/queue)│   │ (pgvector /   │
                         │ (primary) │ │            │   │  Qdrant, 2.0+)│
                         └───────────┘ └────────────┘   └───────────────┘
                                 │
                 ┌───────────────┼────────────────┐
                 ▼               ▼                 ▼
        Google Calendar API   Telegram Bot API   Local/S3 Storage
```

### 2.3 Pembagian Tanggung Jawab

| Layer | Tanggung jawab | Alasan pakai teknologi ini |
|---|---|---|
| **Laravel (Core)** | Auth, semua CRUD (life domain → goal → project → task), dashboard, semua view (table/kanban/timeline/matrix/calendar), Inbox & triage, notification dispatcher, orchestrator integrasi Google & Telegram, job scheduling | Unggul untuk web app CRUD, auth, dan job scheduling matang (queue, scheduler, Eloquent ORM) |
| **Python (Intelligence Service)** | NLP command parsing, AI daily/weekly planner, overload & stagnation detection (bisa dimulai rule-based di sini juga), output intelligence report, chatbot, agent workflow (3.0) | Ekosistem Python lebih kaya untuk NLP/LLM orchestration, data analysis (pandas), dan ML bila dibutuhkan |
| **MySQL/PostgreSQL** | Database utama, *source of truth* | Relational, cocok untuk struktur hierarkis Life → Goal → Project → Task |
| **Redis** | Queue Laravel, cache, rate-limit Telegram bot | Ringan, standar pasangan Laravel |
| **Vector Store (pgvector/Qdrant)** | Semantic search atas outputs/tasks/notes untuk fitur AI 2.0+ | Dibutuhkan saat chatbot & agent butuh context retrieval |
| **Google Calendar API** | Sinkronisasi jadwal dua arah | NARA tetap sumber data; Calendar hanya tampilan waktu |
| **Telegram Bot API** | Reminder, daily briefing, evening summary, quick capture | Paling ringan untuk notifikasi + input cepat dari HP |

### 2.4 Struktur Folder — Laravel (Core)

```text
app/
├── Models/
│   ├── LifeDomain.php
│   ├── Goal.php
│   ├── Project.php
│   ├── Task.php
│   ├── Schedule.php
│   ├── Output.php
│   ├── InboxItem.php
│   └── ...
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── ProjectController.php
│   │   ├── TaskController.php
│   │   ├── InboxController.php
│   │   └── Api/
│   │       └── AiBridgeController.php     # endpoint yang dipanggil Python service
├── Services/
│   ├── Calendar/
│   │   └── GoogleCalendarService.php
│   ├── Notification/
│   │   └── TelegramService.php
│   ├── Nara/
│   │   ├── TaskService.php
│   │   ├── ProjectService.php
│   │   ├── OutputService.php
│   │   └── ActivityLogService.php
│   └── AI/
│       └── NaraAIClient.php               # HTTP client ke Python service
├── Jobs/
│   ├── SendTelegramReminder.php
│   ├── SendDailyBriefing.php
│   └── SyncGoogleCalendar.php
└── Console/Commands/
    └── ... (scheduler commands)
```

### 2.5 Struktur Folder — Python AI Service (FastAPI)

```text
nara-ai-service/
├── main.py
├── api/
│   ├── routes_planner.py        # daily/weekly planner
│   ├── routes_nlp.py            # natural language command parsing
│   ├── routes_insight.py        # overload/stagnation/output intelligence
│   ├── routes_chat.py           # chatbot (2.0)
│   └── routes_agent.py          # agent workflow (3.0)
├── core/
│   ├── config.py                # baca token internal, DB read config
│   ├── security.py              # verifikasi service-to-service token
│   └── nara_client.py           # HTTP client BALIK ke Laravel API (untuk tulis data)
├── services/
│   ├── planner_engine.py
│   ├── nlp_engine.py
│   ├── overload_detector.py
│   ├── stagnation_detector.py
│   └── output_intelligence.py
├── models/                      # Pydantic schemas, BUKAN ORM tulis-langsung ke DB
├── embeddings/
│   └── vector_store.py
└── requirements.txt
```

**Aturan penting:** Python service boleh **membaca** database (via read replica/read-only user) untuk analitik berat, tapi **setiap perubahan data (task baru, status berubah, schedule dibuat) tetap ditulis lewat Laravel API** (`AiBridgeController`). Ini menjaga validasi, activity log, dan business rule tetap satu pintu di Laravel.

### 2.6 Contoh Alur: Natural Language Command (fitur AI 2.0)

```text
User (Telegram/Web chat)
   │  "Besok pagi saya mau kerjakan MSKI 2 jam"
   ▼
Laravel (terima pesan) ──► Python /api/nlp/parse
                                   │
                                   ▼
                     { project: "MSKI", duration: 120,
                       date: "2026-09-24", start: "09:00" }
                                   │
   ◄───────────────────────────────┘
Laravel: validasi → buat Schedule + Task (jika belum ada)
   │
   ▼
GoogleCalendarService → sync ke Google Calendar
TelegramService → konfirmasi ke user
```

Pola ini dipakai ulang untuk *Daily Planner*, *Overload Detection*, *Stagnation Detection*, dan *Agent* di fase-fase berikutnya — Python selalu berperan sebagai "otak analisis", Laravel selalu jadi "penjaga data & eksekutor".

---

## 3. Desain UI/UX & Konsep Visual

### 3.1 Fondasi Identitas

Identitas merek lengkap ada di `pt_imfar.md` Bagian 5. Ringkasan yang relevan untuk implementasi produk:

| Elemen | Nilai |
|---|---|
| Konsep logo | Human–AI Connection: node, garis koneksi, orbit, ruang negatif — bukan "robot AI" |
| Arah warna | **Deep Blue / Cyan** sebagai warna dasar + **Violet accent** — futuristik tapi tetap manusiawi, hindari kesan "cyberpunk" |
| Tipografi | Satu keluarga sans-serif modern yang sama dengan ekosistem PT — kesan futuristik dibangun lewat bentuk UI dan micro-interaction, **bukan** lewat font yang sulit dibaca |
| Brand personality | Intelligent — Calm — Personal; berbicara seperti asisten teknologi yang memahami konteks |
| Tagline | *Your Personal AI Agent* / *Intelligence That Works With You* |

### 3.2 Prinsip UX Utama

1. **Ketenangan di atas kepadatan.** NARA mengelola kehidupan yang sudah kompleks (life domain, goal, project, task berlapis) — antarmuka harus terasa lapang, bukan seperti spreadsheet yang dipindahkan ke web. Ruang kosong (whitespace) dipakai sengaja.
2. **Satu fokus per layar.** Setiap halaman punya satu pertanyaan utama yang dijawab: *Apa yang harus saya kerjakan sekarang?* (Dashboard/Today), *Apa progres proyek ini?* (Project view), *Apa yang belum diproses?* (Inbox).
3. **Reversibilitas dan kejujuran data.** Karena ledger-style data (activity log, kredit, dsb. muncul juga di ekosistem Manggala) menjadi prinsip NARA lewat `activity_logs` — UI harus selalu bisa menunjukkan "kenapa angka ini begini" (klik progres → lihat riwayat perubahan).
4. **Mobile untuk capture, desktop untuk mengelola.** Input cepat (Inbox, quick capture, Telegram) dioptimalkan untuk HP di sela aktivitas; pengelolaan mendalam (kanban, timeline, matrix) dioptimalkan untuk layar lebar.
5. **AI selalu terlihat sebagai saran, bukan keputusan sepihak.** Rekomendasi AI (daily plan, overload alert) selalu punya tombol terima/tolak yang jelas — sesuai batasan "keputusan tetap di user".

### 3.3 Peta Layar Utama

```text
NARA
│
├── Today / Dashboard      → KPI cards: Today, Progress, Output, Attention
├── Inbox                  → Quick capture & triage
├── Projects
│   ├── Table View
│   ├── Kanban View
│   ├── Timeline View        (NARA 1.0)
│   ├── Matrix View (Impact/Urgency)  (NARA 1.0)
│   └── Calendar View        (NARA 1.0)
├── Task Detail             → comments, attachments, subtasks, tags, dependencies
├── Outputs                 → form + listing hasil karya/output nyata
├── Focus Mode               → timer sesi fokus terhubung ke task
├── Weekly Review            → ritual refleksi mingguan
├── Chat / Assistant         (NARA 2.0) → percakapan berbasis data NARA sendiri
└── Settings                 → integrasi Google/Telegram, kapasitas mingguan, preferensi notifikasi
```

### 3.4 Rancangan Layar Kunci

**Dashboard / Today**

```text
NARA — Hari ini, 24 September 2026

┌─────────────┬─────────────┬─────────────┬─────────────┐
│   TODAY     │  PROGRESS   │   OUTPUT    │  ATTENTION  │
│ 3 jadwal    │ 62% goal    │ 2 output    │ 1 stagnan   │
│ 5 task jatuh│ aktif       │ minggu ini  │ 2 overload  │
│ tempo       │             │             │ warning     │
└─────────────┴─────────────┴─────────────┴─────────────┘

Jadwal Hari Ini
09:00 — MSKI (2 jam)
13:00 — Client Meeting

Perlu Perhatian
⚠ Project "Robotics" belum ada aktivitas 9 hari
⚠ Minggu ini estimasi 42 jam kerja, kapasitas 35 jam
```

Empat kartu KPI (Today, Progress, Output, Attention) adalah elemen tetap — sesuai rancangan Fase 1 (NARA 0.5). Kartu "Attention" adalah tempat rule-based/AI alert (stagnation, overload) muncul pertama kali ke pengguna.

**Project View — Kanban**

```text
BACKLOG      READY        IN PROGRESS    BLOCKED      REVIEW       DONE
┌────────┐   ┌────────┐   ┌────────┐     ┌────────┐   ┌────────┐   ┌────────┐
│ Task A │   │ Task D │   │ Task F │     │ Task H │   │ Task J │   │ Task K │
└────────┘   └────────┘   └────────┘     └────────┘   └────────┘   └────────┘
```

Status task mengikuti enum: `backlog, ready, in_progress, blocked, waiting, review, done, cancelled`. Kartu kanban menampilkan prioritas (`critical/high/normal/low`) sebagai indikator warna kecil, bukan badge besar — menjaga ketenangan visual.

**Task Detail**

```text
Task: Buat wireframe landing page
Project: MANGGALA Website          Status: In Progress
Priority: High     Impact: High    Urgency: Medium
Estimasi: 120 menit   Aktual: 45 menit

Subtasks
☑ Riset referensi
☐ Sketsa low-fidelity
☐ Review dengan diri sendiri

Comments · Attachments · Tags · Dependencies
```

**Inbox / Quick Capture**

```text
+ Tambah catatan cepat...

Pending (4)
─ "Cek harga domain baru"
─ "Ide: fitur voice note di NARA"
─ "Follow up klien X minggu depan"

[Konversi ke: Project / Task / Goal / Incubator / Someday]
```

Inbox adalah katup pelepas — semua ide masuk ke sini dulu tanpa perlu diklasifikasikan di tempat, baru ditriase kemudian. Ini prinsip UX kunci agar NARA tidak terasa memaksa keputusan struktural saat pengguna sedang menangkap ide cepat.

**Weekly Review**

```text
Weekly Review — 22–28 September 2026

Top 3 Prioritas Minggu Ini
1. ...
2. ...
3. ...

Refleksi
...

Ringkasan otomatis: 14 task selesai · 2 output tercatat
```

**Chat / Assistant (NARA 2.0)**

```text
Anda: "Besok pagi saya mau kerjakan MSKI 2 jam"
NARA: Sudah saya jadwalkan MSKI, besok 09:00–11:00, dan
      disinkronkan ke Google Calendar. Ada yang ingin
      diubah?
```

Nada bahasa NARA: singkat, jelas, tidak berlebihan basa-basi, selalu mengonfirmasi tindakan yang baru diambil — konsisten dengan karakter *Calm* dan *Reliable*.

### 3.5 Komponen & Aksesibilitas

- Satu design token system (warna, spacing, radius, tipografi) dipakai di web dashboard maupun (nanti) tampilan chat Telegram agar identitas tetap konsisten lintas kanal.
- Status task/project selalu direpresentasikan dengan **warna + label teks**, tidak warna saja (aksesibilitas warna-buta).
- Semua aksi destruktif (hapus project, cabut integrasi) meminta konfirmasi eksplisit dan tercatat di `activity_logs`.
- Karena ini alat personal harian, waktu muat halaman dan responsivitas input dianggap bagian dari UX, bukan hanya bagian performa teknis — target interaksi terasa instan (< 200ms untuk aksi lokal seperti update status task).

---

## 4. Rancangan Database

Skema dirancang berlapis: **Core (1.0)** wajib ada dari awal, **Analytics (1.5)**, **AI (2.0)**, dan **Agent (3.0)** ditambahkan belakangan tanpa mengubah struktur inti — hanya menambah tabel baru + kolom opsional.

### 4.1 Diagram Relasi (Konseptual)

```text
users
  │
  ├── life_domains ──► goals ──► projects ──► tasks ──► task_subtasks
  │                                  │            │
  │                                  │            ├── task_dependencies
  │                                  │            ├── task_comments
  │                                  │            ├── task_attachments
  │                                  │            ├── task_tags ──► tags
  │                                  │            ├── schedules ──► calendar_events
  │                                  │            └── outputs ──► output_files
  │                                  │
  │                                  ├── project_members
  │                                  └── project_categories (Type: FEATURE, BUG, ...)
  │
  ├── inbox_items          (belum jadi project/task — hasil quick capture)
  ├── notifications ──► notification_logs
  ├── integrations ──► integration_accounts   (token OAuth Google/Telegram)
  ├── activity_logs        (polymorphic: mencatat semua entitas di atas)
  └── focus_sessions        (timer fokus, terhubung ke task)

--- lapisan 1.5 ---
weekly_reviews, project_health_snapshots, workload_estimates

--- lapisan 2.0 (AI) ---
ai_conversations ──► ai_messages
ai_commands
ai_recommendations
embeddings (vector, terhubung ke tasks/projects/outputs)

--- lapisan 3.0 (Agent) ---
agent_runs ──► agent_actions
agent_permissions
```

### 4.2 Tabel Inti — NARA 1.0

**`users`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| email | varchar unique | |
| password | varchar | |
| timezone | varchar | default Asia/Jakarta |
| telegram_chat_id | varchar nullable | dipakai bot untuk kirim pesan |
| created_at / updated_at | timestamp | |

**`life_domains`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| name | varchar | mis. Career, Business, Technology, Health |
| color | varchar | untuk dashboard visual |
| sort_order | int | |

**`goals`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| life_domain_id | FK → life_domains | |
| title | varchar | |
| description | text nullable | |
| target_date | date nullable | |
| status | enum | `active, achieved, abandoned` |
| progress_percent | int | 0–100, dihitung dari project turunannya |

**`project_categories`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar | FEATURE, IMPROVEMENT, BUG, RESEARCH, LEARNING, ADMINISTRATIVE, PERSONAL, CLIENT, IDEA, MAINTENANCE |
| kind | enum | `project` atau `task` (dipakai di dua level) |

**`projects`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| goal_id | FK → goals, nullable | project bisa berdiri sendiri tanpa goal eksplisit |
| user_id | FK → users | |
| project_category_id | FK → project_categories | |
| name | varchar | |
| description | text nullable | |
| origin | enum | `personal, external, freelance, research, academic` |
| status | enum | `idea, incubator, planned, active, paused, completed, archived` |
| wip_slot | enum nullable | `academic, business, personal` — enforce maksimal 3 `active` per slot |
| priority | enum | `critical, high, normal, low` |
| impact | enum | `high, medium, low` |
| urgency | enum | `high, medium, low` |
| start_date / due_date | date nullable | |
| progress_percent | int | dihitung dari task turunannya |
| definition_of_done | text nullable | checklist dalam bentuk JSON/markdown |
| last_activity_at | timestamp nullable | dipakai untuk stagnation detection |
| created_at / updated_at | timestamp | |

**`project_members`** *(disiapkan sejak 1.0, relevan saat kolaborasi/freelance)*

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects | |
| name | varchar | kolaborator/klien, belum tentu user NARA |
| role | varchar | |

**`tasks`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects | |
| task_category_id | FK → project_categories | |
| title | varchar | |
| description | text nullable | |
| status | enum | `backlog, ready, in_progress, blocked, waiting, review, done, cancelled` |
| priority | enum | `critical, high, normal, low` |
| impact | enum | `high, medium, low` |
| urgency | enum | `high, medium, low` |
| estimated_minutes | int nullable | dasar workload analysis |
| actual_minutes | int nullable | diisi dari `focus_sessions` |
| start_date / due_date | date nullable | |
| progress_percent | int | |
| created_at / updated_at | timestamp | |

**`task_subtasks`, `task_dependencies`, `task_comments`, `task_attachments`, `tags`/`task_tags`**

| Tabel | Kolom penting |
|---|---|
| `task_subtasks` | id, task_id, title, is_done, sort_order |
| `task_dependencies` | id, task_id, depends_on_task_id |
| `task_comments` | id, task_id, body, created_at |
| `task_attachments` | id, task_id, file_path, file_type, uploaded_at |
| `tags` | id, user_id, name, color |
| `task_tags` | task_id, tag_id (pivot) |

**`schedules`, `calendar_events`**

| Tabel | Kolom penting |
|---|---|
| `schedules` | id, task_id, start_at, end_at, is_synced_to_calendar |
| `calendar_events` | id, schedule_id, google_event_id, sync_status (`synced/pending/failed`), last_synced_at |

**`outputs`, `output_files`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects, nullable | |
| task_id | FK → tasks, nullable | |
| title | varchar | |
| output_type | enum | `github, pdf, paper, prototype, website, article, dataset, presentation, video, circuit, pcb, software, documentation, business_doc, legal_doc, physical_prototype, certificate, teaching_material, other` |
| evidence_url | varchar nullable | link GitHub/URL/PDF |
| produced_at | date | |

`output_files`: id, output_id (FK), file_path.

**`inbox_items`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| content | text | isi quick capture mentah |
| source | enum | `web, telegram` |
| status | enum | `pending, converted, discarded` |
| converted_to_type | enum nullable | `project, task, goal, incubator, someday` |
| converted_to_id | bigint nullable | id hasil konversi (polymorphic ringan) |

**`notifications` + `notification_logs`**

| Tabel | Kolom penting |
|---|---|
| `notifications` | id, user_id, type (`reminder, daily_briefing, evening_summary, overload_alert, stagnation_alert`), payload (json), scheduled_at |
| `notification_logs` | id, notification_id, channel (`telegram, web`), status (`sent, failed`), sent_at |

**`integrations` + `integration_accounts`**

| Tabel | Kolom penting |
|---|---|
| `integrations` | id, name (`google_calendar, telegram`), is_active |
| `integration_accounts` | id, user_id, integration_id, access_token, refresh_token, expires_at |

**`activity_logs`** *(polymorphic — fondasi penting untuk AI nanti)*

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| subject_type | varchar | nama model, mis. `Task`, `Project` |
| subject_id | bigint | |
| event | varchar | `created, status_changed, progress_updated, comment_added, output_uploaded` |
| before_value / after_value | json nullable | |
| created_at | timestamp | |

**`focus_sessions`** *(fitur "Current Focus" / timer)*

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| task_id | FK → tasks | |
| started_at / ended_at | timestamp | |
| duration_minutes | int | dihitung otomatis |

### 4.3 Lapisan NARA 1.5 — Analytics

**`weekly_reviews`**: id, user_id, week_start_date, top_priorities (json), reflection_notes, completed_task_count, output_count.

**`project_health_snapshots`**: id, project_id, snapshot_date, days_since_last_activity, progress_percent, health_status (`healthy, slowing, stagnant`).

**`workload_estimates`**: id, user_id, week_start_date, estimated_hours, available_hours, is_overloaded (boolean, dihitung otomatis).

### 4.4 Lapisan NARA 2.0 — AI

| Tabel | Kolom penting |
|---|---|
| `ai_conversations` | id, user_id, title, created_at |
| `ai_messages` | id, conversation_id, role (`user, assistant`), content, created_at |
| `ai_commands` | id, user_id, raw_text, parsed_intent (mis. `create_schedule`), parsed_payload (json), executed (boolean) |
| `ai_recommendations` | id, user_id, type (`overload, stagnation, output_gap, daily_plan`), payload (json), status (`pending, accepted, dismissed`), created_at |
| `embeddings` | id, subject_type (`task, project, output`), subject_id, vector_ref, updated_at — vector sebenarnya disimpan di pgvector/Qdrant |

### 4.5 Lapisan NARA 3.0 — Agent

| Tabel | Kolom penting |
|---|---|
| `agent_runs` | id, user_id, goal_description, status (`running, waiting_approval, completed, failed`), started_at, finished_at |
| `agent_actions` | id, agent_run_id, step_order, action_type (mis. `fetch_data, create_schedule, send_telegram`), input_payload/output_payload (json), requires_approval, approved_at |
| `agent_permissions` | id, user_id, action_type, auto_allowed (boolean) |

---

## 5. Panduan Pembangunan (Fase Developer)

### FASE 0 — Foundation (target label: NARA 0.1)

**Tujuan:** Database + CRUD dasar berjalan, tanpa peduli tampilan.

**Cakupan:**
- Setup project Laravel + repo git + environment (`.env`, CI dasar opsional).
- Migration seluruh tabel **Core 1.0** (Bagian 4.2), walau sebagian belum dipakai UI-nya.
- Auth (Laravel Breeze/Fortify — sederhana, single-user dulu).
- CRUD murni (Filament/Nova atau controller sederhana) untuk: `life_domains`, `goals`, `projects`, `tasks`.
- Struktur folder `app/Services/...` disiapkan dari awal (kosong dulu).

**Definition of Done:**
```
[ ] Auth berjalan
[ ] Semua migration Core 1.0 berhasil dijalankan
[ ] Bisa membuat life_domain → goal → project → task lewat form/admin sederhana
[ ] Data tersimpan benar sesuai relasi
[ ] Repo di-push ke Git dengan README awal
```

### FASE 1 — Visual Dasar (target label: NARA 0.5)

**Tujuan:** Modul-modul inti mulai terlihat dan dipakai sendiri sehari-hari.

**Cakupan:**
- Dashboard versi awal (KPI cards: Today, Progress, Output, Attention — lihat Bagian 3.4).
- Project view: **Table View** dan **Kanban View**.
- Task detail page (comments, attachments, subtasks, tags).
- Modul Output (form + listing `outputs`).
- Modul Inbox (quick capture web + triase manual ke project/task/goal/incubator/someday).
- Schedule dasar (input manual `schedules`, belum sinkron Google).

**Definition of Done:**
```
[ ] Dashboard menampilkan data real dari database
[ ] Kanban board bisa drag status
[ ] Output bisa dicatat & dilihat historinya
[ ] Inbox berfungsi sebagai capture cepat dan bisa dikonversi
[ ] Pengguna mulai memakainya menggantikan sebagian spreadsheet
```

### FASE 2 — NARA 1.0 (Sistem Lengkap, Deterministik — belum AI)

**Tujuan:** Spreadsheet benar-benar bisa ditinggalkan.

**Cakupan:**
- View tambahan: **Timeline**, **Matrix (Impact/Urgency)**, **Calendar view**.
- `project_categories` lengkap diisi (FEATURE, BUG, RESEARCH, dst.) menggantikan kolom `Type` lama.
- Status project & task lengkap sesuai Bagian 4.2.
- **Google Calendar integration** (`GoogleCalendarService`, tabel `calendar_events`, OAuth via `integration_accounts`).
- **Telegram Bot** (`TelegramService`): reminder per task, morning briefing (jadwal + deadline + overdue), evening summary, quick capture via `/add` dan `/idea`.
- Reminder engine via Laravel Scheduler + Queue.
- Weekly Review ritual (form `weekly_reviews`, masih manual diisi).
- `activity_logs` aktif mencatat semua perubahan (fondasi wajib untuk AI nanti).
- `focus_sessions` (mode fokus + timer sederhana).
- WIP limit enforcement (validasi: maksimal 3 project `active` per `wip_slot`).

**Definition of Done:**
```
[ ] Tidak lagi membutuhkan spreadsheet
[ ] Semua project ada di NARA
[ ] Semua task ada di NARA
[ ] Dashboard bisa dilihat
[ ] Calendar bisa dilihat
[ ] Telegram reminder diterima
[ ] Output bisa dicatat
[ ] Weekly review bisa dilakukan
[ ] NARA dipakai setiap hari
```

Baru setelah checklist ini lengkap dan dipakai stabil beberapa minggu–bulan, lanjut ke fase berikutnya.

### FASE 3 — NARA 1.5 (Analytics & Automation, masih Laravel-only)

**Tujuan:** Sistem mulai memberi insight, tapi masih rule-based (belum LLM).

**Cakupan:**
- Migration tabel Analytics 1.5 (`project_health_snapshots`, `workload_estimates`).
- Workload analysis sederhana: jumlah `estimated_minutes` per minggu vs kapasitas → alert manual (query + threshold, belum AI).
- Project stagnation detector rule-based (query `last_activity_at` > N hari).
- Weekly Output Report otomatis (generate dari `outputs` + `activity_logs`, kirim via Telegram).
- Automation tambahan: auto-reminder untuk task `waiting` yang lama tak berubah.

**Definition of Done:**
```
[ ] Sistem otomatis memberi warning overload tanpa AI
[ ] Sistem otomatis menandai project stagnan
[ ] Weekly output report terkirim otomatis tiap minggu
[ ] Semua fitur 1.5 dipakai minimal 1 bulan sebelum lanjut ke AI
```

### FASE 4 — Bootstrap Python Service & NARA 2.0 (AI Assistant)

**Tujuan:** Masuk fase AI — Python service resmi diaktifkan.

**Cakupan teknis:**
- Setup `nara-ai-service` (FastAPI) + deployment terpisah di VPS yang sama (port internal, tidak publicly exposed — hanya nginx yang meneruskan `/api/ai/*`).
- Kontrak API Laravel ↔ Python didefinisikan dulu (OpenAPI spec) sebelum coding fitur.
- Setup vector store (pgvector paling sederhana karena satu database dengan PostgreSQL) untuk `embeddings`.
- Migration tabel AI 2.0 (`ai_conversations`, `ai_messages`, `ai_commands`, `ai_recommendations`, `embeddings`).

**Cakupan fitur:**
- Natural Language Command (`/api/nlp/parse`) → otomatis buat schedule/task dari kalimat bebas.
- AI Daily Planner (membaca deadline, priority, calendar, workload → susun jadwal harian, keputusan tetap di user).
- Overload detection versi AI (menggantikan rule-based 1.5 dengan model lebih kontekstual, boleh tetap rule-based + LLM untuk narasi).
- Project stagnation detection versi AI (dengan penjelasan naratif, bukan cuma angka).
- Output Intelligence (laporan mingguan naratif, mis. "Robotics dapat 6 jam aktivitas tapi belum ada output").
- Chatbot berbasis data NARA sendiri (retrieval dari `embeddings` + database, bukan LLM murni tanpa konteks).

**Definition of Done:**
```
[ ] Python service berjalan stabil & terpisah dari Laravel
[ ] Kontrak API Laravel<->Python terdokumentasi
[ ] Natural language command berhasil membuat schedule dengan akurasi memadai
[ ] Chatbot bisa menjawab pertanyaan berbasis data NARA sendiri
[ ] AI Daily Planner dipakai rutin minimal beberapa minggu
```

### FASE 5 — NARA 3.0 (Agent)

**Tujuan:** Dari asisten pasif menjadi agent yang bisa mengeksekusi tugas multi-langkah (dengan approval).

**Cakupan:**
- Migration tabel Agent 3.0 (`agent_runs`, `agent_actions`, `agent_permissions`).
- Contoh workflow: "siapkan weekly report" → ambil data → hitung progres → ambil output → baca activity log → buat report → kirim Telegram.
- Contoh workflow: "jadwalkan semua task MSKI minggu depan" → baca task+deadline → cek Google Calendar → cari slot kosong → usulkan → minta approval → buat calendar event.
- Mekanisme approval wajib untuk aksi yang mengubah data penting (`agent_permissions.auto_allowed = false` sebagai default).
- Audit trail lengkap tiap `agent_actions` (agar bisa ditelusuri kalau agent salah bertindak).

**Definition of Done:**
```
[ ] Minimal 2 agent workflow berjalan end-to-end dengan approval flow
[ ] Semua aksi agent tercatat di agent_actions
[ ] Tidak ada aksi agent yang mengubah data tanpa jejak audit
```

### FASE 6 (opsional, setelah 3.0 stabil) — Hardening & Scaling

Dilakukan hanya jika NARA benar-benar dipakai intensif dan mulai terasa beban performanya:

- Review keamanan (rate limiting Telegram bot, rotasi token OAuth, backup database terjadwal).
- Pisahkan Python service jadi container tersendiri dengan resource limit jelas (Docker Compose cukup, belum perlu Kubernetes).
- Observability: logging terpusat (Laravel + Python), monitoring uptime (bisa reuse project monitoring internal yang sudah ada).
- Evaluasi ulang: bagian mana dari monolith Laravel yang layak dipisah jadi service sendiri (baru dipertimbangkan di titik ini, bukan sebelumnya).

### Ringkasan Urutan Fase

```text
NARA 0.1  → Foundation (DB + CRUD, Laravel only)
NARA 0.5  → Dashboard + Kanban/Table + Output + Inbox
NARA 1.0  → Views lengkap + Google Calendar + Telegram + Reminder + Weekly Review   ⟵ spreadsheet resmi ditinggalkan
NARA 1.5  → Analytics & automation rule-based (masih Laravel-only)
NARA 2.0  → Python AI Service aktif: NL command, Daily Planner, Chatbot, Output Intelligence
NARA 3.0  → Agent: workflow otonom dengan approval
NARA X    → Hardening & scaling (kondisional)
```

---

## 6. Prinsip Penutup

> **NARA tidak boleh membuat penggunanya sibuk mengelola NARA. NARA harus membuat penggunanya lebih banyak menghasilkan sesuatu di luar NARA.**

Prinsip ini berlaku di setiap fase pembangunan maupun setiap keputusan desain UI/UX: bila sebuah fitur atau tampilan menambah beban administratif tanpa menambah kejelasan atau tindakan nyata, fitur tersebut ditunda — konsisten dengan aturan "fondasi dulu, fitur belakangan" di Bagian 0.
