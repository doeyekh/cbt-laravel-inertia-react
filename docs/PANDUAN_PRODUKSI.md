# Panduan Operasional Production — CBT Laravel Inertia React

Tanggal: 20 November 2026

Panduan ini menjelaskan cara aman mengubah atau menambah fitur ketika aplikasi sudah berjalan dengan stack production:

```text
Browser → Nginx (:80) → PHP-FPM (:9000) → MySQL 8.4
                    ↘ aset React dari public/build (vite build)
```

File stack production:

| File | Fungsi |
|---|---|
| `docker-compose.prod.yml` | Service `db`, `app` (PHP-FPM + queue worker), `nginx` |
| `Dockerfile.prod` | Image PHP 8.3 FPM + Composer + Node untuk build |
| `docker-entrypoint-prod.sh` | Install dependency, build aset, migrate, seed awal, cache Laravel |
| `nginx.prod.conf` | Server block Laravel di Nginx |
| `.env` | Environment aplikasi dan nilai interpolasi Compose |

> Semua perintah dijalankan dari `C:\EYARUS-CBT`.

---

## 1. Aturan cepat: perubahan → perintah

| Jenis perubahan | Perintah wajib |
|---|---|
| Controller, model, service, request, middleware | `docker compose -f docker-compose.prod.yml restart app` |
| Route baru / route diubah | `docker compose -f docker-compose.prod.yml restart app` |
| Migration baru | `php artisan migrate --force` atau `restart app` |
| Seeder baru / data baru | `php artisan db:seed --class=NamaSeeder --force` |
| Job / listener / event queue | `docker compose -f docker-compose.prod.yml restart app` |
| React, CSS, Tailwind, ikon | `npm run build` |
| Package npm baru | `npm install ...` lalu `npm ci --include=dev` lalu `npm run build` |
| Package Composer baru | `composer require ...` lalu `composer install --no-dev ...` lalu `restart app` |
| Ekstensi PHP baru | Ubah `Dockerfile.prod`, `build app`, lalu `up -d --force-recreate app` |
| `.env` / `APP_URL` / kredensial DB | Ubah `.env`, lalu `up -d --force-recreate app` |
| `nginx.prod.conf` | `docker compose -f docker-compose.prod.yml exec nginx nginx -s reload` |
| `docker-compose.prod.yml` / `Dockerfile.prod` | `build app` jika perlu, lalu `up -d` / `up -d --force-recreate app` |

> `restart app` menjalankan ulang entrypoint production, sehingga cache Laravel dibersihkan dan dibuat ulang.
> `up -d --force-recreate app` diperlukan jika nilai environment dari Compose berubah, karena `restart` tidak membaca ulang file Compose.

---

## 2. Sebelum mulai mengubah fitur

### 2.1 Cek kondisi stack

```powershell
cd C:\EYARUS-CBT

docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs --tail=50 app nginx
```

Pastikan `cbt-db-prod` healthy, `cbt-app-prod` Up, dan `cbt-nginx` Up.

### 2.2 Simpan perubahan kode dulu

```powershell
git status --short
git diff --name-only
git add .
git commit -m "Menambah fitur ..."
```

Commit dulu memudahkan rollback jika deploy bermasalah.

### 2.3 Jalankan test sebelum deploy

Image production memakai `composer install --no-dev`, jadi PHPUnit tidak tersedia di container production. Jalankan test dari stack dev atau CI sebelum memindahkan perubahan ke production.

```powershell
# Di stack dev/CI
docker compose exec -T app php artisan test --compact
```

Jika mengubah PHP, jalankan juga formatter:

```powershell
vendor/bin/pint --dirty --format agent
```

### 2.4 Backup database

```powershell
New-Item -ItemType Directory -Force .\backups | Out-Null

1. docker compose -f docker-compose.prod.yml exec -T db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --databases cbt --result-file=/tmp/cbt-backup.sql'

2. docker compose -f docker-compose.prod.yml exec -T db sh -c 'ls -lh /tmp/cbt-backup.sql && head -n 25 /tmp/cbt-backup.sql && tail -n 10 /tmp/cbt-backup.sql'

docker compose -f docker-compose.prod.yml cp db:/tmp/cbt-backup.sql ./backups/cbt-backup.sql
```

