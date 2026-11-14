# Resume Pekerjaan — CBT Panel

**Terakhir diperbarui:** 11 November 2026  
**Status proyek:** Autentikasi (login, logout, dropdown profil, ganti kata sandi), Tahun Pelajaran, Jenis Ujian, Sesi, Ruangan, Sekolah, Kelas, Pembelajaran, dan Siswa sudah terhubung penuh ke database dan UI. Modul lain masih mock/placeholder.

---

## 1. Ringkasan Proyek

Aplikasi panel monitoring CBT berbasis:

| Komponen | Versi / Teknologi |
|---|---|
| Backend | Laravel 13.40.0, PHP 8.3 |
| Frontend | Inertia.js 3.11 + React 19 (JSX, bukan TSX) |
| Styling | Tailwind CSS v4 via `@tailwindcss/vite` |
| Build | Vite 8 |
| Database lokal | SQLite |
| Database test | SQLite in-memory (`phpunit.xml`) |
| Routing frontend | Ziggy 2.6 |
| Autentikasi | Laravel built-in (Authenticatable), middleware `auth` |
| Desain | Lihat `docs/DESIGN.md` |

**URL lokal:** http://localhost:8000/admin/academic-years

---

## 2. Aturan & Konvensi Wajib

- Gunakan **JavaScript JSX** (`.jsx`). Jangan membuat TypeScript/TSX.
- Semua route aplikasi berada di dalam grup `admin` (`/admin`, name prefix `admin.`).
- Gunakan komponen UI bersama, bukan elemen mentah:
  - `Modal`, `Input`, `Select`, `Button`, `Table`, `Pagination`, `Badge`, `Card`, `DeleteConfirmation`.
- Ikuti `docs/DESIGN.md`:
  - No-Line Rule (hindari border 1px).
  - Hierarki lewat surface tier.
  - Headline Noto Serif, body Inter, label Public Sans.
  - Material Symbols untuk ikon.
  - Sudut membulat, tanpa sudut tajam.
- Alias `@/Components/...` **case-sensitive**. Jangan membuat folder `resources/js/components` (huruf kecil) di Windows.
- Jalankan `npm run build` setelah perubahan frontend.
- Jalankan `vendor/bin/pint --format agent <file>` setelah mengubah PHP.
- Proyek ini **bukan git repository**, jadi `pint --dirty` tidak tersedia.
- `.ai/rules` tidak ada. Panduan aktif hanya `AGENTS.md`.

---
## 3. Yang Sudah Selesai

### 3.1 Layout & Shared UI

- Layout admin dengan sidebar, header, dan toast.
- Sidebar memakai dropdown dan active state; menu **Master Data** berisi data akademik (tahun pelajaran, kelas, sekolah, mata pelajaran), sedangkan **Pengelolaan Ujian** berisi jenis ujian, sesi, ruangan, dan jenis penilaian.
- Menu yang route-nya belum ada otomatis tampil nonaktif dengan label “Segera hadir”.
- Komponen UI bersama tersedia di `resources/js/Components/UI`:
  - `Avatar`, `Badge`, `Button`, `Card`, `DeleteConfirmation`, `Input`, `Modal`, `Pagination`, `Select`, `Table`.
- `Button` mendukung `variant`, `size`, `loading`, dan meneruskan atribut `...rest`.
- `Table` & `Pagination` diekstrak sebagai komponen bersama.
- Toast dari backend dibagikan melalui `flash.toast` dan ditampilkan otomatis di halaman Tahun Pelajaran.

### 3.2 Modul Tahun Pelajaran — Selesai

#### Database

Tabel `academic_years`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `code` | string(5), unique | dibuat otomatis oleh model |
| `name` | string(20) | format `YYYY/YYYY` |
| `semester` | string(10) | `ganjil` / `genap` |
| `is_active` | boolean, default false, indexed | hanya satu boleh aktif |
| `created_at`, `updated_at` | timestamp | |

Constraint tambahan:

- Unique `code`.
- Unique gabungan `name + semester`.
- Tidak ada `start_date`, `end_date`, atau status `upcoming`.

#### Aturan bisnis

- `code` dibuat di model saat `saving`, bukan diisi dari form:
  - `2024/2025` + `ganjil` → `20241`
  - `2024/2025` + `genap` → `20242`
- `code` ikut berubah bila `name` atau `semester` diubah.
- Hanya satu tahun pelajaran boleh `is_active = true`.
  - Implementasi: event model `saved` menonaktifkan record lain yang aktif.
- Status **tidak diatur dari form**. Create selalu menghasilkan `is_active = false`; update tidak mengubah status.
- Status diubah dengan mengklik badge status di tabel (endpoint toggle).
- `name` wajib format `YYYY/YYYY` dan tahun akhir harus tahun awal + 1.
- `name + semester` unik.
- `is_active` default `false` saat create.
- `semester` memakai backed enum `App\Enums\AcademicYearSemester`.

#### Seeder

- `AcademicYearSeeder` membuat 16 data:
  - 8 tahun ajaran × 2 semester.
  - Rentang 2018/2019 sampai 2025/2026.
  - Data aktif: kode `20241` = 2024/2025 Ganjil.
- Seeder idempotent (`firstOrNew`).
- `DatabaseSeeder` sudah mendaftarkan seeder ini.
- Database lokal saat ini berisi **2 record**:
  - `20261` = 2026/2027 Ganjil (aktif).
  - `20262` = 2026/2027 Genap (tidak aktif).
- Menjalankan `php artisan migrate:fresh --seed` akan memulihkan 16 record seeder dengan `20241` aktif.
- Karena `2026/2027` Ganjil sudah ada, menekan Generate Otomatis pada November 2026 akan menampilkan toast “Data Sudah Ada” tanpa insert.

#### Backend

Controller:

- `app/Http/Controllers/Admin/AcademicYearController.php`
  - `index`: pagination 5, pencarian `name`/`code`, urut `is_active` lalu `code` desc.
  - `store`: create dalam `DB::transaction` dengan `is_active = false`, flash toast.
  - `generate`: membuat record tahun pelajaran berjalan dari tanggal server, lalu flash toast.
  - `update`: update nama/semester dalam transaksi, kode dihitung ulang oleh model, status tidak disentuh.
  - `toggleStatus`: membalik `is_active`; saat diaktifkan, model menonaktifkan tahun lain.
  - `destroy`: hapus data + flash toast.
  - Mengirim props: `academicYears`, `filters.search`, `semesters`, `stats`.

Aturan Generate Otomatis:

- Ditentukan dari `now()` (tanggal server).
- **Januari–Juni** → semester **Genap**, tahun pelajaran `(tahun - 1)/(tahun)`. Contoh: Juni 2026 → `2025/2026` Genap → kode `20252`.
- **Juli–Desember** → semester **Ganjil**, tahun pelajaran `tahun/(tahun + 1)`. Contoh: Juli 2026 → `2026/2027` Ganjil → kode `20261`.
- Memakai `firstOrCreate` pada `name + semester`; jika sudah ada, **tidak ada insert** dan toast bertipe `info` (“Data Sudah Ada”).
- Record baru dibuat dengan `is_active = false`; aktivasi tetap dilakukan manual lewat form edit.
- Logika periode ada di `AcademicYearController::resolveAcademicPeriod()`.

Form Request:

- `app/Http/Requests/Admin/StoreAcademicYearRequest.php`
- `app/Http/Requests/Admin/UpdateAcademicYearRequest.php`

Validasi:

