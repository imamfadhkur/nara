# NARA — Personal AI Agent (Core System)

> **Versi 0.1** · Lini NARA (Neural Autonomous Reasoning Agent)  
> PT Imfar Manggala Nara · Solo Developer Edition  
> Dokumen Acuan Tunggal: `nara_architecture_1.1.md`

NARA adalah Personal AI Agent yang bekerja bersama manusia untuk memahami, merencanakan, mengatur, dan membantu menyelesaikan berbagai aktivitas personal.

---

## 🎯 Fondasi Fase 0 (NARA 0.1)

Fase 0 berfokus pada fondasi database, integritas aturan bisnis, keamanan (2FA + backup terenkripsi), dan **Panel Native** (Blade + Alpine.js tanpa ketergantungan pihak ketiga seperti Filament/Nova).

### Fitur Inti yang Selesai:
1. **Hirarki Data Inti:**
   `Life Domain → Goal → Project → Task → Subtask`
2. **Aturan Bisnis & Pagar Pengaman:**
   - **WipGuard (7.3):** Maksimal 3 project `active` per slot WIP (`academic`, `business`, `personal`). Menolak aktivasi project ke-4 secara deterministik.
   - **ProgressCalculator (7.2):** Progres project dihitung otomatis dari bobot task `done` vs total task non-cancelled (`bobot = max(estimated_minutes, 30)`). Progres goal dihitung dari rata-rata project aktif di bawahnya.
   - **TaskStatusRules (7.1):** 5 status task (`todo, in_progress, waiting, done, cancelled`). Status `waiting` wajib memiliki alasan menunggu (`waiting_reason`). Menandai `done` mengisi `completed_at`; membuka kembali mengosongkannya.
   - **MatrixClassifier (7.6):** Klasifikasi Eisenhower Matrix dihitung otomatis tanpa input manual (`Kerjakan`, `Jadwalkan`, `Cepatkan`, `Suatu Saat`, dan lajur terpisah `Menunggu`).
   - **ActivityLogger (6.9):** Satu pintu pencatatan riwayat aktivitas polimorfik untuk seluruh perubahan data.
3. **Panel Native (3.7 & 5.5):**
   - Menggunakan Tailwind CSS + Alpine.js dengan *NARA Design Tokens* (`--nara-*`: Deep Blue / Cyan + Violet).
   - CRUD lengkap dengan `FormRequest` & `Policy` untuk:
     - **Life Domains** (`/panel/life-domains`)
     - **Goals** (`/panel/goals`)
     - **Projects & WIP Slot** (`/panel/projects`)
     - **Tasks Table View** (`/panel/tasks`)
     - **Subtask Checklist** (toggle centang, tambah, dan hapus)
     - **Settings & 2FA** (`/panel/settings`)
4. **Keamanan & Operasional (13.1 & 13.2):**
   - Registrasi publik **dimatikan** (sistem pemilik tunggal).
   - **2FA TOTP (Google Authenticator / Aegis)** wajib untuk mengakses panel, dilengkapi kode pemulihan offline yang disimpan terenkripsi.
   - **Backup database otomatis & terenkripsi (AES-256-CBC)** via Artisan `php artisan nara:backup` dan terjadwal harian di `routes/console.php`. Dilengkapi verifikasi restore otomatis.

---

## 🚀 Menjalankan Project

### 1. Kebutuhan Lingkungan
- PHP 8.2+ (ekstensi `pdo_mysql`, `openssl`, `intl`, `gd`, dan disarankan `pdo_sqlite` untuk unit test)
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ & NPM

### 2. Konfigurasi Database & Lingkungan
Pastikan database MySQL `nara` sudah dibuat di MySQL lokal Anda. Salin berkas lingkungan:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan kredensial `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nara
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrasi & Seed Data Contoh
Jalankan migrasi tabel Fase 0 dan seeder data contoh (Bagian 9 arsitektur):
```bash
php artisan migrate:fresh --seed
```

### 4. Akun Demo Bawaan Seeder (Bagian 9)
- **Email:** `imam@nara.local`
- **Password:** `password-imam-nara-2026`
- **Secret 2FA:** `JBSWY3DPEHPK3PXP` (dapat dimasukkan ke authenticator app)
- **Kode Pemulihan Cadangan:** `NARA1-RECOV` s.d. `NARA8-RECOV`

### 5. Kompilasi Aset & Menjalankan Server
```bash
npm install
npm run build # atau npm run dev untuk live reload
php artisan serve
```
Buka browser ke `http://localhost:8000`.

---

## 🧪 Menjalankan Pengujian Otomatis

Seluruh aturan bisnis (Progress, WipGuard, Task Status, 2FA, Backup, DemoSeeder) diuji otomatis dengan Pest:
```bash
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest
# atau jika pdo_sqlite sudah aktif di php.ini:
php artisan test
```

---

## 💾 Perintah Backup Terenkripsi (Bagian 13.2)

```bash
# Membuat backup terenkripsi, memverifikasi restore, dan memangkas retensi lama:
php artisan nara:backup

# Verifikasi integritas file backup terbaru tanpa menulis ke database aktif:
php artisan nara:backup --verify-only
```
Cadangan tersimpan di `storage/app/backups/nara/*.nara.enc`.