> Jangan simpan file backup di git. Simpan di folder lokal/CI yang aman.

#### Salin Database Backup ke folder Backup
```powershell
docker compose -f docker-compose.prod.yml cp db:/tmp/cbt-backup.sql ./cbt-backup.sql
```

### 2.5 Aktifkan maintenance mode untuk perubahan besar

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan down --retry=60
```

Nanti jangan lupa dimatikan lagi:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan up
```

---

## 3. Perubahan backend Laravel

### 3.1 Controller, model, service, request, middleware

Buat file lewat Artisan di dalam container agar mengikuti konvensi Laravel:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan make:controller ContohController --no-interaction

docker compose -f docker-compose.prod.yml exec -T app php artisan make:model Contoh -mf --no-interaction

docker compose -f docker-compose.prod.yml exec -T app php artisan make:request StoreContohRequest --no-interaction
```

Edit file yang bersangkutan di `C:\EYARUS-CBT`, lalu refresh cache dan worker:

```powershell
docker compose -f docker-compose.prod.yml restart app
```

`restart app` juga membuat ulang `config:cache`, `route:cache`, `view:cache`, dan `event:cache`.

### 3.2 Route baru

Edit `routes/web.php` atau file route lain, lalu:

```powershell
docker compose -f docker-compose.prod.yml restart app

docker compose -f docker-compose.prod.yml exec -T app php artisan route:list --path=contoh
```

> Hindari closure di route jika ingin `route:cache` aktif. Gunakan controller.

### 3.3 Migration baru

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan make:migration add_field_to_exam_participants_table --no-interaction
```

Edit file migration di `database/migrations`, lalu jalankan:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

docker compose -f docker-compose.prod.yml exec -T app php artisan migrate:status
```

Alternatif: cukup `restart app`, karena entrypoint production otomatis menjalankan `migrate --force`.

Rollback satu migration terakhir:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate:rollback --step=1 --force
```

> **Jangan** menjalankan `migrate:fresh`, `db:wipe`, atau `docker compose down -v` di production kecuali Anda memang ingin menghapus seluruh data.

### 3.4 Seeder baru

Entrypoint production hanya menjalankan seeder otomatis ketika tabel `users` kosong. Jadi seeder baru harus dijalankan manual.

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan make:seeder NamaSeeder --no-interaction
```

Edit seeder, lalu:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan db:seed --class=NamaSeeder --force
```

Gunakan `firstOrCreate()` atau `updateOrCreate()` agar seeder aman dijalankan ulang.

### 3.5 Job, listener, dan queue

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan make:job ProsesNilai --no-interaction
docker compose -f docker-compose.prod.yml exec -T app php artisan make:listener KirimNotifikasi --no-interaction
```

Setelah kode job/listener berubah, **restart container app**:

```powershell
docker compose -f docker-compose.prod.yml restart app
```

> Jangan hanya mengandalkan `queue:restart`. Stack ini tidak memakai supervisor/systemd, sehingga worker akan berhenti dan tidak otomatis hidup lagi. Gunakan `restart app`.

Cek antrean gagal:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan queue:failed
docker compose -f docker-compose.prod.yml exec -T app php artisan queue:retry all
docker compose -f docker-compose.prod.yml exec -T app php artisan queue:flush
```

---

## 4. Perubahan frontend React / CSS

Di production tidak ada Vite dev server dan tidak ada HMR. Setiap perubahan frontend harus di-build ulang.

```powershell
docker compose -f docker-compose.prod.yml exec -T app npm run build

docker compose -f docker-compose.prod.yml exec -T app rm -f public/hot
```

Cek hasil build:

```powershell
docker compose -f docker-compose.prod.yml exec -T app cat public/build/manifest.json | Select-Object -First 1
```

Atau dari host:

```powershell
Test-Path public\build\manifest.json
Test-Path public\hot
```

Kondisi yang benar: `manifest.json` ada, `public/hot` tidak ada.

### Menambah package npm

```powershell
docker compose -f docker-compose.prod.yml exec -T app npm install nama-package
# atau untuk dev dependency:
docker compose -f docker-compose.prod.yml exec -T app npm install -D nama-package

docker compose -f docker-compose.prod.yml exec -T app npm ci --include=dev --no-audit --no-fund
docker compose -f docker-compose.prod.yml exec -T app npm run build
```

