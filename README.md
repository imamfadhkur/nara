# NARA — Personal AI Agent (Core System)

> **Versi 1.0 (Fase 2 — Sistem Lengkap & Integrasi Eksternal)** · Lini NARA (Neural Autonomous Reasoning Agent)  
> PT Imfar Manggala Nara · Solo Developer Edition  
> Dokumen Acuan Tunggal: `nara_architecture_1.1.md`

NARA adalah Personal AI Agent yang bekerja bersama manusia untuk memahami, merencanakan, mengatur, dan membantu menyelesaikan berbagai aktivitas personal secara terstruktur, deterministik, dan terukur.

---

## 📑 Daftar Isi
1. [Prinsip & Arsitektur Utama](#-prinsip--arsitektur-utama)
2. [Peta Fitur per Fase](#-peta-fitur-per-fase)
   - [Fase 0: Fondasi Sistem (NARA 0.1)](#1-fondasi-sistem-fase-0--nara-01)
   - [Fase 1: Penggunaan Harian & Telegram (NARA 0.5)](#2-penggunaan-harian--integrasi-fase-1--nara-05)
   - [Fase 2: Sistem Lengkap & Integrasi Kalender (NARA 1.0)](#3-sistem-lengkap--integrasi-eksternal-fase-2--nara-10)
3. [Alur & Navigasi Web Panel](#-alur--navigasi-web-panel)
4. [Manajemen Integrasi Eksternal Dinamis](#-manajemen-integrasi-eksternal-dinamis)
5. [Panduan Instalasi & Menjalankan Sistem](#-panduan-instalasi--menjalankan-sistem)
6. [Koneksi Bot Telegram (Mobile)](#-koneksi-bot-telegram-mobile)
7. [Worker Scheduler & Notifikasi Otomatis](#-worker-scheduler--notifikasi-otomatis)
8. [Backup Terenkripsi & Keamanan](#-backup-terenkripsi--keamanan)
9. [Pengujian Otomatis (Test Suite)](#-pengujian-otomatis-test-suite)

---

## 🌟 Prinsip & Arsitektur Utama

- **Hirarki Data Inti:** `Life Domain → Goal → Project → Task → Subtask`
- **Output-Oriented:** Setiap task yang selesai didorong mencatat deliverable/karya nyata (*Output*) lengkap dengan unggah berkas bukti atau URL.
- **WipGuard Deterministic:** Maksimal 3 proyek aktif per slot (`academic`, `business`, `personal`) guna mencegah fragmentasi fokus.
- **Kapasitas Mingguan Berbasis Beban Nyata:** Formula kapasitas terverifikasi (default: 25 jam / 1.500 menit) dengan deteksi dini status *overload*.
- **Authoritative & Privacy First:** NARA adalah sumber kebenaran utama. Integrasi luar (Telegram & Google Calendar) tunduk pada aturan on/off dinamis dan data disimpan secara aman.

---

## 🗺️ Peta Fitur per Fase

### 1. Fondasi Sistem (Fase 0 — NARA 0.1)
- **Aturan Bisnis & Pagar Pengaman:**
  - **WipGuard (7.3):** Menolak aktivasi proyek ke-4 secara deterministik pada slot yang sama.
  - **ProgressCalculator (7.2):** Progres proyek dihitung otomatis dari bobot task selesai (`bobot = max(estimated_minutes, 30)`).
  - **TaskStatusRules (7.1):** 5 status task (`todo`, `in_progress`, `waiting`, `done`, `cancelled`). Status `waiting` wajib memiliki alasan menunggu (`waiting_reason`).
  - **MatrixClassifier (7.6):** Klasifikasi 4 kuadran Eisenhower otomatis berdasarkan urgensi dan kepentingan.
  - **ActivityLogger (6.9):** Satu pintu pencatatan log audit polimorfik untuk seluruh mutasi data.
- **Keamanan & Autentikasi (13.1):**
  - Registrasi publik dinonaktifkan (arsitektur *single-tenant*).
  - Autentikasi 2FA TOTP (Google Authenticator, Aegis, 1Password) dengan kode pemulihan offline terenkripsi.

### 2. Penggunaan Harian & Integrasi (Fase 1 — NARA 0.5)
- **Dashboard Today (`/panel/today`):**
  - 4 KPI utama, jadwal hari ini, task terlambat, dan banner kapasitas mingguan.
  - Deteksi proyek stagnan (> 5 hari tanpa sentuhan) dan task jatuh tempo besok.
- **Inbox & Triase Cepat (`/panel/inbox`):**
  - Tangkap ide instan via web & Telegram dengan hint `task` atau `idea`.
  - Triase 1-klik menjadi Task, Proyek baru, Someday, Goal, atau Buang.
- **Kanban Board 4 Kolom (`/panel/kanban`):**
  - Alur visual task: `TODO`, `IN PROGRESS`, `WAITING`, `DONE`.
  - Filter proyek instan dan modal ubah status terintegrasi pencatatan output.
- **Katalog Output & Unggah Berkas (`/panel/outputs`):**
  - Repositori hasil karya nyata (kode, dokumen PDF, desain, paper, laporan).
  - Dukungan unggah berkas bukti fisik hingga 25 MB dengan tombol unduh langsung dan pratinjau tab baru (`↗`).
- **Integrasi Telegram Bot (Bagian 8):**
  - Webhook aman dengan allowlist chat ID & token secret.
  - Perintah mobile lengkap: `/add`, `/idea`, `/inbox`, `/today`, `/tasks`, `/cap`, `/done`, `/done_<id>`, dan `/done <kata kunci>`.
  - Pengiriman otomatis briefing pagi (07:00) dan pengingat sesi jadwal (30 menit sebelum mulai).

### 3. Sistem Lengkap & Integrasi Eksternal (Fase 2 — NARA 1.0)
- **Kalender Interaktif & Sesi Fokus (`/panel/calendar`):**
  - Tampilan fleksibel: **Bulan (Month)**, **Minggu (Week - 7 Kolom)**, dan **Hari (Day)**.
  - Sidebar task belum terjadwal (*unscheduled*) untuk alokasi cepat 1-klik ke kalender.
  - Indikator sinkronisasi Google Calendar (`✓` Synced, `⟳` Pending, `⚠` Error, `🏠` Mode Lokal).
  - Deteksi konflik otomatis terhadap jadwal internal NARA dan kalender pihak ketiga (`busy_blocks`).
- **Roadmap & Timeline Proyek (`/panel/timeline`):**
  - Visualisasi horizon proyek dan progress bar penyelesaian task ($0\% - 100\%$).
  - Target tenggat dan hitungan mundur sisa hari.
  - Rincian milestone dan task terbuka per proyek.
- **Matrix Eisenhower Cerdas (`/panel/matrix`):**
  - Klasifikasi 4 kuadran otomatis deterministik (`MatrixClassifier`):
    - **Q1: Kerjakan (Do First):** Urgen (tenggat $\le 3$ hari) & Penting.
    - **Q2: Jadwalkan (Schedule):** Penting, belum urgen. Alokasikan ke kalender sebelum menjadi krisis.
    - **Q3: Cepatkan / Delegasikan (Delegate):** Urgen namun dampak strategis rendah.
    - **Q4: Suatu Saat (Someday):** Ide atau backlog tanpa tenggat.
    - **Lajur Khusus: Menunggu (Waiting):** Task yang terhambat pihak ketiga (`waiting_reason`).
- **Weekly Review Ritual (`/panel/weekly-reviews`):**
  - Ritual evaluasi mingguan singkat ($\le 15$ menit).
  - Metrik otomatis 7 hari terakhir: task selesai, deliverable/output nyata, beban kapasitas kerja.
  - Lembar refleksi pembelajaran dan penguncian Top 3 Prioritas Utama (*Must-Wins*) minggu berikutnya.
  - Arsip riwayat seluruh sesi review mingguan.
- **Mode Fokus / Deep Work Timer (`/panel/focus`):**
  - Antarmuka timer hening tanpa distraksi (Pomodoro 25m, Deep Work 45m, Sprint 15m, atau kustom).
  - Akumulasi waktu pengerjaan otomatis dicatat ke field `actual_minutes` task.
  - Opsi instan menandai task selesai saat sesi fokus berakhir.

---

## 🖥️ Alur & Navigasi Web Panel

| Menu Panel | Rute URL | Fungsi Utama |
| :--- | :--- | :--- |
| **Today (Fokus)** | `/panel/today` | Pusat komando harian: jadwal, beban kapasitas, perhatian stagnasi. |
| **Mode Fokus** | `/panel/focus` | Timer kerja hening per task dengan akumulasi `actual_minutes`. |
| **Inbox (Capture)** | `/panel/inbox` | Katup pelepas ide & catatan cepat sebelum ditriase. |
| **Kalender & Sesi** | `/panel/calendar` | Grid kalender interaktif, alokasi sesi, dan sync Google Calendar. |
| **Kanban View** | `/panel/kanban` | Alur kerja 4 kolom (*Todo*, *In Progress*, *Waiting*, *Done*). |
| **Tasks (Table)** | `/panel/tasks` | Tabel lengkap task dengan filter status, proyek, prioritas, & tenggat. |
| **Matrix Eisenhower** | `/panel/matrix` | Klasifikasi cerdas 4 kuadran otomatis untuk menentukan prioritas. |
| **Roadmap & Timeline** | `/panel/timeline` | Horizon penyelesaian proyek, persentase progres, dan milestone. |
| **Weekly Review** | `/panel/weekly-reviews` | Ritual evaluasi mingguan ($\le 15$ min) & penguncian Top 3 Prioritas. |
| **Outputs (Karya)** | `/panel/outputs` | Repositori deliverable nyata dengan lampiran berkas bukti & URL. |
| **Projects & WIP** | `/panel/projects` | Manajemen komitmen proyek dengan batas WIP guard per slot. |
| **Goals** | `/panel/goals` | Sasaran hasil jangka menengah pembimbing proyek. |
| **Life Domains** | `/panel/life-domains` | Area kehidupan yang dijaga keseimbangannya. |
| **Settings & Integrasi**| `/panel/settings` | Pengaturan kapasitas, 2FA, dan aturan on/off integrasi eksternal. |

---

## 🔌 Manajemen Integrasi Eksternal Dinamis

Semua variabel dan aturan integrasi pihak ketiga dikelola secara dinamis melalui database pada tabel `integrations` dan dapat diatur langsung di panel web ([`/panel/settings#integrations`](file:///c:/Users/imam%20fadhkur/Documents/zz-temp/nara/resources/views/panel/settings/index.blade.php)) tanpa harus mengedit file `.env`:

### 1. Integrasi Telegram Bot
- **Sakelar Aturan (Rule Switch):** `Aktif` / `Nonaktif`.
- **Variabel Dinamis:** Bot Token, User Chat ID Allowlist, Webhook Secret.
- **Perilaku:**
  - **Saat ON:** Briefing pagi terkirim, reminder jadwal menyala, dan pesan/perintah bot dari HP diproses seketika.
  - **Saat OFF:** Bot tertidur (*dormant*), pesan briefing/reminder tidak dikirim, dan webhook masuk dari luar ditolak secara aman.

### 2. Integrasi Google Calendar
- **Sakelar Aturan (Rule Switch):** `Aktif` / `Nonaktif`.
- **Variabel Dinamis:** Client ID, Client Secret, Nama Kalender Terdedikasi (default: `NARA`), Sync Mode.
- **Perilaku:**
  - **Saat ON:** NARA memetakan jadwal sesi ke kalender khusus Google dengan tag `nara_schedule_id` dan menarik event sibuk ke `busy_blocks` untuk deteksi konflik waktu.
  - **Saat OFF:** Kalender beroperasi $100\%$ dalam **Mode Lokal (`🏠`)** secara deterministik, tanpa bergantung pada koneksi internet atau risiko kegagalan API eksternal.

---

## 🚀 Panduan Instalasi & Menjalankan Sistem

### 1. Kebutuhan Lingkungan
- PHP 8.2+ (ekstensi: `pdo_mysql`, `openssl`, `intl`, `gd`, `pdo_sqlite`)
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ & NPM

### 2. Konfigurasi Lingkungan (`.env`)
Salin template konfigurasi dan generate application key:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan kredensial database lokal di berkas `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nara
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrasi Database & Seeder Lengkap
Jalankan migrasi seluruh tabel (Fase 0 + Fase 1 + Fase 2) dan muat dataset uji arsitektur Bagian 9:
```bash
php artisan migrate:fresh --seed
```

### 4. Akun Demo Bawaan Seeder
- **Email:** `imam@nara.local`
- **Password:** `password-imam-nara-2026`
- **Secret 2FA:** `JBSWY3DPEHPK3PXP` (dapat dipindai atau dimasukkan ke Google Authenticator / Aegis)
- **Kode Pemulihan Cadangan:** `NARA1-RECOV` s.d. `NARA8-RECOV`

### 5. Kompilasi Aset Frontend & Jalankan Server
```bash
npm install
npm run build
php artisan serve
```
Akses sistem melalui browser di: **`http://localhost:8000`**.

---

## 📱 Koneksi Bot Telegram (Mobile)

1. Buat bot baru di Telegram melalui **[@BotFather](https://t.me/BotFather)** (`/newbot`), dapatkan Bot Token.
2. Dapatkan Chat ID akun Telegram Anda melalui **[@userinfobot](https://t.me/userinfobot)**.
3. Masukkan Token dan Chat ID pada menu **Settings & Integrasi** di web panel (`/panel/settings#integrations`), lalu centang **Aktifkan Integrasi Telegram**.
4. Jika ingin menerima webhook lokal saat pengembangan, buka tunnel HTTPS (misalnya menggunakan Ngrok):
   ```powershell
   ngrok http 8000
   ```
5. Daftarkan URL webhook ke Telegram:
   ```bash
   curl -F "url=https://<DOMAIN-NGROK-ANDA>/api/telegram/webhook" \
        -F "secret_token=<WEBHOOK_SECRET_ANDA>" \
        https://api.telegram.org/bot<BOT_TOKEN_ANDA>/setWebhook
   ```
6. Buka bot di Telegram Anda, kirim `/today` atau `/help` untuk mulai menggunakan!

---

## ⏰ Worker Scheduler & Notifikasi Otomatis

NARA memanfaatkan scheduler bawaan Laravel untuk menjalankan pengiriman briefing pagi, pemantauan reminder, dan backup terjadwal.

### 1. Menjalankan Scheduler Lokal
```powershell
php artisan schedule:work
```

### 2. Perintah CLI untuk Uji Coba Langsung Notifikasi
Anda dapat memicu pengujian pengiriman pesan ke Telegram seketika tanpa menunggu jam jadwal:
```powershell
# 1. Kirim Briefing Pagi langsung ke Telegram pengguna:
php artisan nara:briefing --force

# 2. Kirim Pengingat Jadwal (Reminder) sesi terdekat:
php artisan nara:remind --force

# 3. Kirim Pengingat Jadwal untuk Schedule tertentu:
php artisan nara:remind --schedule=11 --force

# 4. Evaluasi antrean notifikasi (runner):
php artisan nara:notifications
```

---

## 💾 Backup Terenkripsi & Keamanan

Sistem dilengkapi mekanisme backup terenkripsi (*AES-256-CBC*) dengan verifikasi restore otomatis:
```bash
# Menjalankan backup terenkripsi:
php artisan nara:backup

# Verifikasi integritas arsip backup tanpa menimpa database:
php artisan nara:backup --verify-only
```
File cadangan disimpan secara aman di direktori `storage/app/backups/nara/*.nara.enc`.

---

## 🧪 Pengujian Otomatis (Test Suite)

Seluruh logika bisnis, kalkulasi kapasitas, aturan status, webhook Telegram, sinkronisasi kalender, dan skenario Fase 2 diuji secara otomatis:

```bash
# Menjalankan seluruh pengujian (Pest):
php artisan test
```

**Status Pengujian Terverifikasi:**
- **79 Tests Passed** (319 Assertions) · **100% Pass**
- Meliputi Unit Test (WipGuard, Task Status Rules, Progress Calculator, 2FA Authenticator) dan Feature Test (Kapasitas, Skenario 9.13 Overload Recovery, Webhook Telegram & Smart Done, Inbox Triage, Panel CRUD & File Upload, Integrasi Kalender, Matrix Classifier, Timeline Roadmap, Weekly Review Ritual, dan Focus Session).
