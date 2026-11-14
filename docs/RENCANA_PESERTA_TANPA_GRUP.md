# Rencana Penghapusan Grup Peserta — Peserta Ujian Tanpa Grup

**Status:** Selesai diimplementasikan  
**Modul:** Pengelolaan Ujian → Peserta Ujian  
**Tanggal:** 14 November 2026  
**Dokumen terkait:** `docs/RENCANA_PESERTA_UJIAN.md` (rencana lama, menjadi tidak berlaku)

---

## 1. Tujuan

Menghapus entitas **Grup Peserta** dari modul Peserta Ujian, sehingga peserta ujian ditangani secara **flat per sekolah dan tahun pelajaran**, dengan aturan:

> **Satu siswa hanya boleh memiliki satu kredensial dalam satu tahun pelajaran pada satu sekolah.**

Dengan hilangnya grup, seluruh data peserta menjadi lebih sederhana: tidak ada lagi kode/nama grup, relasi kelas-pivot, maupun aturan "satu pasangan sesi+ruangan hanya untuk satu grup".

> Contoh alur baru:
>
> 1. Admin membuka **Peserta Ujian**, memilih sekolah dan tahun pelajaran.
> 2. Admin menekan **Tarik dari Kelas**, memilih satu atau beberapa kelas → anggota kelas menjadi peserta.
> 3. Admin menjalankan **Generate Kredensial** → username dan nomor peserta terisi sesuai template sekolah.
> 4. Admin menetapkan **ruang dan sesi** per peserta (boleh sebagian lewat seleksi massal).
> 5. Admin mencetak **Kartu Peserta** untuk seluruh peserta sekolah + tahun pelajaran.

---

## 2. Keputusan Utama

1. **Entitas Grup Peserta dihapus.** Tabel `exam_participant_groups` dan `exam_participant_group_classes` di-drop.
2. **Kredensial unik per (siswa, sekolah, tahun pelajaran).** Constraint `UNIQUE (student_id, school_id, academic_year_id)` menjadi inti aturan baru — satu siswa hanya bisa jadi peserta sekali per tahun pada satu sekolah.
3. **Template kredensial pindah ke tabel `schools`.** Kolom `username_template`, `participant_number_template`, dan `number_width` ditambahkan ke `schools`, berlaku untuk seluruh peserta sekolah tersebut.
4. **Satu halaman flat** di `/admin/exam-participants` — tidak ada lagi halaman daftar grup → detail grup.
5. **Data lama dihapus, mulai bersih.** 2 grup dan 5 peserta yang ada sekarang dibuang (masih data development).
6. **Generate kredensial dan cetak kartu berlaku untuk seluruh peserta sekolah + tahun pelajaran** (bukan per seleksi/halaman).
7. **Penambahan peserta hanya lewat "Tarik dari Kelas".** Tombol **Tambah Peserta** manual dihapus untuk membatasi beban query; `ExamParticipantModal` hanya dipakai untuk **mode edit**.
8. **Tahun pelajaran selalu otomatis** mengikuti tahun pelajaran aktif — tidak ada pemilih tahun di filter tabel maupun di dalam modal.
9. Aturan lama **"satu pasangan sesi+ruangan hanya untuk satu grup" dihapus** beserta pemeriksaan konfliknya (karena grup sudah tidak ada).
10. **Password tetap seperti semula**: acak per peserta, disimpan dua kali (`hash` + `password_plain` terenkripsi), hanya bisa diacak ulang.

---

## 3. Ruang Lingkup

### Termasuk

- Penghapusan entitas grup: tabel, model, factory, controller, request, route, dan halaman frontend.
- Struktur baru `exam_participants` dengan `academic_year_id` dan unique key baru.
- Template kredensial pada tabel `schools` + endpoint & modal pengaturannya.
- Halaman flat **Peserta Ujian** dengan filter sekolah, kelas, sesi, ruang, status, dan pencarian (tahun pelajaran mengikuti tahun aktif).
- **Tarik dari Kelas**: pilih kelas (multi) → seluruh anggota kelas menjadi peserta.
- Generate kredensial untuk **seluruh** peserta sekolah + tahun.
- Cetak kartu untuk **seluruh** peserta sekolah + tahun (opsional filter ruang/sesi/kelas).
- Penetapan ruang + sesi massal lewat seleksi baris.
- Feature test ditulis ulang untuk seluruh aturan baru.

