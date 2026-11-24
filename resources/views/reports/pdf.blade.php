<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Ujian {{ $schedule['name'] ?? '' }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 16mm;
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

        p,
        h1,
        h2,
        h3 {
            margin: 0;
        }

        /* ========================================
           HEADER
        ======================================== */

        .header {
            margin-bottom: 2mm;
        }

        .header td {
            vertical-align: middle;
        }

        .header-logo {
            width: 18%;
            text-align: center;
        }

        .school-logo {
            width: 21mm;
            height: 21mm;
        }

        

        .exam-type {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .report-title {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }

        .school-name {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #064e3b;
            line-height: 1.2;
        }

        .header-subtitle {
            margin-top: 1mm;
            font-size: 7.5pt;
            color: #64748b;
        }

        .header-meta {
            width: 27%;
            text-align: right;
            font-size: 7pt;
            line-height: 1.5;
            color: #64748b;
        }

        .header-meta strong {
            color: #334155;
        }

        .divider {
            border-top: 2pt solid #047857;
            margin: 2mm 0 4mm;
        }

        /* ========================================
           INFORMASI UJIAN
        ======================================== */

        .info-table {
            margin-bottom: 5mm;
        }

        .info-table td {
            padding: 1.5mm 2mm;
            vertical-align: middle;
        }

        .info-label {
            width: 18%;
            color: #64748b;
            font-size: 7.5pt;
        }

        .info-value {
            width: 32%;
            font-weight: bold;
            color: #334155;
        }

        /* ========================================
           JUDUL BAGIAN
        ======================================== */

        .section-title {
            margin-bottom: 2.5mm;
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        /* ========================================
           TABEL PESERTA
        ======================================== */

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
            word-wrap: break-word;
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

        /* ========================================
           CATATAN
        ======================================== */

        .note {
            margin-top: 2mm;
            padding: 2.5mm 3mm;
            background: #f8fafc;
            border-left: 2pt solid #047857;
            color: #64748b;
            font-size: 7pt;
            page-break-inside: avoid;
        }

        /* ========================================
           TANDA TANGAN
        ======================================== */

        .signature {
            margin-top: 8mm;
            page-break-inside: avoid;
        }

        .signature-block {
            width: 42%;
            text-align: center;
            vertical-align: top;
        }

        .signature-space {
            height: 18mm;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* ========================================
   COVER
======================================== */

        .cover {
            /* height: 265mm; */
            text-align: center;
            position: relative;
            page-break-after: always;
        }

        .cover-inner {
            border: 3px double #047857;
            height: 238mm;
            padding: 15mm 12mm;
            position: relative;
        }

        .cover-logo {
            width: 35mm;
            height: 35mm;
            margin: 15mm auto 10mm;
        }

        .cover-school {
            font-size: 20pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            /* margin-bottom: 5mm; */
        }

        .cover-divider {
            width: 55%;
            margin: 8mm auto;
            border-top: 2pt solid #047857;
        }

        .cover-title {
            font-size: 16pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            /* margin-top: 18mm; */
        }

        .cover-subtitle {
            font-size: 12pt;
            color: #64748b;
            /* margin-top: 5mm; */
        }

        .cover-info {
            margin-top: 18mm;
            font-size: 11pt;
            line-height: 2;
        }

        .cover-footer {
            position: absolute;
            bottom: 10mm;
            left: 20;
            right: 5;
            color: #64748b;
            font-size: 9pt;
            width: 90%;
            border: .2mm solid #064e3b;
        }

        /* ========================================
   PAGE BREAK
======================================== */

        /* .page-break {
            page-break-before: always;
        } */

        /* .section-title {
            page-break-after: avoid;
        }

        .data-table {
            page-break-inside: auto;
        } */
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
    @endphp


    {{-- ========================================
     COVER LAPORAN
======================================== --}}

    <div class="cover">
        <div class="cover-inner">

            <img
                src="{{ public_path('img/logo.png') }}"
                class="cover-logo"
                alt="Logo Sekolah">

            <p class="cover-school">
                {{ strtoupper($schedule['school'] ?? 'NAMA SEKOLAH') }}
            </p>

            <div class="cover-divider"></div>

            

            <h1 class="cover-title">
                LAPORAN
                HASIL UJIAN
            </h1>
            <p style="font-size: 13pt; font-weight: bold;">
                {{ strtoupper($schedule['exam_type'] ?? 'UJIAN') }}
            </p>

            <p class="cover-subtitle">
                Tahun Pelajaran {{ $schedule['academic_year'] ?? '-' }}
            </p>

            <div class="cover-divider"></div>

            <div class="cover-info">
                <p>
                    <strong>Mata Pelajaran :</strong>
                    {{ $schedule['subject'] ?? '-' }}
                </p>

                <p>
                    <strong>Kelas :</strong>
                    {{ $schedule['class'] ?? '-' }}
                </p>
                <p>
                    <strong>Guru Pelajaran :</strong>
                    {{ $schedule['teacher'] ?? '..........................' }}
                </p>

            </div>

            <div class="cover-footer">
                Dokumen Resmi Laporan Hasil Ujian <br>
                Dicetak pada {{ $print_date }}
            </div>

        </div>
    </div>

    {{-- ========================================
         HEADER LAPORAN
    ======================================== --}}

    <table class="header">
        <tr>
            <td class="header-logo">
                <img
                    src="{{ public_path('img/logo.png') }}"
                    alt="Logo Sekolah"
                    class="school-logo">
            </td>

            <td class="header-content">
                <!-- <p class="report-title">
                    Laporan Hasil Ujian
                </p> -->

                <p class="report-title">
                    {{ strtoupper($schedule['exam_type'] ?? 'SUSULAN TES KEMAMPUAN AKADEMIK SMK') }}
                </p>

                <p class="school-name">
                    {{ strtoupper($schedule['school'] ?? 'SMKS IT RAUDHATUL ULUUM') }}
                </p>

                <p class="header-subtitle">
                    @if (!empty($schedule['academic_year']))
                    Tahun Pelajaran {{ $schedule['academic_year'] }}
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- ========================================
         INFORMASI UJIAN
    ======================================== --}}

    <table class="info-table">
        <tr>
            <td class="info-label">Mata Pelajaran</td>
            <td class="info-value">
                {{ $schedule['subject'] ?? '-' }}
            </td>

            <td class="info-label">Tanggal Ujian</td>
            <td class="info-value">
                {{ $examDate }}
            </td>
        </tr>

        <tr>
            

            <td class="info-label">Kelas</td>
            <td class="info-value">
                {{ $schedule['class'] ?? '-' }}
            </td>
        </tr>
    </table>

    {{-- ========================================
         REKAPITULASI NILAI PESERTA
    ======================================== --}}

    <h2 class="section-title">
        Rekapitulasi Nilai Peserta
    </h2>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th width="10%">Username</th>
                <th>Nama Peserta</th>
                <th width="8%">Benar</th>
                <th width="8%">Salah</th>
                <th width="8%">Nilai</th>
                <th width="13%">Waktu</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($participants as $index => $participant)
            <tr>
                <td class="text-center">
                    {{ $index + 1 }}
                </td>

                <td class="text-center">
                    {{ $participant['username'] ?? '-' }}
                </td>

                <td class="participant-name">
                    {{ $participant['name'] ?? '-' }}
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
                    {{ $minutes($participant['time_spent_seconds'] ?? null) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-row">
                    Belum ada siswa yang terdaftar pada kelas ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ========================================
         CATATAN
    ======================================== --}}

    <div class="note">
        <strong>Catatan:</strong>
        Nilai akhir dihitung dari skor perolehan dibagi skor maksimum,
        kemudian dikonversi ke skala 0–100.
        Peserta dengan nilai di bawah 70 dinyatakan belum tuntas.
    </div>

    {{-- ========================================
         TANDA TANGAN
    ======================================== --}}

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

    <div style="page-break-after: always;"></div>


    {{-- ========================================
         HEADER LAPORAN
    ======================================== --}}

    <table class="header">
        <tr>
            <td class="header-logo">
                <img
                    src="{{ public_path('img/logo.png') }}"
                    alt="Logo Sekolah"
                    class="school-logo">
            </td>

            <td class="header-content">
                <!-- <p class="report-title">
                    Laporan Hasil Ujian
                </p> -->

                <p class="report-title">
                    {{ strtoupper($schedule['exam_type'] ?? 'SUSULAN TES KEMAMPUAN AKADEMIK SMK') }}
                </p>

                <p class="school-name">
                    {{ strtoupper($schedule['school'] ?? 'SMKS IT RAUDHATUL ULUUM') }}
                </p>

                <p class="header-subtitle">
                    @if (!empty($schedule['academic_year']))
                    Tahun Pelajaran {{ $schedule['academic_year'] }}
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- ========================================
         INFORMASI UJIAN
    ======================================== --}}

    <table class="info-table">
        <tr>
            <td class="info-label">Mata Pelajaran</td>
            <td class="info-value">
                {{ $schedule['subject'] ?? '-' }}
            </td>

            <td class="info-label">Tanggal Ujian</td>
            <td class="info-value">
                {{ $examDate }}
            </td>
        </tr>

        <tr>
            

            <td class="info-label">Kelas</td>
            <td class="info-value">
                {{ $schedule['class'] ?? '-' }}
            </td>
        </tr>
    </table>

    {{-- ========================================
     ABSENSI PESERTA
======================================== --}}

