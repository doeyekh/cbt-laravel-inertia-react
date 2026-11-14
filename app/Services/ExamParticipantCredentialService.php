<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamParticipant;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use Illuminate\Support\Collection;

class ExamParticipantCredentialService
{
    /**
     * Placeholder yang dapat dipakai pada template username dan nomor peserta.
     *
     * @var array<int, string>
     */
    public const PLACEHOLDERS = ['no', 'nis', 'nisn', 'kelas', 'ruang', 'sesi', 'tahun', 'tanggal'];

    /**
     * Huruf dan angka yang dipakai membangkitkan password peserta.
     *
     * @var string
     */
    private const PASSWORD_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Panjang password peserta yang dibangkitkan.
     *
     * @var int
     */
    private const PASSWORD_LENGTH = 8;

    /**
     * Tarik anggota kelas terpilih menjadi peserta ujian sekolah dan tahun pelajaran.
     *
     * Siswa yang sudah menjadi peserta pada (sekolah, tahun) yang sama tidak dibuat ulang.
     *
     * @param  array<int, int>  $classIds
     * @param  array{session_id: int|null, room_id: int|null}|null  $placement
     * @return int Jumlah peserta yang baru ditambahkan.
     */
    public function syncParticipantsFromClasses(
        School $school,
        AcademicYear $academicYear,
        array $classIds,
        ?array $placement = null,
    ): int {
        $classIds = array_values(array_unique(array_filter(array_map('intval', $classIds))));

        if ($classIds === []) {
            return 0;
        }

        $validClassIds = SchoolClass::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('id', $classIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($validClassIds === []) {
            return 0;
        }

        $placement ??= $this->currentPlacement((int) $school->id, (int) $academicYear->id);

        $existingStudentIds = ExamParticipant::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $members = ClassMember::query()
            ->whereIn('class_id', $validClassIds)
            ->where('academic_year_id', $academicYear->id)
            ->get(['student_id']);

        $timestamp = now();
        $rows = [];

        foreach ($members as $member) {
            $studentId = (int) $member->student_id;

            if (in_array($studentId, $existingStudentIds, true)) {
                continue;
            }

            $rows[] = [
                'student_id' => $studentId,
                'school_id' => (int) $school->id,
                'academic_year_id' => (int) $academicYear->id,
                'session_id' => $placement['session_id'],
                'room_id' => $placement['room_id'],
                'username' => null,
                'participant_number' => null,
                'password' => null,
                'password_plain' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $existingStudentIds[] = $studentId;
        }

        if ($rows === []) {
            return 0;
        }

        ExamParticipant::insert($rows);

        return count($rows);
    }

    /**
     * Ambil penempatan yang sedang dipakai, atau cari yang masih kosong.
     *
     * @return array{session_id: int|null, room_id: int|null}
     */
    public function currentPlacement(int $schoolId, int $academicYearId): array
    {
        $participant = ExamParticipant::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereNotNull('room_id')
            ->whereNotNull('session_id')
            ->orderBy('id')
            ->first(['session_id', 'room_id']);

        if ($participant !== null) {
            return [
                'session_id' => (int) $participant->session_id,
                'room_id' => (int) $participant->room_id,
            ];
        }

        return $this->resolvePlacement($schoolId, $academicYearId);
    }

    /**
     * Tentukan pasangan sesi dan ruangan yang masih kosong secara otomatis.
     *
     * Sesi dan ruangan aktif dicari berurutan. Bila seluruh pasangan sudah
     * dipakai peserta sekolah lain, sesi dan ruangan dikosongkan dan wajib
     * ditentukan manual.
     *
     * @return array{session_id: int|null, room_id: int|null}
     */
    public function resolvePlacement(int $schoolId, int $academicYearId): array
    {
        $used = ExamParticipant::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereNotNull('room_id')
            ->whereNotNull('session_id')
            ->get(['session_id', 'room_id'])
            ->map(fn (ExamParticipant $participant): string => $participant->session_id.'-'.$participant->room_id)
            ->unique()
            ->all();

        $sessions = Session::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->orderBy('id')
            ->get(['id']);

        $rooms = Room::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id']);

        foreach ($sessions as $session) {
            foreach ($rooms as $room) {
                if (! in_array($session->id.'-'.$room->id, $used, true)) {
                    return [
                        'session_id' => (int) $session->id,
                        'room_id' => (int) $room->id,
                    ];
                }
            }
        }

        return [
            'session_id' => null,
            'room_id' => null,
        ];
    }

    /**
     * Bangkitkan username, nomor peserta, dan password untuk seluruh peserta.
     *
     * Template diambil dari sekolah. `$participants` harus memuat seluruh
     * peserta pada (sekolah, tahun pelajaran) agar pemecahan bentrok akurat.
     *
     * @param  Collection<int, ExamParticipant>  $participants
     * @param  bool  $overwrite  Apakah kredensial yang sudah ada ditimpa.
     * @return int Jumlah peserta yang diperbarui.
     */
    public function generateCredentials(
        School $school,
        AcademicYear $academicYear,
        Collection $participants,
        bool $overwrite = false,
    ): int {
        $participants = $participants
            ->load([
                'student.registrations' => fn ($query) => $query->where('school_id', $school->id),
                'room:id,code,name',
                'session:id,code,name',
                'academicYear:id,code,name',
            ])
            ->sortBy(fn (ExamParticipant $participant): string => $this->participantName($participant))
            ->values();

        $classCodes = $this->participantClassCodes(
            $school,
            $academicYear,
            $participants->pluck('student_id')->map(fn ($id): int => (int) $id)->all(),
        );

        $takenUsernames = $participants
            ->pluck('username')
            ->filter()
            ->map(fn ($value): string => (string) $value)
            ->unique()
            ->values()
            ->all();

        $takenNumbers = $participants
            ->pluck('participant_number')
            ->filter()
            ->map(fn ($value): string => (string) $value)
            ->unique()
            ->values()
            ->all();

        $updated = 0;

        foreach ($participants as $index => $participant) {
            if (! $overwrite && $participant->hasCredential()) {
                continue;
            }

            $sequence = $index + 1;

            $takenUsernames = $this->releaseValue($takenUsernames, $participant->username);
            $takenNumbers = $this->releaseValue($takenNumbers, $participant->participant_number);

            $username = $this->makeUnique(
                $this->buildUsername($school, $participant, $sequence, $classCodes),
                $takenUsernames,
                50,
            );

            $participantNumber = $this->makeUnique(
                $this->buildParticipantNumber($school, $participant, $sequence, $classCodes),
                $takenNumbers,
                30,
            );

            $password = $overwrite || $participant->password_plain === null
                ? $this->generatePassword()
                : $participant->password_plain;

            $participant->update([
                'username' => $username,
                'participant_number' => $participantNumber,
                'password' => $password,
                'password_plain' => $password,
            ]);

            $updated++;
        }

        return $updated;
    }

    /**
     * Bangkitkan username peserta berdasarkan template sekolah.
     *
     * @param  array<int, string>  $classCodes  Kode kelas peserta berdasarkan id siswa.
     */
    public function buildUsername(
        School $school,
        ExamParticipant $participant,
        int $sequence,
        array $classCodes = [],
    ): string {
        return $this->renderTemplate(
            $school,
            $participant,
            $sequence,
            $school->username_template,
            $classCodes,
        );
    }

    /**
     * Bangkitkan nomor peserta berdasarkan template sekolah.
     *
     * @param  array<int, string>  $classCodes  Kode kelas peserta berdasarkan id siswa.
     */
    public function buildParticipantNumber(
        School $school,
        ExamParticipant $participant,
        int $sequence,
        array $classCodes = [],
    ): string {
        return $this->renderTemplate(
            $school,
            $participant,
            $sequence,
            $school->participant_number_template,
            $classCodes,
        );
    }

    /**
     * Kumpulkan kode kelas tiap peserta untuk placeholder `{kelas}`.
     *
     * @param  array<int, int>  $studentIds
     * @return array<int, string>
     */
    private function participantClassCodes(School $school, AcademicYear $academicYear, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $classCodes = SchoolClass::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->pluck('code', 'id');

        if ($classCodes->isEmpty()) {
            return [];
        }

        return ClassMember::query()
            ->whereIn('class_id', $classCodes->keys())
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'class_id'])
            ->groupBy('student_id')
            ->map(fn ($members): string => (string) ($classCodes[$members->first()->class_id] ?? ''))
            ->all();
    }

