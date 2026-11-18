import React from 'react';

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

    // Sort pairs by order column
    const sortedPairs = Array.isArray(currentQuestion.pairs)
        ? [...currentQuestion.pairs].sort(
            (a, b) => (a.order ?? 0) - (b.order ?? 0)
        )
        : [];

    const currentAnswer = answers[currentQuestion.id] ?? null;
    const isAnswered =
        currentAnswer !== null &&
        currentAnswer !== undefined &&
        currentAnswer !== '' &&
        currentAnswer !== 'flagged' &&
        !(Array.isArray(currentAnswer) && currentAnswer.length === 0);

    const isFlagged = currentAnswer === 'flagged';

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
                    <div className="space-y-6">
                        <p className="font-semibold text-slate-800 mb-2">
                            Pasangkan entitas di Kolom A dengan Kolom B:
                        </p>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Kolom A */}
                            <div className="space-y-3">
                                <h4 className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
                                    <span className="w-5 h-5 rounded-md bg-slate-800 text-white flex items-center justify-center text-[11px] font-mono">
                                        A
                                    </span>
                                    Kolom A
                                </h4>
                                {sortedPairs.map((pair, index) => (
                                    <div
                                        key={pair.id}
                                        className={`p-3 rounded-xl border cursor-pointer transition flex items-center justify-between gap-3 group ${isFlagged ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-200'}`}
                                        onClick={() => {
                                            if (isFlagged) {
                                                onAnswerChange(currentQuestion.id, null);
                                            } else {
                                                onAnswerChange(
                                                    currentQuestion.id,
                                                    `pair_${index}`
                                                );
                                            }
                                        }}
                                    >
                                        <span className="text-xs font-bold text-blue-950">
                                            {pair.left_text}
                                        </span>
                                        <span className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold ${isFlagged ? 'bg-amber-500 text-white' : 'bg-blue-100 text-blue-600'}`}>
                                            {String(index + 1)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            {/* Kolom B */}
                            <div className="space-y-3">
                                <h4 className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
                                    <span className="w-5 h-5 rounded-md bg-blue-700 text-white flex items-center justify-center text-[11px] font-mono">
                                        B
                                    </span>
                                    Kolom B
                                </h4>
                                {sortedPairs.map((pair, index) => (
                                    <div
                                        key={`target_${pair.id}`}
                                        className="p-3 rounded-xl border flex items-center justify-between gap-3 group"
                                    >
                                        <span className="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold bg-slate-100 text-slate-600">
                                            {String(index + 1)}
                                        </span>
                                        <span className="text-sm text-slate-700">
                                            {pair.right_text}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                        {isFlagged && (
                            <p className="text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded-lg border border-amber-200">
                                Menjodohkan: {currentAnswer}
                            </p>
                        )}
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
