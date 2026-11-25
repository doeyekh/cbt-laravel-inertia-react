<div class="cover-page">

    <div class="inner-border"></div>

    <div class="logo-wrapper">
        @if ($logo_path)
            <img
                src="{{ $logo_path }}"
                alt="Logo Lembaga"
                class="logo"
            >
        @endif
    </div>

    <div class="institution">
        {{ $school }}
    </div>

    <div class="institution-subtitle">
        Dokumen Rekapitulasi Hasil Ujian
    </div>

    <div class="divider"></div>

    <div class="document-label">
        Dokumen Akademik
    </div>

    <div class="document-title">
        LEGER NILAI
    </div>

    <div class="document-description">
        Rekapitulasi Nilai Ujian Peserta Didik
    </div>

    <table class="information">
        <tbody>
            <tr>
                <td>Tahun Ajaran</td>
                <td>{{ $filters['academic_year'] ?? '-' }}</td>
            </tr>

            <tr>
                <td>Jenis Ujian</td>
                <td>{{ $filters['exam_type'] ?? '-' }}</td>
            </tr>

            <tr>
                <td>Kelas</td>
                <td>{{ $filters['class'] ?? '-' }}</td>
            </tr>

            <tr>
                <td>Mata Pelajaran</td>
                <td>{{ $filters['subject'] ?? '-' }}</td>
            </tr>

            <tr>
                <td>Sekolah</td>
                <td>{{ $filters['school'] ?? '-' }}</td>
            </tr>
        </tbody>
    </table>

</div>