    /**
     * Bangkitkan password acak untuk peserta.
     */
    public function generatePassword(): string
    {
        $alphabetLength = strlen(self::PASSWORD_ALPHABET);
        $password = '';

        for ($index = 0; $index < self::PASSWORD_LENGTH; $index++) {
            $password .= self::PASSWORD_ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $password;
    }

    /**
     * Pindahkan peserta terpilih ke ruang dan sesi lain.
     *
     * @param  Collection<int, int>  $participantIds
     */
    public function moveParticipants(Collection $participantIds, int $roomId, int $sessionId): int
    {
        $ids = $participantIds->map(fn ($id): int => (int) $id)->unique()->values()->all();

        if ($ids === []) {
            return 0;
        }

        return ExamParticipant::query()
            ->whereIn('id', $ids)
            ->update([
                'room_id' => $roomId,
                'session_id' => $sessionId,
            ]);
    }

    /**
     * Render template dengan mengganti placeholder yang tersedia.
     *
     * @param  array<int, string>  $classCodes
     */
    private function renderTemplate(
        School $school,
        ExamParticipant $participant,
        int $sequence,
        string $template,
        array $classCodes = [],
    ): string {
        $fallback = $this->paddedSequence($school, $sequence);

        $replacements = [
            'no' => $fallback,
            'nis' => $this->participantNis($participant),
            'nisn' => (string) ($participant->student?->nisn ?? ''),
            'kelas' => (string) ($classCodes[(int) $participant->student_id] ?? ''),
            'ruang' => (string) ($participant->room?->code ?? ''),
            'sesi' => (string) ($participant->session?->code ?? ''),
            'tahun' => (string) ($participant->academicYear?->code ?? ''),
            'tanggal' => now()->format('Ymd'),
        ];

        $rendered = preg_replace_callback(
            '/\{([a-z]+)\}/',
            function (array $matches) use ($replacements, $fallback): string {
                $value = $replacements[$matches[1]] ?? '';

                return $value !== '' ? $value : $fallback;
            },
            $template,
        ) ?? $template;

        $sanitized = $this->sanitize($rendered);

        return $sanitized !== '' ? $sanitized : $fallback;
    }

    /**
     * Bersihkan nilai hasil render template.
     */
    private function sanitize(string $value): string
    {
        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9\-]+/', '', $value) ?? '';

        return trim($value, '-');
    }

