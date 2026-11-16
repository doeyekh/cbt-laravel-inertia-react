import React, { useState, useEffect, useRef } from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import { useToast } from '@/Hooks/useToast';
import ExamNavigation from '@/Components/Student/ExamNavigation';
import QuestionSheet from '@/Components/Exam/QuestionSheet';
import { v4 as uuidv4 } from 'uuid';

export default function ExamPage({ exam }) {
    const { now } = usePage().props.now || { now: Date.now() };
    const { flash, auth } = usePage().props;
    const { toast, dismiss } = useToast();

    // State untuk timer
    const [timeRemaining, setTimeRemaining] = useState(
        exam.duration_minutes * 60 // menit ke detik
    );
    const [isRunning, setIsRunning] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [showConfirmation, setShowConfirmation] = useState(false);
    const examId = uuidv4();

    // Ref untuk timer
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

    // Fungkon reset timer jika soal berubah
    useEffect(() => {
        setTimeRemaining(exam.duration_minutes * 60);
        setIsRunning(true);
    }, [exam]);

    // Format waktu menjadi menit:detik
    const formattedTime = () => {
        const mins = Math.floor(timeRemaining / 60);
        const secs = timeRemaining % 60;
        return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    };

    // Kosongkan jawaban saat berpindah soal
    const handleQuestionChange = (newQuestionNo) => {
        // Reset answer state when changing questions
        setTimeRemaining(exam.duration_minutes * 60);
    };

    // Submit jawaban
    const handleSubmitAnswer = async (e) => {
        e.preventDefault();
        // Submit logic - akan diintegrasi dengan backend
        toast({
            title: 'Menyimpan Jawaban',
            description: 'Jawaban sedang disimpan...',
            type: 'info',
        });
    };

    // Keluar ujian
    const handleExitExam = () => {
        setShowConfirmation(true);
    };

    // Konfirmasi keluar
    const confirmExit = async () => {
        setShowConfirmation(false);
        // Logika konfirmasi keluar
        toast({
            title: 'Ujian Dihentikan',
            description: 'Sesi ujian dihentikan.',
            type: 'warning',
        });
    };

    return (
        <div className="min-h-screen bg-slate-100">
            <Link
                href={route('student.dashboard')}
                className="inline-flex items-center gap-2 font-label text-[12px] font-semibold text-primary transition-colors hover:text-primary-container mb-4">
                <span className="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Dashboard
            </Link>

            <section className="rounded-xl bg-surface-container-lowest p-8 shadow-sm max-w-3xl mx-auto">
                <div className="flex flex-col gap-6">
                    {/* Header dengan timer */}
                    <div className="bg-slate-50 p-4 rounded-lg border-b">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="font-semibold text-on-surface mb-2">Pengerjaan Ujian</h2>
                                <p className="font-body text-[12px] leading-relaxed text-on-surface-variant">
                                    {exam.subject || 'Mata Pelajaran Tidak Ditemukan'}
                                </p>
                            </div>
                            <div className="relative">
                                <span className="material-symbols-outlined text-[20px] text-primary">access_time</span>
                                <div className="absolute -right-2 -top-2 bg-primary text-white text-xs rounded-pxl px-2 py-1">
                                    {exam.duration_minutes}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Timer Display */}
                    <div className="mb-6">
                        {isRunning ? (
                            <div className="flex items-center gap-3">
                                <span className="text-3xl font-bold font-extrabold text-on-surface">
                                    {formattedTime()}
                                </span>
                                <span className="text-sm text-on-surface-variant">
                                    sisa waktu
                                </span>
                            </div>
                        ) : (
                            <div className="flex items-center gap-3 text-emerald-600">
                                <span className="text-3xl font-bold font-extrabold text-emerald-600">00:00</span>
                                <span className="text-sm text-on-surface-variant">waktu habis</span>
                            </div>
                        )}
                    </div>

                    {/* Navigasi Soal */}
                    <ExamNavigation
                        exam={exam}
                        onQuestionChange={handleQuestionChange}
                        showConfirmation={showConfirmation}
                        onConfirmExit={confirmExit}
                    />

                    {/* Area Soal */}
                    <div className="flex-1 min-h-[700px]">
                        <QuestionSheet
                            exam={exam}
                            onQuestionChange={handleQuestionChange}
                        />
                    </div>

                    {/* Footer aksi */}
                    <div className="mt-6 p-4 bg-slate-50 rounded-t-xl border-t border-slate-200">
                        <div className="flex justify-between items-center">
                            <button
                                onClick={handleExitExam}
                                className="inline-flex items-center gap-2 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-600 hover:bg-red-200 transition-colors">
                                <span className="material-symbols-outlined text-[14px]">logout</span>
                                Keluar Ujian
                            </button>
                            <button
                                onClick={() => toast({title: 'Disimpan', description: 'Jawaban disimpan ke server', type: 'success'})}
                                className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 transition-shadow shadow-md">
                                <span className="material-symbols-outlined">save</span>
                                Simpan & Kumpulkan
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            {/* Modal Konfirmasi Keluar */}
            <div
                aria-labelledby="exit-exam-label"
                aria-modal="true"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200"
                role="dialog"
                id="exit-exam-modal">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-sm w-full flex flex-col p-6 animate-in fade-in zoom-in-95 duration-200">
                    <h2 id="exit-exam-label" class="text-xl font-bold text-slate-900 mb-4">Konfirmasi</h2>
                    <p className="text-slate-500 mb-6">
                        Apakah Anda yakin ingin keluar dari ujian ini? Jawaban yang belum disimpan mungkin akan hilang.
                    </p>
                    <div className="flex gap-3">
                        <button
                            className="flex-1 px-4 py-2 rounded-lg bg-gray-100 text-slate-600 hover:bg-gray-200 transition"
                            onClick={() => setShowConfirmation(false)}
                            >Batal</button>
                        <button
                            className="flex-1 px-4 py-2 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700 transition-shadow shadow-md"
                            onClick={() => {
                                confirmExit();
                                setShowConfirmation(false);
                            }}
                            >Ya, Keluar</button>
                    </div>
                </div>
            </div>
        </div>
    );
}