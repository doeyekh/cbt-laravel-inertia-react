<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir Peserta Ujian</title>

    @include('exam-packages.partials.attendance-style')
</head>

<body>
    @include('exam-packages.partials.attendance-content', [
        'schedule' => $schedule,
        'participants' => $participants,
        'print_date' => $print_date,
    ])
</body>

</html>
