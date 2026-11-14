# Rencana Modul Paket & Jadwal Ujian

**Status:** Rencana sebelum implementasi  
**Modul:** Pengelolaan Ujian → Paket Ujian  
**Tanggal:** 11 November 2026

---

## 1. Tujuan

Membangun modul **Paket Ujian** yang menghubungkan data ujian dengan sekolah, tahun pelajaran, jenis ujian, kelas, mata pelajaran, sesi, dan ruangan.

Satu **Paket Ujian** merupakan satu event ujian, misalnya:

> PTS Ganjil 2026/2027

Di dalam paket tersebut terdapat banyak **Jadwal Ujian**, misalnya:

| Kelas | Mata Pelajaran | Tanggal | Sesi | Ruangan |
|---|---|---|---|---|
| X IPA 1 | Matematika | 2026-09-14 | Sesi 1 | R-01 |
| X IPA 1 | Bahasa Indonesia | 2026-09-14 | Sesi 2 | R-01 |
| X IPA 2 | Matematika | 2026-09-14 | Sesi 1 | R-02 |

Dengan pemisahan ini, satu paket tidak perlu dibuat ulang untuk setiap kelas atau mata pelajaran.

---

## 2. Keputusan Utama

1. Satu paket ujian dapat memiliki banyak jadwal.
2. Setiap jadwal mewakili kombinasi **kelas + mata pelajaran + tanggal + sesi + ruangan**.
3. Status paket menggunakan tiga nilai: `draft`, `aktif`, dan `selesai`.
4. Sekolah tidak dapat diubah ketika paket sedang diedit, mengikuti pola modul ujian yang sudah ada.
5. Guru pengawas belum diisi manual pada tahap ini. Guru pengampu dapat ditampilkan dari data `subject_teachers`.
6. **Jenis Penilaian** belum dikaitkan ke jadwal. Relasi tersebut akan dipakai saat modul penilaian dikerjakan.
7. Peserta ujian belum disimpan ke tabel tersendiri. Jumlah peserta dihitung dari `class_members` pada tahun pelajaran paket.

---

## 3. Ruang Lingkup

### Termasuk

- CRUD Paket Ujian.
- CRUD Jadwal Ujian di dalam paket.
- Filter dan statistik pada halaman index.
- Pembatasan akses berdasarkan sekolah.
- Validasi konsistensi sekolah antar-relasi.
- Pencegahan bentrok ruangan dan kelas.
- Feature test untuk seluruh aturan utama.

### Belum termasuk

- Bank soal.
- Penugasan peserta ke ruangan.
- Penilaian dan perhitungan nilai.
- Laporan hasil ujian.
- Monitoring ujian real-time.
- Guru pengawas manual per jadwal.
- Penghubungan Jenis Penilaian ke jadwal.

---

## 4. Struktur Database

### 4.1 Tabel `exam_packages`

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint | primary key |
| `school_id` | foreignId | relasi ke `schools`, cascade delete |
| `academic_year_id` | foreignId | relasi ke `academic_years` |
| `exam_type_id` | foreignId | relasi ke `exam_types` |
| `code` | string(30) | unik per sekolah |
| `name` | string(150) | nama paket |
| `description` | string(500), nullable | deskripsi opsional |
| `start_date` | date | awal periode ujian |
| `end_date` | date | akhir periode ujian |
| `status` | string(20) | `draft`, `aktif`, `selesai` |
| `created_at`, `updated_at` | timestamp | |

Constraint:

- Unique `(school_id, code)`.
- `start_date` tidak boleh setelah `end_date`.

### 4.2 Tabel `exam_schedules`

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint | primary key |
| `exam_package_id` | foreignId | relasi ke `exam_packages`, cascade delete |
| `class_id` | foreignId | relasi ke `classes` |
| `subject_id` | foreignId | relasi ke `subjects` |
| `session_id` | foreignId | relasi ke `exam_sessions` |
| `room_id` | foreignId | relasi ke `rooms` |
| `exam_date` | date | tanggal pelaksanaan |
| `duration_minutes` | unsignedSmallInteger | durasi ujian dalam menit |
| `open_time` | time | jam soal dibuka |
| `close_time` | time | jam soal ditutup |
| `shuffle_questions` | boolean, default `true` | soal diacak |
| `shuffle_options` | boolean, default `true` | pilihan jawaban diacak |
| `show_score_to_student` | boolean, default `true` | nilai ditampilkan ke siswa |
| `status` | varchar(20), default `aktif` | `aktif` atau `nonaktif` |
| `created_at`, `updated_at` | timestamp | |

Constraint:

- Unique `(exam_package_id, class_id, subject_id)`.
- Index untuk `exam_date`, `room_id`, dan `session_id`.

---

## 5. Relasi Model

### `ExamPackage`

- `school()` → `belongsTo(School::class)`
- `academicYear()` → `belongsTo(AcademicYear::class)`
- `examType()` → `belongsTo(ExamType::class)`
- `schedules()` → `hasMany(ExamSchedule::class)`

