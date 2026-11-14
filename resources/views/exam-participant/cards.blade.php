<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Kartu Peserta {{ $school['code'] }}</title>

    <style>
        /* =====================================================
           PENGATURAN KERTAS
        ===================================================== */

        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7pt;
            color: #1e293b;
        }

        /* =====================================================
           GRID KARTU: 2 KOLOM
        ===================================================== */

        .card-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 2mm 3mm;
        }

        .card-cell {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }

        /* =====================================================
           KARTU PESERTA
        ===================================================== */

        .card {
            width: 96mm;
            height: 66mm;
            padding: 2.5mm;
            border: 0.8pt solid #475569;
            position: relative;
            page-break-inside: avoid;
        }

        /* =====================================================
           HEADER
        ===================================================== */

        .header {
            width: 100%;
            height: 13mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-logo {
            width: 5mm;
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }

        .header-logo img {
            max-width: 10mm;
            max-height: 10mm;
        }

        .header-content {
            text-align: center;
            vertical-align: middle;
            padding: 0 1mm;
        }

        .header-title {
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .header-year {
            font-size: 6.5pt;
            margin-top: 1mm;
        }

        .header-divider {
            border: none;
            border-top: 0.8pt solid #475569;
            margin: 1mm 0 2mm;
        }

        /* =====================================================
           IDENTITAS PESERTA
        ===================================================== */

        .identity {
            width: 100%;
            border-collapse: collapse;
            /* table-layout: fixed; */
        }

        .photo-cell {
            width: 10mm;
    /* padding: 0 1mm 0 0; */
    text-align: left;
    vertical-align: top;
        }

        .photo {
            width: 18mm;
    height: 24mm;
    border: 0.6pt solid #cbd5e1;
    display: block;
        }

        .details-cell {
            width: auto;
    padding: 0;
    vertical-align: top;
        }

        .details-table {
            width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
        }

        .details-table td {
            padding: 0.65mm 0;
            vertical-align: top;
            font-size: 6.7pt;
            line-height: 1.2;
        }

        .detail-label {
            width: 21mm;
            color: #475569;
        }

        .detail-value {
            font-weight: bold;
            word-wrap: break-word;
        }

        .participant-number {
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
        }

        /* =====================================================
           USERNAME & PASSWORD
        ===================================================== */

        .credential-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 1.5mm 0;
            table-layout: fixed;
            margin-left: -1.5mm;
            margin-top: 2mm;
        }

        .credential-cell {
            width: 50%;
            padding: 1.5mm;
            border: 0.7pt solid #94a3b8;
            vertical-align: middle;
        }

        .credential-label {
            font-size: 5.8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 0.8mm;
        }

        .credential-value {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            word-wrap: break-word;
        }

        /* =====================================================
           FOOTER
        ===================================================== */

        .footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5mm;
            table-layout: fixed;
        }

        .footer td {
            font-size: 5.8pt;
            color: #64748b;
            vertical-align: middle;
            padding: 0;
        }

        .footer-info {
            width: 55%;
        }

        .footer-signature {
            width: 45%;
            text-align: right;
        }

        /* =====================================================
           PAGE BREAK
        ===================================================== */

        .page-break {
            page-break-after: always;
        }

        /* =====================================================
           DATA KOSONG
        ===================================================== */

        .empty {
            padding: 20mm;
            text-align: center;
            font-size: 10pt;
            color: #64748b;
        }

    </style>
</head>

<body>

