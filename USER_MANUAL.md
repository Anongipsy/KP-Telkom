# 📘 PANDUAN PENGGUNA (USER MANUAL)
## Dashboard & Monitoring Kontrak Kerja B2B Telkom Indonesia

---

### 📌 Informasi Dokumen
- **Nama Aplikasi**: Web Dashboard & Monitoring Kontrak Kerja B2B Telkom Indonesia
- **Versi**: 1.3
- **Sasaran Pengguna**: Account Manager (AM) / Mentor & Administrator / Developer
- **Integrasi Utama**: Google Sheets API v4 (Single Source of Truth) & Penyimpanan Dokumen Server Lokal (Database MySQL + Disk Storage)

---

## DAFTAR ISI
1. [Pengenalan Sistem](#1-pengenalan-sistem)
2. [Hak Akses & Kredensial Pengguna](#2-hak-akses--kredensial-pengguna)
3. [Masuk (Login) & Pengaturan Tampilan](#3-masuk-login--pengaturan-tampilan)
4. [Navigasi Antarmuka Sistem](#4-navigasi-antarmuka-sistem)
5. [Modul 1: Dashboard Overview](#5-modul-1-dashboard-overview)
6. [Modul 2: Manajemen Kontrak & Pipeline](#6-modul-2-manajemen-kontrak--pipeline)
   - [Tampilan Tabel & Papan Kanban](#61-tampilan-tabel--papan-kanban)
   - [Pencarian Cerdas (Real-Time Auto Filter 0.5 Detik)](#62-pencarian-cerdas-real-time-auto-filter-05-detik)
   - [Filter Lanjutan](#63-filter-lanjutan)
   - [Menambah Kontrak Baru & Upload Dokumen](#64-menambah-kontrak-baru--upload-dokumen)
   - [Melihat Detail Kontrak & Pratinjau Dokumen](#65-melihat-detail-kontrak--pratinjau-dokumen)
   - [Mengubah (Edit) Data Kontrak & Manajemen Dokumen](#66-mengubah-edit-data-kontrak--manajemen-dokumen)
   - [Menyelesaikan Kontrak (Mark as Completed)](#67-menyelesaikan-kontrak-mark-as-completed)
7. [Modul 3: Early Warning & Pusat Notifikasi](#7-modul-3-early-warning--pusat-notifikasi)
8. [Modul 4: Audit Trail & Aktivitas Sistem (Khusus Admin)](#8-modul-4-audit-trail--aktivitas-sistem-khusus-admin)
9. [Tanya Jawab & Penyelesaian Masalah (FAQ & Troubleshooting)](#9-tanya-jawab--penyelesaian-masalah-faq--troubleshooting)

---

## 1. Pengenalan Sistem

**Web Dashboard & Monitoring Kontrak Kerja B2B Telkom** adalah portal aplikasi web yang dirancang khusus untuk mempermudah operasional Account Manager (AM) dalam:
- Memantau seluruh siklus kontrak dan tahapan pipeline penjualan (**F0 Lead** hingga **F4 Negotiation**).
- Memantau masa berlaku kontrak secara proaktif (*Early Warning System*) guna mencegah keterlambatan perpanjangan (*overdue*).
- Memantau status kelengkapan administratif: Surat Pesanan/Purchase Order (**SP/PO**), status **Invoice**, dan realisasi **Billcomp**.
- Mengunggah, menyimpan, dan melihat dokumen PDF serta gambar kontrak secara langsung di server lokal internal tanpa ketergantungan pada akun Google Drive pihak ketiga.
- Menjaga sinkronisasi data kontrak dengan **Google Spreadsheet** sebagai basis data utama (*Single Source of Truth*).

---

## 2. Hak Akses & Kredensial Pengguna

Sistem menyediakan dua peran pengguna utama:

| Peran (Role) | Akses & Wewenang | Contoh Pengguna |
| :--- | :--- | :--- |
| **Account Manager (AM)** | Pengguna operasional utama: Akses Dashboard, Manajemen Kontrak, Tambah/Edit Kontrak, Penyelesaian Kontrak, dan Early Warning. | `am@telkom.co.id` |
| **Administrator / Developer** | Pengguna teknis: Memiliki seluruh akses AM ditambah menu **Audit Trail & Log** untuk inspeksi aktivitas serta pengaturan sistem. | `admin@telkom.co.id` |

---

## 3. Masuk (Login) & Pengaturan Tampilan

### 3.1 Langkah Masuk ke Aplikasi
1. Buka peramban (browser) seperti Google Chrome, Microsoft Edge, atau Mozilla Firefox.
2. Akses alamat URL aplikasi (misal: `http://localhost:8000` atau domain hosting Jagoan Hosting).
3. Halaman login resmi Telkom B2B Monitoring akan tampil.
4. Masukkan **Alamat Email** dan **Kata Sandi (Password)** Anda.
5. Tekan tombol **Masuk ke Dashboard**.

### 3.2 Pemilihan Tema Tampilan (Light, Dark, Pink)
Aplikasi dilengkapi pengatur tema di pojok kanan bawah / navbar:
- **Light Mode**: Nuansa latar putih bersih dan kontras tinggi untuk siang hari.
- **Dark Mode**: Nuansa latar gelap elegan (*slate-950*) yang nyaman di mata.
- **Pink Mode**: Nuansa aksen lembut modern.
> *Catatan: Pilihan tema Anda akan otomatis tersimpan di peramban dan dipertahankan saat membuka halaman berikutnya.*

---

## 4. Navigasi Antarmuka Sistem

Antarmuka dirancang responsif dengan bilah sisi (**Sidebar Navigasi**) di sebelah kiri:

1. **Logo & Identitas**: Logo Telkom Indonesia dan label "Telkom B2B Contract Monitor".
2. **Tombol "+ Tambah Kontrak"**: Pintasan cepat untuk mendaftarkan kontrak baru.
3. **Menu Dashboard (Overview)**: Halaman beranda ringkasan metrik, grafik analitik, dan peringatan kritis.
4. **Menu Kontrak & Pipeline**: Daftar seluruh kontrak dengan tampilan Tabel dan Kanban.
5. **Menu Early Warning & Alerts**: Pusat pemantauan masa kedaluwarsa kontrak dan notifikasi sistem.
6. **Menu Audit Trail & Log** *(Hanya muncul untuk role Admin)*: Catatan riwayat aktivitas.
7. **Panel Profil Pengguna**: Menampilkan inisial, nama, role akun, dan tombol **Logout** (Keluar).

---

## 5. Modul 1: Dashboard Overview

Halaman Dashboard menyajikan *helicopter view* atas seluruh kontrak dan pipeline Anda.

### 5.1 Bilah Filter Data (Tahun & Nama GC)
Di bagian atas dashboard, terdapat bilah filter instan:
- **Pilih Periode Tahun**: Pilih tahun tertentu (misal: 2024, 2025, 2026) atau "Semua Tahun (All Time)".
- **Filter Nama GC (Group Company)**: Pilih korporasi / grup perusahaan tertentu atau "Semua GC".
- Tekan tombol **Terapkan** untuk menyaring data seketika.

### 5.2 Kartu Metrik KPI Utama
Terdapat 4 kartu indikator kinerja:

1. **Total Kontrak Terdaftar**: Jumlah total Lembar Order Penjualan (LOP) yang tercatat.
2. **Total Nilai Pipeline**: Akumulasi nilai kontrak (*Realisasi Win*).
   - Di dalam kartu ini terdapat **Mini-Badge Interaktif**:
     - **Badge Hijau (Kontrak Berjalan)**: Menampilkan jumlah kontrak yang sedang aktif berjalan.
     - **Badge Merah (Kontrak Selesai)**: Menampilkan jumlah kontrak yang telah dinyatakan selesai.
     - *Fitur Cepat*: Mengklik salah satu badge ini akan langsung mengarahkan Anda ke halaman Kontrak dengan filter status terkait yang langsung aktif!
3. **Lewat Jatuh Tempo (Overdue)**:
   - Menampilkan jumlah kontrak yang masa berlakunya telah habis (< 0 hari).
   - *Penting*: Kontrak yang telah diselesaikan (*Status: Selesai*) **tidak akan dihitung** di sini agar tidak membingungkan AM.
4. **Mendekati Jatuh Tempo (Expiring Soon)**:
   - Menampilkan jumlah kontrak yang akan habis masa berlakunya dalam rentang 0 hingga 60 hari ke depan, siap untuk proses perpanjangan (*renewal*).

### 5.3 Ringkasan Tahapan Pipeline (F0–F4)
Menampilkan 5 kartu tahapan alur penjualan:
- **F0 (Lead)**: Prospek awal proyek.
- **F1 (Opportunity)**: Peluang kebutuhan pelanggan teridentifikasi.
- **F2 (Quote)**: Penawaran harga / proposal diajukan.
- **F3 (Bidding)**: Proses tender / pengadaan berjalan.
- **F4 (Negotiation)**: Negosiasi akhir menjelang penandatanganan kesepakatan (*Win*).
Setiap kartu menampilkan jumlah LOP dan total nilai revenue pada tahapan bersangkutan.

### 5.4 Grafik Analitik Visual
- **Donut Chart (Distribusi Tahapan Pipeline)**: Visualisasi proporsi pembagian kontrak berdasarkan stage F0 sampai F4 dengan label interaktif saat disentuh kursor.
- **Horizontal Bar Chart (Revenue per Group Company)**: Peringkat korporasi pelanggan yang memberikan kontribusi nilai revenue tertinggi.

### 5.5 Widget Peringatan Cepat & Kontrak Terbaru
- **Perlu Perhatian Segera**: Menampilkan hingga 4 kontrak yang paling mendesak untuk ditindaklanjuti (overdue atau hampir habis).
- **Daftar Kontrak Terbaru**: Ringkasan 5 kontrak terbaru yang baru diperbarui/didaftarkan.

---

## 6. Modul 2: Manajemen Kontrak & Pipeline

Halaman ini merupakan pusat kerja utama AM untuk mengelola seluruh portofolio kontrak.

### 6.1 Tampilan Tabel & Papan Kanban
Anda dapat berpindah tampilan dengan mudah menggunakan tombol di kanan atas:
- **Tabel Data**: Menampilkan data berbentuk baris kolom komprehensif, mencakup Identitas LOP, Satker/GC, Layanan, Tahapan, Nilai Win, Masa Berlaku, Status SP/PO, dan Realisasi Billcomp.
- **Papan Kanban**: Mengelompokkan kontrak ke dalam kolom visual F0, F1, F2, F3, dan F4. Setiap kartu menampilkan detail pelanggan, nilai, sisa hari, dan tombol aksi cepat.

### 6.2 Pencarian Cerdas (Real-Time Auto Filter 0.5 Detik)
Kotak pencarian di halaman ini dilengkapi teknologi cerdas:
- Ketik kata kunci apa saja (Nomor LOP, Nama Pelanggan, Satker, atau Layanan).
- Sistem akan otomatis menerapkan filter **0.5 detik** setelah Anda berhenti mengetik tanpa harus menekan Enter atau tombol apapun!
- Indikator *"Menyaring..."* warna merah akan menyala saat sistem memproses penyaringan.

### 6.3 Filter Lanjutan
Klik tombol **"Filter Lanjutan"** untuk membuka panel parameter lengkap:
- **Tahun**: Memfilter tahun kontrak.
- **Nama GC**: Memfilter grup perusahaan.
- **Tahapan Pipeline**: Memilih tahap F0, F1, F2, F3, atau F4.
- **Satker**: Memilih satuan kerja / dinas / instansi tertentu.
- **Status Kedaluwarsa**: 
  - 🟢 *ACTIVE* (> 60 hari)
  - 🟡 *EXPIRING SOON* (0–60 hari)
  - 🔴 *OVERDUE* (< 0 hari)
- **Status Kontrak**:
  - 🟢 *Kontrak Berjalan*
  - 🔴 *Kontrak Selesai*
- **Status SP / PO**:
  - *AVAILABLE* (Sudah ada Surat Pesanan)
  - *MISSING* (Belum ada Surat Pesanan)

---

### 6.4 Menambah Kontrak Baru & Upload Dokumen
Untuk mendaftarkan proyek/kontrak baru ke sistem dan Google Sheets:
1. Klik tombol **"+ Tambah Kontrak"** di sidebar atau di pojok atas daftar kontrak.
2. Isi formulir yang tersedia:
   - **Nomor LOP**: Nomor identitas unik (misal: `LOP-2026-001`).
   - **ID MyTens**: Nomor registrasi MyTens (opsional).
   - **Tahun**: Tahun pelaksanaan proyek.
   - **Satuan Kerja (Satker) & Nama GC**: Nama dinas/kantor dan grup perusahaan.
   - **Pelanggan**: Nama instansi pelanggan.
   - **Tahapan Pipeline**: Pilih F0, F1, F2, F3, atau F4.
   - **Nilai Realisasi Win**: Nominal rupiah hasil kesepakatan.
   - **Deskripsi Layanan**: Uraian spesifikasi layanan (mendukung teks panjang **hingga 700 karakter**).
   - **Tanggal Mulai & Tanggal Berakhir**: Periode masa aktif kontrak.
   - **Status SP/PO, Invoice, Billcomp**: Status administratif terkait.
   - **Upload Dokumen Kontrak**: Pilih atau *drag-and-drop* file dokumen kontrak dari komputer Anda. Mendukung format **PDF, PNG, JPG, dan WebP** dengan batas ukuran maksimal **10MB**. Dokumen akan disimpan secara aman di server lokal internal aplikasi.
3. Tekan **Simpan Kontrak**. Sistem akan memvalidasi data, menyimpan data kontrak ke Google Spreadsheet, dan menyimpan dokumen ke disk server.

---

### 6.5 Melihat Detail Kontrak & Pratinjau Dokumen
Klik ikon **Mata (Detail)** pada baris kontrak untuk membuka halaman detail:
- **Header Informasi**: Status LOP, Tahun, Tahapan Pipeline, Status Kontrak (Berjalan/Selesai), dan Masa Berlaku.
- **Progress Realisasi Billcomp**: Menampilkan kalkulasi otomatis persentase tagihan yang telah terealisasi vs total nilai win.
- **Spesifikasi & Layanan**: Menampilkan deskripsi lengkap layanan proyek.
- **Timeline Kontrak**: Visualisasi tanggal awal hingga tanggal berakhir kontrak.
- **Manajemen Dokumen Kontrak Terpadu**:
  - **Pratinjau Dokumen (Preview Modal)**: Jika dokumen telah di-upload, klik tombol **"Lihat Dokumen Kontrak"** untuk membuka modal pratinjau dokumen (PDF atau gambar) secara langsung dan responsif.
  - **Buka di Tab Baru**: Anda dapat membuka dokumen di tab baru peramban untuk melihat ukuran penuh atau mencetaknya.
  - **Hapus Dokumen**: Terdapat tombol hapus dokumen dengan konfirmasi keamanan.
  - **Form Upload Cepat (Inline)**: Jika kontrak belum memiliki dokumen fisik di server, formulir upload dokumen tersedia langsung pada halaman detail ini tanpa harus masuk ke menu edit kontrak.

---

### 6.6 Mengubah (Edit) Data Kontrak & Manajemen Dokumen
1. Buka halaman detail kontrak lalu klik tombol **"Edit Kontrak"** (atau klik ikon Pensil dari tabel/kanban).
2. Perbarui kolom yang diinginkan (misal: menaikkan tahapan dari F3 ke F4, memperbarui nominal, mengubah status invoice, atau memperpanjang tanggal berakhir).
3. **Pengaturan Tanggal Berakhir (End Date)**: Anda bebas memilih tanggal akhir berapa pun (tidak terkunci di tanggal 1) berkat kalender pemilih tanggal standar ISO (`YYYY-MM-DD`).
4. **Penggantian Dokumen Kontrak**: Jika dokumen sudah ada, kartu ringkasan file akan ditampilkan. Anda dapat mengunggah file baru pada kotak upload untuk otomatis menggantikan dokumen lama yang tersimpan di server.
5. Klik tombol **Simpan Perubahan**. Data di Google Sheets dan file di server lokal akan segera diperbarui.

---

### 6.7 Menyelesaikan Kontrak (Mark as Completed)
Jika sebuah proyek telah selesai masa layanannya dan kewajiban administratifnya telah tuntas:
1. Buka halaman Detail Kontrak (`/contracts/{lop}`).
2. Pada bagian kanan atas, klik tombol hijau **"Selesaikan Kontrak"**.
3. Konfirmasi jendela dialog yang muncul.
4. Kontrak akan berganti status menjadi **"Kontrak Selesai"**:
   - Label masa berlaku tidak lagi menampilkan angka minus (misal: `-221 hari`), melainkan badge jelas bertuliskan **"Kontrak Selesai"** berwarna merah tegas.
   - Kontrak ini otomatis **dihapus dari daftar alert Overdue** di Dashboard dan Early Warning.

---

## 7. Modul 3: Early Warning & Pusat Notifikasi

Akses modul ini melalui menu **"Early Warning & Alerts"** di sidebar.

Modul ini memiliki 2 Tab Utama:

### 7.1 Tab Status Kedaluwarsa (*Expiration Health*)
Memantau masa berlaku kontrak dalam 3 tab kartu filter:
1. **OVERDUE (< 0 Hari)**:
   - Berisi kontrak aktif yang telah melewati batas tanggal berakhir dan memerlukan perpanjangan segera.
   - Dilengkapi tombol cepat untuk menghubungi satker, memperbarui kontrak, atau menandainya sebagai selesai jika pekerjaan sudah rampung.
2. **EXPIRING SOON (0–60 Hari)**:
   - Memberikan peringatan dini (*early warning*) untuk kontrak yang akan habis dalam waktu 2 bulan ke depan. Sangat krusial untuk inisiasi pembicaraan addendum/kontrak baru dengan pelanggan.
3. **ACTIVE (> 60 Hari)**:
   - Kontrak yang masih memiliki masa aktif panjang dan berada dalam kondisi aman.

### 7.2 Tab Pusat Notifikasi
- Menyimpan log pemberitahuan otomatis sistem mengenai perubahan status kontrak, peringatan jatuh tempo, dan dokumen yang belum lengkap (*missing SP/PO*).
- Anda dapat menandai notifikasi sebagai telah dibaca (*Mark as Read*), menandai seluruh notifikasi sekaligus (*Mark All as Read*), atau menghapus notifikasi lama.

---

## 8. Modul 4: Audit Trail & Aktivitas Sistem (Khusus Admin)

Menu ini khusus dapat diakses oleh akun dengan peran **Administrator / Developer** (`admin@telkom.co.id`):
- **Pelacakan Aktivitas**: Mencatat secara kronologis siapa yang membuat, mengedit, atau menyelesaikan kontrak.
- **Perbandingan Nilai (Old vs New)**: Menampilkan rincian data sebelum dan sesudah diedit guna mencegah kesalahan manipulasi data dan mempermudah pelacakan jika terjadi inkonsistensi data.
- **Status Sinkronisasi Google Sheets**: Memantau keberhasilan koneksi API dan cache aplikasi.

---

## 9. Tanya Jawab & Penyelesaian Masalah (FAQ & Troubleshooting)

### Q1: Mengapa kontrak yang sudah saya selesaikan masih belum hilang dari dashboard?
> **Jawaban**: Pastikan Anda telah menekan tombol **"Selesaikan Kontrak"** di halaman detail kontrak sehingga statusnya berganti menjadi `SELESAI`. Begitu berstatus selesai, sistem otomatis mengecualikannya dari kartu *Lewat Jatuh Tempo* dan daftar *Urgent Expiration*. Jika baru saja diubah di Google Spreadsheet secara eksternal, tunggu sekitar 5 menit (masa kedaluwarsa cache) atau perbarui data melalui aplikasi agar cache ter-refresh seketika.

### Q2: Bagaimana cara mengubah tanggal akhir kontrak tanpa kembali ke tanggal 1?
> **Jawaban**: Di halaman **Edit Kontrak**, klik kolom **Tanggal Berakhir Kontrak** dan pilih tanggal yang tepat melalui pop-up kalender. Format tanggal telah disinkronkan secara presisi menggunakan format internasional `YYYY-MM-DD` sehingga tanggal apa pun yang Anda pilih (misal: 15, 20, 28) akan tersimpan dengan tepat ke spreadsheet.

### Q3: Berapa batas panjang pengisian Deskripsi Layanan?
> **Jawaban**: Sistem mengizinkan pengisian deskripsi layanan hingga **700 karakter**. Jika teks Anda melebihi batas tersebut, form akan menampilkan pesan peringatan sebelum disimpan.

### Q4: Mengapa dokumen PDF kontrak tidak bisa dibuka di viewer?
> **Jawaban**: Pastikan URL Google Drive yang diinput memiliki format link file Google Drive yang valid, dan perizinan akses (*share permission*) pada Google Drive disetel ke **"Anyone with the link" (Siapa saja yang memiliki link)** atau akun Service Account Telkom diberikan izin melihat (*Viewer*).

### Q5: Apakah saya masih boleh mengedit langsung di Google Spreadsheet?
> **Jawaban**: **Bisa!** Google Spreadsheet bertindak sebagai *Single Source of Truth*. Namun, sangat disarankan melakukan pembaruan melalui aplikasi agar validasi data (seperti format angka rupiah, tanggal ISO, dan riwayat audit trail) tercatat secara rapi dan otomatis.

---

*Buku Panduan ini disusun untuk operasional Tim B2B Telkom Indonesia. Untuk bantuan teknis lebih lanjut, silakan hubungi tim Administrator Sistem / Developer.*
