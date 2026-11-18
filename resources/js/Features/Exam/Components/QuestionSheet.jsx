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
    const totalQuestions = questions.length;
    const currentQuestion = questions.find(
        (q) => q.number === currentQuestionNo
    ) || questions[0];

    const currentQuestionNoIndex = currentQuestion
        ? questions.findIndex((q) => q.id === currentQuestion.id) + 1
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

    if (!currentQuestion) {
        return (
            <section className="flex-1 w-full bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden min-h-[750px]">
                <div className="flex-1 flex items-center justify-center">
                    <p className="text-slate-500">Soal tidak tersedia.</p>
                </div>
            </section>
        );
    }

    const currentAnswer = answers[currentQuestion.id] ?? null;
    const isAnswered =
        currentAnswer !== null &&
        currentAnswer !== undefined &&
        currentAnswer !== '' &&
        currentAnswer !== 'flagged';

    const isFlagged = currentAnswer === 'flagged';

    const textStyle = fontSize === 'small' ? 'text-sm' : fontSize === 'large' ? 'text-lg' : 'text-base';

    return (
        <section className="flex-1 w-full bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden min-h-[750px]">
            {/* Panel Kontrol Soal (Header Kartu Soal) */}
            <div className="px-6 py-4 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <span className="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-sm tracking-wide shadow-xs">
                        Soal No. {currentQuestion.number}
                    </span>
                    <span className="text-xs text-slate-400 font-medium">
                        dari {totalQuestions} Soal
                    </span>
                    <span className={`px-2.5 py-1 rounded text-xs font-semibold ${getQuestionTypeBadge(currentQuestion.type)}`}>
                        {getTypeLabel(currentQuestion.type)}
                    </span>
                </div>
                <div className="flex items-center gap-4">
                    {/* Bobot Nilai */}
                    <div className="text-xs font-medium text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                        Bobot Soal: <span className="font-bold text-blue-700">{currentQuestionPoints} Poin</span>
                    </div>
                    {/* Ukuran Font Soal Switcher */}
                    <div className="flex items-center bg-white rounded-lg border border-slate-200 p-0.5 shadow-2xs">
                        <span className="text-[11px] font-medium text-slate-400 px-2">Ukuran:</span>
                        <button
                            onClick={() => { /* handled in parent */ }}
                            className="w-7 h-7 rounded text-xs font-bold flex items-center justify-center transition text-slate-600 hover:bg-slate-100"
                            title="Perkecil Font"
                            type="button"
                            disabled
                        >
                            A-
                        </button>
                        <button
                            className="w-7 h-7 rounded text-xs font-bold bg-blue-50 text-blue-700 flex items-center justify-center border border-blue-200"
                            title="Font Standar"
                            type="button"
                            disabled
                        >
                            A
                        </button>
                        <button
                            onClick={() => { /* handled in parent */ }}
                            className="w-7 h-7 rounded text-xs font-bold flex items-center justify-center transition text-slate-600 hover:bg-slate-100"
                            title="Perbesar Font"
                            type="button"
                            disabled
                        >
                            A+
                        </button>
                    </div>
                </div>
            </div>

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
                    <p className={textStyle}>{currentQuestion.body}</p>
                </div>

                {/* Area Jawaban Berdasarkan Tipe Soal */}
                {currentQuestion.type === 'pilihan_ganda' && (
                    <div className="space-y-3">
                        <p className="font-semibold text-slate-800 mb-2">Pilih salah satu jawaban:</p>
                        {currentQuestion.options?.map((option) => (
                            <label
                                key={option.id}
                                className="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition hover:bg-slate-50 hover:border-blue-200"
                            >
                                <input
                                    type="radio"
                                    name={`question_${currentQuestion.id}`}
                                    value={option.option_text}
                                    checked={currentAnswer === option.option_text}
                                    onChange={() =>
                                        onAnswerChange(
                                            currentQuestion.id,
                                            option.option_text
                                        )
                                    }
                                    className="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500"
                                />
                                <span className={textStyle}>{option.option_text}</span>
                            </label>
                        ))}
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
                                {currentQuestion.pairs?.map((pair, index) => (
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
                                            {pair.premise_text ?? pair.premise}
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
                                {currentQuestion.pairs?.map((pair, index) => (
                                    <div
                                        key={`target_${pair.id}`}
                                        className="p-3 rounded-xl border flex items-center justify-between gap-3 group"
                                    >
                                        <span className="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold bg-slate-100 text-slate-600">
                                            {String(index + 1)}
                                        </span>
                                        <span className="text-sm text-slate-700">
                                            {pair.target_text ?? pair.target}
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

                {(currentQuestion.type === 'isian_singkat' ||
                    currentQuestion.type === 'pilihan_ganda_kompleks') && (
                    <div className="space-y-3">
                        <p className="font-semibold text-slate-800 mb-2">Masukkan jawaban Anda:</p>
                        <input
                            type="text"
                            value={currentAnswer ?? ''}
                            onChange={(e) =>
                                onAnswerChange(currentQuestion.id, e.target.value)
                            }
                            className="w-full p-3 text-slate-800 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-sans"
                            placeholder="Jawaban..."
                        />
                    </div>
                )}
            </div>

            {/* Status Bar di Bawah Area Jawaban */}
            <div className="bg-slate-50/90 px-4 py-2 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div className="flex items-center gap-2">
                    <span
                        className={`w-2 h-2 rounded-full ${isAnswered ? 'bg-emerald-500' : 'bg-slate-400'} ${isFlagged ? 'animate-ping' : ''}`}
                    ></span>
                    <span className="font-medium text-[11px]">
                        {isFlagged
                            ? 'Tandai ragu-ragu'
                            : isAnswered
                              ? 'Sudah dijawab'
                              : 'Belum dijawab'}
                    </span>
                </div>
                <div className="flex items-center gap-3 text-slate-500 font-medium text-[11px]">
                    <span className="font-mono text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                        Soal {currentQuestionNoIndex} dari {totalQuestions}
                    </span>
                </div>
            </div>
        </section>
    );
};

export default QuestionSheet;
