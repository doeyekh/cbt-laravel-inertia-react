<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    <style>
        

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            font-size: 8pt;
        }

        /* Cover */
        .cover-page {
            position: relative;
            height: 168mm;
            padding: 10mm 12mm;
            border: 2px solid #047857;
            text-align: center;
            page-break-after: always;
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
            line-height: 1.4;
            text-transform: uppercase;
        }

        .cover-page .institution-subtitle {
            margin-top: 2mm;
            color: #64748b;
            font-size: 9pt;
        }

        .cover-page .divider {
            width: 65%;
            margin: 7mm auto;
            border-top: 2px solid #047857;
        }

        .cover-page .document-label {
            color: #64748b;
            font-size: 11pt;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .cover-page .document-title {
            margin-top: 4mm;
            color: #064e3b;
            font-size: 27pt;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .cover-page .document-description {
            margin-top: 3mm;
            color: #64748b;
            font-size: 10pt;
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

        .cover-page .footer {
            position: absolute;
            right: 12mm;
            bottom: 8mm;
            left: 12mm;
            color: #64748b;
            font-size: 8pt;
            text-align: center;
        }

        .cover-page .footer-line {
            width: 50%;
            margin: 0 auto 3mm;
            border-top: 1px solid #a7f3d0;
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
            background: #ecfdf5;
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
    </style>
</head>

<body>
    @include('leger-grades.cover', ['filters' => $filters['labels'] ?? []])
    @include('leger-grades.content')
</body>

</html>
