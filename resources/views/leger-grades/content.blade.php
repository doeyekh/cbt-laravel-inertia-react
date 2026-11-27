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

    $className = $class['name']
    ?? ($filters['labels']['class'] ?? '-');

    $schoolName = is_array($school ?? null)
    ? ($school['name'] ?? null)
    : ($school ?? null);

    $schoolName = $schoolName ?: ($filters['labels']['school'] ?? '-');

    $headmasterName = is_array($school ?? null)
    ? ($school['headmaster_name'] ?? null)
    : null;

    $headmasterName = $headmasterName ?: '-';

    $adminName = is_array($school ?? null)
    ? ($school['admin_name'] ?? null)
    : null;

    $adminName = $adminName ?: '-';
    @endphp

    <div class="title">Leger Nilai</div>

    <div class="subtitle">
        Rekap nilai ujian {{ $filters['labels']['exam_type'] ?? '-' }}
    </div>

    <div class="meta">
        Sekolah: <strong>{{ $schoolName }}</strong>
        &nbsp;|&nbsp;
        Tahun Ajaran: <strong>{{ $filters['labels']['academic_year'] ?? '-' }}</strong>
        &nbsp;|&nbsp;
        Kelas: <strong>{{ $className }}</strong>
        &nbsp;|&nbsp;
        Mapel: <strong>{{ $filters['labels']['subject'] ?? '-' }}</strong>
        &nbsp;|&nbsp;
        Jenis Ujian: <strong>{{ $filters['labels']['exam_type'] ?? '-' }}</strong>
    </div>
    <table class="header">
        <tr>
            <td class="header-logo">

                @if ($logo_path)
                <img
                    src="{{ $logo_path }}"
                    alt="Logo Lembaga"
                    class="logo">
                @endif
            </td>
        </tr>
    </table>

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
                <th class="text-center" width="5%">No</th>
                <th width="10%">Nomor Peserta</th>
                <th width="10%">NISN</th>
                <th>Nama Siswa</th>

                @foreach ($columns as $column)
                <th class="text-center">
                    {{ $column['name'] }}
                </th>
                @endforeach

                <th class="text-center" width="5%">Rata-rata</th>
                <th class="text-center" width="5%">Ujian</th>
                <th class="text-center" width="5%">Tuntas</th>
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

    {{-- Tanda tangan --}}
    <table class="signature">
        <tr>
            <td>
                <p>Mengetahui,</p>
                <p>Kepala Sekolah</p>

                <div class="signature-space"></div>

                <p class="signature-name">{{ $headmasterName }}</p>
            </td>

            <td>
                <p>Admin Sekolah</p>

                <div class="signature-space"></div>

                <p class="signature-name">{{ $adminName }}</p>
            </td>
        </tr>
    </table>

    <div class="foot">
        Dicetak pada {{ $print_date }}
    </div>

</section>