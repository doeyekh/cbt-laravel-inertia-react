# Analisis & Rekomendasi Perbaikan — CBT EduTest

**Tanggal analisis:** 18 November 2026
**Cakupan:** seluruh modul `app/`, `routes/web.php`, `resources/js`, `database/migrations`, `tests/`
**Metode:** pembacaan kode statis + verifikasi alur request → response + menjalankan test suite
**Status test saat ini:** `485 passed`, `2823 assertions` (`php artisan test --compact`)
**Sifat dokumen:** analisis dan rekomendasi.
**Status perbaikan:** **P0-1 sampai P0-6 dan P1-1 sampai P1-9 sudah diperbaiki** — lihat status pada setiap temuan.

---

## 0. Ringkasan Eksekutif

Secara struktur, proyek ini sudah rapi: arsitektur feature-based, Form Request, factory, policy, dan **485 test backend yang lulus**. Fondasi CRUD admin (sekolah, kelas, peserta, bank soal, paket ujian, jadwal) sudah solid.

Masalah utama justru ada di **mesin ujian (exam engine)** — bagian yang paling kritikal untuk CBT:

| # | Temuan | Dampak | Prioritas |
|---|---|---|---|
| 1 | **Kunci jawaban ikut terkirim ke browser siswa** (`is_correct`, `question_blanks.answers`) | Siswa bisa melihat jawaban benar dari DevTools/Network tab → ujian tidak valid | **Diperbaiki** |
| 2 | **Batas waktu ujian tidak ditegakkan di server**; `time_spent_seconds` dipercaya dari client | Siswa bisa mengulang waktu, atau terus menjawab sampai `close_time` walau durasi habis | **Diperbaiki** |
| 3 | **`submitted_at` diisi di setiap autosave**, `started_at` ikut di-reset | Tidak ada status "sudah dikumpulkan", jawaban masih bisa diubah setelah submit | **Diperbaiki** |
| 4 | **Flag "ragu-ragu" menimpa jawaban** (`answer = 'flagged'`) | Jawaban siswa hilang saat ditandai ragu-ragu, dan siswa bisa terkunci tidak bisa submit | **Diperbaiki** |
| 5 | **Login siswa tanpa throttle, fallback password plaintext, tanpa `session()->regenerate()`, username tidak unik global** | Brute force PIN, session fixation, salah ambil akun antar sekolah/tahun | **Diperbaiki** |
| 6 | **`ExamController@show` tidak memvalidasi kepemilikan jadwal** | Siswa bisa membuka soal kelas/ruangan lain dalam paket yang sama | **Diperbaiki** |
| 7 | **Belum ada submit final, penilaian, dan halaman hasil** | Tidak ada skor, tidak ada rekap, monitoring tidak punya data progres | **Diperbaiki** |
| 8 | **Autosave tanpa debounce, kirim seluruh peta jawaban setiap ketikan** | Load server tinggi saat 30–40 siswa mengetik esai bersamaan | **Diperbaiki** |

Temuan P0 dan P1 pada mesin ujian sudah diperbaiki. Uji beban dan modul laporan tetap menjadi pekerjaan lanjutan.

---

## 1. Gambaran Proyek

### 1.1 Stack terpasang

| Komponen | Versi |
|---|---|
| PHP | 8.3 |
| Laravel | 13.x |
| Inertia (server / client) | 3.7 / 3.11 |
| React | 19.3 (JSX, tanpa TypeScript) |
| Tailwind CSS | 4.x |
| Vite | 8.x |
| Database lokal / test | SQLite / SQLite in-memory |
| Timezone aplikasi | `Asia/Jakarta` |
| Excel import | `maatwebsite/excel` 4.x |
| PDF kartu peserta | `dompdf/dompdf` 3.x |

### 1.2 Modul yang sudah berjalan

- Auth admin (login, logout, profil, ganti password) + throttle 5x.
- Master data: tahun pelajaran, sekolah, mata pelajaran, jenis ujian, jenis penilaian, sesi, ruangan, kelas, anggota kelas, pembelajaran.
- Data siswa + registrasi + import + bulk graduate.
- Bank soal + soal (5 tipe: pilihan ganda, PG kompleks, menjodohkan, isian, esai) + import + preview + print.
- Paket ujian + jadwal ujian + bulk schedule + token akses paket (HMAC-SHA256).
- Peserta ujian + generate kredensial + reset password + kartu peserta (PDF).
- Portal siswa: login, dashboard agenda, verifikasi token, halaman pengerjaan, autosave, submit final, penilaian, dan hasil.
- Monitoring ujian: peserta terjadwal, progres attempt, koneksi, flag, penilaian esai, dan polling.

### 1.3 Yang masih mock / belum ada

- **Dashboard admin** (`Pages/Home.jsx`) belum memakai data nyata.
- **Laporan ujian** belum tersedia.

### 1.4 Hal yang sudah baik (pertahankan)

- Pemisahan `Concerns/` untuk logika bersama (authorize, present, sync) — pola bagus, lanjutkan.
- Token ujian tidak disimpan plaintext (hanya hash HMAC), verifikasi `hash_equals`, dan ada rate limit percobaan token.
- Form Request lengkap dengan custom validation concern.
- Policy untuk `School` dan `Subject`.
- Factory + seeder + 485 test → modal untuk refactor dengan aman.
- Semua query admin sudah dibatasi `accessibleSchoolIds()` (multi-sekolah).

---

## 2. Temuan Kritis (P0)

### P0-1 — Kunci jawaban bocor ke browser siswa — **Diperbaiki**

**Lokasi:**
- `app/Http/Controllers/Student/ExamController.php:52-57`
- `app/Models/QuestionOption.php:12` (`is_correct` ada di `#[Fillable]`, tanpa `#[Hidden]`)
- `app/Models/QuestionBlank.php:12` (`answers` berisi daftar jawaban diterima)

**Bukti:**