- `name` wajib, regex `^\d{4}\/\d{4}$`, maksimal 20 karakter.
- Tahun akhir harus tepat satu tahun setelah tahun awal.
- `name + semester` unik; update mengabaikan record yang sedang diedit.
- `semester` wajib dan harus enum valid.
- `is_active` boolean; create default `false`; update tidak berubah jika tidak dikirim.
- Pesan error Bahasa Indonesia.

Route:

| Method | URI | Name |
|---|---|---|
| GET | `/admin/academic-years` | `admin.academic-years.index` |
| POST | `/admin/academic-years` | `admin.academic-years.store` |
| POST | `/admin/academic-years/generate` | `admin.academic-years.generate` |
| PATCH | `/admin/academic-years/{academic_year}/status` | `admin.academic-years.toggle-status` |
| PUT/PATCH | `/admin/academic-years/{academic_year}` | `admin.academic-years.update` |
| DELETE | `/admin/academic-years/{academic_year}` | `admin.academic-years.destroy` |

Route didefinisikan sebagai:

```php
Route::post('academic-years/generate', [AcademicYearController::class, 'generate'])
    ->name('academic-years.generate');

Route::patch('academic-years/{academic_year}/status', [AcademicYearController::class, 'toggleStatus'])
    ->name('academic-years.toggle-status');

Route::resource('academic-years', AcademicYearController::class)
    ->only(['index', 'store', 'update', 'destroy']);
```

#### Frontend

- `resources/js/Pages/AcademicYears/Index.jsx`
  - Tidak lagi memakai mock data.
  - Menerima props dari controller.
  - Pencarian server-side dengan debounce 300 ms.
  - Partial reload `only: ['academicYears', 'filters']`.
  - Pagination server-side.
  - Statistik: total, tahun aktif, jumlah tidak aktif.
  - Tombol **Generate Otomatis** di header memanggil `router.post` ke `admin.academic-years.generate` dengan state loading.
  - Klik badge status memanggil `router.patch` ke `admin.academic-years.toggle-status` dengan state `togglingId`.
  - Toast otomatis dari `flash.toast`, termasuk toast `info` saat data hasil generate sudah ada.
  - Hapus data lewat `router.delete`.
- `resources/js/Pages/AcademicYears/Components/AcademicYearModal.jsx`
  - Memakai `useForm` Inertia.
  - Create → `admin.academic-years.store`.
  - Edit → `admin.academic-years.update`.
  - Field: **nama dan semester saja**. Field status sudah dihapus.
  - Create menampilkan info bahwa status awal selalu Tidak Aktif dan diubah lewat badge di tabel.
  - Kode ditampilkan read-only saat edit.
  - Error validasi server tampil di field.
  - Modal tetap terbuka dan data tidak hilang saat validasi gagal.
- `resources/js/Pages/AcademicYears/Components/AcademicYearTable.jsx`
  - Kolom: No, Kode, Tahun Pelajaran, Semester, Status, Aksi.
  - Badge status adalah tombol: klik untuk mengaktifkan/menonaktifkan.
  - Saat proses toggle, ikon badge berputar dan tombol dinonaktifkan sementara.
  - Kolom aksi memakai komponen bersama `RowActions` (edit, hapus); tombol hapus disembunyikan untuk tahun yang aktif.
  - Kolom tanggal/periode sudah dihapus.

#### Test

- `tests/Feature/AcademicYearTest.php` — 6 test.
- `tests/Feature/Admin/AcademicYearControllerTest.php` — 15 test.

Cakupan:

- Generate kode.
- Regenerasi kode saat edit.
- Aturan satu tahun aktif.
- Cast enum.
- Nama tidak valid.
- Seeder.
- Index + pagination.
- Pencarian.
- Statistik.
- Store + kode otomatis.
- Aktivasi menonaktifkan tahun lain.
- Duplikat nama + semester.
- Tahun tidak berurutan.
- Generate Januari–Juni → Genap.
- Generate Juli–Desember → Ganjil.
- Generate tidak insert saat data sudah ada.
- Store selalu menghasilkan status Tidak Aktif walau `is_active` dikirim.
- Toggle status mengaktifkan tahun dan menonaktifkan tahun lain.
- Toggle status menonaktifkan tahun yang sedang aktif.
- Update tidak mengubah status.
- Update.
- Destroy.

Hasil terakhir:

```text
php artisan test --compact
23 tests passed, 95 assertions
```

Catatan: 23 test total termasuk 2 test contoh bawaan Laravel. Test `ExampleTest` bergantung pada `public/hot` atau manifest Vite; jika `npm run build` belum dijalankan dan dev server mati, test tersebut bisa gagal karena `welcome.blade.php` meminta `resources/js/app.js`.

### 3.3 Jenis Ujian — Selesai & Terhubung DB

Database:

Tabel `exam_types`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `code` | string(20), unique | Kode jenis ujian, huruf kapital/angka/tanda hubung |
| `name` | string(100) | Nama jenis ujian |
| `description` | text, nullable | Deskripsi opsional |
| `is_active` | boolean, default false, indexed | Boleh lebih dari satu aktif |
| `created_at`, `updated_at` | timestamp | |

- Unique `code` (global, bukan hanya di antara aktif).
- Model `App\Models\ExamType` pakai `#[Fillable([...])]` dan cast `is_active` ke boolean.

Backend:

- `app/Http/Controllers/Admin\ExamTypeController.php`
  - `index`: pagination 5, pencarian `code`/`name`/`description`, urut `latest()`.
  - `store`: create dalam `DB::transaction`, default `is_active = false`.
  - `update`: update field yang dikirim, `description` nullable dipertahankan.
  - `toggleStatus` (`PATCH /admin/exam-types/{id}/status`): nyala/mati, tidak menonaktifkan jenis ujian lain (lebih dari satu boleh aktif).
  - `destroy`: hapus data.
  - Mengirim props: `examTypes`, `filters.search`, `stats` (total, active objek, inactive).
- `app/Http\Requests\Admin\StoreExamTypeRequest.php` dan `UpdateExamTypeRequest.php`:
  - `code` wajib, maks 20, regex `^[A-Z0-9-]+$`, unik.
  - `name` wajib, maks 100.
  - `description` nullable, maks 255.
  - Pesan Bahasa Indonesia.

Route:

| Method | URI | Name |
|---|---|---|
| GET | `/admin/exam-types` | `admin.exam-types.index` |
| POST | `/admin/exam-types` | `admin.exam-types.store` |
| PUT/PATCH | `/admin/exam-types/{exam_type}` | `admin.exam-types.update` |
| PATCH | `/admin/exam-types/{examTypeId}/status` | `admin.exam-types.toggle-status` |
| DELETE | `/admin/exam-types/{exam_type}` | `admin.exam-types.destroy` |

Route didefinisikan sebagai:

```php
Route::patch('exam-types/{examTypeId}/status', [ExamTypeController::class, 'toggleStatus'])
    ->name('exam-types.toggle-status');

Route::resource('exam-types', ExamTypeController::class)
    ->only(['index', 'store', 'update', 'destroy']);
```

Seeder:

- `database/seeders/ExamTypeSeeder.php`: 8 data (UTS, UAS, PTK, PAK, UN, ANBK, TPS, UKK), UTS aktif.
- `DatabaseSeeder` memanggil `ExamTypeSeeder` setelah `AcademicYearSeeder`.

Frontend:

- `resources/js/Pages/ExamTypes/Index.jsx`
  - Tidak lagi mock data; menerima props dari controller.
  - Pencarian server-side dengan debounce 300 ms.
  - Pagination server-side.
  - Statistik: total, jenis aktif (nama + kode), jumlah tidak aktif.
  - Toast otomatis dari `flash.toast`.
  - Hapus data lewat `router.delete`.
  - Klik badge status untuk toggle (loading spinner saat `togglingId`).
- `resources/js/Pages/ExamTypes/Components/ExamTypeModal.jsx`
  - Memakai `useForm` Inertia.
  - Create → `admin.exam-types.store`; Edit → `admin.exam-types.update`.
  - Field: kode, nama, deskripsi.
  - Error validasi server tampil di field.
  - Mendukung `mode="show"` untuk detail read-only.
- `resources/js/Pages/ExamTypes/Components/ExamTypeTable.jsx`
  - Kolom: No, Kode, Jenis Ujian, Deskripsi, Status, Aksi.
  - Status memakai `Badge` (Aktif / Tidak Aktif), klik untuk toggle.
  - Aksi memakai `RowActions` (show, edit, hapus; hapus hanya tersedia saat tidak aktif).

Test:

- `tests/Feature/Admin\ExamTypeControllerTest.php` — 12 test: index, pencarian, statistik, store, validasi, duplikat, toggle status, update, destroy.

Hasil terakhir suite penuh:

```text
php artisan test --compact
61 tests passed, 291 assertions
```

### 3.4 Sesi — Selesai & Terhubung DB

Database:

Tabel `exam_sessions` (nama tabel `exam_sessions` digunakan untuk menghindari konflik dengan tabel `sessions` bawaan Laravel):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `code` | string(20), unique | Kode sesi, huruf kapital/angka/tanda hubung |
| `name` | string(100) | Nama sesi |
| `start_time` | time | Waktu mulai |
| `end_time` | time | Waktu selesai |
| `is_active` | boolean, default false, indexed | Boleh lebih dari satu aktif |
| `created_at`, `updated_at` | timestamp | |

- Unique `code` (global).
- Model `App\Models\Session` pakai `#[Fillable([...])]` dan cast `is_active` ke boolean, dengan `$table = 'exam_sessions'`.

Backend:

- `app/Http/Controllers/Admin\SessionController.php`
  - `index`: pagination 5, pencarian `code`/`name`, urut `latest()`.
  - `store`: create dalam `DB::transaction`, default `is_active = false`.
  - `update`: update field yang dikirim.
  - `toggleStatus` (`PATCH /admin/sessions/{id}/status`): nyala/mati, tidak menonaktifkan sesi lain.
  - `destroy`: hapus data.
  - Mengirim props: `sessions`, `filters.search`, `stats` (total, active objek, inactive).
- `app/Http\Requests\Admin\StoreSessionRequest.php` dan `UpdateSessionRequest.php`:
  - `code` wajib, maks 20, regex `^[A-Z0-9-]+$`, unik.
  - `name` wajib, maks 100.
  - `start_time`, `end_time` wajib, format `H:i:s`, end_time setelah start_time.
  - `is_active` boolean.
  - Pesan Bahasa Indonesia.

Route:

| Method | URI | Name |
|---|---|---|
| GET | `/admin/sessions` | `admin.sessions.index` |
| POST | `/admin/sessions` | `admin.sessions.store` |
| PUT/PATCH | `/admin/sessions/{session}` | `admin.sessions.update` |
| PATCH | `/admin/sessions/{sessionId}/status` | `admin.sessions.toggle-status` |
| DELETE | `/admin/sessions/{session}` | `admin.sessions.destroy` |

Seeder:

- `database/seeders/SessionSeeder.php`: 4 data (Sesi Pagi, Sesi Siang, Sesi Sore, Sesi Malam), Sesi Pagi aktif.
- `DatabaseSeeder` memanggil `SessionSeeder` setelah `ExamTypeSeeder`.

Test:

- `tests/Feature/Admin\SessionControllerTest.php` — 13 test: index, pencarian, statistik, store, validasi, duplikat, end_time setelah start_time, toggle status, update, destroy.

Frontend:

- `resources/js/Pages\Sessions\Index.jsx`
  - Tidak lagi mock data; menerima props dari controller.
  - Pencarian server-side dengan debounce 300 ms.
  - Pagination server-side.
  - Statistik: total, sesi aktif, jumlah tidak aktif.
  - Toast otomatis dari `flash.toast`.
  - Hapus data lewat `router.delete`.
  - Klik badge status untuk toggle (loading spinner saat `togglingId`).
- `resources/js/Pages\Sessions\Components\SessionModal.jsx`
  - Memakai `useForm` Inertia.
  - Create → `admin.sessions.store`; Edit → `admin.sessions.update`.
  - Field: kode, nama, waktu mulai, waktu selesai, status aktif.
  - Waktu menggunakan input `type="time"`.
  - Error validasi server tampil di field.
- `resources/js/Pages\Sessions\Components\SessionTable.jsx`
  - Kolom: No, Kode, Nama Sesi, Waktu, Status, Aksi.
  - Status memakai `Badge` (Aktif / Tidak Aktif), klik untuk toggle.
  - Aksi memakai `RowActions` (edit, hapus; hapus hanya tersedia saat tidak aktif).

### 3.5 Ruangan — Selesai & Terhubung DB

Database:

Tabel `rooms`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `code` | string(20), unique | Kode ruangan, huruf kapital/angka/tanda hubung |
| `name` | string(100) | Nama ruangan |
| `building` | string(100) | Nama gedung |
| `floor` | string(20) | Lantai |
| `capacity` | integer | Kapasitas ruangan |
| `is_active` | boolean, default false, indexed | Boleh lebih dari satu aktif |
| `created_at`, `updated_at` | timestamp | |

- Unique `code` (global).
- Model `App\Models\Room` pakai `#[Fillable([...])]` dan cast `is_active`, `capacity` ke tipe yang sesuai.

Backend:

- `app/Http\Controllers/Admin\RoomController.php`
  - `index`: pagination 5, pencarian `code`/`name`/`building`, urut `latest()`.
  - `store`: create dalam `DB::transaction`, default `is_active = false`.
  - `update`: update field yang dikirim.
  - `toggleStatus` (`PATCH /admin/rooms/{id}/status`): nyala/mati, tidak menonaktifkan ruangan lain.
  - `destroy`: hapus data.
  - Mengirim props: `rooms`, `filters.search`, `stats` (total, active objek, inactive).
- `app/Http\Requests\Admin\StoreRoomRequest.php` dan `UpdateRoomRequest.php`:
  - `code` wajib, maks 20, regex `^[A-Z0-9-]+$`, unik.
  - `name` wajib, maks 100.
  - `building` wajib, maks 100.
  - `floor` wajib, maks 20.
  - `capacity` wajib, integer, minimal 1.
  - `is_active` boolean.
  - Pesan Bahasa Indonesia.

Route:

| Method | URI | Name |
|---|---|---|
| GET | `/admin/rooms` | `admin.rooms.index` |
| POST | `/admin/rooms` | `admin.rooms.store` |
| PUT/PATCH | `/admin/rooms/{room}` | `admin.rooms.update` |
| PATCH | `/admin/rooms/{roomId}/status` | `admin.rooms.toggle-status` |
| DELETE | `/admin/rooms/{room}` | `admin.rooms.destroy` |

Seeder:

- `database/seeders/RoomSeeder.php`: 5 data (Ruangan Ujian 1-5 di Gedung A/B/C), RM01 aktif.
- `DatabaseSeeder` memanggil `RoomSeeder` setelah `SessionSeeder`.

