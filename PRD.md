# Product Requirements Document (PRD)

## Web Dashboard & Monitoring Kontrak Kerja B2B Telkom Indonesia

**Versi:** 1.2  
**Status:** Final Revised  
**Platform:** Web Application / PWA  
**Role:** Full-Stack Web Developer Intern  
**Target User:** 1 Account Manager (AM) / Mentor  
**Development Environment:** Windows + XAMPP + Laravel + MySQL  
**Production Hosting:** Jagoan Hosting / cPanel  
**Primary Data Source:** Google Sheets  
**Document Storage:** Google Drive

---

# 1. Ringkasan Produk

**Web Dashboard & Monitoring Kontrak Kerja B2B Telkom** adalah aplikasi web internal yang dirancang khusus untuk membantu seorang Account Manager (AM) memantau pipeline, kontrak, administrasi, invoice, Billcomp, masa berlaku kontrak, dan dokumen pendukung secara terpusat.

Aplikasi dibangun menggunakan **Laravel** dan terintegrasi dengan:

- **Google Sheets API v4** sebagai **Single Source of Truth** untuk data kontrak dan pipeline.
- **Google Drive API** sebagai sumber dokumen kontrak dan dokumen administratif.
- **MySQL 8** sebagai **Application Database** untuk data yang dibutuhkan oleh aplikasi.
- **Jagoan Hosting** sebagai platform production hosting.

Scope aplikasi sengaja dibatasi untuk **satu AM/mentor**, sehingga tidak membutuhkan arsitektur multi-tenant atau multi-AM yang kompleks.

---

# 2. Tujuan Produk

## 2.1 Tujuan Utama

Menyediakan dashboard yang memungkinkan AM:

- Melihat seluruh pipeline kontrak.
- Mengetahui posisi proyek pada stage F0–F4.
- Melihat informasi kontrak secara terpusat.
- Memantau SP/PO, Invoice, dan Billcomp.
- Mengetahui kontrak yang akan berakhir.
- Mendapatkan notifikasi expiration.
- Membuka dokumen kontrak tanpa mencari file secara manual.
- Memperbarui data kontrak melalui aplikasi.

## 2.2 Tujuan Operasional

Sistem diharapkan:

- Mengurangi waktu pencarian informasi.
- Mengurangi proses pengecekan spreadsheet secara manual.
- Memudahkan monitoring expiration.
- Mempercepat akses dokumen.
- Membantu proses renewal.
- Menyediakan dashboard visual tanpa memaksa AM meninggalkan workflow Google Sheets.

---

# 3. Problem Statement

Data kontrak yang dikelola AM berada pada Google Spreadsheet sehingga proses monitoring masih membutuhkan pencarian manual, pengecekan status administrasi, pengecekan dokumen, dan pemantauan tanggal berakhirnya kontrak.

Dibutuhkan application layer yang:

1. Mengambil data dari Google Sheets.
2. Mengubah data menjadi dashboard yang mudah dipahami.
3. Menghitung KPI dan status expiration.
4. Menyediakan search dan filter.
5. Menampilkan dokumen dari Google Drive.
6. Memungkinkan perubahan dari aplikasi dikirim kembali ke Google Sheets.
7. Tetap memungkinkan AM melakukan perubahan langsung pada Google Sheets.

---

# 4. Scope Pengguna

## 4.1 Account Manager / Mentor

Merupakan satu-satunya pengguna utama aplikasi.

AM dapat:

- Login.
- Melihat dashboard.
- Melihat pipeline.
- Mencari dan memfilter kontrak.
- Melihat detail kontrak.
- Melihat dokumen.
- Memperbarui data melalui aplikasi.
- Melihat notification center.

## 4.2 Developer/Admin

Untuk kebutuhan pengembangan dan maintenance, dapat tersedia satu akun administratif/developer.

Akun ini hanya digunakan untuk:

- Maintenance.
- Monitoring aplikasi.
- Melihat log.
- Troubleshooting.

Tidak dibutuhkan sistem multi-AM.

---

# 5. Out of Scope

Fitur berikut tidak termasuk dalam versi utama:

- Multi-AM.
- Multi-tenant.
- CRM lengkap.
- ERP.
- Accounting system.
- Payment gateway.
- Invoice generation.
- E-signature.
- Otomasi bidding.
- Otomasi negosiasi.
- Penggantian Google Sheets sebagai sumber utama data kontrak.
- Penyimpanan PDF kontrak secara permanen di server aplikasi.
- Sinkronisasi MySQL sebagai mirror utama seluruh data kontrak.