```php
// ExamController@show
$questions = Question::query()
    ->where('question_bank_id', $questionBank->id)
    ->where('is_active', true)
    ->with(['options', 'pairs', 'blanks']) // ← seluruh atribut ikut
    ->orderBy('number')
    ->get();

return Inertia::render('Student/ExamPage', [
    'questions' => $questions, // ← payload JSON berisi is_correct
    // ...
]);
```

Karena `QuestionOption` tidak punya `$hidden`, payload Inertia berisi:

```json
{
  "id": 91,
  "label": "A",
  "text": "Rp 2.800.000,00",
  "is_correct": true,
  "score": 5
}
```

Dan `QuestionBlank` mengirim `answers: ["jakarta", "Jakarta"]` — jawaban isian singkat bocor semua.

**Dampak:** siswa cukup membuka Network tab / `__inertia_props__` untuk melihat kunci jawaban. Ini membatalkan seluruh validitas ujian.

**Rekomendasi (wajib):**
1. Buat Resource/transformer khusus siswa yang hanya mengirim field yang aman:
   - opsi: `id`, `label`, `text`, `order`, `image_path`, `audio_path` (tanpa `is_correct`, tanpa `score`).
   - pairs: hanya `id`, `left_text`, `right_text`, `order`.
   - blanks: hanya `id`, `order` (jangan kirim `answers`).
2. Tambahkan `#[Hidden(['is_correct', 'score'])]` pada `QuestionOption` sebagai lapisan pertahanan kedua.
3. Tambahkan test regresi yang memastikan payload **tidak** memuat `is_correct`/`answers`.

```php
// app/Http/Resources/StudentExamQuestionResource.php (usulan)
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'number' => $this->number,
        'type' => $this->type,          // enum QuestionType
        'body' => $this->body,
        'score' => $this->score,        // boleh: bobot soal memang ditampilkan
        'image_path' => $this->image_path,
        'audio_path' => $this->audio_path,
        'options' => $this->options->map->only(['id', 'label', 'text', 'order', 'image_path', 'audio_path']),
        'pairs' => $this->pairs->map->only(['id', 'left_text', 'right_text', 'order']),
        'blanks' => $this->blanks->map->only(['id', 'order']),
    ];
}
```

---

### P0-2 — Durasi ujian tidak ditegakkan di server — **Diperbaiki**

**Lokasi:**
- `app/Http/Controllers/Student/ExamAnswerController.php:90-102` (menyimpan `time_spent_seconds` dari client apa adanya)
- `app/Http/Controllers/Student/ExamAnswerController.php:160-175` (`scheduleIsOpen()` hanya cek jendela jam jadwal, bukan durasi pengerjaan)
- `resources/js/Features/Exam/Hooks/useExamSession.js:115-118`

**Bukti:**

```php
'time_spent_seconds' => $request->integer('time_spent_seconds'),
```

Tidak ada `max()` dengan nilai lama, tidak ada perhitungan dari `started_at`, tidak ada penolakan bila `time_spent_seconds >= duration_minutes * 60`.

**Dampak:**
- Siswa cukup mengirim `time_spent_seconds: 0` setiap autosave → timer bisa "di-reset" terus-menerus. Bahkan tanpa niat curang, refresh halaman setelah mengubah nilai bisa memberi tambahan waktu.
- Penolakan hanya terjadi saat `close_time` jadwal tercapai. Jika jadwal dibuka 07:00–11:30 dengan durasi 90 menit, siswa tetap bisa menjawab sampai 11:30 (≈4,5 jam).

**Rekomendasi:**
1. Tambah kolom `deadline_at` di `exam_attempts`, diisi **sekali** saat attempt pertama dibuat:

```php
$attempt = ExamAttempt::firstOrCreate(
    [
        'exam_participant_id' => $participant->id,
        'exam_schedule_id' => $examSchedule->id,
    ],
    [
        'exam_package_id' => $examPackage->id,
        'duration_minutes' => $examSchedule->duration_minutes,
        'started_at' => now(),
        'deadline_at' => now()->addMinutes($examSchedule->duration_minutes),
        'status' => 'in_progress',
        'time_spent_seconds' => 0,
    ],
);
```

2. Tolak semua penyimpanan setelah `deadline_at` (kecuali request submit final):

```php
if ($attempt->deadline_at->isPast()) {
    $this->autoSubmit($attempt);

    return response()->json([
        'message' => 'Waktu ujian telah habis. Jawaban tidak dapat diubah lagi.',
        'status' => 'submitted',
    ], 409);
}
```

3. Abaikan `time_spent_seconds` dari client; hitung di server:

```php
'time_spent_seconds' => max(
    $attempt->time_spent_seconds,
    (int) $attempt->started_at->diffInSeconds(now()),
),
```

4. Kembalikan `deadline_at` + `remaining_seconds` pada setiap respons autosave agar timer frontend selalu sinkron dengan server (sumber kebenaran tunggal).

> Catatan: kolom `deadline_at` saat ini **tidak ada**. Lihat §6 untuk migrasi.

---

### P0-3 — `started_at` dan `submitted_at` ditimpa setiap autosave — **Diperbaiki**

**Lokasi:** `app/Http/Controllers/Student/ExamAnswerController.php:90-102`

```php
$attempt = ExamAttempt::updateOrCreate(
    [...],
    [
        'exam_package_id' => $examPackage->id,
        'duration_minutes' => $examSchedule->duration_minutes,
        'time_spent_seconds' => $request->integer('time_spent_seconds'),
        'started_at' => now(),      // ← reset setiap autosave
        'submitted_at' => now(),    // ← attempt selalu "sudah dikumpulkan"
    ]
);
```

**Dampak:**
- Semua attempt terlihat `submitted_at != null` → tidak mungkin membedakan yang sedang mengerjakan dan yang sudah selesai.
- `started_at` tidak lagi merepresentasikan waktu mulai → perhitungan durasi berbasis server mustahil.
- Ketika halaman hasil/monitoring dibuat nanti, datanya sudah tercemar.
- Resume timer (`ExamController@show:70-79`) terpaksa memakai `updated_at` sebagai proxy — rapuh, karena setiap autosave menggesernya.

