# Rencana Modul Peserta Ujian

**Status:** Selesai diimplementasikan  
**Modul:** Pengelolaan Ujian → Peserta Ujian  
**Tanggal:** 14 November 2026

---

## 1. Tujuan

Membangun modul **Peserta Ujian** yang menjadi satu-satunya tempat penentuan identitas peserta ujian: **ruang**, **sesi**, **username**, **nomor peserta**, dan **password**.

Modul ini berdiri sendiri di luar modul Paket Ujian.

> Contoh alur:
>
> 1. Admin membuat grup peserta dengan memilih sekolah dan satu atau beberapa kelas.
> 2. Tahun pelajaran, sesi, dan ruangan ditetapkan otomatis oleh sistem.
> 3. Semua anggota kelas terpilih otomatis menjadi peserta grup tersebut.
> 4. Admin menjalankan **Generate Kredensial** → username dan nomor peserta terisi sesuai template.
> 5. Admin mencetak **Kartu Peserta** untuk dibagikan ke siswa.

---

## 2. Keputusan Utama

1. Entitas utama modul adalah **Grup Peserta**. Satu grup dapat memuat **lebih dari satu kelas** melalui tabel pivot `exam_participant_group_classes`.
2. **Tanggal ujian tidak dicantumkan.** Waktu pelaksanaan mengikuti jadwal paket ujian.
3. **Tahun pelajaran otomatis** mengikuti tahun pelajaran aktif (`academic_years.is_active`).
4. **Sesi dan ruangan otomatis** diambil dari sesi dan ruangan aktif sekolah yang belum dipakai grup lain. Bila seluruh pasangan sudah terpakai, sesi dan ruangan dikosongkan dan wajib ditetapkan manual.
5. **Pengaturan sesi dan ruangan dipindahkan ke halaman detail grup** (`/admin/exam-participant-groups/{id}`) melalui aksi **Atur Ruangan & Sesi**.
6. Peserta grup diambil otomatis dari `class_members` seluruh kelas terpilih, dan dapat ditambah atau dikurangi manual oleh admin.
7. `username` dan `nomor peserta` **dibangkitkan otomatis dari template** yang dapat diatur admin per grup.
8. `password` dibangkitkan **acak per peserta** dan tidak dapat diubah manual, hanya diacak ulang.
9. Password disimpan dua kali: `password` (hash, untuk autentikasi siswa di kemudian hari) dan `password_plain` (terenkripsi, untuk mencetak ulang kartu peserta).
10. Modul menyediakan **Kartu Peserta** siap cetak dalam format PDF.

---

## 3. Ruang Lingkup

### Termasuk

- CRUD Grup Peserta: sekolah, kelas (multi), nama, kode, template kredensial.
- Penetapan otomatis tahun pelajaran aktif, sesi aktif, dan ruangan aktif.
- Tarik peserta otomatis dari seluruh anggota kelas terpilih, tambah peserta manual, keluarkan peserta.
- Pembangkitan massal username, nomor peserta, dan password berdasarkan template.
- Pengubahan ruang dan sesi untuk seluruh batch peserta.
- Tampilan, tampilkan, dan acak ulang password per peserta.
- Cetak Kartu Peserta dalam PDF.
- Filter, statistik, dan pagination.
- Pembatasan akses berdasarkan sekolah.
- Feature test untuk seluruh aturan utama.

### Belum termasuk

- Modul autentikasi siswa / client CBT.
- Export daftar peserta ke CSV atau Excel.
- Integrasi dengan Monitoring real-time.
- Penilaian dan perhitungan nilai.
- Laporan hasil ujian.

---

## 4. Struktur Database

### 4.1 Tabel `exam_participant_groups`

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint | primary key |
| `school_id` | foreignId | relasi ke `schools`, cascade delete |
| `academic_year_id` | foreignId | relasi ke `academic_years`, cascade delete |
| `session_id` | foreignId | relasi ke `exam_sessions`, **nullable** |
| `room_id` | foreignId | relasi ke `rooms`, **nullable** |
| `code` | string(30) | kode grup, unik per sekolah |
| `name` | string(150) | nama grup |
| `username_template` | string(120) | template username, default `{nis}` |
| `participant_number_template` | string(120) | template nomor peserta, default `{ruang}-{no}` |
| `number_width` | unsignedTinyInteger | lebar nomor urut, default `2` |
| `created_at`, `updated_at` | timestamp | |