---

# 6. Prinsip Arsitektur Data

Aplikasi menggunakan prinsip tiga komponen data.

## 6.1 Google Sheets

Google Sheets adalah:

> **Single Source of Truth / System of Record untuk data kontrak dan pipeline.**

Data utama yang berasal dari Google Sheets:

- LOP
- Contract Number
- Customer
- Satker
- Service
- Stage
- Revenue
- Start Date
- End Date
- SP/PO
- Invoice
- Billcomp
- Document Reference

## 6.2 MySQL

MySQL adalah:

> **Application Database**

MySQL tidak menjadi sumber utama data kontrak.

MySQL digunakan untuk:

- User
- Authentication-related data
- Notifications
- Sync Logs
- Audit Logs
- Application Settings

## 6.3 Google Drive

Google Drive adalah:

> **Document Storage**

Untuk:

- Contract PDF
- SP/PO PDF
- Dokumen administrasi lainnya

---

# 7. Arsitektur Sistem

```text
                         MENTOR / AM
                             │
                         Web Browser
                             │
                             ▼
                 ┌─────────────────────┐
                 │   Jagoan Hosting    │
                 │                     │
                 │      Laravel        │
                 │         │           │
                 │      MySQL 8        │
                 └─────────┬───────────┘
                           │
             ┌─────────────┴──────────────┐
             │                            │
             ▼                            ▼
    Google Sheets API v4            Google Drive API
             │                            │
             ▼                            ▼
      Google Spreadsheet             Google Drive
      Source of Truth                Contract PDF
      Contract Data                  SP/PO PDF
```

---

# 8. Development Architecture

Development dilakukan secara lokal menggunakan:

```text
Developer Laptop
│
├── Antigravity
├── Laravel
├── PHP
├── XAMPP
│   └── MySQL
└── Browser
```

XAMPP hanya digunakan untuk development/testing.

Production tidak bergantung pada XAMPP lokal developer.

---

# 9. Production Architecture

Production menggunakan Jagoan Hosting.

```text
Git Repository
      │
      ▼
Jagoan Hosting
      │
      ├── Laravel
      ├── PHP
      ├── MySQL
      ├── Composer
      ├── HTTPS/SSL
      └── Cron Job
             │
             ├── Google Sheets API
             └── Google Drive API
```

Jagoan Hosting menyediakan panduan deployment Laravel melalui cPanel, Composer, dan akses SSH pada layanan yang mendukungnya. Ketersediaan fitur tertentu seperti SSH bergantung pada paket hosting yang digunakan.

---

# 10. Google Cloud & Service Account

## 10.1 Development

Development dapat menggunakan Google Cloud Project terpisah:

```text
telkom-b2b-monitoring-dev
```

Service Account development hanya diberikan akses ke:

- Google Spreadsheet development/dummy.
- Folder Google Drive development bila diperlukan.

## 10.2 Production

Production dapat menggunakan:

```text
telkom-b2b-monitoring-prod
```

atau Google Cloud project yang disetujui oleh pihak yang berwenang.

Service Account production hanya diberikan akses ke resource yang diperlukan.

## 10.3 Principle of Least Privilege

Service Account tidak boleh diberikan akses ke seluruh Google Drive jika hanya membutuhkan satu folder.

Akses harus dibatasi pada:

- Spreadsheet yang digunakan aplikasi.
- Folder dokumen yang digunakan aplikasi.

---

# 11. Google Sheets Configuration

Header spreadsheet yang digunakan aplikasi:

| Kolom | Header | Format |
|---|---|---|
| A | `LOP` | String / Unique Identifier |
| B | `Contract Number` | String |
| C | `Customer` | String |
| D | `Satker` | String |
| E | `Service` | String |
| F | `Stage` | F0–F4 |
| G | `Revenue` | Currency/Number |
| H | `Start Date` | Date |
| I | `End Date` | Date |
| J | `SP/PO` | AVAILABLE/MISSING |
| K | `Invoice Status` | UNBILLED/ISSUED/PAID |
| L | `Billcomp Status` | NOT_COMPLETE/PARTIAL/COMPLETED |
| M | `Billcomp Percentage` | 0–100% |
| N | `Document Reference` | Drive File ID/URL |