**Rekomendasi:** gunakan `firstOrCreate` untuk field yang bersifat sekali-set, dan **hanya** isi `submitted_at` di endpoint submit.

```php
$attempt = ExamAttempt::firstOrCreate([...], [...]); // started_at, deadline_at, duration_minutes

$attempt->update([
    'time_spent_seconds' => $calculatedSeconds,
]);
```

Tambahkan `status` (`in_progress` / `submitted` / `graded`) sebagai sumber kebenaran, jangan mengandalkan `submitted_at` saja.

---

### P0-4 — Flag "ragu-ragu" merusak jawaban siswa — **Diperbaiki**

**Lokasi:**
- `resources/js/Features/Exam/Components/ExamWorkspace.jsx:28-31`
- `resources/js/Features/Exam/Hooks/useExamSession.js:68-96`
- `resources/js/Features/Exam/Components/QuestionSheet.jsx:126`

**Bukti:**

```js
const handleFlagToggle = () => {
    const isFlagged = answers[currentQuestionId] === 'flagged';
    handleAnswerChange(currentQuestionId, isFlagged ? null : 'flagged');
};
```

Penanda ragu-ragu disimpan **di slot jawaban yang sama**. Konsekuensi berantai:

1. Siswa menjawab "A", lalu mencentang ragu-ragu → jawaban "A" **hilang**, diganti string `'flagged'`.
2. `'flagged'` ikut tersimpan ke database sebagai `exam_answers.answer = 'flagged'`.
3. `answerStats` menghitung soal ber-flag sebagai **tidak dijawab**.
4. Di soal terakhir, tombol "Selesaikan Ujian" **disabled** bila masih ada soal belum dijawab (`ExamWorkspace.jsx:26`), sehingga siswa bisa **terkunci tidak bisa submit** hanya karena menandai ragu-ragu.

**Rekomendasi:**
1. Pisahkan state flag dari jawaban: `const [flagged, setFlagged] = useState(new Set())`.
2. Tambah kolom `is_flagged` (boolean) di `exam_answers`.
3. Hitung progres: `answered` berdasarkan isi jawaban, `flagged` berdasarkan `is_flagged`, keduanya independen.
4. Jangan memblokir submit hanya karena ada soal kosong — tampilkan peringatan konfirmasi ("3 soal belum dijawab, tetap kumpulkan?") seperti perilaku CBT pada umumnya.

---

### P0-5 — Keamanan login siswa — **Diperbaiki**

**Lokasi:** `app/Http/Controllers/Auth/StudentLoginController.php`

**Temuan 4-in-1:**

**(a) Fallback password plaintext (baris 69-72)**

```php
if (! empty($participant->password) && Hash::check($password, $participant->password)) {
    $passwordMatches = true;
} elseif ($participant->password_plain === $password) {  // ← perbandingan non-constant-time
    $passwordMatches = true;
}
```

`password_plain` disimpan dengan cast `encrypted` (bisa didekripsi dengan `APP_KEY`). Selama fallback ini ada, password peserta pada praktiknya adalah plaintext. Jika `APP_KEY` bocor/berubah, seluruh kredensial peserta terbaca.

**(b) Tidak ada rate limiting (baris 50-75).** Berbeda dengan login admin yang sudah pakai `RateLimiter` 5x (`LoginController.php:44-56`), login siswa bisa dicoba tanpa batas. Kombinasi username + PIN 8 karakter bisa di-brute force.

**(c) Tidak ada `session()->regenerate()` (baris 90-92).** Hanya `forget()` + `put()`. Rentan session fixation.

**(d) Username tidak unik secara global (baris 56-61).**

```php
$participant = ExamParticipant::query()
    ->where('username', $username)
    ->first();
```

Padahal constraint database hanya unik per `(school_id, academic_year_id, username)` — lihat `database/migrations/2026_11_14_041337_rebuild_exam_participants_without_groups.php`. Jika dua sekolah memakai pola username yang sama (mis. NISN), `first()` bisa mengambil peserta dari sekolah/tahun yang salah.

**Rekomendasi:**
1. Hapus fallback `password_plain`; gunakan `Hash::check()` saja.
2. Tambahkan throttle per `username|ip` (5 percobaan / 60 detik) memakai pola yang sudah ada di `LoginController`.
3. Panggil `$request->session()->regenerate()` setelah login sukses (dan `invalidate()` saat logout).
4. Scope login ke tahun pelajaran aktif + sekolah: tambahkan pilihan/segmentation sekolah, atau gunakan username yang benar-benar unik. Minimal:

```php
$participant = ExamParticipant::query()
    ->where('username', $username)
    ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
    ->first();

if ($participant === null) { /* pesan generik, jangan bedakan username vs password */ }
```

5. Pertimbangkan jangka menengah: pindahkan autentikasi siswa ke **guard Laravel** (`auth:student` dengan provider `exam_participants`) daripada menyimpan `student_participant_id` mentah di session. Ini otomatis memperbaiki regenerate, remember, dan middleware.

6. `password_plain` tetap diperlukan untuk cetak kartu peserta. Alternatif yang lebih aman: simpan PIN **terenkripsi** hanya sementara saat generate, atau sediakan aksi "cetak ulang kartu" yang me-reset PIN — sehingga PIN tidak perlu tersimpan permanen.

---

### P0-6 — `ExamController@show` tanpa cek kepemilikan jadwal — **Diperbaiki**

**Lokasi:** `app/Http/Controllers/Student/ExamController.php:19-57`

`ExamAccessController` dan `ExamAnswerController` sama-sama memanggil `participantMayAccessSchedule()` (cek sekolah, tahun, sesi, ruangan, keanggotaan kelas). **`ExamController@show` tidak.** Ia hanya memeriksa hash token paket di session.

**Dampak:** siswa yang sudah memverifikasi token paket dapat membuka `/siswa/exams/{id}` untuk jadwal kelas/ruangan lain dalam paket yang sama dan membaca seluruh soalnya. Penyimpanan jawaban memang ditolak, tetapi **kebocoran soal tetap terjadi** (dan `ExamPackageStatus`/`ExamScheduleStatus` juga tidak dicek di sini).