### Belum termasuk

- Modul autentikasi siswa / client CBT.
- Export daftar peserta ke CSV atau Excel.
- Validasi kapasitas ruangan (`rooms.capacity` belum dipakai).
- Jejak audit perubahan penempatan/kredensial.
- Integrasi dengan Monitoring real-time.

---

## 4. Struktur Database

### 4.1 Tabel `schools` (ditambah)

| Kolom | Tipe | Catatan |
|---|---|---|
| `username_template` | string(120) | default `{nis}` |
| `participant_number_template` | string(120) | default `{ruang}-{no}` |
| `number_width` | unsignedTinyInteger | lebar nomor urut, default `3` |

### 4.2 Tabel `exam_participant_groups` — **DIHAPUS**

### 4.3 Tabel `exam_participant_group_classes` — **DIHAPUS**

### 4.4 Tabel `exam_participants` (dibangun ulang)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint | primary key |
| `student_id` | foreignId | relasi ke `students`, cascade delete |
| `school_id` | foreignId | relasi ke `schools`, cascade delete |
| `academic_year_id` | foreignId | relasi ke `academic_years`, cascade delete — **pengganti `group_id`** |
| `session_id` | foreignId, nullable | relasi ke `exam_sessions`, nullOnDelete |
| `room_id` | foreignId, nullable | relasi ke `rooms`, nullOnDelete |
| `username` | string(50), nullable | login peserta |
| `participant_number` | string(30), nullable | nomor peserta pada kartu |
| `password` | string, nullable | hash password |
| `password_plain` | text, nullable | password terenkripsi untuk cetak kartu |
| `created_at`, `updated_at` | timestamp | |

Constraint:

- **`UNIQUE (student_id, school_id, academic_year_id)`** — satu siswa satu kredensial per sekolah per tahun.
- `UNIQUE (school_id, academic_year_id, username)` — pengganti `UNIQUE (group_id, username)`.
- `UNIQUE (school_id, academic_year_id, participant_number)`.
- `INDEX (session_id, room_id)`, `INDEX (student_id, school_id)`, `INDEX (school_id, academic_year_id)`.

> Kolom `username`/`participant_number` nullable — SQLite dan MySQL mengizinkan banyak `NULL` pada unique index, sehingga peserta yang belum digenerate tidak saling bentrok.

---

## 5. Relasi Model

### `ExamParticipant` (baru)

- `student()` → `belongsTo(Student::class)`
- `school()` → `belongsTo(School::class)`
- `academicYear()` → `belongsTo(AcademicYear::class)`
- `session()` → `belongsTo(Session::class)`
- `room()` → `belongsTo(Room::class)`
- `hasCredential()` → `bool`
- `hasPlacement()` → `bool`

### Dihapus

- `ExamParticipantGroup` (model, factory, seluruh relasi).
- Relasi grup di `School`, `AcademicYear`, `SchoolClass`, `Session`, `Room`.
- `ExamParticipant::group()`.

### Dipertahankan

- `Student::examParticipants()`, `Student::classMembers()` (dasar filter kelas).
- `AcademicYear::active()`.

---

## 6. Aturan Bisnis & Validasi

### Penambahan Peserta

- Peserta **hanya** ditambahkan lewat **Tarik dari Kelas** (`sync`) — tidak ada tambah manual.
- Kelas yang dipilih wajib milik sekolah + tahun pelajaran yang sedang dibuka.
- Anggota kelas yang sudah menjadi peserta pada (sekolah, tahun) yang sama **tidak dibuat ulang**.
- Penempatan (sesi + ruang) mengikuti pasangan aktif yang belum terpakai, atau dikosongkan bila tidak tersedia.

### Kredensial

