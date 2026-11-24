<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #1e293b;
        }

        .title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #064e3b;
        }

        .subtitle {
            margin-top: 1mm;
            font-size: 8pt;
            color: #64748b;
        }

        .meta {
            margin-top: 2mm;
            font-size: 7.5pt;
            color: #475569;
        }

        .meta strong {
            color: #0f172a;
        }

        .divider {
            border-top: 2pt solid #047857;
            margin: 3mm 0 4mm;
        }

        .summary {
            width: 100%;
            margin-bottom: 4mm;
            border-collapse: collapse;
        }

        .summary td {
            border: 0.5pt solid #cbd5e1;
            padding: 2mm;
            text-align: center;
        }

        .summary .label {
            font-size: 7pt;
            color: #64748b;
        }

        .summary .value {
            margin-top: 1mm;
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th,
        table.data td {
            border: 0.5pt solid #cbd5e1;
            padding: 1.6mm 1.2mm;
        }

        table.data th {
            background: #ecfdf5;
            color: #065f46;
            font-size: 7pt;
            text-transform: uppercase;
        }

        table.data td {
            font-size: 7.5pt;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .foot {
            margin-top: 3mm;
            font-size: 7pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $format = static fn ($value): string => $value === null
            ? '-'
            : number_format((float) $value, 1).'%';
    @endphp

    <div class="title">Leger Nilai</div>
    <div class="subtitle">Rekap nilai ujian per mata pelajaran</div>

    <div class="meta">
        Sekolah: <strong>{{ $filters['labels']['school'] }}</strong> &nbsp;|&nbsp;
        Tahun Ajaran: <strong>{{ $filters['labels']['academic_year'] }}</strong> &nbsp;|&nbsp;
        Kelas: <strong>{{ $filters['labels']['class'] }}</strong> &nbsp;|&nbsp;
        Mapel: <strong>{{ $filters['labels']['subject'] }}</strong> &nbsp;|&nbsp;
        Jenis Ujian: <strong>{{ $filters['labels']['exam_type'] }}</strong>
    </div>

    <div class="divider"></div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Jumlah Siswa</div>
                <div class="value">{{ $summary['student_count'] }}</div>
            </td>
            <td>
                <div class="label">Mata Pelajaran</div>
                <div class="value">{{ $summary['subject_count'] }}</div>
            </td>
            <td>
                <div class="label">Rata-rata</div>
                <div class="value">{{ $format($summary['average_percentage']) }}</div>
            </td>
            <td>
                <div class="label">Ketuntasan</div>
                <div class="value">{{ $format($summary['passed_percentage']) }}</div>
            </td>
            <td>
                <div class="label">Nilai Tertinggi</div>
                <div class="value">{{ $format($summary['highest_percentage']) }}</div>
            </td>
            <td>
                <div class="label">Nilai Terendah</div>
                <div class="value">{{ $format($summary['lowest_percentage']) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Nomor Peserta</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Sekolah</th>
                @foreach ($columns as $column)
                    <th class="text-center">{{ $column['name'] }}</th>
                @endforeach
                <th class="text-center">Rata-rata</th>
                <th class="text-center">Ujian</th>
                <th class="text-center">Tuntas</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['participant_number'] ?? '-' }}</td>
                    <td>{{ $row['nisn'] ?? '-' }}</td>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    <td>{{ $row['class'] ?? '-' }}</td>
                    <td>{{ $row['school'] ?? '-' }}</td>

                    @foreach ($columns as $column)
                        @php
                            $value = $row['scores'][(string) $column['id']] ?? null;
                        @endphp
                        <td class="text-right">{{ $format($value) }}</td>
                    @endforeach

                    <td class="text-right"><strong>{{ $format($row['average']) }}</strong></td>
                    <td class="text-center">{{ $row['completed_count'] ?? 0 }}</td>
                    <td class="text-center">{{ $row['passed_count'] ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + count($columns) + 3 }}" class="text-center">
                        Tidak ada data nilai yang cocok dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">Dicetak pada {{ $print_date }}</div>
</body>

</html>