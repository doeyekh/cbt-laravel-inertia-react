import React, { useEffect, useState } from 'react';

const SubmitConfirmationModal = ({
    isOpen,
    onClose,
    onSubmit,
    subjectName,
    packageName,
    formattedTime,
    answeredCount,
    flaggedCount,
    totalQuestions,
    isAutoSaving,
}) => {
    const [isConfirmed, setIsConfirmed] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setIsConfirmed(false);
        }
    }, [isOpen]);

    if (!isOpen) {
        return null;
    }

    const unansweredCount = totalQuestions - answeredCount - flaggedCount;
    const progress = totalQuestions > 0
        ? Math.round((answeredCount / totalQuestions) * 100)
        : 0;

    return (
        <div
            aria-labelledby="modal-selesaikan-title"
            aria-modal="true"
            className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200"
            role="dialog"
        >
            <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div className="p-6 pb-4 border-b border-slate-200 bg-slate-50/80 flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3.5">
                        <div className="w-12 h-12 rounded-xl bg-amber-100 border border-amber-300 text-amber-700 flex items-center justify-center shrink-0 shadow-xs">
                            <span className="material-symbols-outlined text-[24px]">assignment</span>
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h2 className="text-lg font-bold text-slate-900 leading-tight" id="modal-selesaikan-title">Konfirmasi Selesaikan Ujian</h2>
                                <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200">Penting</span>
                            </div>
                            <p className="text-xs text-slate-500 font-medium mt-1">{subjectName} • {packageName}</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        aria-label="Tutup Modal"
                        className="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition shrink-0"
                        type="button"
                    >
                        <span className="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div className="p-6 space-y-5 overflow-y-auto max-h-[75vh]">
                    <div className="flex items-center justify-between px-4 py-2.5 rounded-xl bg-blue-50/80 border border-blue-200">
                        <div className="flex items-center gap-2 text-xs font-semibold text-blue-900">
                            <span className="material-symbols-outlined text-[18px] text-blue-600">timer</span>
                            <span>Sisa Waktu Pengerjaan Anda:</span>
                        </div>
                        <div className="font-mono font-bold text-sm text-blue-700 bg-white px-2.5 py-0.5 rounded-lg border border-blue-200 shadow-2xs">
                            {formattedTime} Tersisa
                        </div>
                    </div>

                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Ringkasan Lembar Jawaban Siswa:</p>
                        <div className="grid grid-cols-3 gap-3">
                            <div className="bg-blue-50/70 border border-blue-200 rounded-xl p-3 flex flex-col justify-between">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-[11px] font-semibold text-blue-800">Sudah Dijawab</span>
                                    <span className="w-2 h-2 rounded-full bg-blue-600"></span>
                                </div>
                                <p className="text-xl font-extrabold text-blue-950 font-mono">{answeredCount} <span className="text-xs font-sans font-medium text-slate-500">Soal</span></p>
                                <span className="text-[10px] text-blue-700 mt-1 font-medium">{progress}% Lembar selesai</span>
                            </div>

                            <div className="bg-amber-50/70 border border-amber-300 rounded-xl p-3 flex flex-col justify-between">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-[11px] font-semibold text-amber-900">Ragu-Ragu</span>
                                    <span className="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                </div>
                                <p className="text-xl font-extrabold text-amber-950 font-mono">{flaggedCount} <span className="text-xs font-sans font-medium text-slate-500">Soal</span></p>
                                <span className="text-[10px] text-amber-800 font-bold mt-1">Harap periksa kembali</span>
                            </div>

                            <div className="bg-rose-50 border border-rose-200 rounded-xl p-3 flex flex-col justify-between">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-[11px] font-semibold text-rose-900">Kosong / Belum</span>
                                    <span className="w-2 h-2 rounded-full bg-rose-500"></span>
                                </div>
                                <p className="text-xl font-extrabold text-rose-950 font-mono">{unansweredCount} <span className="text-xs font-sans font-medium text-slate-500">Soal</span></p>
                                <span className="text-[10px] text-rose-700 font-bold mt-1">Akan bernilai 0</span>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl bg-amber-50/80 border border-amber-300 p-3.5 flex items-start gap-3">
                        <span className="material-symbols-outlined text-[20px] text-amber-600 shrink-0 mt-0.5">warning</span>
                        <p className="text-xs text-amber-950 leading-relaxed font-normal">
                            <span className="font-bold">Perhatian:</span> Setelah ujian diselesaikan, Anda <span className="font-semibold underline decoration-amber-500">tidak dapat mengubah jawaban</span> atau kembali ke ruang ujian ini. Semua lembar jawaban akan langsung dikirim dan dienkripsi ke server CBT.
                        </p>
                    </div>

                    <label className="flex items-start gap-3 p-3.5 rounded-xl border border-slate-300 bg-slate-50 hover:bg-white transition cursor-pointer select-none">
                        <input
                            className="w-4 h-4 rounded text-blue-600 border-slate-400 focus:ring-blue-500 shrink-0 mt-0.5"
                            type="checkbox"
                            checked={isConfirmed}
                            onChange={(event) => setIsConfirmed(event.target.checked)}
                        />
                        <span className="text-xs text-slate-700 leading-relaxed">
                            Saya menyatakan telah memeriksa seluruh lembar jawaban dengan teliti dan yakin untuk mengakhiri sesi ujian ini sekarang.
                        </span>
                    </label>
                </div>

                <div className="p-5 bg-slate-50 border-t border-slate-200 flex flex-col gap-2.5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <button
                            onClick={onClose}
                            className="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 font-semibold text-xs sm:text-sm transition shadow-2xs"
                            type="button"
                        >
                            Batal / Kembali Periksa Soal
                        </button>
                        <button
                            onClick={onSubmit}
                            disabled={isAutoSaving || !isConfirmed}
                            className="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm transition shadow-md shadow-blue-500/20 active:scale-98 flex items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                        >
                            <span className="material-symbols-outlined text-[18px]">send</span>
                            <span>{isAutoSaving ? 'Mengumpulkan...' : 'Ya, Selesaikan & Kumpulkan'}</span>
                        </button>
                    </div>
                    <p className="text-[11px] text-center text-slate-400 font-medium">
                        Tekan <kbd className="px-1.5 py-0.5 rounded bg-white border border-slate-300 font-mono text-[10px] text-slate-600 shadow-2xs">Esc</kbd> untuk menutup konfirmasi dan melanjutkan ujian
                    </p>
                </div>
            </div>
        </div>
    );
};

export default SubmitConfirmationModal;