`LOP` digunakan sebagai identifier yang stabil untuk operasi pencarian dan update.

---

# 12. Functional Requirements

# FR-01 Authentication

Sistem harus menyediakan:

- Login.
- Logout.
- Session management.
- Protected routes.
- Error handling.

Hanya user yang telah login dapat mengakses dashboard.

---

# FR-02 Authorization

Karena hanya terdapat satu AM, authorization dibuat sederhana.

Minimal:

```text
ADMIN/DEVELOPER
AM
```

Tidak perlu multi-tenant authorization.

Backend wajib tetap melakukan authorization check.

---

# FR-03 Dashboard

Dashboard menampilkan:

### KPI

- Total Pipeline Revenue.
- Total LOP.
- Active Contracts.
- Expiring Contracts.
- Overdue Contracts.
- Realized Billcomp.

### Pipeline Summary

F0–F4 ditampilkan secara visual.

### Expiration Summary

Tampilkan kontrak:

- Active.
- Expiring Soon.
- Overdue.

### Notification Center

Tampilkan notifikasi expiration.

---

# FR-04 Pipeline

Pipeline stage:

```text
F0 Lead
F1 Opportunity
F2 Quote
F3 Bidding
F4 Negotiation
```

Sediakan dua tampilan:

### Kanban

Setiap kolom menampilkan:

- Jumlah LOP.
- Total Revenue.
- Daftar proyek.

### Table

Kolom:

- LOP.
- Customer.
- Satker.
- Service.
- Stage.
- Revenue.
- Start Date.
- End Date.
- SP/PO.
- Invoice.
- Billcomp.
- Document.

---

# FR-05 Search & Filter

Search:

- LOP.
- Contract Number.
- Customer.
- Satker.
- Service.

Filter:

- Stage.
- Satker.
- Service.
- Invoice Status.
- Billcomp Status.
- Expiration Status.

---

# FR-06 Contract Detail

Detail kontrak menampilkan:

- LOP.
- Contract Number.
- Customer.
- Satker.
- Service.
- Stage.
- Revenue.
- Start Date.
- End Date.
- Contract Status.
- SP/PO Status.
- Invoice Status.
- Billcomp Status.
- Billcomp Percentage.
- Document Reference.

---

# FR-07 Contract Create & Update

AM dapat menambahkan atau mengubah data melalui aplikasi.

Flow:

```text
User
 ↓
Form
 ↓
Validation
 ↓
Authorization
 ↓
Contract Service
 ↓
Google Sheets API
 ↓
Google Spreadsheet
```

Google Spreadsheet tetap menjadi sumber data utama.

---

# FR-08 Direct Google Sheet Update

AM tetap dapat mengubah data secara langsung pada Google Spreadsheet.

Contoh:

```text
AM mengubah Revenue
        ↓
Google Sheets
        ↓
Laravel membaca data terbaru
        ↓
Dashboard menampilkan nilai terbaru
```

Aplikasi tidak boleh menganggap nilai MySQL sebagai data kontrak yang lebih authoritative daripada Google Sheets.

---

# FR-09 Data Transformation

Laravel harus memiliki transformation layer.

## Currency

```text
Rp 150.000.000
        ↓
150000000
```

## Date

Normalisasi berbagai format tanggal menjadi format internal yang konsisten.

## Status

Normalisasi:

```text
Stage:
F0 / F1 / F2 / F3 / F4

Invoice:
UNBILLED / ISSUED / PAID

Billcomp:
NOT_COMPLETE / PARTIAL / COMPLETED
```

---

# FR-10 Expiration Engine

Hitung:

```text
Days Remaining = End Date - Current Date
```

Status:

| Kondisi | Status |
|---|---|
| > 60 hari | ACTIVE |
| 0–60 hari | EXPIRING_SOON |
| < 0 hari | OVERDUE |

Logic harus berada di satu service, misalnya:

```text
ExpirationService
```

Jangan menduplikasi logic di controller dan Blade.

---

# FR-11 Notification

Notifikasi expiration disimpan di MySQL.

Minimal:

```text
notifications
├── id
├── user_id
├── lop_reference
├── alert_type
├── days_remaining
├── is_read
├── created_at
└── updated_at
```

Sistem harus mencegah duplicate notification untuk kondisi yang sama.

---

# FR-12 Google Drive Integration

Buat:

```text
GoogleDriveService
```