**Rekomendasi:** ekstrak logika akses ke satu tempat (lihat P1-5) dan panggil di ketiga controller:

```php
abort_unless($this->examAccess->participantMayAccessSchedule($participant, $examSchedule), 404);
abort_unless($examPackage->status === ExamPackageStatus::Aktif, 403);
abort_unless($examSchedule->status === ExamScheduleStatus::Aktif, 403);
```

---

## 3. Temuan Penting (P1)

Deskripsi di bawah ini mencatat temuan saat analisis awal. Status **Diperbaiki** menandakan implementasi sekarang sudah menutup temuan tersebut.

### P1-1 — Belum ada submit final & penilaian (fitur inti CBT) — **Diperbaiki**

Pada saat analisis awal, "mengumpulkan ujian" hanya = autosave terakhir lalu `window.location.href = dashboard` (`useExamSession.js:105-108, 158-169`). Saat ini submit final, auto-submit ketika waktu habis, penilaian otomatis untuk soal non-esai, penilaian esai manual, dan halaman hasil sudah tersedia.

- status `submitted` yang final (jawaban masih bisa diubah sampai `close_time`);
- endpoint `POST /siswa/exams/{examSchedule}/submit`;
- penyimpanan `is_correct` / skor per jawaban;
- skor attempt (`score`, `max_score`);
- halaman hasil yang menghormati `show_score_to_student` (kolom ini sudah ada di `exam_schedules` tapi tidak pernah dipakai);
- penilaian esai manual oleh admin sekolah.

**Rekomendasi arsitektur penilaian:**

| Tipe soal | Cara menilai |
|---|---|
| `pilihan_ganda` | bandingkan **ID opsi** jawaban dengan `options.is_correct` |
| `pilihan_ganda_kompleks` | bandingkan himpunan ID opsi (skor penuh; opsi: partial credit) |
| `menjodohkan` | bandingkan ID pair kanan per indeks kiri (skor proporsional per pasangan) |
| `isian_singkat` | bandingkan ternormalisasi (trim, lowercase, hapus spasi ganda) terhadap `question_blanks.answers` |
| `esai` | `score_awarded = null` sampai dinilai guru secara manual |

Simpan hasil di `exam_answers.is_correct` + `exam_answers.score_awarded`, agregat di `exam_attempts.score`.

### P1-2 — Jawaban disimpan sebagai **teks opsi**, bukan ID opsi — **Diperbaiki**

**Lokasi:** `resources/js/Features/Exam/Components/QuestionSheet.jsx:168-182, 208-231`

```jsx
const isSelected = currentAnswer === option.text;
// ...
value={option.text}
```

**Dampak:**
- Guru mengedit teks opsi (mis. perbaiki typo) → semua jawaban siswa yang sudah tersimpan tidak lagi cocok.
- Dua opsi dengan teks identik tidak bisa dibedakan.
- Opsi berupa rich text/HTML harus dicocokkan persis (termasuk atribut).
- Penilaian jadi bergantung pada string, bukan relasi.

**Rekomendasi:** kirim dan simpan `option.id` (atau `option.label` yang unik per soal). Ini juga menyederhanakan penilaian di P1-1. Untuk `menjodohkan` sebaiknya gunakan `pair.id` yang sudah dilakukan sekarang agar konsisten.

### P1-3 — Autosave per ketikan, tanpa debounce, mengirim seluruh peta jawaban — **Diperbaiki**

**Lokasi:** `resources/js/Features/Exam/Hooks/useExamSession.js:110-156`

Pada saat analisis awal, setiap `onChange` (termasuk **setiap huruf** pada esai/isian) langsung memicu `POST` yang membawa **semua** jawaban. Autosave sekarang di-debounce, mengirim diff per soal, dan antreannya diserialkan.

Risiko tambahan: request paralel yang tidak berurutan dapat saling menimpa (tidak ada `AbortController`/antrean), dan indikator "Menyimpan..." berkedip terus (`processing` berubah tiap ketikan).

**Rekomendasi:**
1. Debounce 800–1500 ms per soal, simpan hanya **diff** (`{ [questionId]: answer }`).
2. Flush saat: pindah soal, toggle flag, `visibilitychange`, `beforeunload`, dan submit.
3. Batalkan request lama untuk soal yang sama (AbortController) atau gunakan antrean serial.
4. Tambahkan endpoint per soal (`PUT /answers/{question}`) agar payload kecil dan idempoten.

### P1-4 — Jawaban yang dihapus tidak terhapus di database — **Diperbaiki**

**Lokasi:** `app/Http/Controllers/Student/ExamAnswerController.php:106-123`

```php
foreach ($answers as $questionId => $answer) {
    ExamAnswer::updateOrCreate([...], [...]);
}
```

Tidak ada penghapusan untuk jawaban yang dikirim `null`/kosong, dan tidak ada penghapusan untuk soal yang tidak ada di payload. Akibatnya siswa menghapus jawaban esai di layar, tapi setelah refresh teks lama muncul kembali.

**Rekomendasi:** kirim payload eksplisit (mis. `answers: { "12": null }`), lalu:

```php
if ($answer === null || $answer === '' || $answer === []) {
    ExamAnswer::where(...)->delete();
    continue;
}
```

### P1-5 — Logika akses jadwal & resolusi bank soal terduplikasi 3x — **Diperbaiki**

`participantMayAccessSchedule()` dan query `QuestionBank::...->first()` disalin di:

- `app/Http/Controllers/Student/ExamController.php:41-46`
- `app/Http/Controllers/Student/ExamAccessController.php:40-46, 181-217`
- `app/Http/Controllers/Student/ExamAnswerController.php:78-88, 131-158`

`ExamController` bahkan tidak memakai cek aksesnya (P0-6). Ini sudah menyebabkan inkonsistensi.

**Rekomendasi:** buat satu service, mis. `app/Services/ExamScheduleAccessService.php`:

```php
final class ExamScheduleAccessService
{
    public function participantMayAccess(ExamParticipant $participant, ExamSchedule $schedule): bool { /* ... */ }

    public function resolveQuestionBank(ExamSchedule $schedule): ?QuestionBank { /* ... */ }

    public function isOpen(ExamSchedule $schedule): bool { /* jendela + status + tanggal */ }
}
```

### P1-6 — `QuestionBank` dipilih dengan `->first()` (ambigu) — **Diperbaiki**

Paket ujian **tidak menyimpan referensi bank soal**. Bank soal dicari berdasarkan kombinasi `(school_id, academic_year_id, exam_type_id, subject_id)` lalu diambil `first()` tanpa `orderBy`.

**Dampak:** bila ada dua bank soal untuk kombinasi yang sama (mis. paket "UTS Matematika Remedial" dan "UTS Matematika Susulan"), `first()` bisa mengambil bank yang salah secara acak tergantung urutan database.

**Rekomendasi:** tambahkan `question_bank_id` pada `exam_schedules` (atau `exam_packages`) dan pakai relasi eksplisit. Ini juga membuat preview paket ujian di admin akurat.

### P1-7 — `shuffle_questions` / `shuffle_options` diabaikan — **Diperbaiki**

Kolom `exam_schedules.shuffle_questions`, `shuffle_options`, `show_score_to_student` sudah ada (default `true`) dan sudah bisa diatur di UI (`ExamScheduleModal.jsx:18-20`), tetapi:

- `ExamController@show` selalu `orderBy('number')` → urutan soal identik untuk semua siswa;
- opsi jawaban tidak diacak (kecuali pasangan menjodohkan, itu pun diacak **di client** dengan seed `question.id` → deterministik, mudah ditebak).

**Rekomendasi:** terapkan pengacakan **di server** saat menyusun prop, dengan seed per attempt (mis. `seed = attempt.id`) dan kembalikan urutan final ke client. Untuk `shuffle_options`, kirim opsi dalam urutan teracak sehingga label A/B/C/D mengikuti urutan tampil.

### P1-8 — Monitoring masih 100% statis — **Diperbaiki**

Pada saat analisis awal, `PageController.php` merender komponen tanpa data dan `StudentTable.jsx` memakai array `STUDENTS` hardcoded. Monitoring kini memakai peserta yang terjadwal, attempt, progres jawaban, status koneksi, flag integritas, dan polling berkala.

**Rekomendasi:** kirim data nyata: peserta terdaftar untuk jadwal aktif, `exam_attempts.status`, `time_spent_seconds`, `updated_at` terakhir, jumlah jawaban terisi, dan `flagged`. Update via polling ringan (`router.reload({ only: ['participants'] })` tiap 10 detik) atau Inertia `usePoll`.

### P1-9 — Route `/admin/exam/{examSchedule}/start` mengarah ke komponen yang tidak ada — **Diperbaiki**

`PageController::examStart()` merender `Student/ExamStart`, tetapi `resources/js/Pages/Student/ExamStart.jsx` tidak ada. Route diberi middleware `school-admin` (`routes/web.php:76-77`) dan akan error jika diklik/diakses.

**Rekomendasi:** hapus route + method (paling sederhana, karena portal siswa sudah punya alur sendiri), atau buat komponennya.

---

## 4. Temuan Sedang & Kebersihan Kode (P2/P3)

| # | Temuan | Lokasi | Rekomendasi |
|---|---|---|---|
| P2-1 | File mock besar tidak terpakai, masih berisi JSX tidak valid (`class=` alih-alih `className=`), dan komponen hardcoded (soal menjodohkan statis) | `resources/js/Features/Exam/Pages/ExamPage.jsx` | Hapus. `Pages/Student/ExamPage.jsx` adalah versi yang dipakai |
| P2-2 | Komponen navigasi duplikat & tidak terpakai | `resources/js/Components/Student/ExamNavigation.jsx`, `resources/js/Features/Exam/Components/QuestionNavigation.jsx` | Hapus; `ExamWorkspace` sudah menangani navigasi |
| P2-3 | Tiga modul "jenis penilaian" legacy masih ada padahal bank soal sudah pindah ke `exam_type` | `AssessmentType` model/controller/pages + migrasi `2026_11_10_000002` | Konfirmasi ke pemilik produk, lalu hapus atau tandai deprecated |
| P2-4 | Nama parameter menyesatkan: `handleAnswerChange(questionNo, answer)` padahal mengirim `question.id` | `useExamSession.js:149` | Rename ke `handleAnswerChange(questionId, answer)` |
| P2-5 | `ExamParticipantCredentialService` menyimpan PIN yang bisa didekripsi permanen | `ExamParticipant::casts()` (`password_plain => encrypted`) | Lihat P0-5; minimal batasi akses & audit log |
| P2-6 | `ExamAccessController@create` memakai `$examPackage->school_id` **sebelum** null-check | `ExamAccessController.php:40` vs `:51` | Pindahkan guard null ke atas |
| P2-7 | Inkonsistensi filter jadwal: dashboard mensyaratkan `session_id`/`room_id` persis, sedangkan access controller mengizinkan `null` | `StudentDashboardController.php:112-113` vs `ExamAccessController.php:191-195` | Satukan ke service akses (P1-5) |
| P2-8 | `validQuestionIds` memuat seluruh koleksi `questions` lalu `pluck`, plus `getQuestionNo()` query koleksi berulang | `ExamAnswerController.php:85-88, 177-186` | Gunakan `pluck('id')` + map `id => number` sekali di awal |
| P2-9 | Tidak ada index khusus untuk monitoring/attempt | migrasi `exam_attempts` | Tambah `index(['exam_schedule_id', 'status'])` |
| P2-10 | Dokumentasi tidak sinkron dengan kode | `docs/RESUME.md` ("proyek bukan git repository", "Laravel 13.40", modul mock yang sebenarnya sudah ada), `docs/FEATURE_STATUS.md` (menandai fitur yang sudah ada sebagai ⏳) | Perbarui setelah perbaikan P0/P1 |
| P2-11 | `question_banks`/`exam_packages` tidak punya index gabungan untuk query akses siswa | migrasi | Tambah `index(['school_id','academic_year_id','exam_type_id','subject_id'])` |
| P3-1 | Banyak string UI/komentar berisi karakter rusak (`�?"`) | mis. `StudentLoginController.php:22,32`, `QuestionSheet.jsx:126` | Ganti dengan tanda baca UTF-8 yang benar |
| P3-2 | `stripHtml()` memakai `document` saat render | `QuestionSheet.jsx:98-105` | Aman untuk CSR, tapi akan gagal bila SSR diaktifkan; gunakan util yang SSR-safe |