`package.json` dan `package-lock.json` berada di bind mount, jadi perubahan langsung tersimpan di host.

> Jika browser masih memuat file lama, lakukan hard refresh. Nama file di `/build/assets/` berubah sesuai hash Vite, jadi ini biasanya tidak masalah.

---

## 5. Perubahan dependency PHP

```powershell
docker compose -f docker-compose.prod.yml exec -T app composer require vendor/package --no-interaction

docker compose -f docker-compose.prod.yml exec -T app composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

docker compose -f docker-compose.prod.yml restart app
```

Jika package baru membutuhkan ekstensi PHP tambahan:

1. Tambahkan ekstensi di `Dockerfile.prod` pada bagian `docker-php-ext-install`.
2. Build ulang image:
   ```powershell
   docker compose -f docker-compose.prod.yml build app
   docker compose -f docker-compose.prod.yml up -d --force-recreate app
   ```

> Jangan mengedit langsung folder `vendor/`. Selalu lewat Composer.

---

## 6. Perubahan environment dan konfigurasi

### 6.1 Ubah `.env`

Contoh mengubah alamat LAN:

```powershell
notepad .env
```

```text
APP_URL=http://192.168.8.116
```

Lalu recreate container agar Compose membaca ulang interpolasi `${APP_URL:-...}`:

```powershell
docker compose -f docker-compose.prod.yml up -d --force-recreate app
```

Cek:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan config:show app.url
```

Variabel yang sudah di-set eksplisit di `docker-compose.prod.yml` tidak bisa diubah hanya lewat `.env`, antara lain:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql`
- `DB_HOST=db`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`

Untuk mengubah salah satu dari daftar itu, edit `docker-compose.prod.yml` dulu, lalu:

```powershell
docker compose -f docker-compose.prod.yml up -d --force-recreate app
```

> Jangan menghapus `APP_KEY` dari `.env`. Jika hilang, buat ulang dengan `php artisan key:generate --force` lalu recreate container.

### 6.2 Perubahan konfigurasi Laravel tanpa recreate

Jika hanya ingin menyegarkan cache:

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan view:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan event:cache
```

Cara paling aman tetap `restart app`, karena entrypoint melakukan semua itu otomatis.

---

## 7. Perubahan Nginx, Dockerfile, dan Compose

### Nginx

`nginx.prod.conf` di-mount langsung ke container, jadi cukup reload:

```powershell
docker compose -f docker-compose.prod.yml exec nginx nginx -s reload
```

Jika gagal, cek konfigurasi dan restart:

```powershell
docker compose -f docker-compose.prod.yml restart nginx
```

### Dockerfile.prod

```powershell
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml up -d --force-recreate app
```

### docker-compose.prod.yml

```powershell
docker compose -f docker-compose.prod.yml up -d
```

Gunakan `--force-recreate app` jika perubahan menyangkut environment atau command.

---

## 8. Checklist deploy fitur baru

Contoh alur lengkap:

```powershell
cd C:\EYARUS-CBT

# 1. Cek status
docker compose -f docker-compose.prod.yml ps

# 2. Backup database
New-Item -ItemType Directory -Force .\backups | Out-Null
docker compose -f docker-compose.prod.yml exec -T db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --databases cbt --result-file=/tmp/cbt-backup.sql'
docker compose -f docker-compose.prod.yml cp db:/tmp/cbt-backup.sql ./backups/cbt-backup.sql

# 3. Maintenance
docker compose -f docker-compose.prod.yml exec -T app php artisan down --retry=60

# 4. Lakukan perubahan kode di sini
#    - backend: controller/model/route/migration/seeder/job
#    - frontend: resources/js/**, CSS, Tailwind

# 5. Build frontend
docker compose -f docker-compose.prod.yml exec -T app npm run build
docker compose -f docker-compose.prod.yml exec -T app rm -f public/hot

# 6. Database
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
# Jalankan hanya jika ada seeder/data baru:
docker compose -f docker-compose.prod.yml exec -T app php artisan db:seed --class=NamaSeeder --force

# 7. Refresh cache + worker PHP-FPM
docker compose -f docker-compose.prod.yml restart app

# 8. Selesai maintenance
docker compose -f docker-compose.prod.yml exec -T app php artisan up
```