- `username` dan `participant_number` unik dalam `(school_id, academic_year_id)`.
- Satu siswa hanya boleh menjadi peserta sekali dalam `(school_id, academic_year_id)` — **ditolak jika sudah ada** (misalnya sudah ditarik lewat kelas lain).
- Template diambil dari `schools.username_template`, `schools.participant_number_template`, `schools.number_width`.
- Generate berlaku untuk **seluruh** peserta sekolah + tahun; peserta yang sudah lengkap tidak ditimpa kecuali `overwrite` dicentang.
- Password tetap acak, tidak bisa diedit manual.

### Placeholder Template (tidak berubah)

| Placeholder | Nilai |
|---|---|
| `{no}` | nomor urut peserta, diisi nol sesuai `number_width` |
| `{nis}` | NIS dari `student_registrations` pada sekolah peserta |
| `{nisn}` | NISN siswa |
| `{kelas}` | kode kelas peserta |
| `{ruang}` | kode ruangan |
| `{sesi}` | kode sesi |
| `{tahun}` | kode tahun pelajaran |
| `{tanggal}` | tanggal hari ini format `Ymd` |

- Hasil dibersihkan: huruf kapital, selain huruf/angka/tanda hubung dibuang.
- Placeholder kosong → digantikan `{no}`.
- `{no}` mengikuti urutan nama peserta (A-Z) agar stabil.
- Bentrok di dalam lingkup unik → diberi akhiran `-2`, `-3`, dst.

### Penempatan Ruang & Sesi

- Ruang dan sesi wajib berasal dari sekolah peserta.
- Boleh berbeda antar peserta dalam satu sekolah + tahun (tidak ada aturan "satu grup satu ruang" lagi).
- Aksi massal (**Atur Ruangan & Sesi**) hanya memengaruhi baris yang dipilih.

### Hak Akses (tidak berubah)

| Peran | Akses |
|---|---|
| Administrator | Semua sekolah |
| School Admin | Sekolah yang dapat diakses |
| Headmaster | Hanya melihat |
| Guru | `403` |

---

## 7. Rute

```text
GET     /admin/exam-participants                      index
PUT     /admin/exam-participants/{participant}        update
DELETE  /admin/exam-participants/{participant}        destroy

POST    /admin/exam-participants/sync                 tarik dari kelas
POST    /admin/exam-participants/generate             generate kredensial
PATCH   /admin/exam-participants/placement            atur ruang+sesi massal
PATCH   /admin/exam-participants/{participant}/password
GET     /admin/exam-participants/cards                cetak kartu
PATCH   /admin/schools/{school}/credential-template   ubah template kredensial
```

> Endpoint `POST /admin/exam-participants` (tambah manual) **tidak dibuat** — penambahan peserta hanya lewat `sync`.

Nama route:

```text
admin.exam-participants.index
admin.exam-participants.update
admin.exam-participants.destroy
admin.exam-participants.sync
admin.exam-participants.generate
admin.exam-participants.bulk-placement
admin.exam-participants.reset-password
admin.exam-participants.cards
admin.schools.credential-template
```

> Route lama `admin.exam-participant-groups.*` **dihapus total** — URL `/admin/exam-participant-groups/{id}` akan `404` (tanpa redirect, karena data lama juga dihapus).

---

## 8. Tampilan Frontend

### 8.1 Sidebar

`resources/js/Components/Layout/Sidebar.jsx` — href menu **Peserta Ujian** diubah dari `admin.exam-participant-groups.index` menjadi `admin.exam-participants.index`.

### 8.2 Halaman Peserta Ujian (satu halaman flat)

Komponen: `resources/js/Pages/ExamParticipants/Index.jsx`

Isi:

