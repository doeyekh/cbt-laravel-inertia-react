<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Kartu Peserta {{ $school['code'] }}</title>

    <style>
        /* =========================================
           PENGATURAN HALAMAN
        ========================================= */
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

        /* =========================================
           GRID KARTU (2 KOLOM x 4 BARIS = 8 / HAL)
        ========================================= */
        .card-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 2mm 3mm;

        }

        .card-cell {
            width: 47%;
            padding: 0;
            vertical-align: top;
            margin-right: 2mm;
            
        }

        .card {
            width: 94%;
            height: 64mm;
            padding: 2mm;
            border: 0.8pt solid #475569;
            page-break-inside: avoid;
            overflow: hidden;
            /* border: 1mm solid red; */
        }

        /* =========================================
           HEADER SEKOLAH
        ========================================= */
        .header {
            width: 100%;
            height: 10mm;
            border-collapse: collapse;
            table-layout: fixed;
            border-bottom: 0.8pt solid #475569;
            margin-bottom: 2mm;
        }

        .header-logo {
            width: 10%;
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }

        .header-logo img {
            max-width: 10mm;
            max-height: 10mm;
            display: block;
            margin: auto;
        }

        .header-content {
            text-align: center;
            vertical-align: middle;
            padding: 0 1mm;
        }

        .header-title {
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .header-year {
            font-size: 6.5pt;
        }

        .header-divider {
            border: 0;
            border-top: 0.8pt solid #94a3b8;
            margin: 1mm 0 2mm;
        }

        /* =========================================
           IDENTITAS PESERTA
        ========================================= */
        .identity {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .photo-cell {
            width: 19%; /* 18mm lebar foto + 2mm jarak ke identitas */
            /* padding: 0 2mm 0 0; */
            text-align: left;
            vertical-align: top;
        }

        .photo {
            width: 18mm;
            height: 24mm;
            border: 0.6pt solid #cbd5e1;
            display: block;
            object-fit: cover;
        }

        .details-cell {
            /* width: 71mm; */
            padding: 0;
            vertical-align: top;
        }

        .details-table {
            margin-left: 2mm;
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .details-table td {
            padding: 0.65mm 0.50mm;
            vertical-align: top;
            font-size: 6.7pt;
            line-height: 1.2;
        }

        .detail-label {
            width: 26%;
            color: #475569;
        }

        .detail-separator {
            width: 2%;
            text-align: center;
        }

        .detail-value {
            font-weight: bold;
            word-wrap: break-word;
        }

        .participant-number {
            font-size: 8pt;
            letter-spacing: 0.3pt;
        }

        /* =========================================
           USERNAME & PASSWORD
        ========================================= */
        .credential-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 2mm;
        }

        .credential-cell {
            width: 49%;
            padding: 1.5mm;
            border: 0.7pt solid #94a3b8;
            vertical-align: middle;
        }

        .credential-spacer {
            width: 2%;
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

        /* =========================================
           FOOTER & UTILITAS
        ========================================= */
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

        .page-break {
            page-break-after: always;
        }

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
                {{-- HEADER SEKOLAH --}}
                <table class="header" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="header-logo">
                            @if(file_exists(public_path('img/logo.png')))
                                <img
                                    src="{{ public_path('img/logo.png') }}"
                                    class="logo"
                                >
                            @endif
                        </td>

                        <td class="header-content">
                            <div class="header-title">
                                KARTU PESERTA UJIAN<br>
                                {{ strtoupper($school['name']) }}
                            </div>
                            <div class="header-year">
                                TAHUN AJARAN {{ $academic_year['name'] }}
                            </div>
                        </td>

                        <td class="header-logo">
                            @if (!empty($school['logo']))
                                <img src="{{ $school['logo'] }}" alt="Logo Sekolah">
                            @endif
                        </td>
                    </tr>
                </table>

                {{-- IDENTITAS PESERTA --}}
                <table class="identity" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="photo-cell">
                            @if (!empty($participant['photo']))
                                <img src="{{ $participant['photo'] }}" class="photo" alt="Foto Peserta">
                            @else
                                <div class="photo"></div>
                            @endif
                        </td>

                        <td class="details-cell">
                            <table class="details-table" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="detail-label">No. Peserta</td>
                                    <td class="detail-separator">:</td>
                                    <td class="detail-value participant-number">{{ $participant['participant_number'] }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Nama</td>
                                    <td class="detail-separator">:</td>
                                    <td class="detail-value">{{ $participant['name'] }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">NIS</td>
                                    <td class="detail-separator">:</td>
                                    <td class="detail-value">{{ $participant['nis'] }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Kelas</td>
                                    <td class="detail-separator">:</td>
                                    <td class="detail-value">{{ $participant['class_name'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Sesi / Ruang</td>
                                    <td class="detail-separator">:</td>
                                    <td class="detail-value">
                                        {{ $participant['session_name'] ?? '-' }} / {{ $participant['room_name'] ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                {{-- USERNAME & PASSWORD --}}
                <table class="credential-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="credential-cell">
                            <div class="credential-label">Username</div>
                            <div class="credential-value">{{ $participant['username'] }}</div>
                        </td>
                        <td class="credential-spacer"></td>
                        <td class="credential-cell">
                            <div class="credential-label">Password</div>
                            <div class="credential-value">{{ $participant['password'] }}</div>
                        </td>
                    </tr>
                </table>

                {{-- FOOTER --}}
               {{-- FOOTER --}}
                <table class="footer" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="footer-info">
                            Harap membawa kartu saat ujian.<br>
                            <span>Dicetak: {{ $print_date }}</span>
                        </td>
                        <td class="footer-signature">
                            <div>Kepala Sekolah,</div>
                            <div class="principal-name">{{ $school['headmaster_name'] }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>

        {{-- Jika iterasi terakhir ganjil, tambahkan sel kosong agar kolom kanan tetap terkunci --}}
        @if ($loop->last && $loop->iteration % 2 === 1)
            <td class="card-cell"></td>
        @endif

        {{-- Tutup baris setiap 2 peserta atau di akhir --}}
        @if ($loop->iteration % 2 === 0 || $loop->last)
            </tr>
        @endif

        {{-- Tutup tabel setiap 8 peserta atau di akhir --}}
        @if ($loop->iteration % 8 === 0 || $loop->last)
            </table>

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