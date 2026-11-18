import React, { useState, useEffect, useRef } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { useToast } from '@/Hooks/useToast';
import QuestionSheet from '@/Features/Exam/Components/QuestionSheet';

export default function ExamPage({ exam, questions = [] }) {
    const { now } = usePage().props.now || { now: Date.now() };
    const { toast } = useToast();

    const subjectName = exam.subject?.name ?? exam.subject ?? 'Mata Pelajaran Tidak Ditemukan';
    const packageName = exam.examPackage?.name ?? exam.package ?? 'Paket Ujian';
    const packageCode = exam.examPackage?.code ?? exam.package_code ?? '-';
    const examType = exam.examPackage?.examType?.name ?? exam.exam_type ?? 'Ujian';
    const sessionName = exam.session?.name ?? exam.session ?? '-';
    const totalQuestions = questions.length > 0 ? questions.length : (exam.total_questions ?? 40);
    const durationMinutes = exam.duration_minutes ?? 0;

    // State untuk timer
    const [timeRemaining, setTimeRemaining] = useState(durationMinutes * 60);
    const [isRunning, setIsRunning] = useState(true);
    const [answers, setAnswers] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showSubmitModal, setShowSubmitModal] = useState(false);
    const [showQuestionModal, setShowQuestionModal] = useState(false);
    const [fontSize, setFontSize] = useState('normal');
    const [currentQuestionNo, setCurrentQuestionNo] = useState(1);
    const [answeredCount, setAnsweredCount] = useState(0);
    const [flaggedCount, setFlaggedCount] = useState(0);

    const timerRef = useRef(null);

    // Inisialisasi timer saat komponen mount
    useEffect(() => {
        timerRef.current = setInterval(() => {
            setTimeRemaining(prev => {
                if (prev <= 0) {
                    clearInterval(timerRef.current);
                    setIsRunning(false);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(timerRef.current);
    }, []);

    // Format waktu menjadi HH:MM:SS
    const formattedTime = () => {
        const hours = Math.floor(timeRemaining / 3600);
        const mins = Math.floor((timeRemaining % 3600) / 60);
        const secs = timeRemaining % 60;
        return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    // Hitung statistik jawaban
    const answerStats = Object.values(answers).reduce(
        (acc, answer) => {
            if (answer === 'flagged') {
                acc.flagged += 1;
            } else if (answer !== null && answer !== undefined && answer !== '') {
                acc.answered += 1;
            }
            return acc;
        },
        { answered: 0, flagged: 0 }
    );

    useEffect(() => {
        setAnsweredCount(answerStats.answered);
        setFlaggedCount(answerStats.flagged);
    }, [answers]);

    // Simpan jawaban ke state lokal
    const handleAnswerChange = (questionNo, answer) => {
        setAnswers(prev => ({
            ...prev,
            [questionNo]: answer,
        }));
    };

    // Pindah soal tanpa mereset timer
    const handleQuestionChange = (newQuestionNo) => {
        setCurrentQuestionNo(newQuestionNo);
    };

    // Submit semua jawaban ke server
    const handleSubmitAnswer = async (e) => {
        if (e) {
            e.preventDefault();
        }
        setIsSubmitting(true);

        try {
            const response = await fetch(route('student.exam-answers.store', exam.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    answers,
                    time_spent_seconds: durationMinutes * 60 - timeRemaining,
                }),
            });

            if (response.ok) {
                setShowSubmitModal(true);
            } else {
                toast({
                    title: 'Gagal Menyimpan',
                    description: 'Terjadi kesalahan saat menyimpan jawaban.',
                    type: 'error',
                });
            }
        } catch (error) {
            toast({
                title: 'Gagal Menyimpan',
                description: 'Terjadi kesalahan jaringan saat menyimpan jawaban.',
                type: 'error',
            });
        } finally {
            setIsSubmitting(false);
        }
    };

    // Status soal untuk daftar nomor soal
    const getQuestionStatus = (qNo) => {
        if (qNo === currentQuestionNo) {
            return 'active';
        }
        const answer = answers[qNo];
        if (answer === 'flagged') {
            return 'flagged';
        }
        if (answer !== null && answer !== undefined && answer !== '') {
            return 'answered';
        }
        return 'empty';
    };

    const getQuestionLabel = (qNo) => {
        const answer = answers[qNo];
        if (answer === 'flagged') {
            return '?';
        }
        if (answer !== null && answer !== undefined && answer !== '') {
            return answer;
        }
        return '--';
    };

    // Keluar ujian
    const handleExitExam = () => {
        setShowSubmitModal(false);
        // Redirect ke dashboard jika ingin keluar sepenuhnya
        window.location.href = route('student.dashboard');
    };

    return (
        <div className="min-h-screen bg-slate-100 font-sans">
            {/* Header Panel Ujian */}
            <header className="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs">
                <div className="w-full px-5 py-2.5 flex flex-wrap items-center justify-between gap-4">
                    {/* Brand */}
                    <div className="flex items-center gap-3 pr-5 border-r border-slate-200">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 to-blue-500 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                            <span className="text-white font-bold text-xs">CBT</span>
                        </div>
                        <div>
                            <div className="flex items-center gap-1.5 leading-none">
                                <span className="text-lg font-black tracking-tight text-blue-700">EduTest</span>
                                <span className="text-lg font-black tracking-tight text-slate-800">Pro</span>
                            </div>
                            <p className="text-[10px] uppercase font-bold tracking-wider text-slate-400 mt-0.5">Computer-Based Test</p>
                        </div>
                    </div>
                    {/* Info Ujian & Mapel */}
                    <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-2.5">
                            <h1 className="text-sm font-bold text-slate-900 leading-tight">{packageName}</h1>
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                Sesi Ujian Aktif
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 font-medium mt-0.5">
                            {subjectName} • Kode: {packageCode}
                        </p>
                    </div>
                    {/* Timer di tengah header */}
                    <div className="flex items-center bg-blue-50/80 border border-blue-200 px-4 py-1.5 rounded-xl shadow-xs">
                        <div className="mr-3 text-blue-600 flex items-center justify-center">
                            <span className="material-symbols-outlined text-[20px]">timer</span>
                        </div>
                        <div>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-blue-600 block leading-tight">Sisa Waktu Ujian</span>
                            <span className="font-mono font-extrabold text-xl tracking-tight text-blue-900">{formattedTime()}</span>
                        </div>
                    </div>
                    {/* Profil Peserta */}
                    <div className="flex items-center pl-3 border-l border-slate-200 gap-3">
                        <div className="relative">
                            <div className="w-9 h-9 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center border-2 border-white shadow-xs">
                                {subjectName.charAt(0)}
                            </div>
                            <span className="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                        </div>
                        <div className="text-right hidden sm:block">
                            <p className="text-xs font-bold text-slate-800 leading-tight">Peserta Ujian</p>
                            <div className="flex items-center justify-end gap-2 text-[11px] text-slate-500 font-medium">
                                <span>{sessionName}</span>
                                <span>•</span>
                                <span className="text-blue-700 font-semibold">{examType}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content Workspace */}
            <main className="flex-1 w-full max-w-[1720px] mx-auto p-4 sm:p-5 flex flex-col gap-5 items-start">
                {/* Question Sheet Section */}
                <section className="flex-1 w-full bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden min-h-[750px]">
                    {/* Panel Kontrol Soal (Header Kartu Soal) */}
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
                            {/* Bobot Nilai */}
                            <div className="text-xs font-medium text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                                Bobot Soal: <span className="font-bold text-blue-700">{Math.ceil(durationMinutes / totalQuestions) || 5} Poin</span>
                            </div>
                            {/* Ukuran Font Soal Switcher */}
                            <div className="flex items-center bg-white rounded-lg border border-slate-200 p-0.5 shadow-2xs">
                                <span className="text-[11px] font-medium text-slate-400 px-2">Ukuran:</span>
                                <button
                                    onClick={() => setFontSize('small')}
                                    className={`w-7 h-7 rounded text-xs font-bold flex items-center justify-center transition ${fontSize === 'small' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-600 hover:bg-slate-100'}`}
                                    title="Perkecil Font"
                                    type="button"
                                >
                                    A-
                                </button>
                                <button
                                    onClick={() => setFontSize('normal')}
                                    className={`w-7 h-7 rounded text-xs font-bold flex items-center justify-center transition ${fontSize === 'normal' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-600 hover:bg-slate-100'}`}
                                    title="Font Standar"
                                    type="button"
                                >
                                    A
                                </button>
                                <button
                                    onClick={() => setFontSize('large')}
                                    className={`w-7 h-7 rounded text-xs font-bold flex items-center justify-center transition ${fontSize === 'large' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-600 hover:bg-slate-100'}`}
                                    title="Perbesar Font"
                                    type="button"
                                >
                                    A+
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Area Teks Soal & Stimulus Soal */}
                    <div className={`p-6 md:p-8 flex-1 overflow-y-auto space-y-6 ${fontSize === 'small' ? 'text-sm' : fontSize === 'large' ? 'text-lg' : 'text-base'}`}>
                        <QuestionSheet
                            exam={{ ...exam, questions }}
                            onAnswerChange={handleAnswerChange}
                            answers={answers}
                            fontSize={fontSize}
                            currentQuestionNo={currentQuestionNo}
                        />
                        {/* Status Bar di bawah area jawaban */}
                        <div className="bg-slate-50/90 px-4 py-2 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div className="flex items-center gap-2 text-emerald-700">
                                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span className="font-medium text-[11px]">
                                    Tersimpan otomatis pada <span className="font-mono font-semibold">{new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })} WIB</span> (Draft tersinkron)
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
                    </div>

                    {/* Footer Tombol Navigasi Soal */}
                    <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        {/* Tombol Sebelumnya */}
                        <button
                            onClick={() => setCurrentQuestionNo(prev => Math.max(1, prev - 1))}
                            disabled={currentQuestionNo <= 1}
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:border-slate-400 font-semibold text-sm transition shadow-2xs disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                        >
                            <span className="material-symbols-outlined text-[16px] text-slate-500">arrow_back</span>
                            Soal Sebelumnya
                        </button>
                        {/* Toggle Ragu-Ragu (Tengah) */}
                        <label className="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 cursor-pointer hover:bg-amber-100/80 transition select-none">
                            <input
                                className="w-4 h-4 rounded text-amber-600 border-amber-400 focus:ring-amber-500"
                                type="checkbox"
                                onChange={() => {
                                    const flagged = answers[currentQuestionNo] === 'flagged';
                                    handleAnswerChange(currentQuestionNo, flagged ? null : 'flagged');
                                }}
                            />
                            <span className="text-xs font-bold flex items-center gap-1.5">
                                <span className="material-symbols-outlined text-[16px] text-amber-600">flag</span>
                                Tandai Ragu-Ragu
                            </span>
                        </label>
                        {/* Tombol Daftar Soal & Berikutnya */}
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
                                onClick={() => setCurrentQuestionNo(prev => Math.min(totalQuestions, prev + 1))}
                                disabled={currentQuestionNo >= totalQuestions}
                                className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition shadow-md shadow-blue-500/20 active:scale-98 disabled:cursor-not-allowed disabled:opacity-50"
                                type="button"
                            >
                                Soal Berikutnya
                                <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </section>

                {/* Modal Konfirmasi Selesaikan Ujian */}
                {showSubmitModal && (
                    <div
                        aria-labelledby="modal-selesaikan-title"
                        aria-modal="true"
                        className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200"
                        role="dialog"
                    >
                        <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                            {/* Header Modal */}
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
                                    onClick={() => setShowSubmitModal(false)}
                                    aria-label="Tutup Modal"
                                    className="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition shrink-0"
                                    type="button"
                                >
                                    <span className="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>
                            {/* Body Modal */}
                            <div className="p-6 space-y-5 overflow-y-auto max-h-[75vh]">
                                {/* Sisa Waktu Ujian Bar */}
                                <div className="flex items-center justify-between px-4 py-2.5 rounded-xl bg-blue-50/80 border border-blue-200">
                                    <div className="flex items-center gap-2 text-xs font-semibold text-blue-900">
                                        <span className="material-symbols-outlined text-[18px] text-blue-600">timer</span>
                                        <span>Sisa Waktu Pengerjaan Anda:</span>
                                    </div>
                                    <div className="font-mono font-bold text-sm text-blue-700 bg-white px-2.5 py-0.5 rounded-lg border border-blue-200 shadow-2xs">
                                        {formattedTime()} Tersisa
                                    </div>
                                </div>
                                {/* Summary Metric Cards (3 Kolom) */}
                                <div>
                                    <p className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Ringkasan Lembar Jawaban Siswa:</p>
                                    <div className="grid grid-cols-3 gap-3">
                                        {/* Terjawab */}
                                        <div className="bg-blue-50/70 border border-blue-200 rounded-xl p-3 flex flex-col justify-between">
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="text-[11px] font-semibold text-blue-800">Sudah Dijawab</span>
                                                <span className="w-2 h-2 rounded-full bg-blue-600"></span>
                                            </div>
                                            <p className="text-xl font-extrabold text-blue-950 font-mono">{answeredCount} <span className="text-xs font-sans font-medium text-slate-500">Soal</span></p>
                                            <span className="text-[10px] text-blue-700 mt-1 font-medium">{Math.round((answeredCount / totalQuestions) * 100)}% Lembar selesai</span>
                                        </div>
                                        {/* Ragu-ragu */}
                                        <div className="bg-amber-50/70 border border-amber-300 rounded-xl p-3 flex flex-col justify-between">
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="text-[11px] font-semibold text-amber-900">Ragu-Ragu</span>
                                                <span className="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                            </div>
                                            <p className="text-xl font-extrabold text-amber-950 font-mono">{flaggedCount} <span className="text-xs font-sans font-medium text-slate-500">Soal</span></p>
                                            <span className="text-[10px] text-amber-800 font-bold mt-1">Harap periksa kembali</span>
                                        </div>
                                        {/* Belum Dijawab */}
                                        <div className="bg-rose-50 border border-rose-200 rounded-xl p-3 flex flex-col justify-between">
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="text-[11px] font-semibold text-rose-900">Kosong / Belum</span>
                                                <span className="w-2 h-2 rounded-full bg-rose-500"></span>
                                            </div>
                                            <p className="text-xl font-extrabold text-rose-950 font-mono">{totalQuestions - answeredCount - flaggedCount} <span className="text-xs font-sans font-medium text-slate-500">Soal</span></p>
                                            <span className="text-[10px] text-rose-700 font-bold mt-1">Akan bernilai 0</span>
                                        </div>
                                    </div>
                                </div>
                                {/* Kotak Peringatan / Warning Notice */}
                                <div className="rounded-xl bg-amber-50/80 border border-amber-300 p-3.5 flex items-start gap-3">
                                    <span className="material-symbols-outlined text-[20px] text-amber-600 shrink-0 mt-0.5">warning</span>
                                    <p className="text-xs text-amber-950 leading-relaxed font-normal">
                                        <span className="font-bold">Perhatian:</span> Setelah ujian diselesaikan, Anda <span className="font-semibold underline decoration-amber-500">tidak dapat mengubah jawaban</span> atau kembali ke ruang ujian ini. Semua lembar jawaban akan langsung dikirim dan dienkripsi ke server CBT.
                                    </p>
                                </div>
                                {/* Konfirmasi Pernyataan Siswa (Checkbox Interaktif) */}
                                <label className="flex items-start gap-3 p-3.5 rounded-xl border border-slate-300 bg-slate-50 hover:bg-white transition cursor-pointer select-none">
                                    <input
                                        className="w-4 h-4 rounded text-blue-600 border-slate-400 focus:ring-blue-500 shrink-0 mt-0.5"
                                        type="checkbox"
                                    />
                                    <span className="text-xs text-slate-700 leading-relaxed">
                                        Saya menyatakan telah memeriksa seluruh lembar jawaban dengan teliti dan yakin untuk mengakhiri sesi ujian ini sekarang.
                                    </span>
                                </label>
                            </div>
                            {/* Footer Aksi Modal */}
                            <div className="p-5 bg-slate-50 border-t border-slate-200 flex flex-col gap-2.5">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <button
                                        onClick={() => setShowSubmitModal(false)}
                                        className="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 font-semibold text-xs sm:text-sm transition shadow-2xs"
                                        type="button"
                                    >
                                        Batal / Kembali Periksa Soal
                                    </button>
                                    <button
                                        onClick={() => handleSubmitAnswer()}
                                        disabled={isSubmitting}
                                        className="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm transition shadow-md shadow-blue-500/20 active:scale-98 flex items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        type="button"
                                    >
                                        <span className="material-symbols-outlined text-[18px]">send</span>
                                        <span>{isSubmitting ? 'Mengumpulkan...' : 'Ya, Selesaikan &amp; Kumpulkan'}</span>
                                    </button>
                                </div>
                                <p className="text-[11px] text-center text-slate-400 font-medium">
                                    Tekan <kbd className="px-1.5 py-0.5 rounded bg-white border border-slate-300 font-mono text-[10px] text-slate-600 shadow-2xs">Esc</kbd> untuk menutup konfirmasi dan melanjutkan ujian
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Modal Dialog Daftar Nomor Soal Interaktif */}
                {showQuestionModal && (
                    <div
                        aria-labelledby="modal-daftar-soal-title"
                        aria-modal="true"
                        className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                        role="dialog"
                    >
                        <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-4xl w-full flex flex-col max-h-[92vh] overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                            {/* Modal Header */}
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
                                    onClick={() => setShowQuestionModal(false)}
                                    aria-label="Tutup Modal"
                                    className="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition"
                                    type="button"
                                >
                                    <span className="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>
                            {/* Quick Stats & Status Badges Strip */}
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
                                        Belum Dijawab: {totalQuestions - answeredCount - flaggedCount}
                                    </span>
                                </div>
                                <div className="text-[11px] text-slate-500 hidden sm:flex items-center gap-1.5 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                    <span className="font-medium">Komposisi:</span>
                                    <span className="font-semibold text-slate-700">PG ({totalQuestions})</span>
                                </div>
                            </div>
                            {/* Modal Body */}
                            <div className="p-6 overflow-y-auto space-y-5 flex-1">
                                {/* Progress Bar & Instruction Header */}
                                <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div className="w-full sm:w-7/12">
                                        <div className="flex items-center justify-between text-xs mb-1.5">
                                            <span className="font-bold text-slate-700 flex items-center gap-1.5">
                                                Kemajuan Ujian: {answeredCount} dari {totalQuestions} soal selesai
                                            </span>
                                            <span className="font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">{Math.round((answeredCount / totalQuestions) * 100)}%</span>
                                        </div>
                                        <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                                            <div className="h-full bg-blue-600 rounded-full transition-all" style={{ width: `${Math.round((answeredCount / totalQuestions) * 100)}%` }}></div>
                                        </div>
                                    </div>
                                    <div className="text-xs text-slate-500 sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 w-full sm:w-auto">
                                        <p className="font-medium text-slate-700">Soal Aktif Saat Ini:</p>
                                        <p className="font-bold text-indigo-700">Nomor {currentQuestionNo}</p>
                                    </div>
                                </div>
                                {/* Interactive Filter Tabs */}
                                <div className="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto text-xs font-semibold">
                                    <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white shadow-2xs">Semua Soal ({totalQuestions})</button>
                                    <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Belum Dijawab ({totalQuestions - answeredCount - flaggedCount})</button>
                                    <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-amber-100 text-amber-800 transition">Ragu-ragu ({flaggedCount})</button>
                                    <button onClick={() => {}} className="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-100 text-blue-800 transition">Sudah Dijawab ({answeredCount})</button>
                                </div>
                                {/* Grid 40 Nomor Soal (8 Kolom x 5 Baris) */}
                                <div>
                                    <div className="flex items-center justify-between mb-2.5">
                                        <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Pilih nomor untuk langsung membuka soal:</p>
                                        <span className="text-[11px] text-slate-400 font-medium hidden sm:inline">Klik kotak nomor soal</span>
                                    </div>
                                    <div className="grid grid-cols-5 sm:grid-cols-8 gap-2.5">
                                        {Array.from({ length: totalQuestions }, (_, i) => i + 1).map(qNo => {
                                            const status = getQuestionStatus(qNo);
                                            const isActive = qNo === currentQuestionNo;
                                            const label = getQuestionLabel(qNo);

                                            let btnClass = '';
                                            if (isActive) {
                                                btnClass = 'bg-indigo-50 text-indigo-700 ring-4 ring-indigo-600 border border-indigo-200 shadow-md';
                                            } else if (status === 'answered') {
                                                btnClass = 'bg-blue-600 hover:bg-blue-700 text-white';
                                            } else if (status === 'flagged') {
                                                btnClass = 'bg-amber-400 hover:bg-amber-500 text-amber-950 ring-1 ring-amber-500';
                                            } else {
                                                btnClass = 'bg-white text-slate-700 border-2 border-slate-300 hover:border-blue-500 hover:bg-blue-50/50';
                                            }

                                            return (
                                                <button
                                                    key={qNo}
                                                    onClick={() => {
                                                        setCurrentQuestionNo(qNo);
                                                        setShowQuestionModal(false);
                                                    }}
                                                    className={`h-11 rounded-lg font-bold text-xs flex flex-col items-center justify-center shadow-2xs transition ${btnClass}`}
                                                    type="button"
                                                >
                                                    <span className={isActive ? 'text-sm font-black' : 'font-bold'}>{String(qNo).padStart(2, '0')}</span>
                                                    <span className="text-[9px] uppercase leading-none font-bold">
                                                        {isActive ? 'LINK ●' : label === '--' ? '--' : label}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                            {/* Modal Footer */}
                            <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                                <div className="flex items-center gap-2 text-xs text-slate-500">
                                    <span className="px-2 py-0.5 rounded bg-slate-200 font-mono text-[10px] text-slate-700 font-semibold">Esc</span>
                                    <span>Tekan tombol Esc untuk menutup popup navigator</span>
                                </div>
                                <div className="flex items-center gap-2.5">
                                    <button
                                        onClick={() => setShowQuestionModal(false)}
                                        className="px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 font-semibold text-xs sm:text-sm transition shadow-2xs"
                                        type="button"
                                    >
                                        Tutup Dialog
                                    </button>
                                    <button
                                        onClick={() => setShowQuestionModal(false)}
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
                )}
            </main>
        </div>
    );
}
