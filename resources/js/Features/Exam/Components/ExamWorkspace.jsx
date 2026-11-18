import React from 'react';
import QuestionSheet from './QuestionSheet';

const ExamWorkspace = ({
    exam,
    questions,
    examType,
    durationMinutes,
    totalQuestions,
    answeredCount,
    currentQuestionNo,
    fontSize,
    setFontSize,
    answers,
    isAutoSaving,
    handleAnswerChange,
    goToPreviousQuestion,
    goToNextQuestion,
    setShowQuestionModal,
    setShowSubmitModal,
}) => {
    const sortedQuestions = [...questions].sort((first, second) => (first.number ?? 0) - (second.number ?? 0));
    const currentQuestion = sortedQuestions[currentQuestionNo - 1] || sortedQuestions[0];
    const currentQuestionId = currentQuestion?.id ?? currentQuestionNo;
    const isLastQuestion = currentQuestionNo >= totalQuestions;
    const hasUnansweredQuestions = totalQuestions === 0 || answeredCount < totalQuestions;

    const handleFlagToggle = () => {
        const isFlagged = answers[currentQuestionId] === 'flagged';
        handleAnswerChange(currentQuestionId, isFlagged ? null : 'flagged');
    };

    return (
        <section className="flex-1 w-full bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden min-h-[750px]">
            <div className="px-6 py-4 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <span className="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-sm tracking-wide shadow-xs">
                        Soal No. {currentQuestionNo}
                    </span>
                    <span className="text-xs text-slate-400 font-medium">dari {totalQuestions} Soal</span>
                    <span className={`px-2.5 py-1 rounded text-xs font-semibold ${fontSize === 'normal' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                        {examType}
                    </span>
                </div>
                <div className="flex items-center gap-4">
                    <div className="text-xs font-medium text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                        Bobot Soal: <span className="font-bold text-blue-700">{Math.ceil(durationMinutes / totalQuestions) || 5} Poin</span>
                    </div>
                    <div className="flex items-center bg-white rounded-lg border border-slate-200 p-0.5 shadow-2xs">
                        <span className="text-[11px] font-medium text-slate-400 px-2">Ukuran:</span>
                        {[
                            ['small', 'A-', 'Perkecil Font'],
                            ['normal', 'A', 'Font Standar'],
                            ['large', 'A+', 'Perbesar Font'],
                        ].map(([size, label, title]) => (
                            <button
                                key={size}
                                onClick={() => setFontSize(size)}
                                className={`w-7 h-7 rounded text-xs font-bold flex items-center justify-center transition ${fontSize === size ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-600 hover:bg-slate-100'}`}
                                title={title}
                                type="button"
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            <div className={`flex-1 overflow-y-auto space-y-6 ${fontSize === 'small' ? 'text-sm' : fontSize === 'large' ? 'text-lg' : 'text-base'}`}>
                <QuestionSheet
                    exam={{ ...exam, questions }}
                    onAnswerChange={handleAnswerChange}
                    answers={answers}
                    fontSize={fontSize}
                    currentQuestionNo={currentQuestionNo}
                />
            </div>

            <div className="bg-slate-50/90 px-4 py-2 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div className="flex items-center gap-2">
                    <span className={`w-2 h-2 rounded-full ${isAutoSaving ? 'bg-blue-500 animate-pulse' : 'bg-emerald-500'}`}></span>
                    <span className="font-medium text-[11px]">
                        {isAutoSaving ? 'Menyimpan otomatis...' : 'Tersimpan otomatis ke server'}
                    </span>
                </div>
                <div className="flex items-center gap-3 text-slate-500 font-medium text-[11px]">
                    <span className="font-mono text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                        {Object.keys(answers).length} Jawaban
                    </span>
                    <span className="text-slate-400">•</span>
                    <span>Maksimal {totalQuestions} Soal</span>
                </div>
            </div>

            <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <button
                    onClick={goToPreviousQuestion}
                    disabled={currentQuestionNo <= 1}
                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:border-slate-400 font-semibold text-sm transition shadow-2xs disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                >
                    <span className="material-symbols-outlined text-[16px] text-slate-500">arrow_back</span>
                    Soal Sebelumnya
                </button>

                <label className="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 cursor-pointer hover:bg-amber-100/80 transition select-none">
                    <input
                        className="w-4 h-4 rounded text-amber-600 border-amber-400 focus:ring-amber-500"
                        type="checkbox"
                        checked={answers[currentQuestionId] === 'flagged'}
                        onChange={handleFlagToggle}
                    />
                    <span className="text-xs font-bold flex items-center gap-1.5">
                        <span className="material-symbols-outlined text-[16px] text-amber-600">flag</span>
                        Tandai Ragu-Ragu
                    </span>
                </label>

                <div className="flex items-center gap-2">
                    <button
                        onClick={() => setShowQuestionModal(true)}
                        className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-sm transition shadow-2xs"
                        title="Tampilkan Grid Daftar Nomor Soal"
                        type="button"
                    >
                        <span className="material-symbols-outlined text-[16px] text-blue-600">menu_book</span>
                        <span>Daftar Soal</span>
                    </button>
                    <button
                        onClick={isLastQuestion ? () => setShowSubmitModal(true) : goToNextQuestion}
                        disabled={isLastQuestion && hasUnansweredQuestions}
                        title={isLastQuestion && hasUnansweredQuestions ? 'Jawab semua soal terlebih dahulu sebelum menyelesaikan ujian.' : undefined}
                        className={`inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-white font-semibold text-sm transition shadow-md active:scale-98 disabled:cursor-not-allowed disabled:opacity-50 ${
                            isLastQuestion
                                ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20'
                                : 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20'
                        }`}
                        type="button"
                    >
                        {isLastQuestion ? 'Selesaikan Ujian' : 'Soal Berikutnya'}
                        <span className="material-symbols-outlined text-[16px]">
                            {isLastQuestion ? 'task_alt' : 'arrow_forward'}
                        </span>
                    </button>
                </div>
            </div>
        </section>
    );
};

export default ExamWorkspace;
