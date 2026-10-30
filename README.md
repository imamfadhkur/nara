# NARA — Personal AI Agent (Core System)

> **Versi 0.5 (Fase 1 — Daily Use)** · Lini NARA (Neural Autonomous Reasoning Agent)  
> PT Imfar Manggala Nara · Solo Developer Edition  
> Dokumen Acuan Tunggal: `nara_architecture_1.1.md`

NARA adalah Personal AI Agent yang bekerja bersama manusia untuk memahami, merencanakan, mengatur, dan membantu menyelesaikan berbagai aktivitas personal secara terstruktur dan terukur.

---

## 🗺️ Peta Perkembangan & Fitur

### 1. Fondasi Sistem (Fase 0 — NARA 0.1)
- **Hirarki Data Inti:**
  `Life Domain → Goal → Project → Task → Subtask`
- **Aturan Bisnis & Pagar Pengaman:**
  - **WipGuard (7.3):** Maksimal 3 project `active` per slot WIP (`academic`, `business`, `personal`). Menolak aktivasi project ke-4 secara deterministik.
  - **ProgressCalculator (7.2):** Progres project dihitung otomatis dari bobot task `done` vs total task non-cancelled (`bobot = max(estimated_minutes, 30)`). Progres goal dihitung dari rata-rata project aktif di bawahnya.
  - **TaskStatusRules (7.1):** 5 status task (`todo, in_progress, waiting, done, cancelled`). Status `waiting` wajib memiliki alasan menunggu (`waiting_reason`). Menandai `done` mengisi `completed_at`; membuka kembali mengosongkannya.
  - **MatrixClassifier (7.6):** Klasifikasi Eisenhower Matrix otomatis tanpa input manual (`Kerjakan`, `Jadwalkan`, `Cepatkan`, `Suatu Saat`, dan lajur terpisah `Menunggu`).
  - **ActivityLogger (6.9):** Satu pintu pencatatan riwayat aktivitas polimorfik untuk seluruh perubahan data.
- **Keamanan & Operasional (13.1 & 13.2):**
  - Registrasi publik **dimatikan** (sistem pemilik tunggal).
  - **2FA TOTP (Google Authenticator / Aegis)** wajib untuk mengakses panel, dilengkapi kode pemulihan cadangan offline terenkripsi.
  - **Backup database otomatis & terenkripsi (AES-256-CBC)** via Artisan `php artisan nara:backup` dan terjadwal harian di `routes/console.php`. Dilengkapi verifikasi restore otomatis.

### 2. Penggunaan Harian & Integrasi (Fase 1 — NARA 0.5)
- **Dashboard "Today" (`/panel/today`):**
  - 4 Kartu KPI: Jadwal Hari Ini, Task Terlambat (merah jika ada), Task Selesai Minggu Ini, Output Minggu Ini.
  - **Banner Kapasitas Mingguan (7.4):** Realisasi + rencana beban vs kapasitas (25 jam / 1.500 menit), persentase, dan 3 saran tindakan otomatis jika `overload` (geser tenggat, jeda project, atau turunkan prioritas).
  - **Panel Perhatian:** Deteksi otomatis project aktif yang stagnan (> 5 hari tanpa aktivitas) dan task jatuh tempo besok yang belum dijadwalkan.
- **Inbox & Triase Cepat (`/panel/inbox`):**
  - Quick capture via web & Telegram dengan hint `task` atau `idea`.
  - Counter badge item pending secara *live* pada sidebar panel navigasi.
  - Modal triase untuk mengalirkan catatan menjadi Task, Project baru, Someday, Goal, atau Discard.
- **Kanban Board 4 Kolom (`/panel/kanban`):**
  - Tampilan visual alur kerja: `TODO`, `IN PROGRESS`, `WAITING`, `DONE`.
  - Saringan instan berdasarkan project.
  - Modal ubah status cepat terintegrasi pencatatan output.
- **One-Click Output Capture (D-16) & Repositori Output (`/panel/outputs`):**
  - Dialog pencatatan deliverable / bukti karya nyata seketika saat task ditandai `done` (1 klik dari Kanban maupun halaman Task Detail).
  - Halaman katalog hasil karya nyata (`github`, `pdf`, `prototype`, `paper`, dll.) dengan tautan bukti langsung, penyaring project/tipe, dan soft-delete.
- **Engine Kapasitas Mingguan (`CapacityService`):**
  - Implementasi formula 7.4 & 9.11 terverifikasi: beban 1.350 menit = 90% ("padat") pada data seed; penambahan task mendadak 240 mnt memicu 106% ("overload"); jeda satu project memulihkan ke 100% ("padat").
