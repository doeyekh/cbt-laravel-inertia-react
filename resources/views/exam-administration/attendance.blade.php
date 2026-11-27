<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir Peserta Ujian</title>

    @include('exam-packages.partials.attendance-style')
</head>

<body>
    @forelse ($reports as $report)
        @include('exam-packages.partials.attendance-content', [
            'schedule' => $report['schedule'],
            'participants' => $report['participants'],
            'print_date' => $print_date,
        ])

        @unless ($loop->last)
            <div style="page-break-after: always;"></div>
        @endunless
    @empty
        <p style="text-align: center; margin-top: 30mm; color: #64748b;">
            Tidak ada jadwal ujian yang sesuai dengan mata pelajaran dan jenis ujian yang dipilih.
        </p>
    @endforelse
</body>

</html>
