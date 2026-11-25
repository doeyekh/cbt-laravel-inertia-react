<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: dejavusans;
            color: #1e293b;
            font-size: 8pt;
        }

        /* Cover */
        .cover-page {
            height: 250mm;
            padding: 10mm 12mm;
            border: 2px solid #047857;
            text-align: center;
            position: relative;
        }

        .cover-page .inner-border {
            position: absolute;
            top: 3mm;
            right: 3mm;
            bottom: 3mm;
            left: 3mm;
            border: 0.5px solid #a7f3d0;
        }

        .cover-page .logo-wrapper {
            margin-top: 8mm;
            margin-bottom: 6mm;
        }

        .cover-page .logo {
            width: 30mm;
            height: 30mm;
        }

        .cover-page .institution {
            color: #065f46;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .cover-page .institution-subtitle {
            margin-top: 2mm;
            font-size: 10pt;
            color: #475569;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .cover-page .divider {
            width: 55%;
            margin: 8mm auto;
            border-top: 1.5pt solid #047857;
        }

        .cover-page .document-label {
            font-size: 9pt;
            color: #64748b;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .cover-page .document-title {
            margin-top: 3mm;
            font-size: 30pt;
            font-weight: bold;
            letter-spacing: 3px;
            color: #064e3b;
        }

        .cover-page .document-description {
            margin-top: 2mm;
            font-size: 10pt;
            color: #475569;
        }

        .cover-page .information {
            width: 85%;
            margin: 9mm auto 0;
            border-collapse: collapse;
            text-align: left;
        }

        .cover-page .information td {
            padding: 2mm;
            border-bottom: 0.5px solid #d1d5db;
            font-size: 9pt;
        }

        .cover-page .information td:first-child {
            width: 38%;
            color: #64748b;
        }

        .cover-page .information td:last-child {
            color: #0f172a;
            font-weight: bold;
        }

        /* Content */
        .content-page .title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #064e3b;
        }

        .content-page .subtitle {
            margin-top: 1mm;
            font-size: 8pt;
            color: #64748b;
        }

        .content-page .meta {
            margin-top: 2mm;
            font-size: 7.5pt;
            color: #475569;
        }

        .content-page .meta strong {
            color: #0f172a;
        }

        .content-page .divider {
            border-top: 2pt solid #047857;
            margin: 3mm 0 4mm;
        }

        .content-page .summary {
            width: 100%;
            margin-bottom: 4mm;
            border-collapse: collapse;
        }

        .content-page .summary td {
            border: 0.5pt solid #cbd5e1;
            padding: 2mm;
            text-align: center;
        }

        .content-page .summary .label {
            font-size: 7pt;
            color: #64748b;
        }

        .content-page .summary .value {
            margin-top: 1mm;
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
        }

        .content-page table.data {
            width: 100%;
            border-collapse: collapse;
        }

        .content-page table.data th,
        .content-page table.data td {
            border: 0.5pt solid #cbd5e1;
            padding: 1.6mm 1.2mm;
        }

        .content-page table.data th {
            background-color: #ecfdf5;
            color: #065f46;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .content-page table.data td {
            font-size: 7.5pt;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .content-page .foot {
            margin-top: 3mm;
            font-size: 7pt;
            color: #64748b;
            text-align: right;
        }

        .content-page .signature {
            width: 100%;
            margin-top: 6mm;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .content-page .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8pt;
        }

        .content-page .signature-space {
            height: 16mm;
        }

        .content-page .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    @foreach ($schools as $school)
        @php
            $schoolName = $school['name'] ?? 'Semua Sekolah';
            $schoolClasses = $school['classes'] ?? [];
            $schoolClassLabel = $filters['labels']['class'] ?? 'Semua kelas';
            if (empty($filters['class_id']) && $schoolClasses !== []) {
                $schoolClassLabel = collect($schoolClasses)
                    ->pluck('name')
                    ->filter()
                    ->implode(', ');
            }
            $schoolFilters = array_merge(
                $filters['labels'] ?? [],
                [
                    'school' => $schoolName,
                    'class' => $schoolClassLabel,
                ],
            );
        @endphp
        {{-- Cover sekolah --}}
        @include('leger-grades.cover', [
            'filters' => $schoolFilters,
            'school' => $schoolName,
        ])
        {{-- Konten leger dikelompokkan per kelas --}}
        @forelse ($schoolClasses as $class)
            <pagebreak orientation="L" />

            @include('leger-grades.content', [
                'rows' => $class['students'] ?? [],
                'summary' => $class['summary'] ?? [],
                'class' => $class,
                'school' => $school,
            ])
        @empty
            <pagebreak orientation="L" />
            @include('leger-grades.content', [
                'rows' => [],
                'summary' => [],
                'class' => ['name' => $schoolClassLabel],
                'school' => $school,
            ])
        @endforelse
        @unless ($loop->last)
            <pagebreak orientation="P" />
        @endunless
    @endforeach
</body>

</html>