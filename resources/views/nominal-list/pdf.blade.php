
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Nominatif Peserta Ujian</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.4;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        p {
            margin: 0;
        }

        /* HEADER */

        .header {
            margin-bottom: 2mm;
        }

        .logo-cell {
            width: 16%;
            text-align: center;
            vertical-align: middle;
        }

        .logo {
            width: 20mm;
            height: 20mm;
            object-fit: contain;
        }

        .header-title {
            text-align: center;
            vertical-align: middle;
        }

        .header-title h1 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.5;
        }

        .header-title p {
            font-size: 9pt;
            font-weight: bold;
        }

        .header-info {
            margin-top: 2mm;
            margin-bottom: 2mm;
            font-size: 8pt;
        }

        .header-info td {
            padding: 1.2mm 0;
        }

        .info-label {
            width: 30%;
        }

        .info-value {
            width: 70%;
            border-bottom: 0.5pt solid #111;
            padding-left: 2mm !important;
        }

        .divider {
            border-top: 1.5pt solid #111;
            margin: 3mm 0;
        }

        /* SECTION */

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        /* DATA TABLE */

        .data-table {
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table th {
            border: 1pt solid #111;
            padding: 2.2mm 1mm;
            background: #e5e7eb;
            color: #111;
            font-size: 7.5pt;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
        }

        .data-table td {
            border: 0.8pt solid #111;
            padding: 2mm 1.5mm;
            font-size: 7.5pt;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        .data-table tbody tr {
            page-break-inside: avoid;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .text-center {
            text-align: center;
        }

        .empty-row {
            text-align: center;
            padding: 8mm !important;
        }

        .signature-number {
            text-align: center;
            font-weight: bold;
        }

        /* NOTE */

        .note {
            margin-top: 3mm;
            font-size: 7.5pt;
            line-height: 1.6;
        }

        .note-title {
            font-weight: bold;
            font-style: italic;
        }

        /* RECAP */

        .recap {
            width: 43%;
            margin-top: 5mm;
            border-collapse: collapse;
        }

        .recap td {
            border: 0.8pt solid #111;
            padding: 1.5mm 2mm;
            font-size: 7pt;
        }

        /* SIGNATURE */

        .signature {
            margin-top: 8mm;
            page-break-inside: avoid;
        }

        .signature td {
            text-align: center;
            vertical-align: top;
            font-size: 8pt;
        }

        .signature-space {
            height: 19mm;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            margin-top: 8mm;
            border: 1pt solid #111;
            text-align: center;
            padding: 2mm;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

    </style>
</head>

<body>

@php

    $school = !empty($filters['school_id'])
        ? \App\Models\School::with('headmaster')
            ->find($filters['school_id'])
        : null;

    $schoolName = $school?->name ?? 'Semua Sekolah';

    $headmasterName = $school?->headmaster?->name ?? '-';

    $yearName = !empty($filters['academic_year_id'])
        ? (\App\Models\AcademicYear::find(
            $filters['academic_year_id']
        )?->name ?? 'Semua Tahun')
        : 'Semua Tahun';

    $className = !empty($filters['class_id'])
        ? (\App\Models\SchoolClass::find(
            $filters['class_id']
        )?->name ?? 'Semua Kelas')
        : 'Semua Kelas';

@endphp

{{-- HEADER --}}

<table class="header">
    <tr>
        <td class="logo-cell">
            @if(file_exists(public_path('img/logo.png')))
                <img
                    src="{{ public_path('img/logo.png') }}"
                    class="logo"
                >
            @endif
        </td>

        <td class="header-title">
            <h1>
                {{ strtoupper($schoolName) }}
                </h1>
                
                <p>
                    DAFTAR NOMINATIF PESERTA UJIAN
            </p>

            <p>
                TAHUN PELAJARAN {{ $yearName }}
            </p>
        </td>

        <td width="10%"></td>
    </tr>
</table>

<div class="divider"></div>

{{-- IDENTITAS UJIAN --}}

<table class="header-info">
    <tr>
        <td class="info-label">
            SEKOLAH/MADRASAH
        </td>

        <td width="3%">:</td>

        <td class="info-value">
            {{ $schoolName }}
        </td>
    </tr>

    <tr>
        <td class="info-label">
            TAHUN PELAJARAN
        </td>

        <td>:</td>

        <td class="info-value">
            {{ $yearName }}
        </td>
    </tr>

    <tr>
        <td class="info-label">
            KELAS
        </td>

        <td>:</td>

        <td class="info-value">
            {{ $className }}
        </td>
    </tr>
</table>


<table class="data-table">

    <thead>
        <tr>
            <th width="6%">No.</th>
            <th width="20%">Username</th>
            <th width="38%">Nama Peserta</th>
            <th width="17%">Sesi</th>
            <th width="19%">Ruang</th>
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



{{-- TANDA TANGAN KEPALA SEKOLAH --}}

@if($school)

<table class="signature">

    <tr>
        <td width="58%"></td>

        <td width="42%">

            <p>
                Mengetahui,
            </p>

            <p>
                Kepala Sekolah
            </p>

            <div class="signature-space"></div>

            <p class="signature-name">
                {{ $headmasterName }}
            </p>

        </td>
    </tr>

</table>

@endif

</body>
</html>
