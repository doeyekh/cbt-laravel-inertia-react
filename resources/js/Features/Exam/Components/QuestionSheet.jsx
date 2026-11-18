import React, { useMemo } from 'react';

const getPairId = (pair) => String(pair.id);

/**
 * QuestionSheet - Menampilkan soal beserta instruksi dan area jawaban
 * Komponen reusable untuk menampilkan struktur soal dalam ujian CBT
 * Data soal diambil dari backend melalui props questions.
 */

const QuestionSheet = ({
    question,
    exam,
    onAnswerChange,
    answers,
    fontSize,
    currentQuestionNo,
}) => {
    const questions = exam?.questions || [];
    // Urutkan soal berdasarkan nomor untuk navigasi yang konsisten
    const sortedQuestions = [...questions].sort(
        (a, b) => (a.number ?? 0) - (b.number ?? 0)
    );
    const totalQuestions = sortedQuestions.length;
    // currentQuestionNo adalah indeks sequential (1-based), gunakan untuk akses array
    const currentQuestion =
        sortedQuestions[currentQuestionNo - 1] || sortedQuestions[0];

    const sortedPairs = useMemo(
        () =>
            Array.isArray(currentQuestion?.pairs)
                ? [...currentQuestion.pairs].sort(
                    (a, b) => (a.order ?? 0) - (b.order ?? 0)
                )
                : [],
        [currentQuestion?.pairs],
    );
    const shuffledRightPairs = useMemo(() => {
        const shuffled = [...sortedPairs];
        let seed = Number(currentQuestion?.id) >>> 0;

        for (let index = shuffled.length - 1; index > 0; index -= 1) {
            seed = (Math.imul(seed || 1, 1664525) + 1013904223) >>> 0;
            const swapIndex = seed % (index + 1);
            [shuffled[index], shuffled[swapIndex]] = [
                shuffled[swapIndex],
                shuffled[index],
            ];
        }

        if (
            shuffled.length > 1
            && shuffled.some(
                (pair, index) => getPairId(pair) === getPairId(sortedPairs[index])
            )
        ) {
            const offset = (seed % (shuffled.length - 1)) + 1;

            return sortedPairs.map(
                (_, index) => sortedPairs[(index + offset) % sortedPairs.length]
            );
        }

        return shuffled;
    }, [currentQuestion?.id, sortedPairs]);

    const currentQuestionNoIndex = currentQuestion
        ? sortedQuestions.findIndex((q) => q.id === currentQuestion.id) + 1
        : 1;

    const currentQuestionPoints = currentQuestion?.score ?? 0;

    const getTypeLabel = (type) => {
        const labels = {
            pilihan_ganda: 'Pilihan Ganda',
            pilihan_ganda_kompleks: 'Pilihan Ganda Kompleks',
            menjodohkan: 'Menjodohkan',
            isian_singkat: 'Isian',
            esai: 'Esai',
        };
        return labels[type] ?? type;
    };

    const getQuestionTypeBadge = (type) => {
        const colors = {
            pilihan_ganda: 'bg-blue-50 border-blue-200 text-blue-700',
            pilihan_ganda_kompleks:
                'bg-purple-50 border-purple-200 text-purple-700',
            menjodohkan: 'bg-indigo-50 border-indigo-200 text-indigo-700',
            isian_singkat: 'bg-teal-50 border-teal-200 text-teal-700',
            esai: 'bg-rose-50 border-rose-200 text-rose-700',
        };
        return colors[type] ?? 'bg-slate-100 border-slate-200 text-slate-600';
    };

    /**
     * Strip HTML tags from text for safe display.
     */
    const stripHtml = (html) => {
        if (!html) {
            return '';
        }
        const tmp = document.createElement('DIV');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    };

    if (!currentQuestion) {
        return (
            <section className="flex-1 w-full bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden min-h-[750px]">
                <div className="flex-1 flex items-center justify-center">
                    <p className="text-slate-500">Soal tidak tersedia.</p>
                </div>
            </section>
        );
    }

    // Sort options by order column
    const sortedOptions = Array.isArray(currentQuestion.options)
        ? [...currentQuestion.options].sort(
            (a, b) => (a.order ?? 0) - (b.order ?? 0)
        )
        : [];

    const currentAnswer = answers[currentQuestion.id] ?? null;
    const isFlagged = currentAnswer === 'flagged';
    const matchingAnswer = Array.isArray(currentAnswer) ? currentAnswer : [];
    const matchingAnswers = sortedPairs.map((_, index) => {
        const answer = matchingAnswer[index];

        return typeof answer === 'string'
            && shuffledRightPairs.some((pair) => getPairId(pair) === answer)
            ? answer
            : '';
    });

    const textStyle = fontSize === 'small' ? 'text-sm' : fontSize === 'large' ? 'text-lg' : 'text-base';

    return (
        <section className="flex-1 w-full flex flex-col min-h-[750px]">
            {/* Panel Kontrol Soal (Header Kartu Soal) */}


            {/* Area Teks Soal & Stimulus Soal */}
            <div className={`p-6 md:p-8 flex-1 overflow-y-auto space-y-6 ${textStyle}`}>
                {/* Instruksi Soal */}
                <div className="bg-blue-50/50 border border-blue-100 rounded-xl p-4 text-sm text-slate-700">
                    <p className="font-semibold text-blue-950 flex items-center gap-2 mb-1">
                        <span className="material-symbols-outlined text-[16px] text-blue-600">article</span>
                        Petunjuk Pengerjaan:
                    </p>
                    <p className="font-normal text-slate-700 leading-relaxed">
                        Baca soal dengan seksama dan pilih jawaban yang paling tepat.
                        Jawaban dapat diubah sebelum waktu pengerjaan habis.
                    </p>
                </div>

                {/* Teks Soal */}
                <div className="prose prose-slate max-w-none">
                    <p className={textStyle} dangerouslySetInnerHTML={{ __html: currentQuestion.body }} />
                </div>

                {/* Area Jawaban Berdasarkan Tipe Soal */}
                {currentQuestion.type === 'pilihan_ganda' && (
                    <div className="space-y-3">
                        <p className="font-semibold text-slate-800 mb-2">Pilih salah satu jawaban:</p>
                        {sortedOptions.map((option) => {
                            const isSelected = currentAnswer === option.text;
                            return (
                                <label
                                    key={option.id}
                                    className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition ${isSelected ? 'bg-blue-50 border-blue-200 shadow-sm' : 'bg-white border-slate-200 hover:bg-slate-50 hover:border-blue-200'}`}
                                >
                                    <input
                                        type="radio"
                                        name={`question_${currentQuestion.id}`}
                                        value={option.text}
                                        checked={currentAnswer === option.text}
                                        onChange={() =>
                                            onAnswerChange(
                                                currentQuestion.id,
                                                option.text
                                            )
                                        }
                                        className="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500"
                                    />
                                    <span className="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold border border-slate-300">
                                        {option.label}
                                    </span>
                                    <span className={textStyle}>
                                        {stripHtml(option.text)}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                )}

                {currentQuestion.type === 'pilihan_ganda_kompleks' && (
                    <div className="space-y-3">
                        <p className="font-semibold text-slate-800 mb-2">
                            Pilih satu atau lebih jawaban yang benar:
                        </p>
                        {sortedOptions.map((option) => {
                            const isSelected =
                                currentAnswer &&
                                Array.isArray(currentAnswer) &&
                                currentAnswer.includes(option.text);
                            return (
                                <label
                                    key={option.id}
                                    className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition ${isSelected ? 'bg-purple-50 border-purple-200 shadow-sm' : 'bg-white border-slate-200 hover:bg-slate-50 hover:border-purple-200'}`}
                                >
                                    <input
                                        type="checkbox"
                                        name={`question_${currentQuestion.id}`}
                                        value={option.text ?? ''}
                                        checked={Boolean(isSelected)}
                                        onChange={(e) => {
                                            const selected = currentAnswer
                                                ? Array.isArray(currentAnswer)
                                                    ? [...currentAnswer]
                                                    : [currentAnswer]
                                                : [];
                                            if (e.target.checked) {
                                                if (!selected.includes(option.text)) {
                                                    selected.push(option.text);
                                                }
                                            } else {
                                                const idx = selected.indexOf(option.text);
                                                if (idx >= 0) {
                                                    selected.splice(idx, 1);
                                                }
                                            }
                                            onAnswerChange(currentQuestion.id, selected);
                                        }}
                                        className="w-4 h-4 text-purple-600 border-slate-300 focus:ring-purple-500"
                                    />
                                    <span className="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold border border-slate-300">
                                        {option.label}
                                    </span>
                                    <span className={textStyle}>
                                        {stripHtml(option.text)}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                )}

                {currentQuestion.type === 'menjodohkan' && (
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-3">
                            <p className="text-sm font-medium leading-relaxed text-indigo-950">
                                Pilih pasangan Kolom B untuk setiap pernyataan di Kolom A. Jika memilih pasangan yang sudah digunakan, pasangan akan otomatis ditukar.
                            </p>
                            <span className="shrink-0 rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-bold text-indigo-700">
                                {matchingAnswers.filter(Boolean).length}/{sortedPairs.length} terpasang
                            </span>
                        </div>

                        <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                            <div className="space-y-3">
                                <h4 className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-md bg-slate-800 font-mono text-[11px] text-white">
                                        A
                                    </span>
                                    Kolom A · Pernyataan
                                </h4>

                                {sortedPairs.map((pair, index) => {
                                    const selectedRightId = matchingAnswers[index];
                                    const selectedRightIndex = shuffledRightPairs.findIndex(
                                        (rightPair) => getPairId(rightPair) === selectedRightId
                                    );
                                    const selectId = `matching-answer-${currentQuestion.id}-${index}`;

                                    return (
                                        <div
                                            key={pair.id}
                                            className={`rounded-xl border p-4 transition ${selectedRightId
                                                ? 'border-emerald-300 bg-emerald-50/60'
                                                : 'border-slate-200 bg-white hover:border-indigo-200'
                                                }`}
                                        >
                                            <div className="flex items-start gap-3">
                                                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-800 font-mono text-xs font-bold text-white">
                                                    A{index + 1}
                                                </span>
                                                <p className="min-w-0 flex-1 text-sm font-medium leading-relaxed text-slate-800">
                                                    {stripHtml(pair.left_text)}
                                                </p>
                                            </div>

                                            <div className="mt-4">
                                                <label
                                                    htmlFor={selectId}
                                                    className="mb-1.5 block text-xs font-semibold text-slate-600"
                                                >
                                                    Pasangkan dengan:
                                                </label>
                                                <select
                                                    id={selectId}
                                                    value={selectedRightId}
                                                    onChange={(event) => {
                                                        const nextAnswers = [...matchingAnswers];
                                                        const nextRightId = event.target.value;
                                                        const previousRightId = matchingAnswers[index];
                                                        const previousOwnerIndex = nextRightId
                                                            ? matchingAnswers.findIndex(
                                                                (answer, answerIndex) =>
                                                                    answerIndex !== index
                                                                    && answer === nextRightId
                                                            )
                                                            : -1;

                                                        nextAnswers[index] = nextRightId;

                                                        if (previousOwnerIndex >= 0) {
                                                            nextAnswers[previousOwnerIndex] = previousRightId;
                                                        }

                                                        onAnswerChange(
                                                            currentQuestion.id,
                                                            nextAnswers.some(Boolean) ? nextAnswers : null
                                                        );
                                                    }}
                                                    className={`w-full rounded-lg border bg-white px-3 py-2.5 text-sm leading-relaxed text-slate-800 shadow-xs outline-none transition focus:ring-2 ${selectedRightId
                                                        ? 'border-emerald-300 focus:border-emerald-500 focus:ring-emerald-100'
                                                        : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100'
                                                        }`}
                                                >
                                                    <option value="">Pilih pasangan Kolom B</option>
                                                    {shuffledRightPairs.map((rightPair, rightIndex) => {
                                                        const rightPairId = getPairId(rightPair);

                                                        return (
                                                            <option
                                                                key={rightPair.id}
                                                                value={rightPairId}
                                                            >
                                                                B{rightIndex + 1} — {stripHtml(rightPair.right_text)}
                                                            </option>
                                                        );
                                                    })}
                                                </select>
                                                {selectedRightIndex >= 0 && (
                                                    <p className="mt-2 flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                                        <span className="material-symbols-outlined text-[15px]">check_circle</span>
                                                        Terpasang dengan B{selectedRightIndex + 1}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            <aside className="space-y-3 rounded-xl border border-blue-200 bg-blue-50/50 p-4">
                                <h4 className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-800">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-md bg-blue-700 font-mono text-[11px] text-white">
                                        B
                                    </span>
                                    Pilihan Kolom B
                                </h4>
                                <ol className="space-y-2">
                                    {shuffledRightPairs.map((pair, index) => {
                                        const isUsed = matchingAnswers.includes(getPairId(pair));

                                        return (
                                            <li
                                                key={pair.id}
                                                className={`flex items-start gap-3 rounded-lg border p-3 transition ${isUsed
                                                    ? 'border-blue-200 bg-white/70'
                                                    : 'border-slate-200 bg-white'
                                                    }`}
                                            >
                                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100 font-mono text-[11px] font-bold text-blue-700">
                                                    B{index + 1}
                                                </span>
                                                <span className="text-sm leading-relaxed text-slate-700">
                                                    {stripHtml(pair.right_text)}
                                                </span>
                                                {isUsed && (
                                                    <span className="material-symbols-outlined ml-auto shrink-0 text-[16px] text-emerald-600" aria-label="Sudah dipilih">
                                                        check_circle
                                                    </span>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ol>
                            </aside>
                        </div>
                    </div>
                )}

                {currentQuestion.type === 'esai' && (
                    <div className="space-y-3">
                        <p className="font-semibold text-slate-800 mb-2">Tuliskan jawaban esai Anda:</p>
                        <textarea
                            value={currentAnswer ?? ''}
                            onChange={(e) =>
                                onAnswerChange(currentQuestion.id, e.target.value)
                            }
                            className="w-full p-4 min-h-[200px] text-slate-800 text-sm leading-relaxed border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y font-sans selection:bg-blue-100"
                            placeholder="Tuliskan jawaban Anda di sini..."
                        />
                        <div className="text-xs text-slate-500">
                            {currentAnswer?.length ?? 0} karakter
                        </div>
                    </div>
                )}

                {currentQuestion.type === 'isian_singkat' && (
                    <div className="space-y-3">
                        <p className="font-semibold text-slate-800 mb-2">
                            Masukkan jawaban Anda:
                        </p>
                        <input
                            type="text"
                            value={currentAnswer ?? ''}
                            onChange={(e) =>
                                onAnswerChange(
                                    currentQuestion.id,
                                    e.target.value
                                )
                            }
                            className="w-full p-3 text-slate-800 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-sans"
                            placeholder="Jawaban..."
                        />
                    </div>
                )}
            </div>


        </section>
    );
};

export default QuestionSheet;
