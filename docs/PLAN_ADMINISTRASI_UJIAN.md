# Rencana Pembuatan Administrasi Ujian

## Daftar Isi
1. [Overview](#overview)
2. [Fitur yang Akan Dibangun](#fitur-yang-akan-dibangun)
3. [Arsitektur Data](#aritektur-data)
4. [Alur Pembuatan Setiap Fitur](#alur-pembuatan-setiap-fitur)
5. [Tahapan Implementasi](#tahapan-implementasi)
6. [Catatan Penting](#catatan-penting)

---

## 1. Overview

Aplikasi CBT ini sudah memiliki fondasi administrasi peserta ujian melalui `ExamParticipantController` yang menyediakan:
- Daftar peserta ujian (index)
- Kartu peserta PDF (cards)
- Sync peserta dari kelas
- Generate kredensial

Perencanaan ini akan memperluas fitur tersebut untuk mencakup:
1. **Daftar Hadir** - Absensi peserta pada sesi ujian tertentu
2. **Kartu Peserta** - Cetak kartu identitas peserta (sudah ada, butuh perbaikan)
3. **Daftar Nominatif/Daftar Peserta** - Lapor keseluruhan peserta per kelas/tahun pelajaran
4. **Berita Acara** - Dokumentasi hasil ujian per tempat/tahun

---

## 2. Fitur yang Akan Dibangun

### 2.1. Daftar Hadir (Attendance List)
- Menampilkan daftar peserta yang hadir pada sesi ujian tertentu
- Filter berdasarkan: sekolah, tahun pelajaran, kelas, sesi, ruangan
- Status hadir/dtidak hadir
- Export ke PDF/Excel

### 2.2. Kartu Peserta (Participant Card)
- Sudah ada fitur melalui `ExamParticipantController::cards`
- Perbaikan desain/templat kartu
- Pilihan ukuran kertas dan font
- Batch print per sekolah/tahun/sesi

### 2.3. Daftar Nominatif/Daftar Peserta (Nominal List)
- Daftar lengkap peserta per kelas
- Terstruktur: NISN, Nama, Kelas, Status
- Filter: Sekolah, Tahun Pelajaran, Kelas, Mata Pelajaran
- Export format: PDF, Excel

### 2.4. Berita Acara (Minutes/Report)
- Berita acara ujian per jadwal pelaksanaan
- Meliputi: Jam, Ruangan, Mata Pelajaran, Kelas, Total Peserta, Peserta Hadir, Peserta Tidak Hadir
- Ringkasan skor/hasil
- Export ke PDF

---

## 3. Aritektur Data

### 3.1. Tabel yang Digunakan (sudah ada)

| Tabel | Keterangan |
|-------|------------|
| `exam_participants` | Daftar peserta ujian (sudah ada) |
| `exam_schedules` | Jadwal ujian (sudah ada) |
| `exam_schedules` | Catatan waktu dan ruangan |
| `students` | Data siswa |
| `school_classes` | Struktur kelas |
| `rooms` | Data ruangan |
| `sessions` | Sesi ujian |

### 3.2. Perbaikan/Tambahan Model

Mungkin perlu menambah kolom atau relasi baru:

```php
// Pada model ExamParticipant
public function attendance(): HasMany {
    return $this->hasMany(ExamAttendance::class);
}
```

Atau bisa pakai enum status kehadiran:

```php
// App/Enums/ExamAttendanceStatus.php
enum ExamAttendanceStatus: string {
    case Hadir = 'hadir';
    case Tidak_Hadir = 'tidak_hadir';
    case Ijin = 'ijin';
    case Sakit = 'sakit';
}
```

---

## 4. Alur Pembuatan Setiap Fitur

### 4.1. Daftar Hadir

1. **Create ExamAttendance model/migration**
   - `exam_schedule_id`
   - `exam_participant_id`
   - `status` (hadir/tidak_hadir)
   - `check_in_time`
   - `created_at`, `updated_at`

2. **Create attendance tracking UI**
   - Halaman daftar absensi per jadwal
   - Checkbox per peserta untuk menandai hadir/tidak hadir
   - Save attendance records

3. **API/Controller method**
   - `AttendanceController@index` - List absensi dengan filter
   - `AttendanceController@update` - Update status hadir per peserta

4. **Export**
   - PDF attendance list
   - Excel export

### 4.2. Kartu Peserta (Perbaikan)

1. **Update view `resources/views/exam-participant/cards.blade.php`**
   - Desain yang lebih bagus
   - Menambahkan foto peserta (jika ada)
   - Menambahkan informasi tambahan: alamat, nomor telepon

2. **Tambahkan opsi cetak**
   - Filter per kelas/ruangan
   - Preview sebelum print

### 4.3. Daftar Nominatif

1. **Create NominalList controller/method**
   - Menampilkan daftar per kelas
   - Terstruktur dan mudah dibaca

2. **Filter dan search**
   - Filter berdasarkan mata pelajaran

3. **Export**
   - PDF nominal list
   - Excel export

### 4.4. Berita Acara

1. **Create MinutesController**
   - Menampilkan berita acara per jadwal
   - Ringkasan: total peserta, hadir, tidak hadir
   - Analisis hasil (jika ada nilai)

2. **Export PDF berita acara**
   - Format standar sekolah
   - Dilengkapi tandatangan pengawas

---

## 5. Tahapan Implementasi

### Fase 1: Setup Database & Model (1 minggu)
- [ ] Buat migration `ExamAttendance`
- [ ] Tambah kolom/status ke `exam_participants` jika perlu
- [ ] Buat model `ExamAttendance`

### Fase 2: Controller & API (2 minggu)
- [ ] Buat `AttendanceController` untuk manajemen absensi
- [ ] Buat `NominalListController` untuk daftar peserta per kelas
- [ ] Buat `MinutesController` untuk berita acara
- [ ] Perbaiki `ExamParticipantController::cards` jika perlu

### Fase 3: View & UI (2 minggu)
- [ ] Buat halaman absensi di `resources/views/attendance/`
- [ ] Perbaiki `resources/views/exam-participant/cards.blade.php`
- [ ] Buat halaman nominal list `resources/views/nominal-list/`
- [ ] Buat halaman berita acara `resources/views/minutes/`

### Fase 4: Export & PDF (1 minggu)
- [ ] Tambahkan fungsi export PDF untuk masing-masing fitur
- [ ] Gunakan Dompdf yang sudah digunakan di kodebase
- [ ] Test pada berbagai browser/device

### Fase 5: Testing & Documentation (1 minggu)
- [ ] Tulis fitur tests untuk setiap controller
- [ ] Dokumentasi penggunaan fitur untuk admin
- [ ] Training/petunjuk pengunaan

---

## 6. Catatan Penting

### 6.1. Teknologi yang Digunakan
- **Dompdf** untuk generate PDF (sudah terintegrasi)
- **Inertia.js** untuk frontend interaksi
- **Laravel** backend dengan Eloquent ORM

### 6.2. Konvensi yang Harus Dikuti
- Gunakan enum untuk status (seperti `RegistrationStatus` yang sudah ada)
-ikuti pola nama file dan namespace yang sudah ada di project
- Gunakan `php artisan make:` untuk membuat boilerplate
- Run `vendor/bin/pint --format agent` setelah menambah kode PHP

### 6.3. Dependensi
- Package `dompdf/dompdf` sudah terinstall (lihat `ExamParticipantController::cards`)
- Pastikan ` DejaVu Sans` font tersedia atau gunakan font yang ada

### 6.4. Risiko
- Perlu perhatikan performa saat generate PDF untuk jumlah besar peserta (>500 orang)
- Perlu berhati-hati dengan timezone saat mencatat waktu check-in/check-out
- Pastikan izin akses (authorization) sudah benar sebelum merender halaman

### 6.5. Prioritas Implementasi
1. **Sedang**: Daftar Hadir (absensi) - kritikal untuk pelaksanaan ujian
2. **Depan**: Kartu Peserta (perbaikan desain)
3. **Depan**: Daftar Nominatif - untuk administrasi sekolah
4. **Lahir**: Berita Acara - untuk dokumentasi dan evaluasi

---

*Rencana ini dibuat berdasarkan struktur kode yang ada di Laravel CBT application dan akan terus diperbarui sesuai kebutuhan proyek.*