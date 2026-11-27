<style>
    @page {
        size: A4 portrait;
        margin: 8mm 10mm 10mm;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 8pt;
        line-height: 1.25;
        color: #1e293b;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    p {
        margin: 0;
        padding: 0;
    }

    /* HEADER */

    .header {
        margin-bottom: 2mm;
    }

    .header td {
        vertical-align: middle;
    }

    .header-logo {
        width: 18%;
        text-align: center;
    }

    .school-logo {
        width: 21mm;
        height: 21mm;
    }

    .header-content {
        width: 55%;
    }

    .exam-type {
        font-size: 8pt;
        font-weight: bold;
        margin-bottom: 1mm;
    }

    .report-title {
        font-size: 10pt;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 1mm;
    }

    .school-name {
        font-size: 12pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #064e3b;
        line-height: 1.2;
    }

    .header-subtitle {
        margin-top: 1mm;
        font-size: 7.5pt;
        color: #64748b;
    }

    .header-meta {
        width: 27%;
        text-align: right;
        font-size: 7pt;
        line-height: 1.5;
        color: #64748b;
    }

    .header-meta strong {
        color: #334155;
    }

    .divider {
        border-top: 2pt solid #047857;
        margin: 2mm 0 3mm;
    }

    /* INFORMASI UJIAN */

    .info-table {
        margin-bottom: 3mm;
        font-size: 7.5pt;
    }

    .info-table td {
        padding: 1.1mm 1mm;
        vertical-align: middle;
    }

    .info-label {
        width: 16%;
        color: #475569;
    }

    .info-separator {
        width: 2%;
        text-align: center;
    }

    .info-value {
        font-weight: bold;
        color: #334155;
    }

    /* TABEL PESERTA */

    .data-table {
        table-layout: fixed;
        margin-bottom: 3mm;
    }

    .data-table thead {
        display: table-header-group;
    }

    .data-table th {
        padding: 1.6mm 1mm;
        background-color: #047857;
        border: 0.5pt solid #047857;
        color: #ffffff;
        font-size: 7pt;
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .data-table td {
        padding: 1.5mm;
        border: 0.5pt solid #cbd5e1;
        font-size: 7.5pt;
        vertical-align: middle;
        word-wrap: break-word;
    }

    .data-table tbody tr:nth-child(even) td {
        background-color: #f8fafc;
    }

    .data-table tbody tr {
        page-break-inside: avoid;
    }

    .text-center {
        text-align: center;
    }

    .participant-name {
        font-weight: bold;
    }

    .signature-number {
        padding-left: 3mm !important;
        text-align: left;
    }

    .empty-row {
        padding: 5mm !important;
        text-align: center;
        color: #64748b;
    }

    /* CATATAN */

    .footer-section {
        margin-top: 2mm;
        font-size: 7pt;
        line-height: 1.4;
        page-break-inside: avoid;
    }

    .footer-section ol {
        margin: 1mm 0 0 5mm;
        padding-left: 3mm;
    }

    .footer-section li {
        padding: 0.3mm 0;
    }

    /* TANDA TANGAN */

    .signature-table {
        margin-top: 5mm;
        page-break-inside: avoid;
        font-size: 7.5pt;
    }

    .signature-table td {
        width: 50%;
        text-align: center;
        vertical-align: top;
    }

    .signature-space {
        height: 15mm;
    }

    /* CETAK */

    @media print {
        .data-table {
            page-break-inside: auto;
        }

        .data-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }
</style>