    /**
     * Nomor urut peserta dengan padding sesuai lebar nomor sekolah.
     */
    private function paddedSequence(School $school, int $sequence): string
    {
        $width = max(1, min(6, (int) $school->number_width));

        return str_pad((string) $sequence, $width, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil NIS peserta dari registrasi sekolah aktif.
     */
    private function participantNis(ExamParticipant $participant): string
    {
        $registrations = $participant->student?->registrations ?? collect();

        $registration = $registrations->firstWhere('status', RegistrationStatus::Aktif)
            ?? $registrations->first();

        return (string) ($registration?->nis ?? '');
    }

    /**
     * Ambil nama peserta untuk pengurutan.
     */
    private function participantName(ExamParticipant $participant): string
    {
        return strtolower((string) ($participant->student?->name ?? ''));
    }

    /**
     * Lepaskan nilai lama peserta dari daftar nilai yang sudah terpakai.
     *
     * @param  array<int, string>  $taken
     * @return array<int, string>
     */
    private function releaseValue(array $taken, ?string $value): array
    {
        if ($value === null || $value === '') {
            return $taken;
        }

        return array_values(array_diff($taken, [$value]));
    }

    /**
     * Pastikan nilai kredensial unik dalam lingkup sekolah dan tahun pelajaran.
     *
     * @param  array<int, string>  $taken  Daftar nilai yang sudah terpakai, ikut diperbarui.
     */
    private function makeUnique(string $value, array &$taken, int $maxLength): string
    {
        if (! in_array($value, $taken, true)) {
            $taken[] = $value;

            return $value;
        }

        $suffix = 2;

        do {
            $candidate = $this->truncate($value, $maxLength - strlen((string) $suffix) - 1).'-'.$suffix;
            $suffix++;
        } while (in_array($candidate, $taken, true));

        $taken[] = $candidate;

        return $candidate;
    }

    /**
     * Potong nilai agar tidak melebihi panjang kolom.
     */
    private function truncate(string $value, int $maxLength): string
    {
        if ($maxLength < 1) {
            return '';
        }

        return substr($value, 0, $maxLength);
    }

    /**
     * Kumpulkan seluruh peserta sekolah dan tahun pelajaran beserta relasinya.
     *
     * @return Collection<int, ExamParticipant>
     */
    public function participantsWithRelations(School $school, AcademicYear $academicYear): Collection
    {
        return ExamParticipant::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->with([
                'student.registrations' => fn ($query) => $query->where('school_id', $school->id),
            ])
            ->get();
    }
}
