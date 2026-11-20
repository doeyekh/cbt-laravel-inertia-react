
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Daftar Nominatif Peserta</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 16mm 12mm;
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

        .header {
            margin-bottom: 3mm;
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

        .section-title {
            margin-bottom: 2mm;
            font-size: 9pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

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

        .empty-row {
            padding: 8mm 2mm !important;
            text-align: center;
            color: #94a3b8;
        }

        .note {
            margin-top: 2mm;
            padding: 2.5mm 3mm;
            background: #f8fafc;
            border-left: 2pt solid #047857;
            color: #64748b;
            font-size: 7pt;
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
        $schoolName = isset($filters['school_id']) && $filters['school_id']
            ? \App\Models\School::find($filters['school_id'])?->name ?? 'Semua Sekolah'
            : 'Semua Sekolah';

        $yearName = isset($filters['academic_year_id']) && $filters['academic_year_id']
            ? \App\Models\AcademicYear::find($filters['academic_year_id'])?->name ?? 'Semua Tahun'
            : 'Semua Tahun';

        $className = isset($filters['class_id']) && $filters['class_id']
            ? \App\Models\SchoolClass::find($filters['class_id'])?->name ?? 'Semua Kelas'
            : 'Semua Kelas';
    @endphp

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
                    @if (!empty($className))
                        · Kelas {{ $className }}
                    @endif
                </p>
            </td>
            <td width="35%" class="header-meta">
                <p>Dicetak pada</p>
                <strong>{{ $print_date }}</strong>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <h2 class="section-title">Daftar Peserta</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5mm;">No.</th>
                <th style="width: 26mm;">Nama Peserta</th>
                <th style="width: 17mm;">NISN</th>
                <th style="width: 17mm;">No. Peserta</th>
                <th>Kelas</th>
                <th style="width: 22mm;">Username</th>
                <th style="width: 18mm;">Kredensial</th>
                <th style="width: 18mm;">Penempatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($participants as $index => $participant)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="participant-name">
                        {{ $participant['name'] ?? '-' }}
                    </td>
                    <td class="text-center">
                        {{ $participant['nisn'] ?? '-' }}
                    </td>
                    <td class="text-center">
                        {{ $participant['participant_number'] ?? '-' }}
                    </td>
                    <td>
                        {{ $participant['class_name'] ?? '-' }}
                    </td>
                    <td class="text-center">
                        {{ $participant['username'] ?? '-' }}
                    </td>
                    <td class="text-center">
                        {{ $participant['has_credential'] ? 'Lengkap' : 'Belum' }}
                    </td>
                    <td class="text-center">
                        {{ $participant['has_placement'] ? 'Terpenuhi' : 'Belum' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-row">
                        Belum ada peserta yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        <strong>Catatan:</strong>
        Daftar ini menyeluruh peserta yang terdaftar pada kelas dan tahun
        pelajaran yang dipilih. Status kredensial menandakan apakah peserta
        telah memiliki username dan nomor peserta. Status penempatan
        menandakan apakah peserta telah diberi ruangan dan sesi ujian.
    </div>

</body>

</html>
