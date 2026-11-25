
<section class="content-page">

    @php
        $format = static fn ($value): string => $value === null
            ? '-'
            : number_format((float) $value, 1) . '%';

        $summary = array_merge([
            'student_count' => 0,
            'subject_count' => 0,
            'average_percentage' => null,
            'passed_percentage' => null,
            'highest_percentage' => null,
            'lowest_percentage' => null,
        ], $summary ?? []);

        $columns = $columns ?? [];
        $rows = $rows ?? [];
    @endphp

    <div class="title">Leger Nilai</div>

    <div class="subtitle">
        Rekap nilai ujian per mata pelajaran
    </div>

    <div class="meta">
        Sekolah: <strong>{{ $school['name'] ?? '-' }}</strong>
        &nbsp;|&nbsp;
        Tahun Ajaran: <strong>{{ $filters['labels']['academic_year'] ?? '-' }}</strong>
        &nbsp;|&nbsp;
        Kelas: <strong>{{ $filters['labels']['class'] ?? '-' }}</strong>
        &nbsp;|&nbsp;
        Mapel: <strong>{{ $filters['labels']['subject'] ?? '-' }}</strong>
        &nbsp;|&nbsp;
        Jenis Ujian: <strong>{{ $filters['labels']['exam_type'] ?? '-' }}</strong>
    </div>

    <div class="divider"></div>

    {{-- Summary --}}
    <table class="summary">
        <tr>
            <td>
                <div class="label">Jumlah Siswa</div>
                <div class="value">
                    {{ $summary['student_count'] }}
                </div>
            </td>

            <td>
                <div class="label">Mata Pelajaran</div>
                <div class="value">
                    {{ $summary['subject_count'] }}
                </div>
            </td>

            <td>
                <div class="label">Rata-rata</div>
                <div class="value">
                    {{ $format($summary['average_percentage']) }}
                </div>
            </td>

            <td>
                <div class="label">Ketuntasan</div>
                <div class="value">
                    {{ $format($summary['passed_percentage']) }}
                </div>
            </td>

            <td>
                <div class="label">Nilai Tertinggi</div>
                <div class="value">
                    {{ $format($summary['highest_percentage']) }}
                </div>
            </td>

            <td>
                <div class="label">Nilai Terendah</div>
                <div class="value">
                    {{ $format($summary['lowest_percentage']) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Tabel nilai --}}
    <table class="data">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Nomor Peserta</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>

                @foreach ($columns as $column)
                    <th class="text-center">
                        {{ $column['name'] }}
                    </th>
                @endforeach

                <th class="text-center">Rata-rata</th>
                <th class="text-center">Ujian</th>
                <th class="text-center">Tuntas</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="text-center">
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $row['participant_number'] ?? '-' }}
                    </td>

                    <td>
                        {{ $row['nisn'] ?? '-' }}
                    </td>

                    <td>
                        {{ $row['name'] ?? '-' }}
                    </td>

                    <td>
                        {{ $row['class'] ?? '-' }}
                    </td>

                    @foreach ($columns as $column)
                        @php
                            $value = $row['scores'][(string) $column['id']] ?? null;
                        @endphp

                        <td class="text-right">
                            {{ $format($value) }}
                        </td>
                    @endforeach

                    <td class="text-right">
                        <strong>{{ $format($row['average'] ?? null) }}</strong>
                    </td>

                    <td class="text-center">
                        {{ $row['completed_count'] ?? 0 }}
                    </td>

                    <td class="text-center">
                        {{ $row['passed_count'] ?? 0 }}
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="{{ 5 + count($columns) + 3 }}" class="text-center">
                        Tidak ada data nilai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">
        Dicetak pada {{ $print_date }}
    </div>

</section>
