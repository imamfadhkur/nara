<?php

namespace Database\Seeders;

use App\Models\DocumentationArticle;
use Illuminate\Database\Seeder;

class DocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'category' => 'google_calendar',
                'category_name' => 'Integrasi Google Calendar',
                'title' => 'Panduan Lengkap Konfigurasi Google Calendar (Lokal hingga Production)',
                'slug' => 'panduan-integrasi-google-calendar-lokal-production',
                'icon' => '📅',
                'sort_order' => 1,
                'is_published' => true,
                'last_updated_by' => 'Imam Fadhkur',
                'excerpt' => 'Langkah menyeluruh integrasi Google Calendar API v3 menggunakan OAuth 2.0 Web Application, isolasi kalender terdedikasi NARA, dan sinkronisasi dua arah.',
                'content' => <<<'MARKDOWN'
# Panduan Lengkap Konfigurasi Google Calendar (Lokal hingga Production)

NARA AI mengusung prinsip **NARA Authoritative**: NARA bertindak sebagai *single source of truth* untuk seluruh jadwal sesi fokus dan tugas harian Anda, yang kemudian disinkronkan secara mulus ke Google Calendar.

---

## 1. Arsitektur & Prinsip Isolasi

- **Kalender Terdedikasi "NARA"**: NARA tidak pernah mencampur aduk sesi kerja dengan kalender pribadi Anda (Personal, Birthday, Keluarga). NARA secara otomatis membuat atau menggunakan kalender sekunder khusus bernama `NARA` di akun Google Anda.
- **Deteksi Konflik Otomatis (Read-Only Busy Blocks)**: Jadwal dari kalender pribadi Anda dibaca sebagai *read-only busy blocks*. Jika Anda mencoba menjadwalkan task pada jam yang bentrok dengan agenda pribadi (misal: jadwal rapat atau olahraga), NARA langsung memperingatkan bentrok waktu tersebut.
- **Siklus Hidup Token OAuth 2.0**:
  - `access_token` berlaku selama 1 jam dan dienkripsi di database (`encrypted`).
  - `refresh_token` disimpan aman untuk menyegarkan token otomatis di latar belakang tanpa meminta Anda login ulang.
  - Jika koneksi dimatikan di pengaturan, kalender beralih seketika ke **Mode Lokal (🏠)** yang aman.

---

## 2. Langkah Setup di Google Cloud Console

