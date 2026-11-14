# PRD — Aplikasi Ujian Online Multi-Sekolah (Skala Yayasan)

**Versi:** 1.0  
**Tanggal:** 9 November 2026  
**Status:** Draft untuk review

---

## 1. Ringkasan Eksekutif

Aplikasi CBT (Computer Based Test) online untuk manajemen ujian yang dapat digunakan oleh lebih dari satu sekolah under satu yayasan. Setiap sekolah memiliki data sendiri (isolasi), sementara yayasan memiliki visibilitas dan laporan agregat terhadap semua sekolah.

Saat ini proyek sudah memiliki fondasi backend dan beberapa modul master data, tetapi masih dalam skala single-sekolah dan belum memiliki autentikasi maupun multi-tenancy.

---

## 2. Status Proyek Saat Ini

### 2.1 Tech Stack

| Komponen | Versi |
|----------|-------|
| Backend | Laravel 13.40.0, PHP 8.3 |
| Frontend | Inertia.js 3.11 + React 19 (JSX, non-TSX) |
| Styling | Tailwind CSS v4 via `@tailwindcss/vite` |
| Database | SQLite (lokal), dapat dikembangkan ke MySQL/PostgreSQL |
| Routing | Ziggy 2.6 |
| Build | Vite 8 |
| Ujian | PHPUnit (61 test, 291 assertions lulus) |
| Pemformatan Kode | Laravel Pint |

### 2.2 Modul yang Sudah Selesai (Backend + Frontend)

| Modul | Status | Fitur |
|-------|--------|-------|
| Tahun Pelajaran | Selesai | Kode otomatis (2024/2025 Ganjil → 20241), satu aktif saja, CRUD, search, pagination, toggle status |
| Jenis Ujian | Selesai | UTS, UAS, PTK, dll. Beberapa aktif sekaligus, CRUD, search, pagination |
| Sesi Ujian | Selesai | Waktu mulai/selesai, beberapa aktif sekaligus, CRUD, search, pagination |
| Ruangan | Selesai | Kode, nama, gedung, lantai, kapasitas, beberapa aktif, CRUD, search, pagination |

### 2.3 Modul yang Masih Mock / Belum Ada
- Dashboard, Monitoring Ujian (data statis)
- Sekolah, Jenis Penilaian, Mata Pelajaran, Siswa, Paket Ujian, Laporan, Pengaturan
- Autentikasi (login/logout belum ada)

### 2.4 Desain
Mengikuti `docs/DESIGN.md`:
- No-Line Rule (tidak ada border 1px, batasan lewat background shift)
- Hierarki surface tier
- Headline: Noto Serif, Body: Inter, Label: Public Sans
- Ikon: Material Symbols
- Komponen UI bersama: Modal, Input, Select, Button, Table, Pagination, Badge, Card, DeleteConfirmation, RowActions, Textarea

---

## 3. Analisis untuk Skala Multi-Sekolah (Yayasan)

### 3.1 Kebutuhan Multi-Tenancy

Untuk mendukung lebih dari satu sekolah under satu yayasan, diperlukan perubahan arsitektur:

| Aspek | Implementasi Saat Ini | Kebutuhan Multi-Sekolah |
|-------|----------------------|-------------------------|
| Isolasi data | Semua data dalam satu tabel, tanpa kolom `school_id` | Setiap tabel master data perlu `school_id` |
| Admin | Satu level admin | Super Admin (yayasan) + School Admin (per sekolah) |
| Autentikasi | Belum ada | Login per sekolah + role-based access |
| Tahun Pelajaran | Satu set untuk semua | Setiap sekolah punya tahun pelajaran sendiri |
| Siswa | Belum ada | Siswa terikat ke sekolah tertentu |
| Paket Ujian | Belum ada | Paket ujian dibuat per sekolah |

### 3.2 Arsitektur Multi-Tenancy yang Direkomendasikan

**Pendekatan: Shared Database, Separate per School via `school_id`**

Semua tabel master data ditambahkan kolom `school_id` yang mereferensi ke tabel `schools`. Query selalu difilter oleh `school_id` dari user yang login.

```
schools (kelola oleh yayasan)
├── id, code, name, address, contact, is_active
├── school_admins (satu atau lebih per sekolah)
└── academic_years, exam_types, sessions, rooms, students, dll. (semua punya school_id)
```

**Role:**
- **Super Admin** (yayasan level): Kelola semua sekolah, lihat laporan agregat, manage user school admin.
- **School Admin** (sekolah level): Kelola data master sendiri (tahun pelajaran, jenis ujian, sesi, ruangan, siswa, paket ujian), lihat monitoring sendiri.

---

## 4. Kebutuhan Fungsional (PRD)

### 4.1 Modul Autentikasi & Manajemen Sekolah

| Fitur | Deskripsi |
|-------|-----------|
| Login / Logout | Autentikasi per role (Super Admin / School Admin) |
| Manajemen Sekolah | CRUD data sekolah (nama, kode, alamat, kontak, status aktif) |
| Manajemen User | CRUD user per sekolah, role assignment |
| Role-Based Access | Super Admin vs School Admin akses berbeda |

### 4.2 Modul Master Data (per sekolah)

| Modul | Fitur |
|-------|-------|
| Tahun Pelajaran | Kode otomatis, satu aktif per sekolah, CRUD |
| Jenis Ujian | UTS, UAS, dll, beberapa aktif, CRUD |
| Sesi Ujian | Waktu mulai/selesai, beberapa aktif, CRUD |
| Ruangan | Gedung, lantai, kapasitas, beberapa aktif, CRUD |
| Mata Pelajaran | Kode, nama, deskripsi, CRUD |
| Jenis Penilaian | Ulangan, UTS, UAS, CRUD |
| Kelas | Nama kelas, tahun pelajaran, CRUD |
| Siswa | NIS, nama, kelas, jenis kelamin, foto, CRUD + import |
| Paket Ujian | Nama paket, mata pelajaran, kelas, sesi, ruangan, jadwal, CRUD |