### `ExamSchedule`

- `examPackage()` → `belongsTo(ExamPackage::class)`
- `schoolClass()` → `belongsTo(SchoolClass::class, 'class_id')`
- `subject()` → `belongsTo(Subject::class)`
- `session()` → `belongsTo(Session::class)`
- `room()` → `belongsTo(Room::class)`

### Model terkait

Tambahkan relasi inverse:

- `School::examPackages()`
- `School::examSchedules()` bila diperlukan
- `AcademicYear::examPackages()`
- `ExamType::examPackages()`
- `Session::examSchedules()`
- `Room::examSchedules()`
- `SchoolClass::examSchedules()`
- `Subject::examSchedules()`

---

## 6. Aturan Bisnis & Validasi

### Paket Ujian

- `school_id` wajib dan harus berada dalam akses pengguna.
- `academic_year_id` wajib.
- `exam_type_id` wajib dan harus milik sekolah yang sama.
- `code` wajib, maksimal 30 karakter, unik per sekolah.
- `name` wajib, maksimal 150 karakter.
- `start_date` dan `end_date` wajib.
- `end_date` tidak boleh lebih awal dari `start_date`.
- Status hanya boleh `draft`, `aktif`, atau `selesai`.
- Sekolah tidak boleh diubah ketika edit.
- Paket hanya dapat dihapus jika belum memiliki jadwal, atau penghapusan ikut menghapus jadwal dengan konfirmasi yang jelas.

### Jadwal Ujian

- `class_id` wajib dan harus milik sekolah paket.
- `subject_id` wajib.
- `session_id` wajib dan harus milik sekolah paket.
- `room_id` wajib dan harus milik sekolah paket.
- `exam_date` wajib dan harus berada di antara `start_date` dan `end_date` paket.
- `duration_minutes` wajib, minimal 1.
- `open_time` wajib dan `close_time` wajib, harus setelah `open_time`.
- `shuffle_questions`, `shuffle_options`, `show_score_to_student` wajib berupa boolean.
- `subject_id` harus memiliki bank soal dengan sekolah, tahun pelajaran, dan jenis ujian paket **yang juga ditetapkan ke `class_id`**.
- Status tidak diisi lewat form; default `aktif` dan hanya bisa diubah lewat endpoint toggle di tabel.
- Kombinasi kelas + mapel dalam satu paket tidak boleh duplikat.
- Ruangan yang sama tidak boleh dipakai pada tanggal dan sesi yang sama.
- Kelas yang sama tidak boleh memiliki dua jadwal pada tanggal dan sesi yang sama.
- Jadwal tidak dapat ditambahkan jika paket berstatus `selesai`.

---

## 7. Hak Akses

| Peran | Akses |
|---|---|
| Administrator | Melihat dan mengelola semua sekolah |
| School Admin | Melihat dan mengelola sekolah yang dapat diakses |
| Headmaster | Hanya melihat data sekolahnya |
| Guru | `403` |

Aturan tambahan:

- Daftar sekolah, tahun pelajaran, jenis ujian, kelas, sesi, dan ruangan difilter berdasarkan akses pengguna.
- Aksi create/update/delete memeriksa akses ke sekolah paket.
- Headmaster tidak melihat tombol tambah, edit, atau hapus.

---

## 8. Rute

```text
GET     /admin/exam-packages
POST    /admin/exam-packages
GET     /admin/exam-packages/{examPackage}
PUT     /admin/exam-packages/{examPackage}
DELETE  /admin/exam-packages/{examPackage}

POST    /admin/exam-packages/{examPackage}/schedules
PUT     /admin/exam-packages/{examPackage}/schedules/{examSchedule}
DELETE  /admin/exam-packages/{examPackage}/schedules/{examSchedule}
PATCH   /admin/exam-packages/{examPackage}/schedules/{examSchedule}/status
```

Nama route:

```text
admin.exam-packages.index
admin.exam-packages.store
admin.exam-packages.show
admin.exam-packages.update
admin.exam-packages.destroy

admin.exam-schedules.store
admin.exam-schedules.update
admin.exam-schedules.destroy
admin.exam-schedules.toggle-status
```

---

## 9. Tampilan Frontend

### 9.1 Sidebar

Tambahkan menu **Paket Ujian** di dalam **Pengelolaan Ujian**.

Urutan yang disarankan:

1. Jenis Ujian
2. Paket Ujian
3. Sesi
4. Ruangan
5. Jenis Penilaian

Menu disembunyikan jika pengguna tidak memiliki akses sekolah.

### 9.2 Halaman Index Paket Ujian

Komponen: `resources/js/Pages/ExamPackages/Index.jsx`

Isi:

- Breadcrumb: `Pengelolaan Ujian / Paket Ujian`.
- Filter:
  - pencarian nama atau kode;
  - sekolah;
  - tahun pelajaran;
  - jenis ujian;
  - status.