### Langkah 2.1: Buat Project & Aktifkan API
1. Buka [Google Cloud Console](https://console.cloud.google.com/).
2. Buat Project baru, berikan nama: `NARA Personal Agent` (atau nama pilihan Anda).
3. Buka menu **APIs & Services** > **Library**.
4. Cari `Google Calendar API`, lalu klik **Enable** (Aktifkan).

### Langkah 2.2: Konfigurasi OAuth Consent Screen
1. Masuk ke **APIs & Services** > **OAuth consent screen**.
2. Pilih **User Type**: **External** (Eksternal), lalu klik **Create**.
3. Isi data dasar aplikasi:
   - **App name**: `NARA Personal Agent`
   - **User support email**: Email Google Anda (misal: `imamfadhkur1@gmail.com`)
   - **Developer contact information**: Email Google Anda.
4. Pada bagian **Scopes**, tambahkan scope:
   - `https://www.googleapis.com/auth/calendar`
5. **PENTING: Tambahkan Test Users (Pengguna Uji)**:
   - Karena aplikasi berstatus *Testing*, Anda **wajib** mendaftarkan email Anda di bagian **Test users**.
   - Klik **+ ADD USERS**, ketik email Anda (misal: `imamfadhkur1@gmail.com`), lalu klik **Save and Continue**.

### Langkah 2.3: Buat OAuth 2.0 Client ID (Web Application)
1. Masuk ke menu **APIs & Services** > **Credentials**.
2. Klik **+ CREATE CREDENTIALS** > **OAuth client ID**.
3. Pilih **Application type**: **Web application**.
4. Berikan nama: `NARA Web Client`.
5. Pada bagian **Authorized redirect URIs** (URI Pengalihan Resmi):
   - **Untuk Lingkungan Lokal (Pengembangan)**:
     ```text
     http://127.0.0.1:8000/panel/settings/integrations/google/callback
     http://localhost:8000/panel/settings/integrations/google/callback
     ```
   - **Untuk Lingkungan Server / Production**:
     ```text
     https://nara.domainanda.com/panel/settings/integrations/google/callback
     ```
6. Klik **CREATE**. Anda akan mendapatkan **Client ID** dan **Client Secret**.

---

## 3. Menghubungkan ke Panel NARA

1. Buka NARA pada menu **Settings & Integrasi** (`/panel/settings#integrations`).
2. Masukkan **Google Client ID** dan **Google Client Secret** ke dalam formulir.
3. Tetapkan **Nama Kalender Terdedikasi** (default: `NARA`).
4. Berikan centang pada **Aktifkan Sinkronisasi Google Calendar (Rule Switch)**.
5. Klik **Simpan Google Config**.
6. Klik tombol kuning: **`🔑 Hubungkan Akun Google (Otorisasi Sekarang) →`**.
7. Anda akan dialihkan ke layar persetujuan Google.
   - Pilih akun Google Anda.
   - Jika muncul peringatan *"Google belum memverifikasi aplikasi ini"*, klik **Lanjutan (Advanced)** > klik **Buka NARA Personal Agent (tidak aman)**.
   - Berikan centang pada izin kalender lalu klik **Lanjutkan**.
8. NARA akan otomatis memvalidasi token, membuat kalender `NARA` di akun Google Anda, dan menyinkronkan seluruh jadwal yang telah ada!

---

## 4. Migrasi dari Lingkungan Lokal ke Production

Bila Anda ingin memindahkan NARA dari laptop lokal ke server VPS / domain publik:

1. **Update Redirect URI di Google Cloud Console**:
   - Tambahkan URL domain publik Anda:
     `https://your-domain.com/panel/settings/integrations/google/callback`
   - Pastikan production menggunakan protokol **HTTPS** (Google OAuth membatasi HTTP murni hanya untuk `127.0.0.1` dan `localhost`).
2. **Konfigurasi Environment Server**:
   - Pastikan `APP_URL` di berkas `.env` server sesuai dengan domain (misal `APP_URL=https://your-domain.com`).
3. **Hubungkan Ulang Otorisasi**:
   - Di panel pengaturan server, klik **Putuskan Akun**, lalu klik kembali **Hubungkan Akun Google** melalui domain baru.
MARKDOWN
            ],
            [
                'category' => 'troubleshooting',
                'category_name' => 'Troubleshooting & Solusi',
                'title' => 'Troubleshooting Lengkap Integrasi Google Calendar & OAuth',
                'slug' => 'troubleshooting-solusi-google-calendar-oauth',
                'icon' => '🛠️',
                'sort_order' => 2,
                'is_published' => true,
                'last_updated_by' => 'Imam Fadhkur',
                'excerpt' => 'Solusi tuntas untuk kendala umum: Error 403 access_denied, Error 400 redirect_uri_mismatch, token expired, dan tugas tidak muncul di Google Calendar.',
                'content' => <<<'MARKDOWN'
# Troubleshooting Lengkap Integrasi Google Calendar & OAuth

Berikut adalah daftar kendala yang sering dijumpai beserta solusi langkah demi langkah:

---

## 1. Error 403: access_denied ("Aplikasi saat ini sedang diuji...")

### Gejala:
Saat mengklik tombol otorisasi, Google menampilkan halaman hitam dengan pesan:
> *Akses diblokir: NARA Personal Agent belum menyelesaikan proses verifikasi Google. Aplikasi saat ini sedang diuji dan hanya dapat diakses oleh penguji yang disetujui developer.*

### Penyebab:
Project Google Cloud Anda masih berstatus **Testing (Pengujian)** dan akun email Anda belum dimasukkan ke daftar **Test users** (Pengguna Uji).

### Solusi:
1. Buka [Google Cloud Console Credentials Consent](https://console.cloud.google.com/apis/credentials/consent).
2. Scroll ke bagian **Test users** (Pengguna uji).
3. Klik tombol **+ ADD USERS**.
4. Masukkan email Anda (`imamfadhkur1@gmail.com`).
5. Klik **SAVE / Simpan**.
6. Buka kembali NARA dan klik tombol **Hubungkan Akun Google**.

---

## 2. Layar Peringatan "Google belum memverifikasi aplikasi ini"

### Gejala:
Muncul layar peringatan segitiga merah bertuliskan:
> *Google hasn't verified this app / Google belum memverifikasi aplikasi ini.*

### Penyebab:
Ini adalah perilaku normal untuk aplikasi internal pengembang yang belum diajukan verifikasi publik ke Google.

### Solusi:
1. Klik tautan teks kecil bertuliskan **Lanjutan** atau **Advanced** di pojok kiri bawah layar tersebut.
2. Klik tautan **Buka NARA Personal Agent (tidak aman)** atau **Go to NARA Personal Agent (unsafe)**.
3. Berikan centang persetujuan izin akses Google Calendar, lalu klik **Lanjutkan**.

---

## 3. Error 400: redirect_uri_mismatch

### Gejala:
Google menampilkan pesan:
> *Error 400: redirect_uri_mismatch. The redirect URI in the request does not match the ones authorized for the OAuth client.*

### Penyebab:
URL yang dikirim NARA tidak sama persis dengan yang didaftarkan di Google Cloud Console (misal: beda port, beda `127.0.0.1` vs `localhost`, atau ada trailing slash).

### Solusi:
1. Buka Google Cloud Console > **APIs & Services** > **Credentials**.
2. Klik nama OAuth Client Anda: **NARA Web Client**.
3. Di bagian **Authorized redirect URIs**, pastikan kedua URL ini terdaftar:
   ```text
   http://127.0.0.1:8000/panel/settings/integrations/google/callback
   http://localhost:8000/panel/settings/integrations/google/callback
   ```
4. Klik **Save**.
5. Tunggu 1–2 menit agar cache server Google diperbarui, lalu coba lagi.

---

## 4. Jadwal Dibuat di NARA Namun Tidak Muncul di calendar.google.com

### Penyebab & Solusi:
1. **Periksa Indikator Status di NARA**:
   - Masuk ke **Settings & Integrasi** (`/panel/settings#integrations`).
   - Jika badge berwarna **kuning** (*Belum Diotorisasi*), artinya Anda baru menyimpan Client ID & Secret tetapi belum melakukan otorisasi OAuth. Klik tombol **Hubungkan Akun Google**.
2. **Periksa Daftar Kalender di Google**:
   - Di [calendar.google.com](https://calendar.google.com), periksa menu sebelah kiri di bawah **My calendars**.
   - Pastikan kalender bernama **NARA** dicentang (aktif).
3. **Picu Sinkronisasi Manual**:
   - Di halaman **Kalender NARA** (`/panel/calendar`), klik tombol **⟳ Sinkronkan Sekarang**. Seluruh event akan otomatis diunggah ulang ke Google Calendar.

---

## 5. Token OAuth Kedaluwarsa atau Dicabut

### Gejala:
Sinkronisasi gagal atau log menampilkan error otorisasi HTTP 401.

### Solusi:
1. NARA dilengkapi fungsi **auto-refresh token** menggunakan `refresh_token` yang disimpan.
2. Jika token dicabut dari Google Account Security, buka menu **Settings & Integrasi** di NARA.
3. Klik tombol **Putuskan Akun**, lalu klik **Hubungkan Akun Google** kembali.
MARKDOWN
            ],
            [
                'category' => 'telegram',
                'category_name' => 'Integrasi Telegram Bot',
                'title' => 'Panduan Lengkap Integrasi Telegram Bot (API, Perintah & Otomasi)',
                'slug' => 'panduan-integrasi-telegram-bot-api-notifikasi',
                'icon' => '🤖',
                'sort_order' => 3,
                'is_published' => true,
                'last_updated_by' => 'Imam Fadhkur',
                'excerpt' => 'Konfigurasi Bot Telegram via @BotFather, pencatatan Chat ID, daftar komando cepat (/add, /idea, /done, /tasks), briefing pagi, reminder jadwal, dan background worker.',
                'content' => <<<'MARKDOWN'
# Panduan Lengkap Integrasi Telegram Bot (API, Perintah & Otomasi)

NARA terintegrasi dua arah dengan Telegram Bot. Anda dapat menangkap ide dari ponsel saat bepergian, menandai tugas selesai via pesan instan, serta menerima ringkasan pagi (*Morning Briefing*) dan pengingat jadwal (*Schedule Reminder*).

---

## 1. Langkah Pembuatan Bot di Telegram

1. Buka aplikasi Telegram di ponsel atau komputer Anda.
2. Cari akun resmi **`@BotFather`** (dengan centang biru).
3. Ketik perintah: `/newbot`.
4. Masukkan nama bot Anda (misal: `NARA Personal Agent`).
5. Masukkan username bot (harus berakhiran `bot`, misal: `nara_personal_agent_bot`).
6. BotFather akan memberikan **HTTP API Bot Token** berupa string panjang, contoh:
   ```text
   9282810794:AAHICMAcSpUy5Lz4GOIqU_bTP1aohXhoH1g
   ```
7. Simpan token ini untuk dimasukkan ke panel NARA.

---

## 2. Mendapatkan Telegram Chat ID Akun Anda

Telegram memerlukan ID numerik unik Anda agar bot hanya berkomunikasi secara privat dengan Anda (*allowlist protection*).

1. Cari bot **`@userinfobot`** di Telegram.
2. Klik **Start**. Bot akan membalas dengan **Id** Anda (contoh: `969666384`).
3. Masuk ke bot yang baru Anda buat, lalu klik **Start** agar bot memiliki izin mengirim pesan ke akun Anda.

---

## 3. Memasukkan Pengaturan ke Panel NARA

1. Buka panel NARA di menu **Settings & Integrasi** (`/panel/settings#integrations`).
2. Masukkan:
   - **Telegram Bot Token**: Token dari BotFather.
   - **Telegram Chat ID**: ID numerik Anda.
   - **Webhook Secret** (Opsional untuk keamanan webhook di server production).
3. Berikan centang pada **Aktifkan Notifikasi & Komando Telegram (Rule Switch)**.
4. Klik **Simpan Telegram Config**.
5. Klik tombol uji coba: **Kirim Briefing Pagi** atau **Kirim Reminder Jadwal** untuk memverifikasi bot langsung mengirim pesan ke ponsel Anda!

---

## 4. Daftar Perintah Chat yang Didukung

Kirimkan pesan berikut langsung ke ruang obrolan bot NARA Anda:

| Perintah / Format | Fungsi & Hasil di NARA |
| :--- | :--- |
| *Ketik teks biasa langsung* | Otomatis ditangkap sebagai item baru di **Inbox (Capture)** |
| `/add <judul task>` | Membuat task baru langsung di database |
| `/idea <ide baru>` | Menangkap ide baru ke Inbox dengan label *Idea* |
| `/done <judul atau #id>` | Menandai task terkait langsung menjadi **Selesai (Done)** |
| `/tasks` | Menampilkan ringkasan daftar task aktif hari ini beserta statusnya |
| `/help` | Menampilkan panduan bantuan komando lengkap |

---

## 5. Menjalankan Otomasi Pengiriman (Worker & Scheduler)

NARA menyediakan perintah bawaan artisan untuk notifikasi harian:

- **Morning Briefing (07:00 Pagi)**:
  ```bash
  php artisan nara:briefing
  ```
  *Mengirimkan sapaan pagi, kapasitas minggu ini, progres komitmen, dan daftar 3 prioritas utama hari ini.*

- **Schedule Reminder (Pengingat Jadwal)**:
  ```bash
  php artisan nara:remind
  ```
  *Mengecek sesi fokus yang akan dimulai dalam 15–30 menit ke depan dan mengirimkan pengingat instan beserta tautan pintas.*

### Menjalankan Otomatis via Scheduler (Cron Job):
Jalankan scheduler Laravel setiap menit:
```bash
* * * * * cd /path-to-nara && php artisan schedule:run >> /dev/null 2>&1
```
Atau di Windows Task Scheduler, buat task berulang setiap menit yang memanggil:
```powershell
php C:\path\to\nara\artisan schedule:run
```
MARKDOWN
            ],
            [
                'category' => 'features',
                'category_name' => 'Panduan Fitur & Alur Kerja',
                'title' => 'Manual Menyeluruh Fitur, Siklus Hidup & Alur Kerja NARA AI',
                'slug' => 'manual-lengkap-fitur-dan-alur-kerja-nara',
                'icon' => '🎯',
                'sort_order' => 4,
                'is_published' => true,
                'last_updated_by' => 'Imam Fadhkur',
                'excerpt' => 'Panduan lengkap navigasi harian: Today Dashboard, Inbox Capture, Mode Fokus Pomodoro, Kalender Alokasi, Matrix Eisenhower, Roadmap, Outputs, dan Weekly Review.',
                'content' => <<<'MARKDOWN'
# Manual Menyeluruh Fitur, Siklus Hidup & Alur Kerja NARA AI

NARA AI didesain dengan filosofi **alokasi waktu kerja deterministik**, pencegahan kelebihan beban kognitif (*anti-overload*), dan fokus pada penyelesaian karya nyata (*outputs*).

---

## 1. Alur Kerja Harian yang Direkomendasikan

```mermaid
graph LR
    A[📥 Inbox Quick Capture] --> B[⚡ Triase Cepat 5 Menit]
    B --> C[🧭 Matrix Prioritas]
    C --> D[📅 Kalender Sesi Fokus]
    D --> E[⏱️ Mode Deep Work]
    E --> F[🏆 Catat Output Karya]
    F --> G[📊 Weekly Review]
```

---

## 2. Rincian Fitur Utama

### 2.1 Today (Fokus Dashboard)
- **Ringkasan Hari Ini**: Menampilkan kapasitas jam kerja mingguan (default 25 jam / 1500 menit), jam yang sudah teralokasi, dan persentase kapasitas.
- **Deteksi Overload**: Jika alokasi melebihi 80%, NARA menampilkan peringatan kuning (*Warning*). Jika melebihi 100%, NARA menampilkan peringatan merah (*Overload*) dan merekomendasikan penundaan task sekunder.
- **WIP Guard**: Melindungi fokus dengan membatasi maksimal 3 proyek aktif sekaligus per slot kategori (Feature, Maintenance, Learning).

### 2.2 Inbox (Penangkapan Cepat & Triase)
- **Katup Pelepas Ide**: Tempat menampung ide, tugas mendadak, atau catatan tanpa perlu langsung memikirkan proyek atau tenggat waktunya.
- **Triase Cepat**: Cukup 5 menit sehari, ubah item inbox menjadi **Task**, **Project**, **Catatan Ide**, atau **Hapus** jika tidak relevan.

### 2.3 Mode Fokus (Deep Work Timer)
- Sesi kerja hening terdedikasi untuk satu task tanpa distraksi.
- **Pilihan Preset**:
  - `🍅 25 Menit`: Teknik Pomodoro standar.
  - `🧠 45 Menit`: Sesi konsentrasi Deep Work intensif.
  - `⚡ 60 Menit`: Sesi fokus panjang.
  - `☕ 15 Menit`: Sprint cepat atau ulasan singkat.
- **Pencatatan Otomatis**: Waktu yang dihabiskan otomatis diakumulasikan ke kolom `actual_minutes` task, dan terdapat opsi langsung menandai task sebagai **Done**.

### 2.4 Kalender & Sesi Fokus Terjadwal
- Menetapkan alokasi waktu kerja spesifik (misal: 09:00 - 10:30).
- Terintegrasi dua arah dengan **Google Calendar** pada kalender khusus `NARA`.
- Otomatis mendeteksi bentrok waktu dengan jadwal lokal NARA lain maupun agenda pribadi eksternal.

### 2.5 Matrix Eisenhower (4 Kuadran Cerdas)
Klasifikasi cerdas berbasis urgensi (tenggat waktu) dan kepentingan (dampak proyek):
1. **Kuadran 1 (Do First)**: Mendesak & Berdampak Tinggi (kerjakan hari ini).
2. **Kuadran 2 (Schedule)**: Tidak Mendesak tapi Berdampak Tinggi (jadwalkan di kalender).
3. **Kuadran 3 (Delegate / Automate)**: Mendesak tapi Berdampak Rendah (otomatiskan atau selesaikan cepat).
4. **Kuadran 4 (Don't Do / Review)**: Tidak Mendesak & Berdampak Rendah (kaji ulang atau batalkan).

### 2.6 Roadmap & Timeline
- Visualisasi horizon progres jangka menengah dan panjang.
- Memantau status seluruh proyek aktif, on hold, dan selesai beserta indikator capaian persentase.

### 2.7 Outputs (Karya Nyata)
- NARA mengukur kemajuan bukan dari seberapa sibuk Anda, melainkan **karya nyata yang dihasilkan**.
- Setiap task yang diselesaikan dapat dicatat buktinya berupa:
  - Teks deskripsi hasil capaian.
  - Tautan URL (misal: link repo Git, PR, dokumen Google Docs, Figma).
  - Unggah berkas dokumen (PDF, gambar arsitektur, laporan, dll) yang dapat diunduh atau dipratinjau langsung di panel.

### 2.8 Weekly Review (Evaluasi Mingguan ≤15 Menit)
- Ritual rutin setiap akhir pekan (misal Minggu sore/malam).
- Meninjau kembali task yang selesai, merefleksikan hambatan yang terjadi, dan merumuskan **Top 3 Prioritas Mutlak** untuk minggu berikutnya.
MARKDOWN
            ],
            [
                'category' => 'security_backup',
                'category_name' => 'Keamanan & Pemeliharaan',
                'title' => 'Panduan Keamanan, Autentikasi 2FA TOTP & Pemeliharaan Backup',
                'slug' => 'keamanan-2fa-totp-dan-prosedur-backup-database',
                'icon' => '🛡️',
                'sort_order' => 5,
                'is_published' => true,
                'last_updated_by' => 'Imam Fadhkur',
                'excerpt' => 'Prosedur keamanan akun dengan 2FA TOTP, pengelolaan recovery codes, enkripsi token di database, dan pencadangan database mandiri berkala.',
                'content' => <<<'MARKDOWN'
# Panduan Keamanan, Autentikasi 2FA TOTP & Pemeliharaan Backup

NARA dirancang dengan prinsip *Privacy First* dan *Self-Hosted Security*. Seluruh data pribadi, proyek, dan token integrasi Anda berada di bawah kendali penuh Anda.

---

## 1. Autentikasi Dua Langkah (2FA TOTP)

Sebagai lapisan perlindungan ekstra, NARA menyediakan fitur 2FA berbasis standar RFC 6238 TOTP (kompatibel dengan Google Authenticator, Aegis, 1Password, Bitwarden, dll).

### Menyalakan 2FA:
1. Buka menu **Settings & 2FA** > Tab **Keamanan & 2FA**.
2. Klik tombol **Aktifkan 2FA**.
3. Pindai QR Code yang muncul menggunakan aplikasi authenticator di ponsel Anda.
4. Masukkan 6 digit kode yang tampil di aplikasi, lalu klik **Konfirmasi 2FA**.
5. Simpan **Kode Pemulihan Darurat (Recovery Codes)** di tempat yang aman. Kode ini dapat digunakan jika ponsel Anda hilang atau tidak dapat diakses.

### Sifat Opsional 2FA:
- Fitur 2FA bersifat opsional dan modular. Jika Anda memilih menonaktifkannya, seluruh fitur NARA lainnya tetap berfungsi 100% normal tanpa terblokir.

---

## 2. Enkripsi Token & Kredensial Database

- Semua token sensitif seperti `access_token`, `refresh_token`, rahasia 2FA `two_factor_secret`, dan `two_factor_recovery_codes` otomatis dienkripsi di tingkat database menggunakan algoritma enkripsi bawaan Laravel (`AES-256-CBC` dengan `APP_KEY`).
- Token integrasi tidak pernah dicatat dalam bentuk teks polos (*plain text*) di log aplikasi.

---

## 3. Pemeliharaan & Backup Database

Untuk mencegah kehilangan data penting akibat kerusakan perangkat keras atau pembaruan sistem, jalankan backup database secara rutin:

### Menjalankan Backup Mandiri via CLI:
```bash
php artisan nara:backup
```
Perintah ini akan membuat salinan terkompresi dari database NARA dan menyimpannya di direktori:
```text
storage/app/backups/
```

### Tips Pengelolaan Kunci Enkripsi (`.env`):
- Selalu buat salinan cadangan dari berkas `.env`, khususnya baris `APP_KEY`. Jika `APP_KEY` hilang atau berubah, token dan rahasia yang terenkripsi di database tidak akan dapat didekripsi kembali.
MARKDOWN
            ],
        ];

        foreach ($articles as $data) {
            DocumentationArticle::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