Test:

- `tests/Feature/Admin\RoomControllerTest.php` — 13 test: index, pencarian, statistik, store, validasi, duplikat, capacity minimal, toggle status, update, destroy.

Frontend:

- `resources/js/Pages\Rooms\Index.jsx`
  - Tidak lagi mock data; menerima props dari controller.
  - Pencarian server-side dengan debounce 300 ms.
  - Pagination server-side.
  - Statistik: total, ruangan aktif, jumlah tidak aktif.
  - Toast otomatis dari `flash.toast`.
  - Hapus data lewat `router.delete`.
  - Klik badge status untuk toggle (loading spinner saat `togglingId`).
- `resources/js/Pages\Rooms\Components\RoomModal.jsx`
  - Memakai `useForm` Inertia.
  - Create → `admin.rooms.store`; Edit → `admin.rooms.update`.
  - Field: kode, nama, gedung, lantai, kapasitas, status aktif.
  - Error validasi server tampil di field.
- `resources/js/Pages\Rooms\Components\RoomTable.jsx`
  - Kolom: No, Kode, Nama Ruangan, Gedung, Lantai, Kapasitas, Status, Aksi.
  - Status memakai `Badge` (Aktif / Tidak Aktif), klik untuk toggle.
  - Aksi memakai `RowActions` (edit, hapus; hapus hanya tersedia saat tidak aktif).

### 3.6 Kelas — Selesai & Terhubung DB

Database:

Tabel `classes`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `school_id` | bigint | FK ke `schools`, cascade delete |
| `academic_year_id` | bigint | FK ke `academic_years`, cascade delete |
| `code` | string(20) | Kode kelas, huruf kapital/angka/tanda hubung |
| `name` | string(100) | Nama kelas |
| `is_active` | boolean, default false, indexed | Status kelas |
| `created_at`, `updated_at` | timestamp | |

Constraint tambahan:

- Unique `school_id + code`.
- Unique `school_id + academic_year_id + name`.
- Status **tidak diatur dari form** (create selalu `is_active = false`); toggle lewat tabel.

Aturan bisnis:

- Kelas terhubung ke sekolah (`school_id`) dan tahun pelajaran (`academic_year_id`).
- Kode unik **per sekolah**, nama unik **per sekolah + tahun pelajaran**.
- Hanya kelas yang `is_active = true` yang bisa dihapus.

Backend:

- `app/Http/Controllers/Admin/ClassController.php`
  - `index`: pagination 5, pencarian `code`/`name`/`school`/`tahun pelajaran`, urut `is_active` desc lalu `latest()`.
  - Selalu menampilkan kelas dari **tahun pelajaran aktif** (fallback: tahun terbaru) untuk semua sekolah yang accessible.
  - Akses modul dibatasi (lihat bagian 3.6):
    - `authorizeClassAccess`: hanya `administrator`, `admin sekolah` (admin of accessible school), dan `kepala sekolah` (headmaster of accessible school) boleh membuka modul; guru biasa ditolak `403`.
    - `authorizeClassManage`: store/update/toggleStatus/destroy hanya diizinkan untuk `administrator` dan `admin sekolah`. Kepala sekolah hanya boleh melihat (read-only).
  - `store`: create dalam `DB::transaction`, default `is_active = false`, `academic_year_id` diambil otomatis dari tahun pelajaran **aktif** (fallback: tahun terbaru). Tidak membaca `academic_year_id` dari request.
  - `update`: update `code`, `name`; status dan `academic_year_id` tidak disentuh.
  - `toggleStatus` (`PATCH /admin/classes/{schoolClass}/status`): nyala/mati.
  - `destroy`: hapus data; ditolak saat `is_active = true`.
  - Mengirim props: `classes`, `filters.search`, `schools`, `academicYears`, `stats` (total, active, inactive).
  - `presentClass` membaca relasi dengan nama method yang benar (`school`, `academicYear` — camelCase); field seperti `academic_year_name` wajib memakai relasi `academicYear`, bukan `academic_year`.
- `app/Http/Requests/Admin/StoreClassRequest.php` dan `UpdateClassRequest.php`:
  - `school_id` wajib, harus berasal dari sekolah yang user dapat akses.
  - `academic_year_id` **tidak** lagi wajib/diisi dari form; tahun pelajaran ditentukan otomatis dari tahun aktif.
  - `code` wajib, regex `^[A-Z0-9-]+$`, unik per sekolah.
  - `name` wajib, maks 100, unik per sekolah + tahun pelajaran aktif.
  - Pesan Bahasa Indonesia.

Route:

| Method | URI | Name |
|---|---|---|
| GET | `/admin/classes` | `admin.classes.index` |
| POST | `/admin/classes` | `admin.classes.store` |
| PUT/PATCH | `/admin/classes/{schoolClass}` | `admin.classes.update` |
| PATCH | `/admin/classes/{schoolClass}/status` | `admin.classes.toggle-status` |
| DELETE | `/admin/classes/{schoolClass}` | `admin.classes.destroy` |

Seeder:

- `database/seeders/ClassSeeder.php`: kelas contoh untuk sekolah YAS-001 (SMA) dan YAS-002 (SMP) pada tahun pelajaran aktif, idempotent per `school_id + academic_year_id + code`.

Test:

- `tests/Feature/Admin/ClassControllerTest.php` — 18 test: index, pencarian, statistik, store, validasi, duplikat, tahun pelajaran aktif, toggle status, update, destroy, akses guru, kepala sekolah read-only, hanya tahun aktif.
- `tests/Feature/ClassTest.php` — 4 test: relasi `school`/`academic_year`, relasi `hasMany` di kedua sisi, default `is_active`.

Hasil terakhir suite:

```text
php artisan test --compact
 208 tests passed, 1057 assertions
```

### 3.7 Pembelajaran (Guru Pengampuh Mata Pelajaran) — Selesai & Terhubung DB

Database:

- Tabel `subject_teachers`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `school_class_id` | bigint | FK ke `classes`, cascade delete |
| `academic_year_id` | bigint | FK ke `academic_years`, cascade delete |
| `subject_id` | bigint | FK ke `subjects`, cascade delete |
| `teacher_id` | bigint | FK ke `users` (guru), cascade delete |
| `is_active` | boolean, default false, indexed | Status penugasan |
| `created_at`, `updated_at` | timestamp | |

- Unik: `(school_class_id, subject_id)` — satu guru pengampuh per mata pelajaran per kelas.

Backend:

- `app/Http/Controllers/Admin/SubjectTeacherController.php`
  - `index`: memakai `SchoolClass` dari route (`{schoolClass}`), pagination 5, pencarian mata pelajaran/guru, eager load `subject` + `teacher`, stats (total, active, inactive), daftar `subjects` dan `teachers` (guru aktif) untuk form.
  - `store`: menyimpan penugasan guru pengampuh; tahun pelajaran mengikuti kelas; `is_active = true`.
  - `update`: mengubah guru pengampuh.
  - `toggleStatus` (`PATCH /admin/subject-teachers/{subjectTeacher}/status`): nyala/mati.
  - `destroy`: hapus penugasan; ditolak saat `is_active = true`.
  - Akses: admin/admin sekolah/kepala sekolah (kepala sekolah view-only, manage admin/admin sekolah).
- `app/Http/Requests/Admin/StoreSubjectTeacherRequest.php`:
  - `subject_id` wajib, exists di `subjects`.
  - `teacher_id` wajib, exists di `users` dengan role `guru` dan `is_active = true`.
