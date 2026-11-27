<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Format Nilai Praktek</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.25;
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        p {
            margin: 0;
            padding: 0;
        }

        .header td {
            vertical-align: middle;
        }

        .header-logo {
            width: 12%;
            text-align: center;
        }

        .school-logo {
            width: 18mm;
            height: 18mm;
        }

        .header-content {
            width: 63%;
        }

        .school-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #064e3b;
            line-height: 1.2;
        }

        .report-title {
            margin-top: 1mm;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .report-subtitle {
            margin-top: 1mm;
            font-size: 8pt;
            color: #64748b;
        }

        .header-meta {
            width: 25%;
            text-align: right;
            font-size: 7.5pt;
            line-height: 1.5;
            color: #64748b;
        }

        .header-meta strong {
            color: #334155;
        }

        .divider {
            border-top: 2pt solid #047857;
            margin: 2mm 0 3mm;
        }

        .info-table {
            margin-bottom: 3mm;
            font-size: 8pt;
        }

        .info-table td {
            padding: 1mm 1.5mm 1mm 0;
            vertical-align: middle;
        }

        .info-label {
            width: 12%;
            color: #475569;
        }

        .info-separator {
            width: 1.5%;
            text-align: center;
        }

        .info-value {
            font-weight: bold;
            color: #334155;
        }

        .data-table {
            table-layout: fixed;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table th {
            padding: 1.8mm 1mm;
            background-color: #047857;
            border: 0.5pt solid #047857;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .data-table td {
            height: 7mm;
            padding: 1.2mm;
            border: 0.5pt solid #cbd5e1;
            font-size: 7.5pt;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .data-table tbody tr {
            page-break-inside: avoid;
        }

        .text-center {
            text-align: center;
        }

        .student-name {
            font-weight: bold;
        }

        .empty-row {
            padding: 6mm !important;
            text-align: center;
            color: #64748b;
        }

        .signature {
            margin-top: 5mm;
            page-break-inside: avoid;
            font-size: 8pt;
        }

        .signature td {
            vertical-align: top;
        }

        .criteria-legend {
            width: 58%;
            text-align: left;
        }

        .criteria-legend p {
            margin-bottom: 1mm;
        }

        .criteria-legend ol {
            margin: 0 0 0 4mm;
            padding-left: 3mm;
        }

        .signature-block {
            width: 42%;
            text-align: center;
        }

        .signature-space {
            height: 17mm;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .page-break {
            page-break-after: always;
        }

        .empty-report {
            margin-top: 30mm;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>

<body>
    @forelse ($reports as $report)
    @php
    $students = $report['students'];
    $teacherName = $report['teacher_name'];
    $criteriaWidth = $criteria === []
    ? 0
    : round(40 / count($criteria), 2);
    @endphp

    <table class="header">
        <tr>
            <td class="header-logo">
                <img
                    src="{{ public_path('img/logo.png') }}"
                    alt="Logo Sekolah"
                    class="school-logo">
            </td>

            <td class="header-content">

                <div class="report-title">
                    Format Nilai Praktek
                </div>
                <div class="school-name">

                    {{ strtoupper($report['school']->name ?? 'Nama Sekolah') }}
                </div>

                <div class="report-subtitle">
                    Tahun Pelajaran
                    {{ $report['academic_year']->name ?? '-' }}
                </div>
            </td>

            <td class="header-meta">
                <p>Dicetak pada:</p>
                <strong>{{ $print_date }}</strong>

                <p style="margin-top: 2mm;">Jenis Ujian</p>
                <strong>{{ $report['exam_type']->name ?? '-' }}</strong>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="info-table">
        <tr>


            <td class="info-label">Kelas</td>
            <td class="info-separator">:</td>
            <td class="info-value">{{ $report['class']?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Mata Pelajaran</td>
            <td class="info-separator">:</td>
            <td class="info-value">{{ $report['subject']->name ?? '-' }}</td>
        </tr>

        <tr>
            <td class="info-label">Hari / Tanggal</td>
            <td class="info-separator">:</td>
            <td class="info-value">_______________________</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" rowspan="2">No.</th>
                <th style="width: 11%;" rowspan="2">NISN</th>
                <th rowspan="2">Nama Siswa</th>
                <th colspan="{{count($criteria)}}">Kriteria</th>
                <th style="width: 9%;" rowspan="2">Nilai Akhir</th>
                <th style="width: 8%;" rowspan="2">Ket</th>
            </tr>
            <tr>

                @foreach ($criteria as $criterion)
                <th style="width: 6%;">{{ $loop->iteration }}</th>
                @endforeach

            </tr>
        </thead>

        <tbody>
            @forelse ($students as $student)
            <tr>
                <td class="text-center">{{ $student['number'] }}</td>
                <td class="text-center">{{ $student['nisn'] }}</td>
                <td class="student-name">{{ $student['name'] }}</td>

                @foreach ($criteria as $criterion)
                <td></td>
                @endforeach

                <td></td>
                <td></td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ 5 + count($criteria) }}" class="empty-row">
                    Tidak ada siswa pada kelas ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td class="criteria-legend">
                <p>
                    <strong>Keterangan Kriteria Penilaian:</strong>
                </p>

                <ol>
                    @foreach ($criteria as $criterion)
                    <li>{{ $criterion }}</li>
                    @endforeach
                </ol>
            </td>

            <td class="signature-block">
                <p>Penguji,</p>

                <div class="signature-space"></div>

                <p class="signature-name">
                    <br><br><br><br><br><br>
                    {{ $teacherName ?? '........................................' }}
                </p>
            </td>
        </tr>
    </table>
    <div class="page-break"></div>

    <table class="header">
        <tr>
            <td class="header-logo">
                <img
                    src="{{ public_path('img/logo.png') }}"
                    alt="Logo Sekolah"
                    class="school-logo">
            </td>

            <td class="header-content">
                <p class="report-title">
                    Daftar Hadir PRAKTEK
                </p>

                <p class="report-title">
                    {{ $report['exam_type']->name ?? '-' }}
                </p>

                <p class="school-name">
                    {{ strtoupper($report['school']->name ?? 'Nama Sekolah') }}
                </p>

                <div class="report-subtitle">
                    Tahun Pelajaran
                    {{ $report['academic_year']->name ?? '-' }}
                </div>
            </td>

            <td class="header-meta">
                <p>Dicetak pada:</p>
                <strong>{{ $print_date }}</strong>

                <p style="margin-top: 2mm;">Jenis Ujian</p>
                <strong>{{ $report['exam_type']->name ?? '-' }}</strong>
            </td>
        </tr>
    </table>

    <div class="divider"></div>
    <table class="info-table">
        <tr>


            <td class="info-label">Kelas</td>
            <td class="info-separator">:</td>
            <td class="info-value">{{ $report['class']?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Mata Pelajaran</td>
            <td class="info-separator">:</td>
            <td class="info-value">{{ $report['subject']->name ?? '-' }}</td>
        </tr>

        <tr>
            <td class="info-label">Hari / Tanggal</td>
            <td class="info-separator">:</td>
            <td class="info-value">_______________________</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 10%;">NISN</th>
                <th style="width: 38%;">Nama Peserta</th>
                <th style="width: 20%;">Tanda Tangan</th>
                <th style="width: 10%;">Keterangan</th>
            </tr>
        </thead>

        <tbody>
            @forelse($students as $index => $participant)
            @php
            $number = $index + 1;
            @endphp

            <tr>
                <td class="text-center">
                    {{ $number }}
                </td>

                <td class="text-center">
                    {{ $participant['nisn'] ?? '-' }}
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


    <table class="signature">
        <tr>
            <td class="criteria-legend">
                <strong>Keterangan:</strong>

                <ol>
                    <li>
                        Dibuat rangkap 3 (tiga), masing-masing untuk sekolah sebagai arsip.
                    </li>
                    <li>
                        Pengawas ruang menyilang nama peserta yang tidak hadir.
                    </li>
                </ol>
            </td>

            <td class="signature-block">
                <p>Penguji,</p>

                <div class="signature-space"></div>

                <p class="signature-name">
                    <br><br><br><br><br><br>
                    {{ $teacherName ?? '........................................' }}
                </p>
            </td>
        </tr>
    </table>


    @unless ($loop->last)
    <div class="page-break"></div>
    @endunless
    @empty
    <p class="empty-report">
        Tidak ada kelas atau guru pengampu untuk mata pelajaran dan tahun
        pelajaran yang dipilih.
    </p>
    @endforelse
</body>

</html>