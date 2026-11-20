
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Nominatif Peserta Ujian</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 16mm;
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

        /* ==============================
           HEADER
        ============================== */

        .header {
            margin-bottom: 3mm;
        }

        .school-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: #064e3b;
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
            margin: 2mm 0 4mm;
            border-top: 2pt solid #047857;
        }

        /* ==============================
           SECTION
        ============================== */

        .section-title {
            margin-bottom: 2mm;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: #064e3b;
        }

        /* ==============================
           DATA TABLE
        ============================== */

        .data-table {
            table-layout: fixed;
            margin-bottom: 4mm;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tfoot {
            display: table-footer-group;
        }

        .data-table th {
            padding: 2mm 1.5mm;
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
            padding: 1.8mm 1.5mm;
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

        .empty-row {
            padding: 8mm 2mm !important;
            text-align: center;
            color: #94a3b8;
        }

        /* ==============================
           NOTE
        ============================== */

        .note {
            margin-top: 2mm;
            padding: 2.5mm 3mm;
            background: #f8fafc;
            border-left: 2pt solid #047857;
            color: #64748b;
            font-size: 7pt;
        }

        .note strong {
            color: #334155;
        }

        /* ==============================
           SIGNATURE
        ============================== */

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
        $schoolName = !empty($filters['school_id'])
            ? (\App\Models\School::find($filters['school_id'])?->name ?? 'Semua Sekolah')
            : 'Semua Sekolah';

        $yearName = !empty($filters['academic_year_id'])
            ? (\App\Models\AcademicYear::find($filters['academic_year_id'])?->name ?? 'Semua Tahun')
            : 'Semua Tahun';

        $className = !empty($filters['class_id'])
            ? (\App\Models\SchoolClass::find($filters['class_id'])?->name ?? 'Semua Kelas')
            : 'Semua Kelas';

            $school = !empty($filters['school_id'])
            ? \App\Models\School::with('headmaster')->find($filters['school_id'])
            : null;

        $headmasterName = $school?->headmaster?->name ?? '-';
    @endphp

    {{-- HEADER --}}
    <table class="header">
        <tr>
            <td width="65%">
                <p class="school-name">
                    {{ $schoolName }}
                </p>

                <p class="report-title">
                    Daftar Nominatif Peserta Ujian
                </p>

                <p class="header-subtitle">
                    Tahun Pelajaran {{ $yearName }}
                    &middot;
                    Kelas {{ $className }}
                </p>
            </td>

            <td width="35%" class="header-meta">
                <p>Dicetak pada</p>
                <strong>{{ $print_date }}</strong>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- DAFTAR PESERTA --}}
    <h2 class="section-title">
        Daftar Peserta
    </h2>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th>Nama Peserta</th>
                <th width="15%">Username</th>
                <th width="15%">Sesi</th>
                <th width="15%">Ruang</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($participants as $index => $participant)
                <tr>
                    <td class="text-center">
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $participant['name'] ?? '-' }}
                    </td>

                    <td class="text-center">
                        {{ $participant['username'] ?? '-' }}
                    </td>

                    <td class="text-center">
                        {{ $participant['session_name'] ?? '-' }}
                    </td>

                    <td class="text-center">
                        {{ $participant['room_name'] ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-row">
                        Belum ada peserta yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- CATATAN --}}
    <div class="note">
        <strong>Catatan:</strong>
        Daftar ini memuat seluruh peserta ujian yang sesuai dengan
        filter sekolah, tahun pelajaran, dan kelas yang dipilih.
        Informasi sesi dan ruang menunjukkan penempatan peserta
        dalam pelaksanaan ujian.
    </div>

    {{-- TANDA TANGAN KEPALA SEKOLAH --}}
<table class="signature">
    <tr>
        <td width="58%"></td>

        <td width="42%" class="signature-block">
            <p>Mengetahui,</p>
            <p>Kepala Sekolah</p>

            <div class="signature-space"></div>

            <p class="signature-name">
                {{ $headmasterName }}
            </p>
        </td>
    </tr>
</table>

</body>

</html>