- Kartu statistik:
  - total paket;
  - draft;
  - aktif;
  - selesai.
- Tabel:
  - nama dan kode paket;
  - sekolah;
  - tahun pelajaran;
  - jenis ujian;
  - periode;
  - jumlah jadwal;
  - status;
  - aksi.
- Modal tambah/edit paket.
- Konfirmasi hapus.
- Toast.

### 9.3 Halaman Detail Paket

Komponen: `resources/js/Pages/ExamPackages/Show.jsx`

Isi:

- Ringkasan paket:
  - sekolah;
  - tahun pelajaran;
  - jenis ujian;
  - periode;
  - status;
  - jumlah jadwal.
- Tabel jadwal:
  - tanggal;
  - sesi dan waktu;
  - kelas;
  - mata pelajaran;
  - ruangan;
  - durasi;
  - jumlah soal;
  - nilai maksimal;
  - aksi.
- Modal tambah/edit jadwal.
- Konfirmasi hapus jadwal.
- Tombol kembali ke daftar paket.

### 9.4 Komponen

- `ExamPackageModal.jsx`
- `ExamScheduleModal.jsx`
- `ExamPackageTable.jsx` bila tabel cukup kompleks.

---

## 10. Backend

### Controller

- `app/Http/Controllers/Admin/ExamPackageController.php`
  - `index`
  - `store`
  - `show`
  - `update`
  - `destroy`

- `app/Http/Controllers/Admin/ExamScheduleController.php`
  - `store`
  - `update`
  - `destroy`

### Form Request

- `StoreExamPackageRequest`
- `UpdateExamPackageRequest`
- `StoreExamScheduleRequest`
- `UpdateExamScheduleRequest`

### Pola implementasi

- Gunakan `DB::transaction` untuk operasi tulis.
- Gunakan eager loading untuk menghindari N+1.
- Gunakan `presentExamPackage()` dan `presentExamSchedule()` untuk format data frontend.
- Gunakan pesan validasi Bahasa Indonesia.
- Gunakan `Rule::unique(...)->where('school_id', ...)` untuk kode paket.
- Validasi lintas tabel dilakukan di `after()` atau rule khusus.

---

## 11. Factory & Seeder

### Factory

- `ExamPackageFactory`
- `ExamScheduleFactory`

Factory harus memastikan relasi sekolah antar-model konsisten.

### Seeder

Tambahkan seeder contoh:

- satu paket PTS untuk sekolah contoh;
- beberapa jadwal untuk kelas dan mata pelajaran yang tersedia.

Seeder harus idempotent dan tidak membuat data duplikat.

---

## 12. Pengujian

### Paket Ujian

- Index menampilkan paket sesuai akses sekolah.
- Filter sekolah, tahun pelajaran, jenis ujian, dan status bekerja.
- Statistik sesuai filter.
- Administrator dapat membuat paket.
- School admin dapat membuat paket hanya di sekolahnya.
- School admin tidak dapat membuat paket di sekolah lain.
- Headmaster tidak dapat membuat, mengubah, atau menghapus.
- Guru mendapat `403`.
- Kode paket unik per sekolah.
- Kode yang sama boleh dipakai di sekolah berbeda.
- Sekolah tidak berubah saat edit.
- Validasi periode tanggal.
- Hapus paket beserta jadwalnya.

### Jadwal Ujian

- Jadwal dapat ditambahkan ke paket.
- Kelas, sesi, dan ruangan harus berasal dari sekolah paket.
- Tanggal harus berada dalam periode paket.
- Kombinasi kelas + mapel unik dalam satu paket.
- Bentrok ruangan pada tanggal dan sesi yang sama ditolak.
- Bentrok kelas pada tanggal dan sesi yang sama ditolak.
- Jadwal ditolak jika paket berstatus `selesai`.
- School admin tidak dapat mengelola jadwal paket sekolah lain.
- Headmaster tidak dapat mengubah jadwal.
- Guru mendapat `403`.

---

## 13. Urutan Implementasi

1. Migration `exam_packages`.
2. Migration `exam_schedules`.
3. Model, relasi, factory, dan seeder.
4. Form request dan aturan validasi.
5. Controller paket ujian.
6. Controller jadwal ujian.
7. Rute.
8. Halaman index paket ujian.
9. Halaman detail dan modal jadwal.
10. Sidebar dan breadcrumb.
11. Feature test.
12. Jalankan Pint, seluruh test, dan `npm run build`.

---

## 14. Kriteria Selesai

- Modul dapat diakses dari sidebar **Pengelolaan Ujian → Paket Ujian**.
- Paket dan jadwal dapat dikelola sesuai hak akses.
- Semua validasi sekolah, periode, dan bentrok berjalan.
- Tidak ada N+1 pada halaman index dan detail.
- Seluruh feature test baru lulus.
- Seluruh test suite tetap lulus.
- `npm run build` berhasil.
- Pint dijalankan pada semua file PHP yang diubah.