- Breadcrumb: `Sistem Evaluasi Terpadu / Pengelolaan Ujian / Peserta Ujian`.
- Filter: pencarian (nama, NIS, NISN, username, nomor peserta), sekolah, kelas, sesi, ruang, status. Tahun pelajaran selalu tahun aktif.
- Kartu statistik: total peserta, kredensial lengkap, belum penempatan.
- Banner peringatan bila ada peserta tanpa ruang/sesi.
- Toolbar aksi: **Tarik dari Kelas**, **Generate Kredensial**, **Cetak Kartu**, **Atur Template Kredensial**.
- Baris seleksi massal: **Atur Ruangan & Sesi** (muncul bila ada baris dipilih).
- Tabel peserta (reuse `ExamParticipantTable`): nomor, nama + NIS/NISN, nomor peserta, username, password (tampil/acak ulang), ruang/sesi, status kredensial, aksi edit/hapus.
- Pagination.

### 8.3 Komponen

| Komponen | Status |
|---|---|
| `ExamParticipantTable.jsx` | Dipertahankan — tambah baris kelas pada kolom nama |
| `ExamParticipantModal.jsx` | Disesuaikan — **hanya mode edit** (hapus mode create & `availableStudents`) |
| `BulkPlacementModal.jsx` | Disesuaikan — buang prop `group`, kirim `school_id` + `academic_year_id` |
| `CredentialTemplateModal.jsx` | Disesuaikan — jadi modal **Generate Kredensial** (pilih sekolah + tahun, template dibaca dari sekolah) |
| `SchoolCredentialTemplateModal.jsx` | **Baru** — ubah 3 kolom template sekolah |
| `SyncFromClassModal.jsx` | **Baru** — pilih sekolah, tahun, dan kelas (multi) lalu tarik |
| `PrintCardsModal.jsx` | **Baru** — pilih sekolah, tahun, opsional kelas/sesi/ruang lalu cetak |
| `ExamParticipants/Show.jsx` | **Dihapus** |
| `ExamParticipantGroupTable.jsx` | **Dihapus** |
| `ExamParticipantGroupModal.jsx` | **Dihapus** |
| `BatchAssignmentModal.jsx` | **Dihapus** |

### 8.4 Kartu Peserta

`resources/views/exam-participant/cards.blade.php` — payload `group` diganti `school` + `academic_year` + `classes`; isi kartu menampilkan nama sekolah, tahun pelajaran, kelas, sesi, ruang, dan data kredensial peserta. A4 landscape, grid 3 kolom.

---

## 9. Backend

### Controller

- **HAPUS** `app/Http/Controllers/Admin/ExamParticipantGroupController.php`.
- **Tulis ulang** `app/Http/Controllers/Admin/ExamParticipantController.php`:
  - `index` — daftar flat + filter + statistik + props (sekolah, tahun, kelas, sesi, ruang, template sekolah)
  - `sync` — tarik anggota kelas terpilih
  - `generate` — generate seluruh peserta sekolah + tahun
  - `bulkPlacement` — ruang + sesi untuk baris terpilih
  - `update`, `destroy`, `resetPassword`
  - `cards` — cetak PDF seluruh peserta sekolah + tahun
  - `updateCredentialTemplate` — ubah template sekolah

### Form Request

| Status | File |
|---|---|
| Hapus | `StoreExamParticipantGroupRequest`, `UpdateExamParticipantGroupRequest`, `GenerateParticipantCredentialsRequest` (lama), `Concerns/ValidatesExamParticipantGroup.php`, `StoreExamParticipantRequest` |
| Tulis ulang | `UpdateExamParticipantRequest` (validasi kredensial unik per sekolah+tahun + kecocokan sekolah ruang/sesi) |
| Baru | `SyncParticipantsRequest` (`school_id`, `academic_year_id`, `class_ids[]` wajib milik sekolah + tahun) |
| Baru | `GenerateCredentialsRequest` (`school_id`, `academic_year_id`, `overwrite`) |
| Baru | `UpdateSchoolCredentialTemplateRequest` (3 kolom template + aturan placeholder) |

### Trait

- `Concerns/AuthorizesExamParticipantAccess.php` — ganti `authorizeParticipantGroupAccess()` → `authorizeParticipantSchoolAccess()` (sudah tersedia) + `authorizeParticipantAccess(user, participant)` berbasis `participant.school_id`.
- `Concerns/PresentsExamParticipant.php` — hapus `presentExamParticipantGroup()`; `presentExamParticipant()` tanpa `group_id`, tambah `academic_year_id` dan `class_name`.