---

## 5. Rencana Perbaikan (Prioritas & Estimasi)

> Estimasi untuk 1 developer, sudah termasuk test.

### Sprint 0 — Integritas & Keamanan (2–3 hari) — **wajib sebelum ujian nyata**

1. ✅ Sembunyikan kunci jawaban dari payload siswa (P0-1) + test regresi — **Diperbaiki**.
2. ✅ `deadline_at` + penegakan durasi server + tolak save setelah deadline (P0-2) — **Diperbaiki**.
3. ✅ Pisahkan `started_at`/`submitted_at` (P0-3) — **Diperbaiki**.
4. ✅ Pisahkan flag dari jawaban + `is_flagged` (P0-4) — **Diperbaiki**.
5. ✅ Perbaiki login siswa: throttle, hapus fallback plaintext, `session()->regenerate()`, scope username (P0-5) — **Diperbaiki**.
6. ✅ Cek kepemilikan jadwal di `ExamController@show` (P0-6) via service akses (P1-5) — **Diperbaiki**.

**Kriteria selesai:** test baru untuk tiap poin; tidak ada `is_correct` di response siswa; save setelah deadline → 409.

### Sprint 1 — Submit, Penilaian, Hasil (3–4 hari) — **Diperbaiki**

1. ✅ Migrasi skema penilaian dan status attempt.
2. ✅ Endpoint submit final serta auto-submit saat timer mencapai nol.
3. ✅ `GradeExamAttempt` service untuk setiap tipe soal.
4. ✅ Halaman hasil menghormati `show_score_to_student`.
5. ✅ Panel admin sekolah untuk penilaian esai.

### Sprint 2 — Hardening Exam Engine (2–3 hari) — **Diperbaiki**

1. ✅ Debounce autosave, kirim diff per soal, dan flush saat navigasi/perubahan visibilitas.
2. ✅ Simpan jawaban berdasarkan ID opsi dan migrasikan data lama yang dapat dipetakan.
3. ✅ Hapus jawaban yang dikosongkan.
4. ✅ Terapkan shuffle soal dan opsi di server dengan urutan stabil per attempt.
5. ✅ Cegah request race dengan antrean serial dan kunci double-submit.

### Sprint 3 — Monitoring & Operasional (2–3 hari)

1. ✅ Data monitoring nyata untuk peserta terjadwal, attempt, dan polling (P1-8).
2. Deteksi peserta offline (mis. tidak ada autosave > 2 menit).
3. Aksi admin: reset attempt, perpanjang waktu, buka kunci.
4. Dashboard admin dengan statistik nyata.

### Sprint 4 — Kebersihan & Dokumentasi (1 hari)

1. ✅ Hapus route rusak (P1-9); dead code P2 tetap menjadi pekerjaan lanjutan.
2. Rapikan index database (P2-9, P2-11).
3. Perbarui `RESUME.md` & `FEATURE_STATUS.md`.

---

## 6. Usulan Perubahan Skema Database

```php
// 1) exam_attempts: status & tenggat server-side
Schema::table('exam_attempts', function (Blueprint $table): void {
    $table->string('status', 20)->default('in_progress')->after('time_spent_seconds');
    $table->timestamp('deadline_at')->nullable()->after('started_at');
    $table->decimal('score', 8, 2)->nullable()->after('deadline_at');
    $table->decimal('max_score', 8, 2)->nullable()->after('score');
    $table->timestamp('graded_at')->nullable()->after('submitted_at');
    $table->foreignId('graded_by')->nullable()->after('graded_at')
        ->constrained('users')->nullOnDelete();

    $table->index(['exam_schedule_id', 'status']);
});

// 2) exam_answers: flag terpisah + hasil penilaian
Schema::table('exam_answers', function (Blueprint $table): void {
    $table->boolean('is_flagged')->default(false)->after('answer');
    $table->boolean('is_correct')->nullable()->after('is_flagged');
    $table->decimal('score_awarded', 8, 2)->nullable()->after('is_correct');
});

// 3) jadwal ujian menunjuk bank soal secara eksplisit
Schema::table('exam_schedules', function (Blueprint $table): void {
    $table->foreignId('question_bank_id')->nullable()->after('subject_id')
        ->constrained('question_banks')->nullOnDelete();
});
```

Migrasi data yang perlu diperhatikan:

- `submitted_at` lama berisi nilai autosave → **jangan** dipakai sebagai penanda submit. Set `status = 'in_progress'` untuk semua attempt lama, lalu `submitted_at = null` (data historis memang tidak reliable).
- `answer = 'flagged'` lama → konversi ke `is_flagged = true`, `answer = null`.
- Backfill `question_bank_id` dari kombinasi `(school, year, exam_type, subject)` — audit dulu bila ada kombinasi ganda.

---

## 7. Usulan Kontrak Endpoint

### 7.1 Sinkronisasi jawaban (autosave)

```
PATCH /siswa/exams/{examSchedule}/answers
Content-Type: application/json

{
  "answers": { "184": "option-3", "185": ["option-7", "option-9"] }
}
```

- Hanya diff per soal; `null` = hapus jawaban.
- Server menjadi sumber kebenaran waktu.

```json
// 200
{
  "status": "in_progress",
  "saved_at": "2026-11-18T09:12:03+07:00",
  "deadline_at": "2026-11-18T09:45:00+07:00",
  "remaining_seconds": 1976
}
```

