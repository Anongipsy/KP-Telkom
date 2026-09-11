# SPESIFIKASI PROYEK & PANDUAN DEPLOYMENT KE JAGOANHOSTING (VIA GITHUB)

Dokumen ini berisi spesifikasi teknis lengkap, kebutuhan server hosting, serta panduan langkah-demi-langkah untuk melakukan deployment aplikasi **Telkom B2B Contract Monitoring System** dari GitHub ke **JagoanHosting** (cPanel / Cloud Hosting).

---

## 1. Lembar Spesifikasi Teknis Proyek (Project Technical Sheet)

| Komponen | Spesifikasi / Versi | Catatan Khusus |
| :--- | :--- | :--- |
| **Nama Aplikasi** | Telkom B2B Contract Monitoring System | Aplikasi Monitoring Kontrak Telkom B2B |
| **Framework Backend** | **Laravel 12.x** | Menggunakan arsitektur Laravel 12 terbaru |
| **Bahasa & Runtime** | **PHP ^8.2** (Rekomendasi: **PHP 8.2** atau **PHP 8.3**) | **Wajib PHP >= 8.2**. Laravel 12 tidak mendukung PHP 8.1 ke bawah. |
| **Database Engine** | **MySQL 8.0+** / **MariaDB 10.4+** | Digunakan untuk User, Log Audit, Sinkronisasi, dan Notifikasi |
| **Frontend Framework** | **Vite 7.x** + **Tailwind CSS v4.x** + **Alpine.js v3.x** | Aset dikompilasi ke folder `public/build/` |
| **Dependency Manager** | **Composer 2.x** & **NPM 10.x** (Node.js >= 18.x) | Node.js digunakan saat build aset lokal / CI-CD |
| **Integrasi Eksternal** | • **Google Sheets API v4** (Single Source of Truth)<br>• **Google Drive API v3** (Document Preview/Proxy) | Menggunakan library resmi `google/apiclient:^2.19` via Service Account |
| **Scheduled Task** | Command: `php artisan contracts:check-expiration` | Dijalankan otomatis setiap hari jam 07:00 WIB via Cron Job |
| **Fitur Autentikasi** | Multi-Role Authentication (`AM` / Account Manager & `Admin` / Developer) | Seed awal user siap via `DatabaseSeeder` |

---

## 2. Kebutuhan Server Hosting (JagoanHosting Requirements)

Untuk memastikan aplikasi Laravel 12 berjalan stabil dan tanpa kendala di JagoanHosting, pastikan paket hosting yang Anda pilih memenuhi kriteria berikut:

### A. Paket Hosting yang Disarankan
- **Paket:** Cloud Hosting atau Shared Hosting JagoanHosting (Paket *Fame / Hits / Superstar* atau paket *Cloud Hosting / Corporate*).
- **Fitur Wajib:** Memiliki akses **SSH (Terminal)** di cPanel atau fitur **Git Version Control**.

### B. Konfigurasi PHP (Atur di cPanel > *Select PHP Version* / *MultiPHP Manager*)
- **Versi PHP:** Pilih **PHP 8.2** atau **PHP 8.3**.
- **Ekstensi PHP Wajib Aktif (Checklist):**
  - `bcmath`
  - `ctype`
  - `curl` (Wajib untuk komunikasi ke Google API)
  - `dom` & `xml` & `simplexml`
  - `fileinfo` (Wajib untuk validasi MIME file PDF/gambar dokumen kontrak)
  - `filter`
  - `hash`
  - `iconv`
  - `json`
  - `mbstring`
  - `openssl` (Wajib untuk enkripsi credential & SSL Google)
  - `pcre`
  - `pdo` & `pdo_mysql` (Wajib untuk koneksi MySQL)
  - `session`
  - `tokenizer`
  - `zip`

### C. Resource PHP INI (Atur di cPanel > *MultiPHP INI Editor*)
```ini
memory_limit = 256M          ; Disarankan 512M jika data Google Sheets besar
max_execution_time = 180     ; Minimal 180 detik (proses sinkronisasi & download preview)
upload_max_filesize = 32M    ; Sesuai batas preview dokumen Google Drive (maks 25MB)
post_max_size = 32M
allow_url_fopen = On         ; Wajib aktif untuk cURL & Google Client library
```

### D. Akses Port Jaringan
- **Port 443 (Outbound HTTPS):** Harus terbuka agar server dapat menghubungi `https://sheets.googleapis.com` dan `https://www.googleapis.com`. (Standar cPanel JagoanHosting sudah mengizinkan ini).

---