### Service

`app/Services/ExamParticipantCredentialService.php` — seluruh method berpindah basis **sekolah + tahun**:

- `syncParticipantsFromClasses(School, AcademicYear, array $classIds, ?array $placement): int`
- `currentPlacement(int $schoolId, int $academicYearId): array`
- `resolvePlacement(int $schoolId, int $academicYearId): array`
- `generateCredentials(School, AcademicYear, Collection, bool $overwrite): int`
- `moveParticipants(Collection $ids, int $roomId, int $sessionId): int` (pengganti `moveGroup`)
- `makeUnique(...)` — satu query `pluck` per pemanggilan (sebelumnya dua query per peserta)
- `generatePassword()`, `participantsWithRelations(...)`

### Pola implementasi

- `DB::transaction` untuk operasi tulis.
- Eager loading untuk menghindari N+1.
- Pesan validasi Bahasa Indonesia.
- Cetak kartu memakai Dompdf mengikuti pola `QuestionController::print`.

---

## 10. Factory & Seeder

- **Hapus** `database/factories/ExamParticipantGroupFactory.php`.
- **Sesuaikan** `database/factories/ExamParticipantFactory.php` — buang `forGroup()`, tambah state `forSchoolYear($school, $academicYear)`.
- **Tulis ulang** `database/seeders/ExamParticipantSeeder.php` — tanpa grup: pilih 1–2 kelas beranggota → sync → generate; idempotent.

---

## 11. Pengujian

**Hapus** `tests/Feature/Admin/ExamParticipantGroupControllerTest.php` (29 test).
**Tulis ulang** `tests/Feature/Admin/ExamParticipantControllerTest.php`:

### Indeks & Filter

- Index menampilkan peserta sesuai akses sekolah.
- Filter sekolah, kelas, sesi, ruang, status bekerja (tahun pelajaran mengikuti tahun aktif).
- Statistik sesuai filter.
- Guru mendapat `403`; kepala sekolah hanya melihat.

### Penambahan Peserta (Sync)

- Tarik anggota kelas terpilih menjadi peserta.
- Kelas dari sekolah lain ditolak.
- Siswa yang sudah menjadi peserta pada (sekolah, tahun) yang sama **ditolak** ← aturan inti.
- Tarik ulang tidak menduplikasi.

### Kredensial

- Generate memakai template dari `schools`.
- Placeholder `{no}`, `{nis}`, `{nisn}`, `{kelas}`, `{ruang}`, `{sesi}`, `{tahun}`, `{tanggal}` terisi benar.
- Template tanpa placeholder / placeholder tidak dikenal ditolak.
- Generate tidak menimpa kredensial yang ada kecuali `overwrite`.
- Username & nomor bentrok diberi akhiran.
- Username/nomor unik lintas kelas pada tahun sama ditolak.
- Password disimpan hash + `password_plain` terenkripsi; acak ulang menulis kedua kolom.

### Penempatan & Cetak

- Atur ruang+sesi massal hanya memengaruhi baris terpilih.
- Ruang/sesi dari sekolah lain ditolak.
- Kartu peserta menghasilkan PDF `200`.
- Template sekolah dapat diubah oleh administrator/school admin.

---

## 12. Urutan Implementasi

1. Migration: tambah kolom template ke `schools`.
2. Migration: drop 3 tabel + bangun ulang `exam_participants`.
3. Model & relasi (hapus `ExamParticipantGroup`, sesuaikan 5 model + `ExamParticipant` + `School`).
4. Service kredensial (rewrite total).
5. Form request + trait otorisasi/presenter.
6. Controller `ExamParticipantController` + hapus `ExamParticipantGroupController`.
7. Rute baru, hapus rute grup.
8. Factory & seeder.
9. Frontend: halaman flat, modal baru, hapus 4 file, sesuaikan sidebar.
10. Blade kartu peserta.
11. Feature test (hapus 1 kelas test lama, tulis ulang yang baru).
12. Jalankan Pint, seluruh test suite, dan `npm run build`.