Kemampuan:

- Find file.
- Validate file.
- Get metadata.
- Generate/access preview reference.

Akses dokumen harus melalui authentication/authorization aplikasi.

---

# FR-13 PDF Viewer

User dapat memilih:

```text
View Document
```

dan mendapatkan:

```text
Contract Detail
      ↓
View Document
      ↓
PDF Modal
```

Modal harus menyediakan:

- Loading state.
- Error state.
- Close action.
- Responsive layout.

---

# FR-14 Invoice Monitoring

Status:

```text
UNBILLED
ISSUED
PAID
```

Ditampilkan di:

- Dashboard.
- Kanban.
- Table.
- Contract detail.

---

# FR-15 Billcomp Monitoring

Status:

```text
NOT_COMPLETE
PARTIAL
COMPLETED
```

Tampilkan:

- Billcomp Percentage.
- Realized Revenue.
- Status.

---

# FR-16 Logging

Sistem mencatat:

- Login.
- Failed login.
- Contract update.
- Contract create.
- Google Sheets errors.
- Google Drive errors.
- Notification generation.
- Scheduled tasks.

Jangan mencatat password, private key, access token, atau secret.

---

# FR-17 Sync Logs

MySQL menyimpan operasi integrasi penting.

Format:

```text
sync_logs
├── id
├── user_id
├── sync_type
├── direction
├── status
├── records_processed
├── error_message
├── started_at
└── completed_at
```

Direction:

```text
SHEETS_TO_APP
APP_TO_SHEETS
```

---

# 13. Database Design

MySQL hanya menyimpan data aplikasi.

## Users

```text
users
├── id
├── name
├── email
├── password
├── role
├── is_active
├── created_at
└── updated_at
```

## Notifications

```text
notifications
├── id
├── user_id
├── lop_reference
├── alert_type
├── days_remaining
├── is_read
├── created_at
└── updated_at
```

## Sync Logs

```text
sync_logs
├── id
├── user_id
├── sync_type
├── direction
├── status
├── records_processed
├── error_message
├── started_at
└── completed_at
```

## Application Settings

```text
application_settings
├── id
├── key
├── value
├── created_at
└── updated_at
```

## Audit Logs

```text
audit_logs
├── id
├── user_id
├── action
├── target_type
├── target_reference
├── metadata
├── created_at
└── updated_at
```

---

# 14. Caching

Aplikasi dapat menggunakan Laravel Cache sebagai optimasi.

Cache bukan Source of Truth.

Alur:

```text
Dashboard Request
       ↓
Check Cache
       ↓
Available?
 ┌─────┴─────┐
Yes          No
 │            │
 ▼            ▼
Return     Google Sheets
Cache          │
               ▼
           Transform
               │
               ▼
             Cache
```

Default TTL dapat menggunakan:

```text
300 seconds / 5 minutes
```

Namun mekanisme refresh/invalidation harus tersedia agar data tidak terlalu lama stale.

---

# 15. Google API Environment Variables

Gunakan `.env`:

```env
APP_ENV=local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=telkom_b2b_monitoring
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_PROJECT_ID=
GOOGLE_CLIENT_EMAIL=
GOOGLE_PRIVATE_KEY=

GOOGLE_SHEETS_SPREADSHEET_ID=
GOOGLE_SHEETS_SHEET_NAME=
GOOGLE_SHEETS_RANGE=
GOOGLE_SHEETS_HEADER_ROW=1
GOOGLE_SHEETS_CACHE_TTL=300

GOOGLE_DRIVE_FOLDER_ID=
```

Jangan memasukkan credential asli ke:

- Git
- GitHub
- PRD
- README
- Source code
- Screenshot dokumentasi

Gunakan `.env.example` untuk placeholder.

---

# 16. Frontend Technology

Gunakan:

- Laravel Blade
- Tailwind CSS
- Alpine.js

UI harus:

- Clean.
- Professional.
- Responsive.
- Enterprise-oriented.
- Mudah dipindai AM.

Tidak membutuhkan frontend SPA yang kompleks.

---

# 17. Responsive Requirements

Dukungan minimal:

- Desktop.
- Laptop.
- Tablet.
- Mobile.

Kanban pada mobile menggunakan horizontal scrolling.

Table harus memiliki responsive strategy yang jelas.

---

# 18. Performance Requirements

Kurangi request eksternal yang tidak perlu.