- **Integrasi Penuh Bot Telegram (Bagian 8):**
  - Webhook endpoint `/api/telegram/webhook` dengan proteksi secret token & *allowlist* chat ID (D-12).
  - Proteksi anti-duplikasi webhook (`telegram_message_id`).
  - Dukungan interaksi mobile:
    - Teks biasa atau `/add <teks>`: otomatis masuk Inbox sebagai task.
    - `/idea <teks>`: simpan ide ke Inbox.
    - `/inbox`: daftar 5 item inbox menunggu triase.
    - `/today`: jadwal hari ini + task terlambat + ID task + tautan ketuk cepat `/done_<id>`.
    - `/tasks`: daftar seluruh task aktif (*Todo* & *In Progress*) dengan tenggat dan tautan `/done_<id>`.
    - `/cap`: cek persentase beban kapasitas mingguan.
    - `/done`: menyajikan daftar task aktif untuk diselesaikan langsung dari HP.
    - `/done <id>` atau tautan 1-ketukan `/done_<id>`: tandai task selesai seketika.
    - `/done <kata kunci judul>`: penyelesaian pintar berdasarkan pencarian judul (*fuzzy match*).
    - `/help`: panduan lengkap perintah bot.

---

## 🚀 Panduan Menjalankan Project

### 1. Kebutuhan Lingkungan
- PHP 8.2+ (ekstensi `pdo_mysql`, `openssl`, `intl`, `gd`, dan disarankan `pdo_sqlite` untuk unit test)
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ & NPM

### 2. Konfigurasi Lingkungan (`.env`)
Salin berkas lingkungan dan buat application key:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database dan integrasi Telegram di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nara
DB_USERNAME=root
DB_PASSWORD=

# Integrasi Telegram Bot (Fase 1)
TELEGRAM_BOT_TOKEN="token_dari_botfather"
TELEGRAM_WEBHOOK_SECRET="string_rahasia_webhook_anda"
```

### 3. Migrasi Database & Seeder Lengkap
Jalankan migrasi seluruh tabel (Fase 0 + Fase 1) dan muat dataset arsitektur Bagian 9:
```bash
php artisan migrate:fresh --seed
```

### 4. Akun Demo Bawaan Seeder (Bagian 9)
- **Email:** `imam@nara.local`
- **Password:** `password-imam-nara-2026`
- **Secret 2FA:** `JBSWY3DPEHPK3PXP` (masukkan ke Google Authenticator / Aegis)
- **Kode Pemulihan Cadangan:** `NARA1-RECOV` s.d. `NARA8-RECOV`
- **Telegram Chat ID:** Telah terkonfigurasi di akun demo (dapat diubah di menu Settings).

### 5. Kompilasi Aset & Menjalankan Server
```bash
npm install
npm run build # atau npm run dev untuk live reload
php artisan serve
```
Akses web panel melalui browser di `http://localhost:8000`.

---

## 📱 Menghubungkan Bot Telegram (Pengujian Mobile)

1. Buat bot baru di Telegram melalui **[@BotFather](https://t.me/BotFather)** (`/newbot`), salin tokennya ke `TELEGRAM_BOT_TOKEN` di `.env`.
2. Dapatkan Chat ID akun Telegram Anda melalui **@userinfobot**, lalu masukkan ke menu **Settings Web Panel** (`/panel/settings`).
3. Jalankan tunnel HTTPS publik lokal (misal menggunakan Ngrok):
   ```powershell
   ngrok http 8000
   ```
4. Daftarkan URL webhook ke Telegram:
   ```bash
   curl -F "url=https://<DOMAIN-NGROK-ANDA>/api/telegram/webhook" \
        -F "secret_token=<TELEGRAM_WEBHOOK_SECRET>" \
        https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook
   ```
5. Buka bot di Telegram, kirim `/help` atau `/today` untuk mulai menggunakan!

---

## 🧪 Menjalankan Pengujian Otomatis

Seluruh logika bisnis, keamanan, kalkulasi kapasitas, webhook Telegram, dan skenario arsitektur diuji otomatis:
```bash
# Menggunakan test runner Pest:
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest

# Atau jika ekstensi sqlite sudah aktif di php.ini:
php artisan test
```

**Status Pengujian Saat Ini:**
- **66 Tests Passed** (242 Assertions)
- Cakupan:
  - Unit: Progress, WipGuard, Task Status Rules, 2FA Authenticator.
  - Feature: Capacity 90% & Scenario 9.13 Overload Recovery, Telegram Webhook & Smart Done, Inbox Triage, Panel CRUD, DemoSeeder Accuracy, Backup Encryption.

---

## 💾 Perintah Operasional & Backup Terenkripsi

```bash
# Menjalankan backup terenkripsi (AES-256-CBC) dengan verifikasi restore otomatis:
php artisan nara:backup

# Verifikasi integritas file backup terbaru tanpa menulis ke database aktif:
php artisan nara:backup --verify-only
```
Cadangan tersimpan di `storage/app/backups/nara/*.nara.enc`.
