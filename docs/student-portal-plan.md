# Rencana Pengerjaan: Portal Siswa Setelah Login

## 1. Tujuan

Menyediakan halaman portal siswa yang otomatis terbuka setelah login berhasil, berisi
identitas peserta, penempatan sesi/ruang, dan agenda ujian siswa. Portal ini menggantikan
perilaku saat ini yang hanya kembali ke halaman login setelah autentikasi.

## 2. Ruang Lingkup

### Termasuk

- Middleware penjaga sesi siswa.
- Route portal siswa dan redirect setelah login.
- Controller dashboard siswa beserta query data peserta dan jadwal.
- Layout dan halaman dashboard siswa (React JSX).
- Komponen agenda ujian dan kartu identitas peserta.
- Test fitur untuk akses, data, dan regresi login.

### Tidak termasuk (tahap berikutnya)

- Pengerjaan soal/ujian (belum ada tabel attempt/jawaban).
- Penyimpanan dan penampilan nilai hasil ujian.
- Notifikasi real-time dari proktor.
- Perubahan skema database.

## 3. Alur Pengguna

1. Siswa membuka `/login`, mengisi username dan kata sandi.
2. Autentikasi berhasil, sesi menyimpan `student_participant_id` dan `student_name`.
3. Siswa diarahkan ke `/siswa` (dashboard portal).
4. Dashboard menampilkan:
   - sapaan dan tanggal hari ini,
   - ringkasan jumlah ujian (total, berlangsung, akan datang, selesai),
   - agenda ujian kelas pada tahun pelajaran peserta,
   - kartu identitas dan penempatan peserta.
5. Tombol keluar mengakhiri sesi dan kembali ke halaman login dengan toast.

## 4. Rute dan Middleware

| Method | Path | Name | Middleware | Keterangan |
|---|---|---|---|---|
| GET | `/login` | `login` | guest | Halaman login siswa (sudah ada) |
| POST | `/login` | `student.login.store` | guest | Proses login (sudah ada) |
| POST | `/logout` | `student.logout` | guest | Logout siswa (sudah ada) |
| GET | `/siswa` | `student.dashboard` | `student.auth` | Dashboard portal siswa |

- Middleware baru: `App\Http\Middleware\EnsureStudentIsAuthenticated`.
- Alias `student.auth` didaftarkan di `bootstrap/app.php`.
- Tamu diarahkan ke `student.login` disertai toast informasi.
- Jika `student_participant_id` menunjuk data yang sudah tidak ada, sesi dibersihkan dan
  pengguna dikembalikan ke halaman login.

## 5. Backend

### Controller

`App\Http\Controllers\Student\StudentDashboardController@index`

1. Ambil peserta dari sesi beserta relasi `student`, `school`, `academicYear`, `session`, `room`.
2. Ambil kelas siswa dari `class_members` berdasarkan `student_id` dan `academic_year_id` peserta.
3. Ambil jadwal ujian kelas tersebut dengan filter:
   - `status` jadwal = `aktif`,
   - paket ujian milik sekolah dan tahun pelajaran peserta,
   - status paket ujian = `aktif`.
4. Kirim props Inertia `participant`, `exams`, dan `systemInfo`.

### Bentuk Props

```text
participant: {
  name, nisn, username, participant_number,
  school, class_name, academic_year, semester,
  session, room
}

exams: [
  {
    id, subject, package, exam_type,
    date, open_time, close_time, duration_minutes,
    session, room
  }
]
```

Status ujian (berlangsung/akan datang/selesai) dihitung di browser memakai jam siswa agar
konsisten dengan jam yang ditampilkan pada halaman login, tanpa bergantung pada timezone server.

## 6. Frontend

### Struktur File

```text
resources/js/
├── Layouts/
│   └── StudentLayout.jsx          # Header, brand, tombol keluar, footer
├── Utils/
│   └── examSchedule.js            # Status & format tanggal/jam ujian
└── Pages/Student/
    ├── Dashboard.jsx              # Komposisi halaman
    └── Components/
        ├── ExamAgenda.jsx         # Ringkasan + daftar agenda ujian
        └── ParticipantIdentity.jsx# Kartu identitas & penempatan
```

### Aturan Desain (docs/design.md)

- Tanpa garis 1px; hierarki memakai pergeseran surface tier.
- Headline Noto Serif, body Inter, label Public Sans.
- Primary hanya untuk aksi/link/focus; tertiary untuk aksen arsip (tahun pelajaran, status).
- CTA memakai gradient; aksi sekunder memakai surface tinggi + teks primary.
- Kedalaman dari tonal layering, bukan shadow (shadow hanya untuk elemen mengambang).
- Whitespace sebagai struktur, minimal satu sudut membulat pada setiap elemen.
- Mobile-first dan responsif.

## 7. Pengujian

Di `tests/Feature/Student/StudentDashboardTest.php`:

1. Tamu diarahkan ke halaman login siswa.
2. Siswa dengan sesi valid dapat membuka dashboard dan melihat identitasnya.
3. Agenda hanya memuat jadwal kelas, sekolah, dan tahun pelajaran peserta yang aktif.
4. Sesi dibersihkan bila peserta tidak ditemukan.
5. Regresi login: login berhasil diarahkan ke `student.dashboard`.
6. Regresi logout: sesi siswa terhapus.

Verifikasi akhir: `vendor/bin/pint --dirty`, `php artisan test`, dan `npm run build`.

## 8. Tahapan Pengerjaan

- [x] Tulis dokumen rencana ini.
- [x] Tambah middleware `student.auth` dan daftarkan aliasnya.
- [x] Buat `StudentDashboardController`.
- [x] Tambah route `/siswa` dan ubah redirect sukses login.
- [x] Buat layout, halaman, dan komponen portal siswa.
- [x] Tambah utility status jadwal.
- [x] Tulis dan jalankan test fitur.
- [x] Jalankan Pint dan build frontend.

## 9. Keputusan Teknis

- **Tanpa tabel baru**: portal hanya membaca data yang sudah ada.
- **Status ujian dihitung di klien**: menghindari asumsi timezone `UTC` pada konfigurasi server.
- **Kelas siswa dari `class_members`**: sumber tunggal keanggotaan kelas per tahun pelajaran.
- **Agenda memakai jadwal, bukan paket ujian**: jadwal sudah memuat mapel, sesi, ruang, dan waktu.
- **Satu halaman + dua komponen**: cukup terpisah untuk keterbacaan tanpa over-engineering.

## 10. Kriteria Selesai

- Login berhasil langsung membuka portal siswa.
- Portal menampilkan identitas, penempatan, dan agenda sesuai data peserta.
- Halaman mengikuti aturan desain dan responsif di mobile.
- Tamu tidak bisa mengakses portal tanpa sesi siswa.
- Test fitur dan build frontend berhasil.