- `app/Http/Requests/Admin/UpdateSubjectTeacherRequest.php`:
  - `teacher_id` wajib, exists di `users` dengan role `guru` dan `is_active = true`.

Routes:

- `GET/POST admin/classes/{schoolClass}/subject-learning` → `admin.subject-teachers.index/store`.
- `PUT/PATCH/DELETE admin/classes/{schoolClass}/subject-learning/{subjectTeacher}` → `admin.subject-teachers.update/toggle-status/destroy`.

Frontend:

- `resources/js/Pages/SubjectTeachers/Index.jsx` — halaman per kelas (`schoolClass` prop), stats, search, pagination, modal, delete, toast.
- `resources/js/Pages/SubjectTeachers/Components/SubjectTeacherModal.jsx` — modal tentukan/ubah guru pengampuh (mata pelajaran + guru).
- `resources/js/Pages/SubjectTeachers/Components/SubjectTeacherTable.jsx` — tabel mata pelajaran, guru pengampuh, status, aksi.

Tombol di Kelas:

- `resources/js/Pages/Classes/Components/ClassTable.jsx` — tombol **Pembelajaran** (ikon `route`) di kolom aksi → `onOpenLearning(item)`.
- `resources/js/Pages/Classes/Index.jsx` — handler `handleOpenLearning` → `router.get(route('admin.subject-teachers.index', { schoolClass: item.id }), { preserveScroll: true })`.

Tidak ada menu sidebar untuk Pembelajaran. Saat berada di halaman pembelajaran (`/admin/classes/{id}/subject-learning`), menu **Kelas** tetap aktif karena `isActive` di `Sidebar.jsx` mencocokkan sub-halaman ke menu induknya (kecuali root `/admin` yang harus cocok persis).

Test:

- `tests/Feature/SubjectTeacherTest.php` — 2 test: relasi kelas/tahun/subject/guru, default inactive.
- `tests/Feature/Admin/SubjectTeacherControllerTest.php` — 14 test: index, empty, guru forbidden, headmaster allowed, store, store validation, duplikat mapel per kelas, store inaccessible school, update, update validation, toggle, destroy active, destroy inactive, headmaster cannot manage.

### 3.8 Siswa (Master, Registrasi Sekolah, Anggota Kelas) — Selesai & Terhubung DB

Modul Siswa memakai tiga lapis data:

- `students` — identitas master siswa, tidak terikat sekolah.
- `student_registrations` — registrasi siswa di sekolah (NIS diterbitkan sekolah, status keaktifan).
- `class_members` — keanggotaan kelas per tahun pelajaran.

Database:

- Tabel `students`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `nisn` | string(20), nullable, unik | Nomor Induk Siswa Nasional |
| `name` | string | Nama lengkap |
| `gender` | string(1) | `L` / `P` |
| `birth_place` | string, nullable | Tempat lahir |
| `birth_date` | date, nullable | Tanggal lahir |
| `photo_path` | string, nullable | Foto di disk `public` (`students/photos`) |
| `father_name` | string, nullable | Nama ayah |
| `father_occupation` | string, nullable | Pekerjaan ayah |
| `father_phone` | string(30), nullable | No. HP ayah |
| `mother_name` | string, nullable | Nama ibu |
| `mother_occupation` | string, nullable | Pekerjaan ibu |
| `mother_phone` | string(30), nullable | No. HP ibu |
| `parent_address` | text, nullable | Alamat orang tua/wali |
| `parent_bio` | text, nullable | Biografi/catatan orang tua/wali |
| `created_at`, `updated_at` | timestamp | |
| `deleted_at` | timestamp, nullable | Soft delete |

- Tabel `student_registrations`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `student_id` | bigint | FK ke `students`, cascade delete |
| `school_id` | bigint | FK ke `schools`, cascade delete |
| `nis` | string(30) | Nomor induk sekolah |
| `entry_academic_year_id` | bigint | FK ke `academic_years`, cascade delete |
| `status` | string(20), default `aktif`, indexed | `aktif` / `lulus` / `pindah` / `keluar` |
| `registered_at` | date | Tanggal registrasi |
| `created_at`, `updated_at` | timestamp | |

- Unik: `(student_id, school_id)` — satu registrasi per siswa per sekolah; `(school_id, nis)` — NIS unik dalam satu sekolah.
- Tabel `class_members`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | auto increment |
| `class_id` | bigint | FK ke `classes`, cascade delete |
| `student_id` | bigint | FK ke `students`, cascade delete |
| `academic_year_id` | bigint | FK ke `academic_years`, disalin dari kelas |
| `created_at`, `updated_at` | timestamp | |

- Unik: `(student_id, academic_year_id)` — satu siswa hanya boleh berada di satu kelas per tahun pelajaran. Index tambahan `(class_id, academic_year_id)`.
- Enum `App\Enums\Gender` (`LakiLaki` = `L`, `Perempuan` = `P`) dan `App\Enums\RegistrationStatus` (`Aktif`, `Lulus`, `Pindah`, `Keluar`) beserta `label()`.

Aturan bisnis:

- Registrasi bersifat persisten per sekolah; pergantian tahun pelajaran hanya mengubah `class_members`.
- Tahun masuk pada **siswa baru**, **import**, dan **registrasi baru** otomatis memakai tahun pelajaran aktif; pilihan tahun manual dihapus dari form tersebut. Jika belum ada tahun pelajaran aktif, proses ditolak. Saat mengedit registrasi lama, tahun masuk tetap dapat diubah manual.
- Satu siswa hanya boleh memiliki satu registrasi berstatus `Aktif`. Menyimpan registrasi baru/ubah status menjadi `Aktif` otomatis mengubah registrasi aktif lainnya menjadi `Pindah`.
- Menu **Siswa** memiliki dua submenu: **Siswa Aktif** hanya menampilkan siswa dengan registrasi `Aktif`, sedangkan **Alumni / Non Aktif** menampilkan siswa tanpa registrasi `Aktif` (termasuk lulus, pindah, dan keluar).
- **Bulk luluskan** pada halaman Siswa Aktif mengubah registrasi aktif siswa terpilih menjadi `Lulus`. Admin sekolah hanya dapat meluluskan registrasi aktif pada sekolah yang dapat diakses.
- **Tarik Siswa** mendaftarkan siswa yang sudah ada di tabel `students` ke sekolah baru dengan status `Aktif`; siswa yang sudah terdaftar di sekolah tujuan tidak muncul, tahun masuk memakai tahun aktif, dan registrasi aktif lama otomatis menjadi `Pindah`. Pencarian untuk admin sekolah dibatasi ke siswa dari sekolah yang dapat diakses.
- Anggota kelas hanya boleh ditambahkan jika siswa memiliki registrasi `Aktif` di sekolah kelas tersebut dan belum punya kelas pada tahun pelajaran yang sama.
- Hapus siswa ditolak jika masih punya registrasi `Aktif` atau masih menjadi anggota kelas. Hapus registrasi ditolak jika statusnya `Aktif` atau siswa masih anggota kelas di sekolah tersebut.

Backend:

- `app/Http/Controllers/Admin/StudentController.php`
  - `index`: pagination 10, pencarian nama/NISN/NIS, filter sekolah, kelas (tahun aktif), status registrasi, dan jenis kelamin; stats total/aktif/tidak aktif; scoping `accessibleSchoolIds()` untuk non-administrator; menampilkan registrasi (aktif atau terbaru) dan kelas pada tahun pelajaran aktif. Route `active` dan `alumni` memakai method yang sama dengan scope `active`/`alumni`; stats tetap dihitung sebelum scope agar ringkasan total/aktif/tidak aktif tetap terlihat.
  - `bulkGraduate`: mengubah registrasi `Aktif` dari siswa terpilih menjadi `Lulus`; validasi `student_ids`; admin sekolah dibatasi ke registrasi pada sekolah yang dapat diakses.
  - `store`: membuat master siswa + registrasi dalam satu transaksi; tahun masuk otomatis dari tahun pelajaran aktif (ditolak jika belum ada); upload foto ke disk `public`; menyimpan data ayah/ibu, alamat, dan biografi orang tua; NIS diubah ke huruf besar; menangani duplikat NIS lewat `UniqueConstraintViolationException`.
  - `update`: memperbarui identitas dan foto (foto lama dihapus saat diganti).
  - `destroy`: soft delete dengan penjagaan registrasi aktif dan anggota kelas.
  - `import`: memakai `App\Imports\StudentsImport`.
- `app/Http/Controllers/Admin/StudentRegistrationController.php`
  - `index`: daftar registrasi siswa, form sekolah/tahun/status.
  - `available`: pencarian JSON siswa yang sudah ada dan belum terdaftar di sekolah tujuan (maksimal 20 hasil) untuk fitur Tarik Siswa; hasil admin sekolah dibatasi ke sekolah yang dapat diakses.
  - `store`/`update`: transaksi registrasi + penonaktifan registrasi aktif lain menjadi `Pindah`; `store` memakai tahun pelajaran aktif secara otomatis, sedangkan `update` mempertahankan/mengizinkan perubahan tahun masuk lama.
  - `destroy`: penjagaan registrasi aktif dan anggota kelas.
- `app/Http/Controllers/Admin/ClassMemberController.php`
  - `index`: anggota kelas, pencarian nama/NIS/NISN, pagination 10, stats total/laki-laki/perempuan.
  - `available`: endpoint JSON pencarian siswa yang memenuhi syarat (registrasi aktif di sekolah kelas, belum punya kelas pada tahun tersebut, maksimal 20 hasil).
  - `store`: menambah anggota dengan `academic_year_id` dari kelas; menangani duplikat lewat unique constraint.
  - `destroy`: mengeluarkan anggota; menolak anggota yang bukan milik kelas pada route.
- Form request: `StoreStudentRequest`, `UpdateStudentRequest`, `StoreStudentRegistrationRequest`, `UpdateStudentRegistrationRequest`, `ImportStudentsRequest`, `BulkGraduateStudentsRequest`, `StoreClassMemberRequest`.
- `app/Imports/StudentsImport.php`: heading `nis`, `nama`, `jenis_kelamin` (wajib) serta `nisn`, `tempat_lahir`, `tanggal_lahir` (opsional), ditambah kolom orang tua opsional (`nama_ayah`, `pekerjaan_ayah`, `no_hp_ayah`, `nama_ibu`, `pekerjaan_ibu`, `no_hp_ibu`, `alamat_orang_tua`, `biografi_orang_tua`); normalisasi gender (L/P/Laki-laki/Perempuan), parsing tanggal Excel/serial, validasi per baris, tahun masuk otomatis dari tahun aktif, ringkasan baris diimpor/dilewati.
- Akses: administrator, admin sekolah, dan kepala sekolah dapat melihat; hanya administrator dan admin sekolah yang dapat mengelola. Guru biasa ditolak (403). Admin sekolah dibatasi ke sekolah yang dapat diakses.
- Factory/seeder: `StudentFactory`, `StudentRegistrationFactory`, `ClassMemberFactory`, `StudentSeeder` (dipanggil dari `DatabaseSeeder`).

Routes:

- `GET admin/students` → `admin.students.index` (semua siswa, tetap tersedia untuk URL langsung).
- `GET admin/students/aktif` → `admin.students.active` (hanya registrasi aktif).
- `GET admin/students/alumni` → `admin.students.alumni` (tanpa registrasi aktif).
- `PATCH admin/students/bulk-graduate` → `admin.students.bulk-graduate` (luluskan registrasi aktif siswa terpilih).
- `GET admin/students/pull/available` → `admin.student-registrations.available` (pencarian siswa untuk fitur Tarik Siswa).
- `POST admin/students` → `admin.students.store`.
- `POST admin/students/import` → `admin.students.import`.
- `PUT/PATCH admin/students/{student}` → `admin.students.update`.
- `DELETE admin/students/{student}` → `admin.students.destroy`.
- `GET/POST admin/students/{student}/registrations` → `admin.student-registrations.index/store`.
- `PUT/PATCH admin/students/{student}/registrations/{registration}` → `admin.student-registrations.update`.
- `DELETE admin/students/{student}/registrations/{registration}` → `admin.student-registrations.destroy`.
- `GET admin/classes/{schoolClass}/students` → `admin.class-members.index`.
- `GET admin/classes/{schoolClass}/students/available` → `admin.class-members.available`.
- `POST admin/classes/{schoolClass}/students` → `admin.class-members.store`.
- `DELETE admin/classes/{schoolClass}/students/{classMember}` → `admin.class-members.destroy`.

Frontend:

- `resources/js/Pages/Students/Index.jsx` — daftar siswa dengan scope **Siswa Aktif** / **Alumni / Non Aktif**, stats, pencarian debounce, filter sekolah/kelas/status/jenis kelamin, tombol Import, **Tarik Siswa**, dan Tambah Siswa, bulk pilih + **Luluskan** pada scope aktif, modal edit/hapus, toast.
- `resources/js/Pages/Students/Components/PullStudentModal.jsx` — modal Tarik Siswa: pilih sekolah tujuan, cari siswa yang sudah ada (nama/NISN/NIS), pilih siswa, isi NIS baru, lalu daftarkan dengan status `Aktif`.
- `resources/js/Pages/Students/Components/BulkGraduateModal.jsx` — konfirmasi bulk luluskan untuk siswa terpilih.
- `resources/js/Pages/Students/Components/StudentModal.jsx` — form identitas, data ayah/ibu + alamat + biografi orang tua, registrasi (create) atau identitas saja (edit), upload dan preview foto; tahun masuk ditampilkan otomatis dari tahun aktif.
- `resources/js/Pages/Students/Components/ImportStudentsModal.jsx` — pilih sekolah, unggah XLSX/XLS/CSV, petunjuk format kolom (termasuk kolom orang tua); tahun masuk otomatis dari tahun aktif.
- `resources/js/Pages/Students/Components/StudentTable.jsx` — NISN, siswa (foto/avatar), sekolah, kelas aktif, status, aksi edit/hapus/registrasi; mendukung checkbox seleksi pada scope Siswa Aktif.
- `resources/js/Pages/Students/Registrations.jsx` + `Components/RegistrationModal.jsx` — halaman kelola registrasi per siswa; registrasi baru otomatis memakai tahun aktif, tahun masuk hanya dapat diubah saat mengedit registrasi lama.
- `resources/js/Pages/ClassMembers/Index.jsx`, `Components/ClassMemberTable.jsx`, `Components/AddClassMemberModal.jsx` — halaman anggota kelas dengan pencarian siswa via endpoint `available`.
- `resources/js/Pages/Classes/Components/ClassTable.jsx` — kolom jumlah siswa dan tombol **Siswa** (ikon `groups`) → `onOpenStudents(item)`; handler di `Classes/Index.jsx` menuju halaman anggota kelas.
- Menu sidebar **Siswa** berupa dropdown dengan submenu **Siswa Aktif** (`admin.students.active`) dan **Alumni / Non Aktif** (`admin.students.alumni`).