---

## 13. Kriteria Selesai

- `/admin/exam-participants` menampilkan daftar peserta flat dengan filter lengkap.
- **Tarik dari Kelas** menambahkan peserta dari kelas terpilih.
- Satu siswa **ditolak** menjadi peserta dua kali pada sekolah + tahun yang sama.
- Generate kredensial memakai template sekolah dan berlaku untuk seluruh peserta sekolah + tahun.
- Atur ruang + sesi massal bekerja untuk baris yang dipilih.
- Cetak kartu menghasilkan PDF untuk seluruh peserta sekolah + tahun.
- Tabel `exam_participant_groups` dan `exam_participant_group_classes` tidak ada lagi.
- Tidak ada referensi tersisa ke `ExamParticipantGroup` (grep bersih di `app/`, `routes/`, `resources/`, `database/`, `tests/`).
- Seluruh feature test baru lulus dan seluruh test suite tetap lulus.
- `npm run build` berhasil dan Pint dijalankan pada semua file PHP yang diubah.

---

## 14. Catatan Implementasi

Perubahan kecil terhadap rencana di atas, dibuat saat implementasi:

1. **Endpoint tambah manual dihapus.** `POST /admin/exam-participants` dan `StoreExamParticipantRequest` tidak dibuat sama sekali — penambahan peserta hanya lewat `sync`.
2. **Komponen `PrintCardsModal.jsx` ditambahkan.** Cetak kartu membutuhkan sekolah + tahun pelajaran yang tegas, sehingga tombol *Cetak Kartu* membuka modal pemilih sekolah, tahun, dan filter opsional (kelas/sesi/ruang) alih-alih memakai filter halaman secara langsung.
3. **Seleksi massal lintas sekolah ditolak di sisi frontend.** Bila baris yang dipilih berasal dari lebih dari satu sekolah, tombol *Atur Ruangan & Sesi* dinonaktifkan karena `bulk-placement` hanya menerima satu `school_id`.
4. **`class_members` ternyata sudah unik pada `(student_id, academic_year_id)`**, sehingga satu siswa hanya punya satu kelas per tahun. Uji aturan "tidak boleh dua kali" dibangun dengan membuat peserta lebih dulu lalu menjalankan `sync` kelas yang sama.
5. **Preview template** memakai kode ruang/sesi/tahun dari peserta yang sedang dimuat; placeholder yang nilainya kosong jatuh ke `{no}`.
6. **Tahun pelajaran dihapus dari UI.** `index` mengisi `academic_year_id` dengan `AcademicYear::active()` bila tidak dikirim, pemilih tahun dihapus dari filter tabel serta dari modal *Tarik dari Kelas*, *Generate*, dan *Cetak Kartu*, dan prop `academicYears` tidak lagi dikirim ke halaman. Bila tidak ada tahun aktif, halaman menampilkan peringatan dan menonaktifkan tombol yang membutuhkannya.
7. **`useForm` Inertia v3 tidak menerima data sebagai argumen.** Signature-nya `post(url, options?)` — payload selalu diambil dari state `useForm`. Pemanggulan `post(url, data, options)` membuat `data` diabaikan sebagai options dan argumen ketiga tidak dibaca, sehingga `class_ids` terkirim kosong (bug "Pilih minimal satu kelas"). Semua modal kini menaruh seluruh field ke state lalu memanggil `post/put/patch(url, options)`.

## 15. Hasil Verifikasi

| Perintah | Hasil |
|---|---|
| `php artisan test --compact` | **399 test lulus** (32 di antaranya `ExamParticipantControllerTest`) |
| `vendor/bin/pint --format agent` | Lolos |
| `npm run build` | Berhasil |
| `php artisan route:list --path=exam-participant` | 8 route flat, tanpa route grup |
| `grep ExamParticipantGroup` pada `app/`, `routes/`, `resources/`, `database/seeders`, `database/factories`, `tests/` | Bersih (sisa referensi hanya di dokumen dan migrasi historis) |
