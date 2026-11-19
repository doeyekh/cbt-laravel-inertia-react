
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Ujian {{ $schedule['name'] }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 16mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.45;
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        p, h1, h2, h3 {
            margin: 0;
        }

        /* HEADER */
        .header {
            margin-bottom: 3mm;
        }

        .header td {
            vertical-align: middle;
        }

        .school-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #064e3b;
            letter-spacing: 0.3pt;
        }

        .report-title {
            margin-top: 1mm;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e293b;
        }

        .header-subtitle {
            margin-top: 1mm;
            font-size: 7.5pt;
            color: #64748b;
        }

        .header-meta {
            text-align: right;
            font-size: 7.5pt;
            line-height: 1.7;
            color: #64748b;
        }

        .header-meta strong {
            color: #334155;
        }

        .divider {
            border-top: 2pt solid #047857;
            margin: 2mm 0 4mm;
        }

        /* INFORMASI UJIAN */
        .info-table {
            margin-bottom: 4mm;
        }

        .info-table td {
            padding: 1.5mm 2mm;
            /* border: 0.5pt solid #e2e8f0; */
            vertical-align: middle;
        }

        .info-label {
            width: 20%;
            background: #f8fafc;
            color: #64748b;
            font-size: 7pt;
        }

        .info-value {
            width: 30%;
            font-weight: bold;
            color: #334155;
        }

        /* RINGKASAN */
        .section-title {
            margin-bottom: 2mm;
            font-size: 9pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        .summary-table {
            table-layout: fixed;
            margin-bottom: 5mm;
        }

        .summary-table td {
            width: 25%;
            padding: 2.5mm 2mm;
            border: 0.5pt solid #d1d5db;
            background: #f8fafc;
            vertical-align: middle;
        }

        .summary-label {
            font-size: 6.5pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 1mm;
            font-size: 12pt;
            font-weight: bold;
            color: #064e3b;
        }

        /* TABEL PESERTA */
        .data-table {
            table-layout: fixed;
            margin-bottom: 4mm;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table th {
            padding: 2mm 1mm;
            background: #047857;
            border: 0.5pt solid #047857;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .data-table td {
            padding: 1.8mm 1.2mm;
            border: 0.5pt solid #dbe3e9;
            font-size: 7.2pt;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data-table tbody tr {
            page-break-inside: avoid;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .participant-name {
            font-weight: bold;
            color: #334155;
        }

        .score {
            font-weight: bold;
            color: #047857;
        }

        .empty-row {
            padding: 8mm 2mm !important;
            text-align: center;
            color: #94a3b8;
        }

        /* CATATAN */
        .note {
            margin-top: 2mm;
            padding: 2.5mm 3mm;
            background: #f8fafc;
            border-left: 2pt solid #047857;
            color: #64748b;
            font-size: 7pt;
        }

        .footer-space {
            height: 3mm;
        }

        .signature {
            margin-top: 8mm;
            page-break-inside: avoid;
        }

        .signature-block {
            width: 42%;
            text-align: center;
        }

        .signature-space {
            height: 18mm;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    @php
        $score = fn ($value) => $value === null
            ? '-'
            : number_format((float) $value, 1, ',', '.');

        $minutes = fn ($seconds) => $seconds === null
            ? '-'
            : floor((int) $seconds / 60) . ' menit';

        $examDate = !empty($schedule['exam_date'])
            ? \Illuminate\Support\Carbon::parse($schedule['exam_date'])->format('d/m/Y')
            : '-';

        $timeRange = trim(
            ($schedule['open_time'] ?? '') . ' - ' . ($schedule['close_time'] ?? ''),
            ' -'
        );

        $participantCount = $summary['participant_count'] ?? 0;
        $completedCount = $summary['completed_count'] ?? 0;
        $average = $summary['average_percentage'] ?? null;
        $highest = $summary['highest_percentage'] ?? null;
        $lowest = $summary['lowest_percentage'] ?? null;
        $passPercentage = $summary['pass_percentage'] ?? null;
    @endphp

    {{-- HEADER --}}
    <table class="header">
        <tr>
            <td width="65%">
                <p class="school-name">
                    {{ $schedule['school'] ?? 'Sekolah' }}
                </p>

                <p class="report-title">
                    Laporan Hasil Ujian
                </p>

                <p class="header-subtitle">
                    {{ $schedule['name'] ?? '-' }}
                    @if (!empty($schedule['code']))
                        · {{ $schedule['code'] }}
                    @endif
                    @if (!empty($schedule['academic_year']))
                        · Tahun Pelajaran {{ $schedule['academic_year'] }}
                    @endif
                </p>
            </td>

            <td width="35%" class="header-meta">
                <p>Dicetak pada</p>
                <strong>{{ $print_date }}</strong>
                <p style="margin-top: 1mm;">
                    {{ $schedule['subject'] ?? '-' }}
                </p>
            </td>
        </tr>
    </table>

    <div class="divider"></div>


    <table class="info-table">
        <tr>
            <td class="info-label">Mata Pelajaran</td>
            <td class="info-value">{{ $schedule['subject'] ?? '-' }}</td>

            <td class="info-label">Sesi</td>
            <td class="info-value">{{ $schedule['session'] ?? '-' }}</td>
        </tr>

        <tr>
            <td class="info-label">Kelas</td>
            <td class="info-value">{{ $schedule['class'] ?? '-' }}</td>

            <td class="info-label">Ruang</td>
            <td class="info-value">{{ $schedule['room'] ?? '-' }}</td>
        </tr>

        <tr>
            <td class="info-label">Tanggal</td>
            <td class="info-value">{{ $examDate }}</td>

            <td class="info-label">Waktu</td>
            <td class="info-value">
                {{ $timeRange !== '' ? $timeRange : '-' }}
            </td>
        </tr>
    </table>

    

    {{-- REKAP PESERTA --}}
    <h2 class="section-title">Rekapitulasi Nilai Peserta</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5mm;">No.</th>
                <th style="width: 23mm;">No. Peserta</th>
                <th>Nama Peserta</th>
                <th style="width: 19mm;">NISN</th>
                <th style="width: 12mm;">Benar</th>
                <th style="width: 12mm;">Salah</th>
                <th style="width: 17mm;">Nilai</th>
                <th style="width: 21mm;">Waktu</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($participants as $index => $participant)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>

                    <td class="text-center">
                        {{ $participant['participant_number'] ?? '-' }}
                    </td>

                    <td class="participant-name">
                        {{ $participant['name'] ?? '-' }}
                    </td>

                    <td class="text-center">
                        {{ $participant['nisn'] ?? '-' }}
                    </td>

                    <td class="text-center">
                        {{ $participant['correct_count'] ?? '-' }}
                    </td>

                    <td class="text-center">
                        {{ $participant['wrong_count'] ?? '-' }}
                    </td>

                    <td class="text-center score">
                        {{ $score($participant['percentage'] ?? null) }}
                    </td>

                    <td class="text-center">
                        {{ $minutes($participant['time_spent_seconds'] ?? 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-row">
                        Belum ada siswa yang terdaftar pada kelas ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- CATATAN --}}
    <div class="note">
        <strong>Catatan:</strong>
        Nilai akhir dihitung dari skor perolehan dibagi skor maksimum,
        kemudian dikonversi ke skala 0–100.
        Peserta dengan nilai di bawah 70 dinyatakan belum tuntas.
    </div>

    <table class="signature">
        <tr>
            <td width="58%"></td>
            <td class="signature-block">
                <p>Pengajar Mata Pelajaran</p>
                <div class="signature-space"></div>
                <p class="signature-name">
                    {{ $schedule['teacher'] ?? '........................................' }}
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