Catatan:

- Foto siswa memerlukan `php artisan storage:link` agar dapat diakses dari `public/storage`.
- Test: `tests/Feature/Admin/StudentControllerTest.php` (21 test), `StudentRegistrationControllerTest.php` (14 test), `ClassMemberControllerTest.php` (13 test). Total suite: **208 test, 1.057 assertion**.

---

## 4. Status Modul

| Modul | Status | Keterangan |
|---|---|---|
| Layout admin | Selesai | Sidebar, header, toast, responsive |
| Shared UI | Selesai | Modal, Input, Select, Button, Table, Pagination, Textarea, RowActions, Badge, Card, DeleteConfirmation. |
| Autentikasi | **Selesai & terhubung DB** | Login, logout, rate limiting, role-based access, `is_active` cek, `auth` middleware pada semua route admin |
| Tahun Pelajaran | **Selesai & terhubung DB** | CRUD, generate otomatis, toggle status via tabel, search, pagination, validasi, test |
| Jenis Ujian | **Selesai & terhubung DB** | CRUD, toggle status, search, pagination, validasi, test |
| Sesi | **Selesai & terhubung DB** | CRUD, toggle status, search, pagination, validasi, test |
| Ruangan | **Selesai & terhubung DB** | CRUD, toggle status, search, pagination, validasi, test |
| Kelas | **Selesai & terhubung DB** | CRUD, toggle status, search, pagination, validasi, test, terhubung sekolah + tahun pelajaran; akses admin/admin sekolah, kepala sekolah read-only; tahun pelajaran otomatis dari tahun aktif |
| Pembelajaran | **Selesai & terhubung DB** | Penentuan guru pengampuh mata pelajaran per kelas, terhubung kelas/tahun pelajaran/mata pelajaran/guru, tombol di tabel Kelas → halaman pembelajaran |
| Siswa | **Selesai & terhubung DB** | Master siswa + registrasi sekolah + anggota kelas; CRUD, import Excel/CSV, foto, satu registrasi aktif, filter sekolah/kelas/status, akses per sekolah |
| Dashboard/Home | Mock | Belum mengambil data nyata |
| Monitoring Ujian | Mock | Komponen sudah ada, data masih statis |
| Sekolah | Belum | Route belum ada |
| Jenis Penilaian | Belum | Route belum ada |
| Mata Pelajaran | Belum | Route belum ada |
| Paket Ujian | Belum | Route belum ada |
| Laporan | Belum | Route belum ada |
| Pengaturan | Belum | Route belum ada |
| Autentikasi/Login | Belum | `auth.user` masih `null`, route `logout` belum ada |

Menu sidebar yang belum memiliki route akan tampil nonaktif karena `navRoute()` mengembalikan `#`.

---

## 5. Hal Penting untuk Sesi Berikutnya

### 5.1 Ziggy

- Ziggy diinisialisasi di `resources/js/app.jsx` dari:

```js
const sharedProps = props.initialPage?.props ?? props;
initZiggy(sharedProps.routes, sharedProps.location);
```

- `route(name)` mengembalikan `#` jika Ziggy belum siap atau route tidak ditemukan.
- Jangan mengandalkan shared props di `props.routes`; Inertia v3 memakai `initialPage.props`.

### 5.2 Flash Toast

- `HandleInertiaRequests` membagikan:

```php
'flash' => [
    'toast' => $request->session()->get('toast'),
],
```

- Controller mengirim flash dengan bentuk:

```php
[
    'type' => 'success',
    'title' => 'Berhasil Ditambahkan',
    'description' => '...',
]
```

- Halaman Tahun Pelajaran memakai `useRef` agar toast yang sama tidak tampil dua kali.

### 5.3 Database & Testing

- Database lokal: SQLite, sudah dimigrasi dan di-seed.
- Test memakai SQLite in-memory.
- Jika ingin reset data lokal:

```bash
php artisan migrate:fresh --seed
```

- Data lokal yang diharapkan setelah seed: 16 tahun pelajaran, aktif `20241`.

### 5.4 Frontend

- Setelah mengubah file JSX/CSS, jalankan:

```bash
npm run build
```

- Untuk development:

```bash
composer run dev
```

atau jalankan `php artisan serve` dan `npm run dev` terpisah.

### 5.5 Verifikasi Browser Terakhir

Sudah diuji dengan headless Chrome + CDP:

- Halaman memuat 5 dari 16 data.
- Pencarian `20241` menghasilkan 1 baris.
- Create `2026/2027` genap → kode `20262`, toast muncul.
- Edit semester ke ganjil → kode menjadi `20261`, toast muncul.
- Delete → data hilang, toast muncul.
- Validasi `2024/2026` → error tampil, modal tetap terbuka.
- Tidak ada error console.
- Data uji sudah dihapus; database kembali ke kondisi sebelum uji (saat itu 16 record).

### 5.6 Verifikasi Generate Otomatis

Diuji pada 9 November 2026 (periode `2026/2027` Ganjil sudah ada):

- `POST /admin/academic-years/generate` → `302` kembali ke index.
- Tidak ada record baru; jumlah record tidak berubah.
- Toast **“Data Sudah Ada”** muncul.
- Test otomatis mencakup batas Juni (Genap), Juli (Ganjil), dan kasus data sudah ada.

### 5.7 Verifikasi Toggle Status

Diuji pada 9 November 2026 terhadap record `20261`:

- `PATCH /admin/academic-years/{id}/status` → `302` kembali ke index.
- Klik pertama menonaktifkan (`is_active = 0`), toast **“Tahun Pelajaran Dinonaktifkan”**.
- Klik kedua mengaktifkan kembali (`is_active = 1`), toast **“Tahun Pelajaran Diaktifkan”**.
- Status akhir dikembalikan seperti semula; tidak ada data yang dibuat atau dihapus.
- Test otomatis mencakup aktivasi (menonaktifkan tahun lain) dan deaktivasi.

---

## 6. Rekomendasi Tahap Berikutnya

Prioritas yang disarankan:

1. **Autentikasi**
   - Login/logout.
   - Middleware `auth` pada grup `admin`.
   - Isi `auth.user`.
   - Aktifkan tombol Keluar di sidebar.

2. **Master Data berikutnya**
   - Jenis Ujian (`exam-types`) — UI mock sudah ada; lanjutkan migration, model, controller, dan test.
   - Sekolah (`schools`).
   - Jenis Penilaian (`assessment-types`).
   - Mata Pelajaran (`subjects`).
   - Untuk setiap modul: migration, model, factory, seeder, form request, controller di `Admin`, route resource, halaman Inertia, test.

3. **Monitoring Ujian**
   - Ganti data mock di `resources/js/Pages/Monitoring/Index.jsx`.
   - Komponen berada di `resources/js/Features/Monitoring/Components`.
   - Tambahkan controller + query agregat.
   - Pertimbangkan polling Inertia (`usePoll`) untuk data live.

4. **Dashboard**
   - Ganti statistik mock di `resources/js/Pages/Home.jsx` dengan data nyata.

5. **Modul lanjutan**
   - Paket Ujian, Laporan, Pengaturan.

6. **Penyempurnaan Tahun Pelajaran**
   - Pertimbangkan konfirmasi khusus saat menghapus tahun yang sedang aktif.
   - Pertimbangkan penanganan halaman kosong setelah menghapus data terakhir di halaman terakhir.
   - Pertimbangkan indikator loading saat pencarian/pagination.

