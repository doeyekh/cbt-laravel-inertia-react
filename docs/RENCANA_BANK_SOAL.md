# Rencana Modul Bank Soal

**Status:** Diperbarui setelah revisi desain (11 November 2026)
**Modul:** Pengelolaan Ujian → Bank Soal
**Tanggal:** 11 November 2026

---

## 1. Tujuan

Modul **Bank Soal** manages **paket bank soal**: kerangka yang menentukan berapa jumlah soal
yang dibutuhkan untuk tiap tipe, dan kelas mana saja yang memakai paket tersebut.

Modul ini **tidak** menyimpan butir soal. Pembuatan butir soal adalah modul terpisah
(nanti menyusul), yang akan mengisi paket bank soal sesuai komposisi yang ditetapkan.

### Pemisahan Modul

| Modul | Isi | Status |
|---|---|---|
| Bank Soal | Paket bank soal: komposisi jumlah soal per tipe + kelas yang berlaku | Selesai |
| Soal | Butir soal (pilihan ganda, pilihan kompleks, menjodohkan, isian, esai) di dalam paket | Selesai (halaman input soal) |

---

## 2. Tipe Soal

| Tipe | Nilai enum | Keterangan |
|---|---|---|
| Pilihan Ganda | `pilihan_ganda` | 1 jawaban benar |
| Pilihan Ganda Kompleks | `pilihan_ganda_kompleks` | ≥ 2 jawaban benar, berbobot |
| Menjodohkan | `menjodohkan` | Pasangan kiri–kanan |
| Isian | `isian_singkat` | Jawaban yang diterima |
| Esai | `esai` | Jawaban bebas, dinilai manual |

Enum `QuestionType` Holds the list above plus `countColumn()` to map the type to the
count column on `question_banks`.

> Tidak ada lagi tingkat kesulitan pada soal maupun bank soal.

---

## 3. Aturan Cakupan Data

- Paket bank soal **terikat sekolah** (`school_id`) dan **mata pelajaran** (`subject_id`).
- **Berlaku untuk beberapa kelas**: relasi many-to-many lewat `question_bank_class`.
- **Jenis ujian opsional** (`exam_type_id`), mengikuti `exam_types`; jenis ujian yang
  dipilih harus berstatus aktif.
- Komposisi: 5 kolom jumlah (0–500 per tipe); total harus lebih dari 0.
- Sekolah tidak dapat diubah setelah bank soal dibuat.
- Status aktif per paket (`is_active`).

---

## 4. Skema Database

### `question_banks`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | PK |
| `school_id` | FK | `schools`, cascade |
| `subject_id` | FK | `subjects`, cascade |
| `exam_type_id` | FK nullable | `exam_types`, set null |
| `code` | string(30) | unik per sekolah |
| `name` | string(150) | |
| `description` | string(500) nullable | |
| `pilihan_ganda_count` | unsignedSmallInteger | default 0 |
| `pilihan_ganda_kompleks_count` | unsignedSmallInteger | default 0 |
| `menjodohkan_count` | unsignedSmallInteger | default 0 |
| `isian_count` | unsignedSmallInteger | default 0 |
| `esai_count` | unsignedSmallInteger | default 0 |
| `pilihan_ganda_score` | decimal(5,2) | default 50, bobot per soal PG |
| `pilihan_ganda_kompleks_score` | decimal(5,2) | default 30, bobot per soal PK |
| `menjodohkan_score` | decimal(5,2) | default 40, bobot per soal MJ |
| `isian_score` | decimal(5,2) | default 20, bobot per soal IS |
| `esai_score` | decimal(5,2) | default 10, bobot per soal ES |
| `pilihan_ganda_option_count` | unsignedSmallInteger | default 4, rentang 3-5 |
| `pilihan_ganda_kompleks_option_count` | unsignedSmallInteger | default 4, rentang 3-5 |
| `is_active` | boolean | default true |
| timestamps | | |

Unik: (`school_id`, `code`).

### `question_bank_class`
`question_bank_id` (FK cascade), `class_id` (FK cascade), timestamps.
Unik: (`question_bank_id`, `class_id`).

### `questions` (disiapkan untuk modul Soal)
Teks soal (HTML), tipe, **nomor soal** (unik per bank soal + tipe), poin (diambil dari
bobot per tipe pada bank soal), kunci jawaban (opsi/pasangan/isian), media gambar/audio
(soal dan opsi), dan `question_bank_id` sebagai tautan ke paket bank soal. Kolom
`difficulty` dan `explanation` sudah dihapus.

---

## 5. Relasi Model

- `QuestionBank`: `school()`, `subject()`, `assessmentType()`, `classes()` (belongsToMany),
  `questions()`, accessor `total_question_count`, `composition`, `scores`, `option_counts`,
  dan `total_score`.
- `Question`: `questionBank()`.
- Inverse: `School::questionBanks()`, `Subject::questionBanks()`.

---

## 6. Validasi