Constraint:

- Unique `(school_id, code)`.
- Unique `(school_id, academic_year_id, session_id, room_id)` — satu pasangan sesi dan ruangan hanya boleh dipakai satu grup. Nilai `NULL` pada sesi atau ruangan tetap dapat diulang.

> Kolom `exam_date` dan `class_id` tidak lagi dipakai. Keduanya dihapus lewat migrasi `add_multiple_classes_to_exam_participant_groups_table`, beserta pemindahan data `class_id` ke tabel pivot.

### 4.2 Tabel `exam_participant_group_classes`

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint | primary key |
| `exam_participant_group_id` | foreignId | relasi ke `exam_participant_groups`, cascade delete |
| `class_id` | foreignId | relasi ke `classes`, cascade delete |
| `created_at`, `updated_at` | timestamp | |

Constraint:

- Unique `(exam_participant_group_id, class_id)`.

### 4.3 Tabel `exam_participants`

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint | primary key |
| `group_id` | foreignId | relasi ke `exam_participant_groups`, cascade delete |
| `student_id` | foreignId | relasi ke `students`, cascade delete |
| `school_id` | foreignId | relasi ke `schools`, cascade delete |
| `username` | string(50), nullable | login peserta |
| `participant_number` | string(30), nullable | nomor peserta pada kartu |
| `password` | string, nullable | hash password, dipakai autentikasi siswa |
| `password_plain` | text, nullable | password terenkripsi, dipakai cetak kartu |
| `created_at`, `updated_at` | timestamp | |

Constraint:

- Unique `(group_id, username)`.
- Unique `(group_id, participant_number)`.
- Index untuk `student_id` dan `school_id`.

> **Catatan kenapa ada dua kolom password.** Kartu peserta harus bisa dicetak ulang kapan saja, sedangkan password untuk login harus disimpan sebagai hash. Karena password hanya bisa dibangkitkan ulang (bukan diedit manual), kedua kolom selalu ditulis bersamaan sehingga tidak mungkin melenceng.

---

## 5. Relasi Model

### `ExamParticipantGroup`

- `school()` → `belongsTo(School::class)`
- `academicYear()` → `belongsTo(AcademicYear::class)`
- `classes()` → `belongsToMany(SchoolClass::class, 'exam_participant_group_classes')`
- `session()` → `belongsTo(Session::class)`
- `room()` → `belongsTo(Room::class)`
- `participants()` → `hasMany(ExamParticipant::class)`
- `hasPlacement()` → `bool`, apakah sesi dan ruangan sudah ditetapkan

### `ExamParticipant`

- `group()` → `belongsTo(ExamParticipantGroup::class)`
- `student()` → `belongsTo(Student::class)`
- `hasCredential()` → `bool`, apakah username dan nomor peserta sudah terisi

### Model terkait

- `School::examParticipantGroups()`
- `AcademicYear::examParticipantGroups()`
- `SchoolClass::examParticipantGroups()`
- `Session::examParticipantGroups()`
- `Room::examParticipantGroups()`
- `Student::examParticipants()`
- `AcademicYear::active()` → `?self`, tahun pelajaran aktif

---

## 6. Aturan Bisnis & Validasi

### Grup Peserta

- `school_id` wajib dan harus berada dalam akses pengguna.
- `class_ids` wajib, minimal satu kelas, dan setiap kelas harus berbeda.
- Kelas yang dipilih harus berasal dari sekolah tersebut dan dari tahun pelajaran aktif.
- `code` wajib, maksimal 30 karakter, unik per sekolah.
- `name` wajib, maksimal 150 karakter.
- `username_template` dan `participant_number_template` wajib, maksimal 120 karakter, dan hanya boleh memuat placeholder yang dikenal.
- `number_width` minimal 1 dan maksimal 6.
- Tahun pelajaran tidak dapat diubah dan selalu mengikuti tahun pelajaran aktif saat grup dibuat.
- Sekolah tidak dapat diubah setelah grup dibuat.
- Menghapus grup ikut menghapus seluruh peserta dan relasi kelas di dalamnya.

### Penetapan Otomatis