---

## 7. File Penting

### Backend

```text
app/Enums/AcademicYearSemester.php
app/Enums/UserRole.php
app/Models/AcademicYear.php
app/Models/SchoolClass.php
app/Models/User.php
app/Http/Controllers/Auth/LoginController.php
app/Http/Controllers/Auth/LogoutController.php
app/Http/Controllers/Auth/PasswordUpdateController.php
app/Http/Controllers/Auth/ProfileController.php
app/Http/Controllers/Admin/AcademicYearController.php
app/Http/Controllers/Admin/ClassController.php
app/Http/Controllers/Admin/SubjectTeacherController.php
app/Http/Controllers/Admin/ExamTypeController.php
app/Http/Controllers/Admin/RoomController.php
app/Http/Controllers/Admin/SessionController.php
app/Http/Requests/Admin/StoreAcademicYearRequest.php
app/Http/Requests/Admin/UpdateAcademicYearRequest.php
app/Http/Requests/Admin/StoreExamTypeRequest.php
app/Http/Requests/Admin/UpdateExamTypeRequest.php
app/Http/Requests/Admin/StoreSessionRequest.php
app/Http/Requests/Admin/UpdateSessionRequest.php
app/Http/Requests/Admin/StoreRoomRequest.php
app/Http/Requests/Admin/UpdateRoomRequest.php
app/Http/Requests/Admin/StoreClassRequest.php
app/Http/Requests/Admin/UpdateClassRequest.php
app/Http/Middleware/Authenticate.php
app/Http/Middleware/HandleInertiaRequests.php
routes/web.php
```

### Database

```text
database/migrations/0001_01_01_000000_create_users_table.php
database/migrations/2026_11_09_155210_add_username_role_is_active_to_users_table.php
database/migrations/2026_11_09_102846_create_academic_years_table.php
database/migrations/2026_11_09_140311_create_exam_types_table.php
database/migrations/2026_11_09_151606_create_sessions_table.php
database/migrations/2026_11_09_151607_create_rooms_table.php
database/migrations/2026_11_10_065118_create_classes_table.php
database/factories/AcademicYearFactory.php
database/factories/ExamTypeFactory.php
database/factories/SessionFactory.php
database/factories/RoomFactory.php
database/factories/SchoolClassFactory.php
database/factories/UserFactory.php
database/seeders/AcademicYearSeeder.php
database/seeders/ExamTypeSeeder.php
database/seeders/SessionSeeder.php
database/seeders/RoomSeeder.php
database/seeders/ClassSeeder.php
database/seeders/DatabaseSeeder.php
```

### Frontend

```text
resources/js/app.jsx
resources/js/Layouts/AppLayout.jsx
resources/js/Layouts/AuthLayout.jsx
resources/js/Components/Layout/Sidebar.jsx
resources/js/Components/Layout/Header.jsx
resources/js/Components/Layout/ToastContainer.jsx
resources/js/Components/UI/*
resources/js/Hooks/useToast.js
resources/js/Utils/route.js
resources/js/Pages/Home.jsx
resources/js/Pages/Monitoring/Index.jsx
resources/js/Pages/Auth/Login.jsx
resources/js/Pages/Auth/Profile.jsx
resources/js/Pages/Auth/UpdatePassword.jsx
resources/js/Pages/AcademicYears/Index.jsx
resources/js/Pages/AcademicYears/Components/AcademicYearModal.jsx
resources/js/Pages/AcademicYears/Components/AcademicYearTable.jsx
resources/js/Pages/Classes/Index.jsx
resources/js/Pages/Classes/Components/ClassModal.jsx
resources/js/Pages/Classes/Components/ClassTable.jsx
resources/js/Pages/SubjectTeachers/Index.jsx
resources/js/Pages/SubjectTeachers/Components/SubjectTeacherModal.jsx
resources/js/Pages/SubjectTeachers/Components/SubjectTeacherTable.jsx
resources/js/Pages/ExamTypes/Index.jsx
resources/js/Pages/ExamTypes/Components/ExamTypeModal.jsx
resources/js/Pages/ExamTypes/Components/ExamTypeTable.jsx
resources/js/Components/UI/Textarea.jsx
resources/js/Components/UI/RowActions.jsx
resources/js/Features/Monitoring/Components/*
```

### Test

```text
tests/Feature/AcademicYearTest.php
tests/Feature/Admin/AcademicYearControllerTest.php
tests/Feature/Admin/ExamTypeControllerTest.php
tests/Feature/Admin/SessionControllerTest.php
tests/Feature/Admin/RoomControllerTest.php
tests/Feature/Admin/ClassControllerTest.php
tests/Feature/ClassTest.php
tests/Feature/SubjectTeacherTest.php
tests/Feature/Admin/SubjectTeacherControllerTest.php
```

### Dokumentasi

```text
AGENTS.md
docs/DESIGN.md
docs/RESUME.md
```

---

## 8. Perintah Cepat

```bash
# Development
composer run dev

# Build frontend
npm run build

# Test
php artisan test --compact

# Format PHP
vendor/bin/pint --format agent app/Http/Controllers/Admin/AcademicYearController.php

# Reset database + seed
php artisan migrate:fresh --seed

# Cek route
php artisan route:list --except-vendor
```

---

## 9. Ringkasan Satu Paragraf

Modul Autentikasi, Tahun Pelajaran, Jenis Ujian, Sesi, Ruangan, Sekolah, Kelas, Pembelajaran, dan Siswa sudah selesai dari database sampai UI: schema, generate kode otomatis, aturan satu atau lebih aktif, login/logout dengan rate limiting, dropdown profil (profil, ganti kata sandi, keluar), halaman profil, ganti kata sandi, role (administrator/guru), cek `is_active`, seeder, controller dan form request di folder `Admin`, route resource dalam grup `admin` (dilindungi middleware `auth`), serta halaman Inertia yang mendukung pencarian, pagination, create, edit, delete, toggle status, validasi server, dan toast. Kelas terhubung ke sekolah dan tahun pelajaran dengan aturan kode unik per sekolah serta nama unik per tahun pelajaran; tahun pelajaran pada create diambil otomatis dari tahun pelajaran aktif, dan modul Kelas hanya dapat diakses oleh administrator, admin sekolah, dan kepala sekolah (kepala sekolah read-only). Modul Pembelajaran menentukan guru pengampuh mata pelajaran per kelas, terhubung ke kelas, tahun pelajaran, mata pelajaran, dan guru, dengan tombol **Pembelajaran** di tabel Kelas yang menuju halaman pembelajaran (kode kelas dan tahun diambil dari kelas). Modul Siswa memisahkan identitas master (`students`, termasuk data ayah/ibu, alamat, dan biografi orang tua), registrasi sekolah (`student_registrations`, NIS per sekolah, satu registrasi aktif per siswa, tahun masuk otomatis dari tahun aktif), dan anggota kelas per tahun pelajaran (`class_members`), lengkap dengan CRUD, import XLSX/XLS/CSV, foto, filter, submenu sidebar **Siswa Aktif** dan **Alumni / Non Aktif**, bulk **Luluskan**, fitur **Tarik Siswa** untuk registrasi siswa lama ke sekolah baru, serta tombol **Siswa** di tabel Kelas; akses dibatasi per sekolah dengan kepala sekolah read-only. Seluruh test lulus dan build frontend berhasil. Pekerjaan berikutnya adalah master data lain (Paket Ujian, Laporan), dan mengganti data mock pada Dashboard serta Monitoring dengan data nyata.