@forelse ($participants as $participant)

    {{-- Buka tabel setiap 8 peserta --}}
    @if ($loop->iteration % 8 === 1)
        <table class="card-grid" cellpadding="0" cellspacing="0">
    @endif

    {{-- Buka baris setiap 2 peserta --}}
    @if ($loop->iteration % 2 === 1)
        <tr>
    @endif

        <td class="card-cell">

            <div class="card">

                {{-- ==========================================
                     HEADER SEKOLAH
                =========================================== --}}

                <table class="header" cellpadding="0" cellspacing="0">
                    <tr>

                        {{-- Logo kiri --}}
                        <td class="header-logo">
                            @if (!empty($school['logo_left']))
                                <img
                                    src="{{ $school['logo_left'] }}"
                                    alt="Logo"
                                >
                            @endif
                        </td>

                        {{-- Identitas sekolah --}}
                        <td class="header-content">

                            <div class="header-title">
                                KARTU PESERTA UJIAN
                                <br>
                                {{ strtoupper($school['name']) }}
                            </div>

                            <div class="header-year">
                                TAHUN AJARAN
                                {{ $academic_year['name'] }}
                            </div>

                        </td>

                        {{-- Logo sekolah --}}
                        <td class="header-logo">
                            @if (!empty($school['logo']))
                                <img
                                    src="{{ $school['logo'] }}"
                                    alt="Logo Sekolah"
                                >
                            @endif
                        </td>

                    </tr>
                </table>

                <hr class="header-divider">

                {{-- ==========================================
                     IDENTITAS PESERTA
                =========================================== --}}

                <table class="identity" cellpadding="0" cellspacing="0" border="1">
                    <tr>

                        {{-- Foto peserta --}}
                        <td class="photo-cell">

                            @if (!empty($participant['photo']))
                                <img
                                    src="{{ $participant['photo'] }}"
                                    class="photo"
                                    alt="Foto Peserta"
                                >
                            @else
                                <div class="photo"></div>
                            @endif

                        </td>

                        {{-- Detail peserta --}}
                        <td class="details-cell">

                            <table class="details-table" cellpadding="0" cellspacing="0">

                                <tr>
                                    <td class="detail-label">
                                        No. Peserta
                                    </td>

                                    <td class="detail-separator">:</td>

                                    <td class="detail-value participant-number">
                                        {{ $participant['participant_number'] }}
                                    </td>
                                </tr>

                                <tr>
                                    <td class="detail-label">
                                        Nama
                                    </td>

                                    <td class="detail-separator">:</td>

                                    <td class="detail-value">
                                        {{ $participant['name'] }}
                                    </td>
                                </tr>

                                <tr>
                                    <td class="detail-label">
                                        NIS
                                    </td>

                                    <td class="detail-separator">:</td>

                                    <td class="detail-value">
                                        {{ $participant['nis'] }}
                                    </td>
                                </tr>

                                <tr>
                                    <td class="detail-label">
                                        Kelas
                                    </td>

                                    <td class="detail-separator">:</td>

                                    <td class="detail-value">
                                        {{ $participant['class_name'] ?? '-' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td class="detail-label">
                                        Sesi / Ruang
                                    </td>

                                    <td class="detail-separator">:</td>

                                    <td class="detail-value">
                                        {{ $participant['session_name'] }} / {{ $participant['room_name'] }}
                                    </td>
                                </tr>


                                
                            </table>

                        </td>
                    </tr>
                </table>

                {{-- ==========================================
                     USERNAME & PASSWORD
                =========================================== --}}

                <table class="credential-table" cellpadding="0" cellspacing="0">
                    <tr>

                        <td class="credential-cell">

                            <div class="credential-label">
                                Username
                            </div>

                            <div class="credential-value">
                                {{ $participant['username'] }}
                            </div>

                        </td>

                        <td class="credential-cell">

                            <div class="credential-label">
                                Password
                            </div>

                            <div class="credential-value">
                                {{ $participant['password'] }}
                            </div>

                        </td>

                    </tr>
                </table>

                {{-- ==========================================
                     FOOTER
                =========================================== --}}

                <table class="footer" cellpadding="0" cellspacing="0">
                    <tr>

                        <td class="footer-info">
                            Harap membawa kartu saat ujian.
                        </td>

                        <td class="footer-signature">
                            Paraf petugas: ______________
                        </td>

                    </tr>
                </table>

            </div>

        </td>

    {{-- Tutup baris setiap 2 peserta --}}
    @if ($loop->iteration % 2 === 0 || $loop->last)
        </tr>
    @endif

    {{-- Tutup tabel setiap 8 peserta --}}
    @if ($loop->iteration % 8 === 0 || $loop->last)

        </table>

        {{-- Pindah halaman jika masih ada peserta --}}
        @if (!$loop->last)
            <div class="page-break"></div>
        @endif

    @endif

@empty

    <div class="empty">
        Belum ada peserta pada sekolah dan tahun pelajaran ini.
    </div>

@endforelse

</body>
</html>