- Tahun pelajaran grup = `academic_years` yang berstatus aktif.
- Sesi = sesi aktif sekolah yang belum dipakai, diurutkan dari waktu mulai paling awal.
- Ruangan = ruangan aktif sekolah yang belum dipakai pada sesi tersebut, diurutkan dari nama.
- Bila tidak ada pasangan yang tersedia, `session_id` dan `room_id` dikosongkan dan grup menampilkan peringatan pada halaman detail.

### Perpindahan Batch

- Perpindahan hanya dapat dilakukan melalui aksi **Atur Ruangan & Sesi** pada halaman detail grup.
- Ruangan dan sesi tujuan harus milik sekolah grup.
- Pasangan sesi dan ruangan tujuan tidak boleh dipakai grup lain pada sekolah dan tahun pelajaran yang sama.

### Placeholder Template

| Placeholder | Nilai |
|---|---|
| `{no}` | nomor urut peserta dalam grup, diisi nol sesuai `number_width` |
| `{nis}` | NIS dari `student_registrations` pada sekolah grup |
| `{nisn}` | NISN siswa |
| `{kelas}` | kode kelas peserta |
| `{ruang}` | kode ruangan |
| `{sesi}` | kode sesi |
| `{tahun}` | kode tahun pelajaran |
| `{tanggal}` | tanggal hari ini format `Ymd` |

Aturan:

- Hasil akhir dibersihkan: spasi dihapus, huruf kapital, karakter selain huruf, angka, dan tanda hubung dibuang.
- Placeholder yang nilainya kosong otomatis digantikan `{no}`.
- Nomor urut `{no}` mengikuti urutan nama peserta (A-Z) sehingga stabil.
- Username dan nomor peserta yang bentrok di dalam grup diberi akhiran `-2`, `-3`, dan seterusnya.
- Generate bersifat aman: peserta yang sudah memiliki kredensial tidak ditimpa kecuali diminta secara eksplisit.

### Peserta

- Peserta harus terdaftar sebagai anggota kelas yang dipilih grup pada tahun pelajaran grup.
- Satu siswa hanya boleh muncul sekali dalam satu grup.
- `username` wajib, maksimal 50 karakter, unik dalam grup.
- `participant_number` wajib, maksimal 30 karakter, unik dalam grup.
- Password hanya dapat dibangkitkan acak ulang, tidak dapat diisi manual.
- `password` disimpan sebagai hash, `password_plain` disimpan sebagai ciphertext.

---

## 7. Hak Akses

| Peran | Akses |
|---|---|
| Administrator | Melihat dan mengelola semua sekolah |
| School Admin | Melihat dan mengelola sekolah yang dapat diakses |
| Headmaster | Hanya melihat data sekolahnya |
| Guru | `403` |

Aturan tambahan:

- Daftar sekolah, tahun pelajaran, kelas, sesi, dan ruangan difilter berdasarkan akses pengguna.
- Aksi create/update/delete memeriksa akses ke sekolah grup.
- Headmaster tidak melihat tombol tambah, edit, hapus, generate, maupun cetak.

---

## 8. Rute

```text
GET     /admin/exam-participant-groups
POST    /admin/exam-participant-groups
GET     /admin/exam-participant-groups/{group}
PUT     /admin/exam-participant-groups/{group}
DELETE  /admin/exam-participant-groups/{group}

POST    /admin/exam-participant-groups/{group}/participants/generate
POST    /admin/exam-participant-groups/{group}/participants/sync
POST    /admin/exam-participant-groups/{group}/participants
PUT     /admin/exam-participant-groups/{group}/participants/{participant}
DELETE  /admin/exam-participant-groups/{group}/participants/{participant}
PATCH   /admin/exam-participant-groups/{group}/participants/{participant}/password
PATCH   /admin/exam-participant-groups/{group}/placement
GET     /admin/exam-participant-groups/{group}/cards
```

Nama route:

```text
admin.exam-participant-groups.index
admin.exam-participant-groups.store
admin.exam-participant-groups.show
admin.exam-participant-groups.update
admin.exam-participant-groups.destroy
admin.exam-participant-groups.move

admin.exam-participants.generate
admin.exam-participants.sync
admin.exam-participants.store
admin.exam-participants.update
admin.exam-participants.destroy
admin.exam-participants.reset-password
admin.exam-participants.cards
```

