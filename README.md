# CBT Laravel Inertia React

CBT adalah aplikasi ujian berbasis komputer untuk mengelola master data sekolah, ujian, bank soal, peserta, jadwal, pelaksanaan ujian, dan hasil ujian. Aplikasi menyediakan portal admin serta portal siswa.

## Teknologi

- Laravel 13
- PHP 8.3
- Inertia.js 3 dan React 19
- Vite 8
- Tailwind CSS 4
- SQLite untuk pengembangan lokal

## Fitur

- Manajemen sekolah, tahun pelajaran, kelas, siswa, guru, mata pelajaran, ruangan, dan sesi.
- Manajemen jenis ujian, jenis penilaian, bank soal, paket ujian, jadwal, dan peserta.
- Import soal serta import data siswa.
- Pengerjaan ujian oleh siswa, token akses, jawaban ujian, pengumpulan, dan hasil.
- Monitoring ujian, penilaian esai, reset percobaan, pencetakan kartu peserta, dan pengelolaan akun.

## Persyaratan

- PHP 8.3
- Composer
- Node.js dan npm
- Ekstensi PHP yang dibutuhkan Laravel
- Laragon direkomendasikan untuk penggunaan di Windows

## Instalasi Singkat

1. Masuk ke direktori proyek:

   ```powershell
   cd C:\laragon\www\cbt
   ```

2. Salin konfigurasi dan buat file database SQLite:

   ```powershell
   Copy-Item .env.example .env
   New-Item database\database.sqlite -ItemType File -Force
   ```

3. Instal dependensi backend dan frontend:

   ```powershell
   composer install
   npm install
   ```

4. Buat application key dan jalankan migrasi beserta data contoh:

   ```powershell
   php artisan key:generate
   php artisan migrate:fresh --seed
   ```

5. Jalankan aplikasi:

   ```powershell
   composer run dev
   ```

6. Buka aplikasi melalui:

   - Portal admin: `http://localhost:8000/admin/login`
   - Portal siswa: `http://localhost:8000`

## Akun Contoh

Data contoh dibuat oleh seeder:

| Peran | Username | Password |
|---|---|---|
| Super Admin | `superadmin` | `password` |
| Admin Sekolah | `adminsekolah` | `password` |

Hapus atau ubah akun contoh sebelum menggunakan aplikasi di lingkungan produksi.

## Perintah Pengembangan

```powershell
# Jalankan server Laravel, queue, dan Vite
composer run dev

# Jalankan build produksi frontend
npm run build

# Jalankan test
php artisan test --compact

# Reset dan isi ulang database
php artisan migrate:fresh --seed

# Lihat daftar route
php artisan route:list --except-vendor
```