<div class="page-break"></div>

<h2 class="section-title">
    DAFTAR HADIR PESERTA UJIAN
</h2>


<table class="data-table">
    <thead>
        <tr>
            <th width="5%">No.</th>
            <th width="13%">Username</th>
            <th>Nama Peserta</th>
            <th width="17%">Kehadiran</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($participants as $index => $participant)
            <tr>
                <td class="text-center">
                    {{ $index + 1 }}
                </td>

                <td class="text-center">
                    {{ $participant['username'] ?? '-' }}
                </td>

                <td>
                    {{ $participant['name'] ?? '-' }}
                </td>

                <td class="text-center">
                    <strong>
                        {{ $participant['attendance_status'] }}
                    </strong>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="empty-row">
                    Tidak ada data peserta.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- ========================================
         TANDA TANGAN
    ======================================== --}}

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

<div style="page-break-after: always;"></div>

{{-- ========================================
     REKAPITULASI SOAL
======================================== --}}
{{-- ========================================
         HEADER LAPORAN
    ======================================== --}}

    <table class="header">
        <tr>
            <td class="header-logo">
                <img
                    src="{{ public_path('img/logo.png') }}"
                    alt="Logo Sekolah"
                    class="school-logo">
            </td>

            <td class="header-content">
                <!-- <p class="report-title">
                    Laporan Hasil Ujian
                </p> -->

                <p class="report-title">
                    {{ strtoupper($schedule['exam_type'] ?? 'SUSULAN TES KEMAMPUAN AKADEMIK SMK') }}
                </p>

                <p class="school-name">
                    {{ strtoupper($schedule['school'] ?? 'SMKS IT RAUDHATUL ULUUM') }}
                </p>

                <p class="header-subtitle">
                    @if (!empty($schedule['academic_year']))
                    Tahun Pelajaran {{ $schedule['academic_year'] }}
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- ========================================
         INFORMASI UJIAN
    ======================================== --}}

    <table class="info-table">
        <tr>
            <td class="info-label">Mata Pelajaran</td>
            <td class="info-value">
                {{ $schedule['subject'] ?? '-' }}
            </td>

            <td class="info-label">Tanggal Ujian</td>
            <td class="info-value">
                {{ $examDate }}
            </td>
        </tr>

        <tr>
            

            <td class="info-label">Kelas</td>
            <td class="info-value">
                {{ $schedule['class'] ?? '-' }}
            </td>
        </tr>
    </table>


<h2 class="section-title">
    REKAPITULASI ANALISIS SOAL
</h2>


<table class="data-table">
    <thead>
        <tr>
            <th width="5%">No.</th>
            <th width="14%">Type Soal</th>
            <th> Soal</th>
            <th width="8%">Bobot</th>
            <th width="8%">Dijawab</th>
            <th width="8%">Benar</th>
            <th width="8%">Ketepatan</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($questionAnalysis as $question)
            <tr>
                <td class="text-center">
                    {{ $question['number'] }}
                </td>

                <td>
                    {{ $question['type'] }}
                </td>
                <td>
                    {{ $question['body'] }}
                </td>

                <td class="text-center">
                    {{ $score($question['score']) }}
                </td>

                <td class="text-center">
                    {{ $question['answered_count'] }}
                </td>

                <td class="text-center">
                    {{ $question['correct_count'] }}
                </td>

                <td class="text-center score">
                    {{ $question['accuracy_percentage'] !== null
                        ? $score($question['accuracy_percentage']) . '%'
                        : '-' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="empty-row">
                    Belum tersedia analisis soal.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- ========================================
         TANDA TANGAN
    ======================================== --}}

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