---

## 9. Tampilan Frontend

### 9.1 Sidebar

Tambahkan menu **Peserta Ujian** di dalam **Pengelolaan Ujian**, placed setelah **Paket Ujian**.

Menu disembunyikan jika pengguna tidak memiliki akses sekolah.

### 9.2 Halaman Index Grup Peserta

Komponen: `resources/js/Pages/ExamParticipants/Index.jsx`

Isi:

- Breadcrumb: `Sistem Evaluasi Terpadu / Pengelolaan Ujian / Peserta Ujian`.
- Filter: pencarian kode atau nama grup, sekolah, tahun pelajaran, kelas, sesi, ruangan.
- Kartu statistik: total grup, total peserta, kredensial lengkap, belum memiliki kredensial.
- Tabel: kode dan nama grup, sekolah, tahun pelajaran, kelas, sesi, ruangan, jumlah peserta, kredensial terisi, aksi.
- Modal tambah/edit grup.
- Konfirmasi hapus.

### 9.3 Halaman Detail Grup Peserta

Komponen: `resources/js/Pages/ExamParticipants/Show.jsx`

Isi:

- Peringatan bila sesi dan ruangan belum ditentukan.
- Ringkasan grup: sekolah, kelas, tahun pelajaran, ruangan, sesi, template kredensial.
- Toolbar: cari nama, NIS, username, atau nomor peserta; **Cetak Kartu Peserta**; **Tarik Peserta dari Kelas**; **Generate Kredensial**.
- Aksi: **Atur Ruangan & Sesi**, **Edit Grup**, **Tambah Peserta**.
- Tabel peserta: nomor, nama dan NIS, nomor peserta, username, password (tersembunyi, aksi tampil dan acak ulang), status kredensial, aksi.
- Modal generate kredensial berisi template, contoh hasil, dan pilihan timpa.
- Modal atur ruang dan sesi untuk batch.

### 9.4 Komponen

- `ExamParticipantGroupModal.jsx`
- `ExamParticipantGroupTable.jsx`
- `ExamParticipantTable.jsx`
- `ExamParticipantModal.jsx`
- `CredentialTemplateModal.jsx`
- `BatchAssignmentModal.jsx`

Form grup memakai `<select multiple>` untuk pilihan kelas, serta menampilkan tahun pelajaran aktif sebagai informasi yang tidak dapat diubah.

### 9.5 Kartu Peserta

Komponen: `resources/views/exam-participant/cards.blade.php`

Isi per kartu:

- nama sekolah dan kode grup;
- nama grup, kelas, sesi, dan ruangan;
- nama peserta dan NIS;
- nomor peserta;
- username dan password dalam huruf besar monospace;
- area paraf petugas.

Ukuran A4 landscape, grid 3 kolom per halaman.

---

## 10. Backend

### Controller

- `app/Http/Controllers/Admin/ExamParticipantGroupController.php`
  - `index`, `store`, `show`, `update`, `move`, `destroy`
- `app/Http/Controllers/Admin/ExamParticipantController.php`
  - `store`, `update`, `destroy`, `sync`, `generate`, `resetPassword`, `cards`

### Form Request

- `StoreExamParticipantGroupRequest`
- `UpdateExamParticipantGroupRequest`
- `StoreExamParticipantRequest`
- `UpdateExamParticipantRequest`
- `GenerateParticipantCredentialsRequest`

### Service

- `app/Services/ExamParticipantCredentialService.php`
  - `syncParticipantsFromClasses()`
  - `resolvePlacement()`
  - `generateCredentials()`
  - `buildUsername()`
  - `buildParticipantNumber()`
  - `generatePassword()`
  - `moveGroup()`

### Pola implementasi

- Gunakan `DB::transaction` untuk operasi tulis.
- Gunakan eager loading untuk menghindari N+1.
- Gunakan `presentExamParticipantGroup()` dan `presentExamParticipant()` untuk format data frontend.
- Gunakan pesan validasi Bahasa Indonesia.
- Gunakan `Rule::unique(...)->where(...)` untuk kode grup dan username.
- Cetak kartu memakai Dompdf, mengikuti pola `QuestionController::print`.

---

## 11. Factory & Seeder