## 3. Strategi Penting Sebelum Push ke GitHub

### A. Build Aset Frontend (`public/build`)
Hosting cPanel umumnya **tidak menyediakan Node.js versi 18+/20+ di CLI** untuk menjalankan `npm run build`. Oleh karena itu, cara paling aman dan praktis:

1. Jalankan build di komputer lokal Anda:
   ```bash
   npm run build
   ```
2. Periksa file `.gitignore` Anda. Jika folder `/public/build` diabaikan, ubah atau hapus baris `/public/build` di `.gitignore` agar folder `public/build` ikut terunggah ke repositori GitHub:
   ```diff
   - /public/build
   ```
   *Dengan cara ini, file CSS & JS hasil kompilasi Tailwind CSS v4 dan Alpine.js langsung tersedia di server hosting tanpa perlu install Node.js di cPanel.*

### B. Keamanan File Rahasia (`.env` dan Google Service Account)
- **JANGAN PERNAH** mengunggah file `.env` yang berisi kredensial asli ke GitHub.
- Buat file `.env` langsung di dalam server cPanel dengan menyalin template dari [`.env.production.example`](file:///d:/KP%20Telkom/POJECT%20INTERN/.env.production.example).

---

## 4. Standar Struktur Folder di cPanel JagoanHosting

> [!IMPORTANT]
> **ATURAN KEAMANAN LARAVEL:**
> Jangan meletakkan seluruh file source code Laravel langsung di dalam `public_html` tanpa pemisahan! File konfigurasi sensitif seperti `.env`, folder `storage/`, dan `vendor/` bisa terekspos ke publik jika tidak diarahkan ke folder `public`.

### Struktur Penataan yang Direkomendasikan:
Letakkan folder proyek di root cPanel (sejajar dengan `public_html`):

```text
/home/username/
│
├── b2b-monitoring/               <-- Repositori Git proyek Anda (di luar public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/                   <-- Target Document Root Web
│   │   ├── build/                <-- File CSS & JS hasil kompilasi Vite
│   │   ├── storage/              <-- Symlink ke ../storage/app/public
│   │   ├── index.php
│   │   └── .htaccess
│   ├── storage/
│   ├── vendor/
│   └── .env                      <-- Kredensial produksi
│
└── public_html/                  <-- Document root default domain utama
```

### Cara Menghubungkan ke Domain:
- **Jika menggunakan Subdomain atau Addon Domain:**
  Di cPanel > **Domains**, cukup ubah **Document Root** langsung ke:
  `/home/username/b2b-monitoring/public`
- **Jika menggunakan Domain Utama (`public_html`):**
  Jalankan perintah symlink via Terminal SSH:
  ```bash
  rm -rf /home/username/public_html
  ln -s /home/username/b2b-monitoring/public /home/username/public_html
  ```
  *(Atau gunakan file `.htaccess` redirect jika hosting melarang modifikasi folder `public_html`).*

---

## 5. Panduan Langkah Deployment (Step-by-Step)

### Langkah 1: Push Repository ke GitHub
1. Pastikan aset sudah di-build: `npm run build`
2. Pastikan file `.env.production.example` sudah ada di repository.
3. Commit dan push ke repository GitHub Anda (disarankan **Private Repository**).

### Langkah 2: Buat Database MySQL di cPanel JagoanHosting
1. Masuk ke **cPanel JagoanHosting**.
2. Buka menu **MySQL Databases** / **MySQL Database Wizard**.
3. Buat database baru, contoh: `usercpanel_b2b_monitoring`.
4. Buat user database baru, contoh: `usercpanel_dbuser` beserta password yang kuat.
5. Hubungkan user tersebut ke database dengan memberikan centang **ALL PRIVILEGES**.
6. Simpan detail Nama Database, User, dan Password untuk diisikan di `.env`.

### Langkah 3: Clone Project ke Server (via Git Version Control atau SSH)

#### Opsi A: Menggunakan Fitur cPanel "Git Version Control"
1. Di cPanel, buka menu **Git™ Version Control**.
2. Klik tombol **Create**.
3. Masukkan **Clone URL** repository GitHub Anda (contoh: `https://github.com/username/b2b-monitoring.git` atau via SSH key).
4. Tentukan **Repository Path**: `b2b-monitoring` (jangan di dalam `public_html`).
5. Klik **Create**.

#### Opsi B: Menggunakan SSH Terminal (Paling Fleksibel & Cepat)
1. Buka menu **Terminal** di cPanel (atau login via PuTTY/SSH client).
2. Clone repositori:
   ```bash
   cd ~
   git clone https://github.com/username/b2b-monitoring.git b2b-monitoring
   cd b2b-monitoring
   ```

### Langkah 4: Setup File Konfigurasi `.env`
1. Di folder proyek, buat file `.env` dari template:
   ```bash
   cp .env.production.example .env
   ```
2. Edit file `.env` (via File Manager cPanel atau perintah `nano .env` di Terminal):
   ```env
   APP_NAME="Telkom B2B Contract Monitoring"
   APP_ENV=production
   APP_KEY=                          # Akan digenerate di langkah berikutnya
   APP_DEBUG=false
   APP_URL=https://namadomainanda.com

   # Koneksi Database cPanel
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=usercpanel_b2b_monitoring
   DB_USERNAME=usercpanel_dbuser
   DB_PASSWORD=PasswordDatabaseAnda

   # Session & Keamanan
   SESSION_DRIVER=database
   SESSION_ENCRYPT=true
   SESSION_SECURE_COOKIE=true

   # Kredensial Google Service Account
   GOOGLE_PROJECT_ID=project-id-anda
   GOOGLE_CLIENT_EMAIL=service-account@project-id.iam.gserviceaccount.com
   GOOGLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC...\n-----END PRIVATE KEY-----\n"

   # Google Sheets (Single Source of Truth)
   GOOGLE_SHEETS_SPREADSHEET_ID=spreadsheet_id_anda
   GOOGLE_SHEETS_SHEET_NAME=Contracts
   GOOGLE_SHEETS_RANGE=Contracts!A:U
   GOOGLE_SHEETS_HEADER_ROW=1
   GOOGLE_SHEETS_CACHE_TTL=300

   # Google Drive (Document Storage)
   GOOGLE_DRIVE_FOLDER_ID=folder_id_google_drive_anda
   ```

### Langkah 5: Eksekusi Perintah Persiapan di Terminal SSH
Masuk ke direktori proyek di Terminal:
```bash
cd ~/b2b-monitoring

# 1. Install dependensi PHP untuk production (tanpa dev package agar ringan)
composer install --no-dev --optimize-autoloader

# 2. Generate Application Key
php artisan key:generate

# 3. Jalankan Migrasi Database
php artisan migrate --force

# 4. Jalankan Seeder Database (Membuat akun default AM & Admin)
php artisan db:seed --force

# 5. Buat Symbolic Link untuk folder storage
php artisan storage:link

# 6. Optimasi Cache Laravel untuk Kecepatan Maksimal di Produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Langkah 6: Konfigurasi Cron Job (Otomasi Monitoring Kontrak)
Aplikasi ini memiliki command terjadwal `contracts:check-expiration` yang berjalan tiap pukul 07:00 pagi untuk mendeteksi kontrak yang mendekati jatuh tempo (H-90, H-60, H-30).

1. Buka menu **Cron Jobs** di cPanel JagoanHosting.
2. Pada pilihan **Common Settings**, pilih **Once Per Minute (* * * * *)**.
3. Pada kolom **Command**, masukkan:
   ```bash
   * * * * * cd /home/username/b2b-monitoring && php artisan schedule:run >> /dev/null 2>&1
   ```
   *(Ganti `username` dengan username cPanel Anda)*.
4. Klik **Add New Cron Job**.

---

## 6. Uji Coba & Verifikasi Pasca Deployment

Setelah semua langkah selesai, lakukan verifikasi berikut:
1. **Akses URL Domain:** Buka `https://namadomainanda.com/login`. Pastikan halaman login Telkom B2B Contract Monitoring muncul dengan rapi beserta styling Tailwind CSS.
2. **Login Akun Default:**
   - **Account Manager:** `am@telkom.co.id` | Password: `password`
   - **Admin / Developer:** `admin@telkom.co.id` | Password: `password`
   *(Segera ubah password setelah login pertama kali!)*
3. **Uji Koneksi Google Sheets:**
   Jalankan perintah ini di Terminal untuk memastikan API key dan Service Account berfungsi:
   ```bash
   php artisan sheets:test --refresh
   ```
4. **Uji Dokumen Drive:** Buka salah satu detail kontrak yang memiliki attachment, pastikan preview PDF dan proxy Google Drive berjalan mulus.
5. **Uji Ekspirasi Kontrak:**
   ```bash
   php artisan contracts:check-expiration --dry-run
   ```

---

## 7. Catatan Pembaruan di Masa Depan (Updating / Redeploy)
Setiap kali Anda melakukan update kode dan push ke GitHub, lakukan langkah ini di server cPanel:
```bash
cd ~/b2b-monitoring
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