---

## 9. Verifikasi setelah deploy

```powershell
docker compose -f docker-compose.prod.yml ps

docker compose -f docker-compose.prod.yml logs --tail=100 app nginx

docker compose -f docker-compose.prod.yml exec -T app php artisan config:show app.env
docker compose -f docker-compose.prod.yml exec -T app php artisan config:show app.debug
docker compose -f docker-compose.prod.yml exec -T app php artisan config:show app.url
```

Cek HTTP:

```powershell
foreach ($u in @('http://localhost/', 'http://localhost/admin/login', 'http://192.168.8.116/', 'http://192.168.8.116/admin/login')) {
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 30
        Write-Output "$u -> $($r.StatusCode)"
    } catch {
        Write-Output "$u -> ERROR: $($_.Exception.Message)"
    }
}
```

Yang diharapkan:

- Semua URL → `200`
- `app.env` → `production`
- `app.debug` → `false`
- Tidak ada `502 Bad Gateway` di log Nginx
- Browser memuat aset dari `/build/assets/app-....js`, bukan `:5173`
- `public/hot` tidak ada

---

## 10. Restore database darurat

Gunakan hanya jika benar-benar diperlukan. Ini akan menimpa data saat ini.

```powershell
docker compose -f docker-compose.prod.yml exec -T app php artisan down --retry=60

docker compose -f docker-compose.prod.yml cp ./backups/cbt-backup.sql db:/tmp/cbt-backup.sql

docker compose -f docker-compose.prod.yml exec -T db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" < /tmp/cbt-backup.sql'

docker compose -f docker-compose.prod.yml restart app

docker compose -f docker-compose.prod.yml exec -T app php artisan up
```

---

## 11. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| `502 Bad Gateway` | PHP-FPM belum siap / restart | `docker compose -f docker-compose.prod.yml restart app` |
| Perubahan PHP tidak muncul | Cache config/route/view belum di-refresh | `restart app` |
| Route baru 404 | `route:cache` lama | `restart app`, cek `route:list` |
| Aset React tidak berubah | Lupa `npm run build` | Jalankan `npm run build` dan hard refresh |
| Masih memuat `:5173` | `public/hot` tertinggal | `rm -f public/hot`, lalu build ulang |
| `Class not found` | Dependency Composer belum terinstall di volume | `composer install --no-dev ...` lalu `restart app` |
| Migration gagal | Nama index terlalu panjang / constraint bentrok | Perbaiki migration, lalu `migrate:rollback` jika perlu |
| Job tidak diproses | Worker ikut mati saat perubahan kode | `restart app`, cek `queue:failed` |
| Port 80 bentrok | Aplikasi lain memakai port 80 | Stop aplikasi tersebut atau ubah mapping di Compose |
| `APP_URL` tidak berubah di aplikasi | Compose environment belum di-refresh | `up -d --force-recreate app` |

---

## 12. Kembali ke mode dev sementara

Jika ingin hot reload lagi:

```powershell
docker compose -f docker-compose.prod.yml down
docker compose up -d --build
```

Vite akan membuat ulang `public/hot`. Data MySQL tetap aman karena memakai volume `db-data` yang sama.

Kembali ke production:

```powershell
docker compose down
docker compose -f docker-compose.prod.yml up -d
```

Pastikan `public/hot` tidak ada setelah kembali ke production; entrypoint production akan menghapusnya otomatis.

---

## 13. Perintah harian yang paling sering dipakai

```powershell
# Status
docker compose -f docker-compose.prod.yml ps

# Log realtime
docker compose -f docker-compose.prod.yml logs -f --tail=100 app nginx

# Artisan
docker compose -f docker-compose.prod.yml exec app php artisan <perintah>

# Masuk shell container
docker compose -f docker-compose.prod.yml exec app sh

# Build frontend setelah edit React/CSS
docker compose -f docker-compose.prod.yml exec -T app npm run build

# Restart setelah edit backend
docker compose -f docker-compose.prod.yml restart app

# Recreate setelah ubah .env / Compose
docker compose -f docker-compose.prod.yml up -d --force-recreate app

# Matikan production
docker compose -f docker-compose.prod.yml down
```