### Factory

- `ExamParticipantGroupFactory` — menyediakan state `forSchool()`.
- `ExamParticipantFactory` — menyediakan state `forGroup()`.

### Seeder

`ExamParticipantSeeder` membuat satu grup contoh pada sekolah pertama:

- tahun pelajaran aktif;
- satu sampai dua kelas yang sudah memiliki anggota;
- sesi dan ruangan aktif;
- seluruh anggota kelas menjadi peserta grup tersebut;
- kredensial sudah dibangkitkan.

Seeder harus idempotent dan tidak membuat data duplikat.

---

## 12. Pengujian

### Grup Peserta

- Index menampilkan grup sesuai akses sekolah.
- Filter sekolah, tahun pelajaran, kelas, sesi, dan ruangan bekerja.
- Statistik sesuai filter.
- Administrator dapat membuat grup.
- School admin dapat membuat grup hanya di sekolahnya.
- School admin tidak dapat membuat grup di sekolah lain.
- Headmaster tidak dapat membuat, mengubah, atau menghapus.
- Guru mendapat `403`.
- Kode grup unik per sekolah.
- Kelas dari sekolah lain ditolak.
- Minimal satu kelas harus dipilih.
- Tahun pelajaran grup mengikuti tahun pelajaran aktif.
- Sesi dan ruangan aktif dipilih otomatis.
- Sesi dan ruangan dikosongkan bila seluruh pasangan sudah dipakai.
- Kelas dapat dipilih lebih dari satu dan peserta ditarik dari seluruh kelas tersebut.
- Sekolah tidak berubah saat edit.
- Menghapus grup ikut menghapus pesertanya.

### Peserta

- Peserta otomatis terisi dari anggota kelas saat grup dibuat.
- Peserta yang bukan anggota kelas terpilih tidak dapat ditambahkan.
- Generate membangkitkan username dan nomor peserta sesuai template.
- Placeholder `{no}`, `{nis}`, `{nisn}`, `{kelas}`, `{ruang}`, `{sesi}`, `{tahun}`, dan `{tanggal}` terisi benar.
- Placeholder tidak dikenal ditolak saat validasi template.
- Template tanpa placeholder ditolak.
- Generate tidak menimpa kredensial yang sudah ada kecuali diminta.
- Username dan nomor peserta bentrok otomatis diberi akhiran.
- Password disimpan sebagai hash dan `password_plain` dapat didekripsi.
- Acak ulang password menulis ulang kedua kolom.
- Perpindahan batch mengubah sesi dan ruangan grup.
- Perpindahan ke pasangan yang sudah dipakai ditolak.
- Kartu peserta menghasilkan PDF dengan status `200`.

---

## 13. Urutan Implementasi

1. Migration `exam_participant_groups` dan `exam_participants`.
2. Migration pivot `exam_participant_group_classes` + penghapusan `exam_date` dan `class_id`.
3. Model, relasi, factory, dan seeder.
4. Service kredensial peserta.
5. Form request dan aturan validasi.
6. Controller grup peserta dan controller peserta.
7. Rute.
8. Halaman index dan modal grup.
9. Halaman detail, tabel peserta, modal kredensial, dan modal batch.
10. Blade kartu peserta dan endpoint PDF.
11. Sidebar.
12. Feature test.
13. Jalankan Pint, seluruh test, dan `npm run build`.

---

## 14. Kriteria Selesai

- Modul dapat diakses dari sidebar **Pengelolaan Ujian → Peserta Ujian**.
- Grup peserta dapat dibuat, diubah, dan dihapus sesuai hak akses.
- Kelas dapat dipilih lebih dari satu dalam satu grup.
- Tahun pelajaran, sesi, dan ruangan ditetapkan otomatis.
- Ruangan dan sesi dapat diubah untuk seluruh batch dari halaman detail.
- Username, nomor peserta, dan password dapat dibangkitkan otomatis dari template.
- Password dapat ditampilkan dan diacak ulang per peserta.
- Kartu peserta dapat dicetak dalam PDF.
- Tidak ada N+1 pada halaman index dan detail.
- Seluruh feature test baru lulus.
- Seluruh test suite tetap lulus.
- `npm run build` berhasil.
- Pint dijalankan pada semua file PHP yang diubah.