```json
// 409 — deadline lewat / sudah submit
{ "status": "submitted", "message": "Jawaban tidak dapat diubah lagi." }
```

### 7.2 Submit final

```
POST /siswa/exams/{examSchedule}/submit
```

- Idempoten (submit kedua → 200 dengan status yang sama, bukan duplikasi).
- Menjalankan penilaian otomatis untuk tipe non-esai.
- Jika `show_score_to_student = true`, kembalikan ringkasan skor; jika tidak, kembalikan status saja.

### 7.3 Hasil

```
GET /siswa/exams/{examSchedule}/result
```

- 403 bila `show_score_to_student = false` dan belum dinilai.

---

## 8. Rencana Pengujian

Suite saat ini berisi **485 test lulus**. Regression tests untuk alur P0/P1 mencakup payload soal, otorisasi jadwal, tenggat dan submit, hapus jawaban, penilaian berbagai tipe soal, penilaian esai, visibilitas hasil, pilihan bank soal ambigu, shuffle stabil, dan monitoring.

Untuk frontend, minimal tambahkan unit test pada fungsi murni (`answerStats`, perhitungan sisa waktu, debounce) bila tooling Vitest ditambahkan. Jangan menambah dependency tanpa persetujuan.

---

## 9. Checklist Cepat

**Sebelum ujian pertama:**

- [x] Kunci jawaban tidak lagi dikirim ke browser — **Diperbaiki**.
- [x] `deadline_at` ditegakkan server; save setelah waktu habis ditolak — **Diperbaiki**.
- [x] Status attempt jelas (`in_progress` / `submitted` / `graded`) — **Diperbaiki**.
- [x] Flag ragu-ragu tidak menghapus jawaban — **Diperbaiki**.
- [x] Login siswa punya throttle + session regenerate + tanpa fallback plaintext — **Diperbaiki**.
- [x] `ExamController@show` memvalidasi kepemilikan jadwal — **Diperbaiki**.
- [x] Submit final, auto-submit saat tenggat, penilaian, dan hasil — **Diperbaiki**.
- [x] Autosave di-debounce, mengirim diff, dan flush saat navigasi/perubahan visibilitas — **Diperbaiki**.
- [x] Bank soal eksplisit, ID opsi, dan shuffle stabil per attempt — **Diperbaiki**.
- [x] Monitoring data nyata, termasuk peserta yang belum mulai — **Diperbaiki**.
- [ ] Uji beban: 40 siswa autosave bersamaan.

**Setelah ujian stabil:**

- [x] Monitoring dan polling berkala.
- [x] Halaman hasil dan penilaian esai oleh admin sekolah.
- [ ] Hapus dead code & perbarui dokumentasi.

---

## 10. Lampiran — Berkas yang Diperiksa

**Backend (student):**
`app/Http/Controllers/Student/ExamController.php`, `ExamAnswerController.php`, `ExamAccessController.php`, `StudentDashboardController.php`

**Backend (auth & admin):**
`app/Http/Controllers/Auth/StudentLoginController.php`, `LoginController.php`, `app/Http/Controllers/Admin/PageController.php`, `app/Http/Middleware/EnsureStudentIsAuthenticated.php`, `HandleInertiaRequests.php`, `bootstrap/app.php`

**Model & service:**
`ExamAttempt`, `ExamAnswer`, `ExamSchedule`, `Question`, `QuestionBank`, `QuestionOption`, `QuestionPair`, `QuestionBlank`, `ExamParticipant`, `User`, `ExamAccessTokenService`, `ExamParticipantCredentialService`

**Migrasi kunci:**
`2026_11_18_000000_create_exam_attempts_table.php`, `2026_11_18_000001_create_exam_answers_table.php`, `2026_11_11_073743_create_exam_packages_table.php`, `2026_11_11_073744_create_exam_schedules_table.php`, `2026_11_14_041337_rebuild_exam_participants_without_groups.php`

**Frontend:**
`resources/js/Pages/Student/ExamPage.jsx`, `Features/Exam/Hooks/useExamSession.js`, `Features/Exam/Components/{ExamWorkspace,QuestionSheet,ExamHeader,QuestionListModal,SubmitConfirmationModal}.jsx`, `Pages/Monitoring/Index.jsx`, `Features/Monitoring/Components/StudentTable.jsx`

**Test yang relevan:**
`tests/Feature/Student/ExamAttemptTest.php`, `ExamAccessControllerTest.php`, `StudentDashboardTest.php`, `tests/Feature/Auth/StudentLoginTest.php`

**Dokumen lama yang perlu diperbarui:**
`docs/RESUME.md`, `docs/FEATURE_STATUS.md`, `docs/prd.md` (§2.3 sudah tidak akurat)

---

## 11. Catatan Implementasi P0-1 & P0-2 (18 November 2026)

### P0-1 — Kunci jawaban tidak lagi dikirim ke browser

| Perubahan | Berkas |
|---|---|
| Trait baru yang hanya memetakan field aman untuk peserta | `app/Http/Controllers/Student/Concerns/PresentsExamQuestions.php` |
| `ExamController@show` memakai trait tersebut; relasi diurutkan berdasarkan `order` | `app/Http/Controllers/Student/ExamController.php` |
| Pertahanan berlapis: `is_correct` disembunyikan dari serialisasi | `app/Models/QuestionOption.php` |
| Pertahanan berlapis: `answers` (kunci isian singkat) disembunyikan | `app/Models/QuestionBlank.php` |
| Test regresi payload | `tests/Feature/Student/ExamAttemptTest.php::test_exam_page_does_not_expose_answer_keys_to_the_student` |

Payload soal untuk siswa sekarang hanya berisi: `id`, `number`, `type`, `body`, `score`, `image_path`, `audio_path`, opsi (`id`, `label`, `text`, `order`, media), pasangan (`id`, `left_text`, `right_text`, `order`), dan blank (`id`, `order`).

### P0-2 — Durasi ujian ditegakkan di server

