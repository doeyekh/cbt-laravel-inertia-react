<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $bank['name'] }} - {{ $bank['code'] }}</title>
    <style>
        @page {
            margin: 16mm 14mm 16mm 14mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.45;
            color: #1b1c1d;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        .head {
            width: 100%;
            border-bottom: 1.2pt solid #1b1c1d;
        }

        .head td {
            padding: 0 0 2mm 0;
            vertical-align: top;
        }

        .head td.main {
            width: 60%;
        }

        .head td.side {
            width: 40%;
            text-align: right;
        }

        .school-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }

        .head-title {
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.6pt;
        }

        .head-sub {
            font-size: 8.5pt;
            color: #434653;
        }

        .instructions {
            border: 0.6pt solid #c3c6d5;
            background-color: #f5f3f4;
            padding: 2.5mm 4mm;
            margin: 3mm 0 5mm 0;
            font-size: 8.5pt;
            line-height: 1.45;
        }

        .instructions strong {
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }

        .question {
            margin-bottom: 5mm;
            page-break-inside: avoid;
        }

        .question-title {
            font-weight: bold;
            margin-bottom: 1.5mm;
        }

        .q-type {
            font-size: 7.5pt;
            font-weight: bold;
            color: #434653;
            border: 0.4pt solid #c3c6d5;
            padding: 0.3mm 1.5mm;
            margin-left: 1.5mm;
        }

        .question-body {
            margin-bottom: 2mm;
            text-align: justify;
        }

        .question-body p {
            margin: 0 0 1.5mm 0;
        }

        .question-body .math {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9.5pt;
            background-color: #efedee;
            padding: 0 2px;
        }

        .question-body img,
        .option-text img {
            margin-top: 1.5mm;
        }

        .options {
            width: 100%;
            margin-top: 1mm;
        }

        .options td {
            padding: 0.8mm 2mm 0.8mm 0;
            vertical-align: top;
            font-size: 10pt;
        }

        .options td.marker {
            width: 7mm;
        }

        .options td.opt-label {
            width: 8mm;
            font-weight: bold;
        }

        .box {
            display: inline-block;
            width: 3.2mm;
            height: 3.2mm;
            border: 0.6pt solid #1b1c1d;
        }

        .pairs {
            width: 100%;
            margin-top: 2mm;
        }

        .pairs th {
            border: 0.5pt solid #737784;
            background-color: #efedee;
            padding: 1.5mm 3mm;
            font-size: 8.5pt;
            text-align: left;
        }

        .pairs td {
            border: 0.4pt solid #c3c6d5;
            padding: 2mm 3mm;
            font-size: 9.5pt;
            vertical-align: middle;
        }

        .pairs td.number {
            width: 10mm;
            text-align: center;
        }

        .pairs td.answer {
            width: 45mm;
            border-bottom: 0.6pt solid #434653;
        }

        .answer-line {
            margin-top: 2mm;
            font-size: 9.5pt;
        }

        .answer-line .line {
            display: inline-block;
            width: 130mm;
            border-bottom: 0.5pt solid #434653;
        }

        .essay-lines {
            margin-top: 2mm;
        }

        .essay-line {
            border-bottom: 0.4pt solid #c3c6d5;
            height: 7mm;
        }

        .media-note {
            font-size: 9pt;
            color: #434653;
            margin-top: 1.5mm;
        }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td class="main">
                <div class="school-name">{{ $bank['school_name'] ?? 'SEKOLAH' }}</div>
                <div class="head-sub">
                    {{ $bank['exam_type_name'] ?? 'Ujian' }}
                    &middot; {{ $bank['subject_name'] ?? '-' }}
                    &middot; {{ implode(', ', $bank['classes']) ?: '-' }}
                </div>
            </td>
            <td class="side">
                <div class="head-title">LEMBAR SOAL</div>
                <div class="head-sub">
                    {{ $bank['name'] }} ({{ $bank['code'] }})
                </div>
                <div class="head-sub">
                    {{ $bank['print_date'] }}
                    &middot; {{ $totalQuestions }} soal
                    &middot; {{ $totalScore }} poin
                </div>
            </td>
        </tr>
    </table>

    <div class="instructions">
        <strong>Petunjuk:</strong>
        Tuliskan jawaban pada lembar jawaban yang terpisah. Soal pilihan ganda
        kompleks dapat memiliki lebih dari satu jawaban benar. Kerjakan soal
        secara berurutan dan periksa kembali sebelum dikumpulkan.
    </div>

    @forelse ($questions as $question)
        <div class="question">
            <div class="question-title">
                {{ $question['number'] }}.<span class="q-type">{{ $question['type_short'] }}</span>
            </div>

            @if ($question['body'] !== '')
                <div class="question-body">{!! $question['body'] !!}</div>
            @endif

            @if ($question['image'])
                <img
                    src="{{ $question['image']['path'] }}"
                    style="width: {{ $question['image']['width'] }}; height: {{ $question['image']['height'] }};"
                    alt="Gambar soal"
                >
            @endif

            @if ($question['audio'])
                <div class="media-note">
                    Audio: {{ basename($question['audio']) }} &mdash; diputar oleh pengawas.
                </div>
            @endif

            @if (count($question['options']) > 0)
                <table class="options">
                    @foreach ($question['options'] as $option)
                        <tr>
                            @if ($question['is_complex'])
                                <td class="marker"><span class="box"></span></td>
                            @endif
                            <td class="opt-label">{{ $option['label'] }}.</td>
                            <td class="option-text">
                                {!! $option['text'] !!}
                                @if ($option['image'])
                                    <br><img
                                        src="{{ $option['image']['path'] }}"
                                        style="width: {{ $option['image']['width'] }}; height: {{ $option['image']['height'] }};"
                                        alt="Gambar opsi"
                                    >
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if (count($question['pairs']) > 0)
                <table class="pairs">
                    <thead>
                        <tr>
                            <th class="number">No</th>
                            <th>Kiri</th>
                            <th>Jawaban</th>
                            <th>Kanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($question['pairs'] as $index => $pair)
                            <tr>
                                <td class="number">{{ $index + 1 }}</td>
                                <td>{{ $pair['left_text'] }}</td>
                                <td class="answer"></td>
                                <td>{{ $pair['right_text'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($question['blank_count'] > 0)
                <div class="answer-line">
                    Jawaban: <span class="line"></span>
                </div>
            @endif

            @if ($question['is_essay'])
                <div class="essay-lines">
                    @for ($line = 0; $line < 5; $line++)
                        <div class="essay-line"></div>
                    @endfor
                </div>
            @endif
        </div>
    @empty
        <div class="instructions">Belum ada soal pada bank soal ini.</div>
    @endforelse
</body>
</html>