Strategi:

- Cache.
- Batch Google Sheets read.
- Hindari request per-row.
- Hindari duplicate API calls.
- Gunakan pagination/filter secara efisien.

Aplikasi harus tetap usable ketika jumlah LOP meningkat.

---

# 19. Security Requirements

Wajib:

- Authentication.
- Authorization.
- CSRF protection.
- Input validation.
- Mass-assignment protection.
- Secure session management.
- Environment secrets.
- XSS protection.
- Injection protection.
- Secure Google API credential management.
- Document access control.

Credential Google tidak boleh dikirim ke browser.

---

# 20. Production Deployment

Target production:

> **Jagoan Hosting**

Deployment harus memperhatikan:

- PHP version yang kompatibel dengan versi Laravel yang digunakan.
- Composer.
- MySQL.
- HTTPS/SSL.
- Environment `.env`.
- Public document root Laravel.
- Storage link bila diperlukan.
- Cron Job untuk Laravel Scheduler.
- Git/FTP/SSH sesuai fitur paket hosting.

Jagoan Hosting menyediakan dokumentasi deployment Laravel melalui cPanel dan Composer, serta akses SSH pada layanan tertentu.

Laravel Scheduler dapat dijalankan menggunakan Cron Job pada hosting. Jagoan Hosting juga memiliki panduan konfigurasi Laravel Scheduler melalui cPanel/SSH.

## Production Flow

```text
Developer
   ↓
GitHub
   ↓
Jagoan Hosting
   ↓
Laravel
   ↓
MySQL Production
   ↓
Google APIs
```

---

# 21. Laravel Scheduler

Scheduler dapat digunakan untuk:

- Pengecekan expiration.
- Pembuatan notification.
- Maintenance task.
- Cache refresh jika dibutuhkan.

Contoh konsep:

```text
Cron Job
   ↓
php artisan schedule:run
   ↓
Laravel Scheduler
   ↓
Expiration / Notification Task
```

Jangan menggunakan scheduler untuk menjadikan MySQL sebagai mirror permanen Google Sheets.

---

# 22. Deployment Environment

## Local

```text
APP_ENV=local
DB → XAMPP MySQL
Google API → DEV
```

## Production

```text
APP_ENV=production
DB → MySQL Jagoan Hosting
Google API → PROD
```

Credential DEV dan PROD harus berbeda.

---

# 23. Error Handling

External API failure harus ditangani dengan:

- Logging.
- User-friendly message.
- Retry bila sesuai.
- Tidak menampilkan secret.
- Tidak menampilkan stack trace pada production.

Contoh:

```text
Unable to retrieve contract data.
Please try again later.
```

---

# 24. Testing Requirements

## Unit Test

Minimal:

- Currency parser.
- Date parser.
- Expiration calculation.
- KPI calculation.
- Status normalization.

## Feature Test

- Login.
- Logout.
- Dashboard.
- Authorization.
- Contract validation.
- Notification access.

## Integration Test

Google API harus menggunakan:

- Mock.
- Fake.
- Stub.

Jangan menggunakan credential production di automated tests.

---

# 25. User Flow

## Login

```text
Open Website
      ↓
Login
      ↓
Authentication
      ↓
Dashboard
```

## Monitoring

```text
Dashboard
   ↓
Search / Filter
   ↓
Contract
   ↓
Contract Detail
   ↓
Document / Update
```

## Update

```text
AM
 ↓
Form
 ↓
Validation
 ↓
Laravel
 ↓
Google Sheets API
 ↓
Google Sheets
```

## Direct Sheet Edit

```text
AM
 ↓
Google Sheets
 ↓
Laravel reads latest data
 ↓
Dashboard
```

---

# 26. Main Pages

## Login

- Email.
- Password.
- Login.
- Error state.

## Dashboard

- KPI cards.
- Pipeline.
- Expiration.
- Notifications.
- Search/filter.

## Contract List

- Search.
- Filter.
- Table.
- Status badges.

## Contract Detail

- Contract information.
- Financial information.
- Pipeline.
- Administrative status.
- Expiration.
- Document viewer.
- Edit.

## Admin/Developer

- Application status.
- Sync logs.
- Audit logs.
- Settings.

---

# 27. Acceptance Criteria

## Dashboard

- KPI ditampilkan berdasarkan data Google Sheets.
- Revenue dihitung dengan benar.
- Jumlah LOP benar.