### 4.3 Modul Ujian & Monitoring

| Fitur | Deskripsi |
|-------|-----------|
| Buat Paket Ujian | Pilih mata pelajaran, kelas, sesi, ruangan, waktu, durasi |
| Jadwal Ujian | Kalender ujian per sekolah |
| Monitoring Real-time | Status siswa (sedang ujian, selesai, pindah tab, multi-wajah) |
| Integritas Ujian | Deteksi pindah tab, multi-wajah, aktivitas mencurigakan |
| Laporan Hasil | Nilai per siswa, per kelas, per paket ujian |

### 4.4 Modul Laporan (Yayasan Level)

| Fitur | Deskripsi |
|-------|-----------|
| Laporan Agregat | Jumlah ujian per sekolah, jumlah siswa, tingkat kehadiran |
| Perbandingan Sekolah | Performa ujian antar sekolah |
| Export | PDF, Excel |

---

## 5. Kebutuhan Non-Fungsional

| Aspek | Spesifikasi |
|-------|-------------|
| Multi-tenancy | Setiap sekolah isolasi data via `school_id` |
| Keamanan | Autentikasi, autorisasi role-based, CSRF protection |
| Skalabilitas | Shared database dapat menangani 10–50 sekolah awal |
| Kinerja | Halaman muat < 2 detik, pencarian server-side |
| Aksesibilitas | Responsive (mobile + desktop), ARIA labels |
| Desain | Mengikuti `docs/DESIGN.md` (No-Line Rule, surface tier, dll.) |
| Uji Coba | Minimal 80% code coverage, PHPUnit |

---

## 6. Model Data (Multi-Tenancy)

Tabel utama yang perlu ditambahkan `school_id`:

```text
schools
├── id, code, name, address, phone, email, is_active, created_at, updated_at

users (existing)
├── school_id (nullable untuk super admin), role (super_admin/school_admin), ...

academic_years (existing)
├── school_id, name, code, semester, is_active

exam_types (existing)
├── school_id, code, name, description, is_active

exam_sessions (existing)
├── school_id, code, name, start_time, end_time, is_active

rooms (existing)
├── school_id, code, name, building, floor, capacity, is_active

subjects (baru)
├── school_id, code, name, description, is_active

assessment_types (baru)
├── school_id, code, name, description, is_active

classes (baru)
├── school_id, code, name, academic_year_id, is_active

students (baru)
├── school_id, nis, name, class_id, gender, photo, is_active

exam_packages (baru)
├── school_id, code, name, subject_id, class_id, session_id, room_id, schedule, duration, is_active
```

---

## 7. Roadmap Implementasi

### Fase 1: Fondasi Multi-Sekolah
- [ ] Autentikasi (login, logout, middleware `auth`)
- [ ] Manajemen Sekolah (CRUD)
- [ ] Role & Permission (Super Admin vs School Admin)
- [ ] Menambahkan `school_id` ke semua tabel master data yang ada
- [ ] Migrasi data esistente ke sekolah default

### Fase 2: Master Data Per Sekolah
- [ ] Mata Pelajaran, Jenis Penilaian, Kelas
- [ ] Siswa (CRUD + import Excel)
- [ ] Paket Ujian & Jadwal

### Fase 3: Ujian & Monitoring
- [ ] Pengadaan paket ujian
- [ ] Monitoring real-time siswa
- [ ] Deteksi integritas ujian
- [ ] Laporan hasil ujian

### Fase 4: Laporan Yayasan
- [ ] Dashboard agregat yayasan
- [ ] Perbandingan antar sekolah
- [ ] Export laporan (PDF/Excel)

---

## 8. Metrik Sukses

| Metrik | Target |
|--------|--------|
| Jumlah sekolah yang terlayani | 10+ sekolah dalam 3 bulan |
| Waktu respons halaman | < 2 detik |
| Uptime | 99.5% |
| Cakupan test | 80%+ |
| Waktu setup sekolah baru | < 15 menit |

---

## 9. Rekomendasi Langkah Berikutnya

Berdasarkan analisis di atas, urutan implementasi yang direkomendasikan:

1. **Autentikasi terlebih dahulu** — Tanpa login, multi-tenancy tidak bisa diterapkan karena tidak ada identitas user/sekolah.
2. **Tambahkan `school_id` ke modul yang sudah ada** — Tahun Pelajaran, Jenis Ujian, Sesi, Ruangan perlu diupdate untuk mendukung multi-sekolah.
3. **Manajemen Sekolah** — CRUD data sekolah dan user school admin.
4. **Modul master data lainnya** — Mata Pelajaran, Siswa, Kelas, Paket Ujian.
5. **Monitoring & Laporan** — Modul inti aplikasi ujian online.

---

## 10. File Terkait

| File | Deskripsi |
|------|-----------|
| `docs/DESIGN.md` | Panduan desain visual (No-Line Rule, surface tier, tipografi) |
| `docs/RESUME.md` | Resume pekerjaan teknis yang sudah dilakukan |
| `AGENTS.md` | Panduan pengembangan (konvensi, Pint, test, build) |
| `routes/web.php` | Definisi route admin group |
| `app/Http/Controllers/Admin/` | Controller modul master data |
| `app/Http/Requests/Admin/` | Form request validasi |
| `database/migrations/` | Migrasi database |
| `database/seeders/` | Seeder data awal |
| `tests/Feature/Admin/` | Test feature untuk controller admin |
| `resources/js/Pages/` | Halaman frontend Inertia + React JSX |