- `school_id` wajib dan harus dalam akses pengguna.
- `subject_id` wajib.
- `exam_type_id` opsional, harus milik sekolah terpilih dan berstatus aktif.
- `code` wajib, maksimal 30 karakter, unik per sekolah.
- `name` wajib, maksimal 150 karakter.
- Kelima jumlah soal wajib diisi, rentang 0–500; total harus lebih dari 0.
- Kelima bobot per tipe wajib diisi, rentang 0–999,99.
- `pilihan_ganda_option_count` dan `pilihan_ganda_kompleks_option_count` wajib diisi,
  rentang 3–5.
- `class_ids` minimal 1 kelas dan semua kelas harus milik sekolah terpilih.

---

## 7. Hak Akses

| Peran | Akses |
|---|---|
| Administrator | Mengelola semua sekolah |
| School Admin | Mengelola sekolah yang dapat diakses |
| Headmaster | Hanya melihat |
| Guru | `403` |

---

## 8. UI

- `resources/js/Pages/QuestionBanks/Index.jsx`
  - Statistik: total bank soal, aktif, nonaktif, total soal dirancang.
  - Filter: pencarian, sekolah, mata pelajaran, status.
  - Tabel: kode/nama, sekolah, mapel, jenis ujian, kelas, komposisi (PG/PK/MJ/IS/ES),
    total soal, total bobot, status.
  - Tombol **Soal** pada setiap baris untuk masuk ke halaman input soal.
  - Modal tambah/edit: sekolah, mapel, kode, nama, jenis ujian (hanya jenis ujian
    aktif), deskripsi, tabel komposisi (jumlah soal + bobot per tipe),
    jumlah pilihan jawaban PG/PK (select 3–5), pemilih kelas (multi-select dengan
    pencarian), status aktif.
- `resources/js/Pages/Questions/Index.jsx` (halaman input soal)
  - Header konteks bank soal: kode, mapel, sekolah, kelas, tombol kembali.
  - Panel komposisi: jumlah soal terisi per tipe vs kebutuhan, dengan progress bar,
    serta bobot per soal dari bank soal.
  - **Tab per tipe soal**: Pilihan Ganda, Pilihan Ganda Kompleks, Menjodohkan,
    Isian, Esai — lengkap dengan jumlah soal pada tiap tab (filter di server).
    Tab "Semua Soal" tidak digunakan; tipe pertama pada komposisi menjadi tab aktif.
  - **Strip nomor soal** sesuai jumlah komposisi bank soal: nomor hijau = soal sudah
    terisi, nomor merah = soal yang sedang dibuka, abu-abu = belum diisi. Klik nomor
    untuk membuka soal tersebut.
  - **Form tambah/edit langsung di halaman** (bukan modal), dua kolom seperti
   acellular:
    - Kiri: editor teks kaya soal + input media gambar/audio.
    - Kanan: pilihan jawaban berbentuk akordeon (opsi terpilih yang terbuka,
      memakai radio untuk Pilihan Ganda dan checkbox untuk Pilihan Ganda Kompleks),
      pasangan untuk Menjodohkan, isian untuk Isian, catatan untuk Esai.
  - Tombol **Kosongkan Soal** menghapus seluruh soal bank soal ini sekaligus,
    termasuk media gambar dan audio pada soal maupun pilihannya.
  - **Poin soal tidak diinput** di form soal: bobot diambil dari bank soal sesuai
    tipe dan ditampilkan sebagai badge read-only.
  - **Jumlah pilihan jawaban** mengikuti pengaturan bank soal (3–5, label A–E);
    form tidak menambah atau menghapus opsi.
  - Editor teks kaya mendukung **rumus matematika (LaTeX)**: tombol `∑ Rumus`
    menampilkan dialog kode LaTeX + pratinjau, hasil sisipan disimpan sebagai
    `<span class="math-node" data-latex="...">` dan dirender dengan MathJax.
  - Strip nomor soal memakai **warna primary** untuk soal yang sudah terisi.
- `resources/js/Pages/Questions/Preview.jsx` (halaman lihat soal, read-only)
  - Diakses dari tombol ikon `visibility` pada kolom Aksi tabel bank soal.
  - menampilkan seluruh soal bank soal dikelompokkan per tipe (PG/PK/MJ/IS/ES)
    dalam satu halaman, tanpa paginasi.
  - Kunci jawaban ditampilkan dan ditandai **Jawaban Benar** pada opsi yang benar.
  - Menampilkan nomor soal, bobot, status aktif, teks soal, media soal, serta
    media pada tiap opsi; formula LaTeX dirender dengan MathJax.
  - Halaman bersifat **read-only**: tidak ada aksi tambah, ubah, atau hapus.
  - HTML teks soal disanitasi sebelum dirender (buang `script`/`style`/`iframe`
    dan atribut `on*`).
