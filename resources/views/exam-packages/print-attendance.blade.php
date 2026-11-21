<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir Peserta Ujian</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .logo-box {
            width: 60px;
            height: 60px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            font-size: 8px;
            font-weight: bold;
        }
        .title-container {
            text-align: center;
            vertical-align: top;
        }
        .title-container h3 {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .title-container h4 {
            margin: 3px 0 0 0;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Tabel Informasi / Identitas */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        /* Tabel Data Peserta */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 10px;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #bfa15f; /* Menyesuaikan warna header tabel kecoklatan/abu gelap di gambar */
            color: #000;
            text-align: center;
            font-weight: bold;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Keterangan & Tanda Tangan */
        .footer-section {
            margin-top: 10px;
            font-size: 9px;
            page-break-inside: avoid;
        }
        .footer-section ol {
            margin: 2px 0 0 0;
            padding-left: 15px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
        }
        .signature-space {
            height: 50px;
        }
    </style>
</head>
<body>

    <!-- KOP / HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 12%;">
                <div class="logo-box">
                    <!-- Jika ada logo gambar, bisa pakai: <img src="{{ public_path('images/logo.png') }}" width="45"> -->
                    TUT WURI<br>HANDAYANI
                </div>
            </td>
            <td class="title-container" style="width: 76%;">
                <h3>DAFTAR HADIR PESERTA</h3>
                <h4>{{ strtoupper($schedule->examPackage->examType->name ?? 'SUSULAN TES KEMAMPUAN AKADEMIK SMK') }}</h4>
                <h4>TAHUN {{ $schedule->examPackage->academicYear->name ?? '2026' }}</h4>
            </td>
            <td style="width: 12%;"></td>
        </tr>
    </table>

    <!-- INFORMASI DETAIL -->
    <table class="info-table">
        <tr>
            <td style="width: 18%;">KOTA/KABUPATEN</td>
            <td style="width: 2%;">:</td>
            <td style="width: 50%;">{{ strtoupper($schedule->examPackage->school->city ?? 'KAB. SUBANG') }}</td>
            <td style="width: 8%;">KODE</td>
            <td style="width: 2%;">:</td>
            <td style="width: 20%;">{{ substr($schedule->room->code ?? '230178', 0, 2) }}</td>
        </tr>
        <tr>
            <td>SEKOLAH/MADRASAH</td>
            <td>:</td>
            <td>{{ strtoupper($schedule->examPackage->school->name ?? 'SMKS IT RAUDHATUL ULUUM') }}</td>
            <td>KODE</td>
            <td>:</td>
            <td>{{ substr($schedule->room->code ?? '230178', 2) }}</td>
        </tr>
        <tr>
            <td>ID SERVER / RUANG</td>
            <td>:</td>
            <td>{{ $schedule->room->server_id ?? 'K02230178-AX7Z' }} / {{ $schedule->room->name ?? '1' }}</td>
            <td>SESI</td>
            <td>:</td>
            <td>{{ $schedule->session->name ?? '1' }}</td>
        </tr>
        <tr>
            <td>HARI</td>
            <td>:</td>
            <td>
                @php
                    $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                    $engDay = date('l', strtotime($schedule->exam_date));
                    echo $days[$engDay] ?? $engDay;
                @endphp
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                TANGGAL : {{ date('d F Y', strtotime($schedule->exam_date)) }}
            </td>
            <td>PUKUL</td>
            <td>:</td>
            <td>{{ date('H:i', strtotime($schedule->open_time)) }}-{{ date('H:i', strtotime($schedule->close_time)) }}</td>
        </tr>
    </table>

    <!-- TABEL UTAMA PESERTA -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th style="width: 24%;">Username</th>
                <th style="width: 40%;">Nama Peserta</th>
                <th style="width: 15%;">Tanda Tangan</th>
                <th style="width: 15%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $index => $participant)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $participant['username'] }}</td>
                    <td>{{ $participant['name'] }}</td>
                    <td style="padding-left: 10px;">{{ $index + 1 }}.</td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada peserta ujian pada sesi/ruangan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- KETERANGAN / CATATAN BAWAH -->
    <div class="footer-section">
        <strong>Keterangan :</strong>
        <ol>
            <li>Dibuat rangkap 3 (tiga), masing-masing untuk sekolah, kota/kab dan Provinsi.</li>
            <li>Pengawas ruang menyilang Nama Peserta yang tidak hadir.</li>
            <li>Daftar hadir untuk pusat di upload melalui laman TKA.</li>
        </ol>
    </div>

    <!-- TANDA TANGAN PENGAWAS & PROKTOR -->
    <table class="signature-table">
        <tr>
            <td>
                Proktor
                <div class="signature-space"></div>
                ( ................................................... )<br>
                NIP. ................................................
            </td>
            <td>
                Pengawas
                <div class="signature-space"></div>
                ( ................................................... )<br>
                NIP. ................................................
            </td>
        </tr>
    </table>

</body>
</html>