| Perubahan | Berkas |
|---|---|
| Kolom `deadline_at` (nullable) | `database/migrations/2026_11_18_140022_add_deadline_at_to_exam_attempts_table.php` |
| `resolveDeadline()`, `remainingSeconds()`, `hasReachedDeadline()`, `elapsedSeconds()` | `app/Models/ExamAttempt.php` |
| Attempt dibuat sekali (`createOrFirst`) dengan `started_at` + `deadline_at` yang tidak lagi di-reset | `app/Http/Controllers/Student/ExamAnswerController.php` |
| Save setelah tenggat → `409` dengan `status: expired`, `remaining_seconds: 0` | `app/Http/Controllers/Student/ExamAnswerController.php` |
| `time_spent_seconds` dihitung server, nilai dari client diabaikan | `app/Http/Controllers/Student/ExamAnswerController.php` |
| Sisa waktu halaman ujian dihitung dari `deadline_at` | `app/Http/Controllers/Student/ExamController.php` |
| Frontend berhenti mengirim `time_spent_seconds` dan menangani `409` (timer berhenti + toast) | `resources/js/Features/Exam/Hooks/useExamSession.js` |

Tenggat dihitung sebagai **nilai terkecil** antara `started_at + duration_minutes` dan jam tutup jadwal, sehingga ujian tidak bisa melewati `close_time`. Attempt lama yang belum punya `deadline_at` memakai `created_at + duration_minutes` sebagai fallback (lihat test `test_legacy_attempt_without_deadline_uses_creation_time_as_fallback`).

Verifikasi: `php artisan test --compact` → **468 passed**. Migrasi sudah dijalankan di database lokal dan aset frontend sudah di-build ulang.

### Konsekuensi yang perlu diketahui

- `submitted_at` kini hanya diisi oleh endpoint submit final dan tidak lagi di-reset setiap autosave. P0-3 sudah diperbaiki.
- Pada saat catatan ini dibuat, auto-submit dan penilaian otomatis belum tersedia; keduanya kemudian ditambahkan sebagai bagian **P1-1** (lihat status dan test terbaru pada §3, §8, dan §9).
- Timer di browser masih berjalan sendiri untuk tampilan; nilai otoritatif tetap dari server saat halaman dimuat dan saat save ditolak.
- Batas waktu tidak memperpanjang `close_time`: bila siswa baru mulai 10 menit sebelum jadwal tutup, sisa waktunya hanya 10 menit.

---

## 12. Perbaikan Countdown Timer (18 November 2026)

**Laporan:** jadwal ujian diset 90 menit, tetapi countdown di halaman pengerjaan menampilkan 130 menit.

### Hasil pemeriksaan data

| Data | Nilai |
|---|---|
| `exam_schedules.duration_minutes` (jadwal id 1) | **90** |
| `exam_attempts.duration_minutes` (attempt id 1) | **90** |
| `started_at` → `deadline_at` | 17:22:09 → 18:52:09 (**tepat 90 menit**) |
| `remaining_seconds` yang dikirim server | ≤ 5400 detik (90 menit) |

`sqlite_sequence` menunjukkan jadwal dan attempt tersebut adalah baris pertama yang pernah dibuat, jadi server tidak pernah mengirim sisa waktu 130 menit untuk data ini. Nilai 130 menit tidak mungkin berasal dari props halaman saat itu.

### Penyebab yang paling mungkin

Timer di browser sebelumnya hanya memakai **snapshot** `remaining_seconds` saat halaman dibuka, lalu menghitung mundur sendiri dengan jam perangkat:

- halaman ujian yang dibuka **sebelum** durasi jadwal diubah tetap memakai durasi lama;
- tab yang di-background/suspended berhenti menghitung sehingga tampilan tidak sesuai tenggat server;
- jam perangkat yang berubah membuat sisa waktu melenceng.

Karena tidak ada rekonsiliasi dengan server, nilai yang salah bisa bertahan sampai halaman dimuat ulang.

### Perbaikan

| Perubahan | Berkas |
|---|---|
| Sisa waktu selalu dihitung ulang dari tenggat absolut server (`deadline_at`), bukan dari snapshot | `resources/js/Features/Exam/Hooks/useExamSession.js` |
| Koreksi selisih jam perangkat memakai `server_now` dari server, dievaluasi ulang setiap respons autosave | `useExamSession.js` + `ExamAnswerController` |
| Setiap autosave menyinkronkan ulang `deadline_at`/`remaining_seconds` (timer pulih sendiri setelah tab tidur) | `useExamSession.js` |
| Tenggat server dibatasi `min(deadline_at, started_at + duration)` sehingga sisa waktu dan penolakan penyimpanan tidak pernah melewati durasi | `app/Models/ExamAttempt.php` |
| Durasi countdown memakai durasi **attempt** (snapshot saat ujian dimulai), bukan durasi jadwal terkini | `ExamController`, `ExamAttempt::durationSeconds()` |
| Props baru: `duration_seconds`, `deadline_at`, `server_now` | `app/Http/Controllers/Student/ExamController.php` |
| Respons save & 409 menyertakan `server_now` | `app/Http/Controllers/Student/ExamAnswerController.php` |

### Test regresi

1. `test_exam_page_exposes_the_server_deadline_for_the_countdown`
2. `test_remaining_time_is_capped_at_the_configured_duration`
3. `test_countdown_uses_the_attempt_duration_when_the_schedule_changes`

Verifikasi: `php artisan test --compact` → **471 passed, 2664 assertions**.

### Cara menguji ulang

1. Muat ulang halaman ujian (Ctrl+F5) agar bundle terbaru dipakai.
2. Karena attempt peserta disimpan permanen, sisa waktu melanjutkan attempt lama. Untuk uji dari 90:00, hapus attempt peserta tersebut lebih dulu (`exam_attempts` + `exam_answers` terkait) atau gunakan peserta lain.
3. Bila masih muncul angka berbeda, kirimkan nilai `remaining_seconds`, `duration_seconds`, dan `deadline_at` dari response `GET /siswa/exams/{id}` (Network tab) agar bisa ditelusuri.