- `resources/js/Pages/Questions/Preview.jsx` (halaman lihat soal, read-only)
  - Diakses dari tombol ikon `visibility` pada kolom Aksi tabel bank soal.
  - menampilkan seluruh soal bank soal dikelompokkan per tipe (PG/PK/MJ/IS/ES)
    dalam satu halaman, tanpa paginasi.
  - Kunci jawaban ditampilkan dan ditandai **Jawaban Benar** pada opsi yang benar.
  - Menampilkan nomor soal, bobot, status aktif, teks soal, media soal, serta
    media pada tiap opsi; formula LaTeX dirender dengan MathJax.
  - Halaman bersifat **read-only**: tidak ada aksi tambah, ubah, atau hapus.
  - Tombol **Cetak Soal** mengunduh lembar soal PDF (mode kertas).
- `resources/js/Pages/Questions/Import.jsx` (halaman import soal)
  - Diakses dari tombol ikon `upload_file` pada kolom Aksi tabel bank soal.
  - Menampilkan format kolom berkas, aturan pemeriksaan, komposisi bank soal, serta
    form unggah berkas XLSX/XLS/CSV (maksimal 5 MB).
  - Tombol **Unduh Template** menghasilkan berkas Excel berisi header kolom dan
    contoh baris untuk setiap tipe soal.
- `app/Imports/QuestionsImport.php` (proses import soal)
  - Satu baris = satu soal; kolom: `tipe`, `nomor`, `soal`, `opsi_a`…`opsi_e`,
    `kunci`, `pasangan`, `isian`.
  - `tipe` menerima nilai enum maupun singkat (PG/PK/MJ/IS/ES).
  - `pasangan` berformat `kiri=kanan;kiri=kanan`, `isian` dipisahkan titik koma.
  - Jumlah pilihan jawaban harus sesuai pengaturan bank soal, bobot soal
    mengikuti bobot per tipe pada bank soal, dan nomor soal harus unik per tipe.
  - Baris tidak valid dilewati tanpa membatalkan baris lain.
- `resources/views/question/print.blade.php` (lembar soal PDF)
  - Dihasilkan server-side dengan **DomPDF** (`dompdf/dompdf`), A4 potret.
  - Header ringkas: nama sekolah, jenis ujian · mata pelajaran · kelas, judul
    lembar, nama dan kode bank soal, tanggal, jumlah soal, dan total poin,
    lalu petunjuk pengisian.
  - Seluruh soal dicetak sebagai **satu daftar berurutan dengan penomoran
    berurutan (1, 2, 3, ...)** tanpa pengelompokan per tipe; tipe soal
    ditampilkan sebagai label kecil di sebelah nomor.
  - **Tanpa kunci jawaban**.
  - Soal pilihan ganda kompleks memakai kotak centang pada tiap opsi agar siswa
    dapat menandai lebih dari satu jawaban; tipe lain tanpa penanda.
  - Menjodohkan memakai tabel (kiri | jawaban | kanan), isian memakai garis
    jawaban, esai memakai lima garis isian.
  - Formula LaTeX dicetak sebagai kode LaTeX (DomPDF tidak mendukung MathJax).
  - Footer setiap halaman memuat kode bank soal dan nomor halaman.
- Sidebar: **Bank Soal** di grup *Pengelolaan Ujian*.

### Route Soal

| Method | URL | Nama |
|---|---|---|
| GET | `admin/question-banks/{questionBank}/questions` | `admin.question-banks.questions.index` |
| GET | `admin/question-banks/{questionBank}/questions/preview` | `admin.question-banks.questions.preview` |
| GET | `admin/question-banks/{questionBank}/questions/print` | `admin.question-banks.questions.print` |
| GET | `admin/question-banks/{questionBank}/questions/import` | `admin.question-banks.questions.import` |
| POST | `admin/question-banks/{questionBank}/questions/import` | `admin.question-banks.questions.import.store` |
| GET | `admin/question-banks/{questionBank}/questions/import/template` | `admin.question-banks.questions.import.template` |
| POST | `admin/question-banks/{questionBank}/questions` | `admin.question-banks.questions.store` |
| PUT | `admin/question-banks/{questionBank}/questions/{question}` | `admin.question-banks.questions.update` |
| DELETE | `admin/question-banks/{questionBank}/questions/{question}` | `admin.question-banks.questions.destroy` |
| DELETE | `admin/question-banks/{questionBank}/questions` | `admin.question-banks.questions.destroy-all` |

---

## 9. Pengujian

- Index: pagination, filter, statistik, scoping sekolah, guru `403`.
- Store: bank soal tersimpan lengkap dengan kelasnya, kode duplikat ditolak,
  kelas dari sekolah lain ditolak, komposisi kosong ditolak.
- Update: komposisi berubah dan kelas tersinkron.
- Destroy: bank soal dan relasi kelas terhapus.
- Soal: daftar soal per bank soal, penyimpanan tiap tipe soal beserta kunci jawabannya,
  tipe di luar komposisi bank soal ditolak, soal dari bank lain tidak dapat diubah.

---

## 10. Urutan Implementasi

1. Migrasi `question_banks`, `question_bank_class`, dan penyesuaian tabel `questions`.
2. Enum `QuestionType`, model `QuestionBank`, factory, seeder.
3. Form request, controller, route.
4. Halaman React + komponen + sidebar.
5. Feature test.
6. Pint, `php artisan test`, `npm run build`.