## Pipeline

- F0–F4 tersedia.
- Kanban bekerja.
- Table bekerja.
- Search/filter bekerja.

## Contract

- Contract dapat dibaca dari Google Sheets.
- Detail tampil.
- User berwenang dapat membuat data.
- User berwenang dapat mengubah data.

## Data Source

- Google Sheets tetap menjadi Source of Truth.
- Perubahan langsung pada Google Sheets dapat terbaca aplikasi.
- Perubahan melalui aplikasi tersimpan kembali ke Google Sheets.

## Document

- Document reference dapat diproses.
- PDF dapat dipreview.
- File unavailable memiliki error state.

## Expiration

- H-60 terdeteksi.
- Expiring Soon ditampilkan.
- Overdue terdeteksi.
- Notification dibuat.

## Deployment

- Laravel dapat berjalan di Jagoan Hosting.
- MySQL production terhubung.
- Google API production terhubung.
- HTTPS aktif.
- Scheduler dapat dijalankan jika digunakan.

---

# 28. Success Metrics

| Metric | Target |
|---|---|
| Waktu pencarian kontrak | Berkurang signifikan |
| Akses dokumen | Langsung dari aplikasi |
| Pipeline visibility | F0–F4 tersedia |
| Expiration monitoring | H-60 otomatis teridentifikasi |
| Update kontrak | Dapat dilakukan melalui aplikasi |
| Direct Sheet Changes | Perubahan dapat terbaca |
| Application availability | Dapat diakses mentor tanpa laptop developer |

---

# 29. Deployment & Operational Model

Setelah aplikasi production aktif:

```text
Developer Laptop
       │
       └── Tidak diperlukan untuk operasi aplikasi

Jagoan Hosting
       │
       ├── Laravel
       ├── MySQL
       └── Scheduler
             │
             ├── Google Sheets
             └── Google Drive

Mentor
       │
       ▼
Browser
       │
       ▼
Production Application
```

**XAMPP hanya digunakan saat development lokal.**

Mentor tidak membutuhkan:

- XAMPP.
- PHP lokal.
- Composer lokal.
- Source code project.
- Laptop developer.

---

# 30. Future Development

Pengembangan di luar scope awal dapat meliputi:

- Multi-AM.
- Role permission lebih detail.
- Email reminder.
- WhatsApp/internal messaging integration.
- Advanced analytics.
- Revenue trend.
- Export Excel/PDF.
- Audit trail lebih detail.
- Integrasi sistem internal Telkom.
- Database mirror/warehouse apabila kebutuhan skala meningkat.

---

# 31. Final Architecture Decision

Keputusan arsitektur final:

| Komponen | Teknologi | Fungsi |
|---|---|---|
| Backend | Laravel | Application Core |
| Frontend | Blade | Server-side UI |
| Interaction | Alpine.js | UI Interaction |
| CSS | Tailwind CSS | Styling |
| Development DB | MySQL via XAMPP | Local Development |
| Production DB | MySQL Jagoan Hosting | Application Database |
| Contract Data | Google Sheets | **Source of Truth** |
| Contract API | Google Sheets API v4 | Data Integration |
| Documents | Google Drive | Document Storage |
| Document API | Google Drive API | Document Integration |
| Cache | Laravel Cache | Performance |
| Scheduler | Laravel Scheduler + Cron | Automated Tasks |
| Production Hosting | Jagoan Hosting | Application Hosting |
| Version Control | Git/GitHub | Source Control |

---

# 32. Final Product Definition

Produk akhir adalah:

> **Aplikasi dashboard internal berbasis Laravel untuk satu Account Manager yang mengambil data kontrak dari Google Sheets sebagai Single Source of Truth, mengakses dokumen melalui Google Drive, menyimpan data kebutuhan aplikasi di MySQL, dan berjalan di Jagoan Hosting sebagai production environment.**

Prinsip utamanya:

```text
Google Sheets
      =
Contract Source of Truth

MySQL
      =
Application Database

Google Drive
      =
Document Storage

Laravel
      =
Application Core

Jagoan Hosting
      =
Production Environment
```

Dengan arsitektur ini, AM tetap dapat menggunakan Google Spreadsheet seperti workflow sebelumnya, sementara dashboard menyediakan visualisasi pipeline, monitoring administrasi, early warning expiration, notifikasi, dan akses dokumen dalam satu aplikasi.