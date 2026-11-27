{{-- HEADER --}}

<table class="header">
    <tr>
        <td class="header-logo">
            <img
                src="{{ public_path('img/logo.png') }}"
                alt="Logo Sekolah"
                class="school-logo"
            >
        </td>

        <td class="header-content">
            <p class="exam-type">
                Daftar Hadir Peserta
            </p>

            <p class="report-title">
                {{ strtoupper($schedule->examPackage->examType->name ?? 'SUSULAN TES KEMAMPUAN AKADEMIK SMK') }}
            </p>

            <p class="school-name">
                {{ strtoupper($schedule->examPackage->school->name ?? 'SMKS IT RAUDHATUL ULUUM') }}
            </p>

            <p class="header-subtitle">
                Tahun Pelajaran
                {{ $schedule->examPackage->academicYear->name ?? '2026' }}
            </p>
        </td>

        <td class="header-meta">
            <p>Dicetak pada:</p>
            <strong>{{ $print_date }}</strong>

            <p style="margin-top: 2mm;">Mata Pelajaran</p>
            <strong>{{ $schedule->subject->name ?? '-' }}</strong>
        </td>
    </tr>
</table>

<div class="divider"></div>

{{-- INFORMASI UJIAN --}}

@php
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    $examDate = strtotime($schedule->exam_date);
    $dayName = $days[date('l', $examDate)] ?? date('l', $examDate);
@endphp
<table class="info-table">
    <tr>
        <td class="info-label">Kelas</td>
        <td class="info-separator">:</td>
        <td class="info-value">
            {{ strtoupper($schedule->schoolClass?->name ?? 'XII RPL 1') }}
        </td>
    </tr>

    <tr>
        <td class="info-label">ID SERVER / RUANG</td>
        <td class="info-separator">:</td>
        <td class="info-value">
            {{ $schedule->room->server_id ?? 'K02230178-AX7Z' }}
            / {{ $schedule->room->name ?? '1' }}
        </td>
    </tr>

    <tr>
        <td class="info-label">HARI / TANGGAL</td>
        <td class="info-separator">:</td>
        <td class="info-value">
            {{ $dayName }}, {{ date('d-m-Y', $examDate) }}
        </td>
    </tr>
</table>

{{-- TABEL PESERTA --}}

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;">No.</th>
            <th style="width: 10%;">Username</th>
            <th style="width: 38%;">Nama Peserta</th>
            <th style="width: 20%;">Tanda Tangan</th>
            <th style="width: 10%;">Keterangan</th>
        </tr>
    </thead>

    <tbody>
        @forelse($participants as $index => $participant)
            @php
                $number = $index + 1;
            @endphp

            <tr>
                <td class="text-center">
                    {{ $number }}
                </td>

                <td class="text-center">
                    {{ $participant['username'] }}
                </td>

                <td class="participant-name">
                    {{ $participant['name'] }}
                </td>

                <td class="signature-number"
                    style="{{ $number % 2 === 0 ? 'text-align: center; padding-left: 0;' : '' }}">
                    {{ $number }}.
                </td>

                <td></td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="empty-row">
                    Tidak ada peserta ujian pada sesi/ruangan ini.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- KETERANGAN --}}

<div class="footer-section">
    <strong>Keterangan:</strong>

    <ol>
        <li>
            Dibuat rangkap 3 (tiga), masing-masing untuk sekolah sebagai arsip.
        </li>
        <li>
            Pengawas ruang menyilang nama peserta yang tidak hadir.
        </li>
    </ol>
</div>

{{-- TANDA TANGAN --}}

<table class="signature-table">
    <tr>
        <td>
            Proktor

            <div class="signature-space"></div>

            ( {{ strtoupper($schedule->examPackage->school->admin->name ?? '.....................................') }} )
        </td>

        <td>
            Pengawas

            <div class="signature-space"></div>

            ( ........................................ )
        </td>
    </tr>
</table>
