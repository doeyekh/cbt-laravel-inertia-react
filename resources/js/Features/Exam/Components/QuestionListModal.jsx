import React from 'react';

const QuestionListModal = ({
    isOpen,
    onClose,
    onSelectQuestion,
    questions = [],
    subjectName,
    packageCode,
    totalQuestions,
    answeredCount,
    flaggedCount,
    currentQuestionNo,
    answers,
    flaggedQuestionIds = [],
}) => {
    if (!isOpen) {
        return null;
    }

    const getQuestion = (questionNo) => questions[questionNo - 1];

    const getQuestionAnswer = (questionNo) => {
        const question = getQuestion(questionNo);

        return answers[question?.id ?? questionNo];
    };

    const hasAnswer = (answer) => (Array.isArray(answer)
        ? answer.some((value) => value !== '')
        : answer !== null && answer !== undefined && answer !== '');

    const isFlaggedQuestion = (questionNo) => flaggedQuestionIds.includes(
        getQuestion(questionNo)?.id ?? questionNo,
    );

    const getQuestionStatus = (questionNo) => {
        if (questionNo === currentQuestionNo) {
            return 'active';
        }

        if (isFlaggedQuestion(questionNo)) {
            return 'flagged';
        }

        if (hasAnswer(getQuestionAnswer(questionNo))) {
            return 'answered';
        }

        return 'empty';
    };

    const getQuestionLabel = (questionNo) => {
        if (isFlaggedQuestion(questionNo)) {
            return '?';
        }

        const answer = getQuestionAnswer(questionNo);

        if (hasAnswer(answer)) {
            return answer;
        }

        return '--';
    };

    const progress = totalQuestions > 0
        ? Math.round((answeredCount / totalQuestions) * 100)
        : 0;
    const unansweredCount = Math.max(0, totalQuestions - answeredCount);

    return (
        <div
            aria-labelledby="modal-daftar-soal-title"
            aria-modal="true"
            className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            role="dialog"
        >
            <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-4xl w-full flex flex-col max-h-[92vh] overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div className="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                            <span className="material-symbols-outlined text-[20px] text-white">menu_book</span>
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h3 className="text-lg font-bold text-slate-900 leading-tight" id="modal-daftar-soal-title">Daftar Nomor Soal</h3>
                                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">{totalQuestions} Butir</span>
                            </div>
                            <p className="text-xs text-slate-500 font-medium mt-0.5">{subjectName} • Kode: {packageCode}</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        aria-label="Tutup Modal"
                        className="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition"
                        type="button"
                    >
                        <span className="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div className="px-6 py-3 bg-blue-50/50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div className="flex items-center flex-wrap gap-2">
                        <span className="font-semibold text-slate-600 mr-1">Status Jawaban:</span>
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-600 text-white font-semibold text-[11px] shadow-2xs">
                            <span className="w-2 h-2 rounded-full bg-white"></span>
                            Dijawab: {answeredCount}
                        </span>
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-400 text-amber-950 font-bold text-[11px] ring-1 ring-amber-500/50">
                            <span className="w-2 h-2 rounded-full bg-amber-900"></span>
                            Ragu-ragu: {flaggedCount}
                        </span>
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-slate-300 text-slate-700 font-medium text-[11px]">
                            <span className="w-2 h-2 rounded-full bg-slate-400"></span>
                            Belum Dijawab: {unansweredCount}
                        </span>
                    </div>
                    <div className="text-[11px] text-slate-500 hidden sm:flex items-center gap-1.5 bg-white px-3 py-1 rounded-lg border border-slate-200">
                        <span className="font-medium">Komposisi:</span>
                        <span className="font-semibold text-slate-700">PG ({totalQuestions})</span>
                    </div>
                </div>

                <div className="p-6 overflow-y-auto space-y-5 flex-1">
                    <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="w-full sm:w-7/12">
                            <div className="flex items-center justify-between text-xs mb-1.5">
                                <span className="font-bold text-slate-700 flex items-center gap-1.5">
                                    Kemajuan Ujian: {answeredCount} dari {totalQuestions} soal selesai
                                </span>
                                <span className="font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">{progress}%</span>
                            </div>
                            <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                                <div className="h-full bg-blue-600 rounded-full transition-all" style={{ width: `${progress}%` }}></div>
                            </div>
                        </div>
                        <div className="text-xs text-slate-500 sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 w-full sm:w-auto">
                            <p className="font-medium text-slate-700">Soal Aktif Saat Ini:</p>
                            <p className="font-bold text-indigo-700">Nomor {currentQuestionNo}</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto text-xs font-semibold">
                        <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white shadow-2xs">Semua Soal ({totalQuestions})</button>
                        <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Belum Dijawab ({unansweredCount})</button>
                        <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-amber-100 text-amber-800 transition">Ragu-ragu ({flaggedCount})</button>
                        <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-100 text-blue-800 transition">Sudah Dijawab ({answeredCount})</button>
                    </div>

                    <div>
                        <div className="flex items-center justify-between mb-2.5">
                            <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Pilih nomor untuk langsung membuka soal:</p>
                            <span className="text-[11px] text-slate-400 font-medium hidden sm:inline">Klik kotak nomor soal</span>
                        </div>
                        <div className="grid grid-cols-5 sm:grid-cols-8 gap-2.5">
                            {Array.from({ length: totalQuestions }, (_, i) => i + 1).map((questionNo) => {
                                const status = getQuestionStatus(questionNo);
                                const isActive = questionNo === currentQuestionNo;
                                const label = getQuestionLabel(questionNo);

                                let buttonClass = '';
                                if (isActive) {
                                    buttonClass = 'bg-indigo-50 text-indigo-700 ring-4 ring-indigo-600 border border-indigo-200 shadow-md';
                                } else if (status === 'answered') {
                                    buttonClass = 'bg-blue-600 hover:bg-blue-700 text-white';
                                } else if (status === 'flagged') {
                                    buttonClass = 'bg-amber-400 hover:bg-amber-500 text-amber-950 ring-1 ring-amber-500';
                                } else {
                                    buttonClass = 'bg-white text-slate-700 border-2 border-slate-300 hover:border-blue-500 hover:bg-blue-50/50';
                                }

                                return (
                                    <button
                                        key={questionNo}
                                        onClick={() => onSelectQuestion(questionNo)}
                                        className={`h-11 rounded-lg font-bold text-xs flex flex-col items-center justify-center shadow-2xs transition ${buttonClass}`}
                                        type="button"
                                    >
                                        <span className={isActive ? 'text-sm font-black' : 'font-bold'}>{String(questionNo).padStart(2, '0')}</span>
                                        
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2 text-xs text-slate-500">
                        <span className="px-2 py-0.5 rounded bg-slate-200 font-mono text-[10px] text-slate-700 font-semibold">Esc</span>
                        <span>Tekan tombol Esc untuk menutup popup navigator</span>
                    </div>
                    <div className="flex items-center gap-2.5">
                        <button
                            onClick={onClose}
                            className="px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 font-semibold text-xs sm:text-sm transition shadow-2xs"
                            type="button"
                        >
                            Tutup Dialog
                        </button>
                        <button
                            onClick={onClose}
                            className="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs sm:text-sm transition shadow-md shadow-blue-500/20 flex items-center gap-2 active:scale-98"
                            type="button"
                        >
                            <span>Kembali ke Soal Aktif (#{currentQuestionNo})</span>
                            <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default QuestionListModal;
