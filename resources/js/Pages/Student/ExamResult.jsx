import React from 'react';
import { Link } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import { route } from '@/Utils/route';

export default function ExamResult({
    exam = {},
    status,
    score_visible: scoreVisible,
    score,
    max_score: maxScore,
    percentage,
    correct_count: correctCount,
    total_questions: totalQuestions,
    pending_essay_count: pendingEssayCount = 0,
}) {
    const isGraded = status === 'graded';

    return (
        <StudentLayout hideSidebar>
            <main className="mx-auto max-w-3xl space-y-6 p-4 sm:p-8">
                <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="flex items-start gap-4">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <span className="material-symbols-outlined">task_alt</span>
                        </div>
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wider text-emerald-700">
                                Ujian selesai
                            </p>
                            <h1 className="mt-1 text-2xl font-bold text-slate-900">
                                Jawaban berhasil dikumpulkan
                            </h1>
                            <p className="mt-2 text-sm text-slate-600">
                                {exam.subject} · {exam.package}
                            </p>
                        </div>
                    </div>

                    {scoreVisible ? (
                        <div className="mt-8 grid gap-4 sm:grid-cols-3">
                            <div className="rounded-xl bg-blue-50 p-4">
                                <p className="text-xs font-medium text-blue-700">Nilai Akhir</p>
                                <p className="mt-1 text-3xl font-bold text-blue-950">
                                    {percentage ?? 0}
                                    <span className="ml-1 text-base font-semibold text-blue-700">
                                        / 100
                                    </span>
                                </p>
                                <p className="mt-1 text-xs font-medium text-blue-700">
                                    {score ?? 0} / {maxScore ?? 0} poin
                                </p>
                            </div>
                            <div className="rounded-xl bg-emerald-50 p-4">
                                <p className="text-xs font-medium text-emerald-700">Benar</p>
                                <p className="mt-1 text-3xl font-bold text-emerald-950">
                                    {correctCount ?? 0}
                                    <span className="ml-1 text-base font-semibold text-emerald-700">
                                        / {totalQuestions ?? 0} soal
                                    </span>
                                </p>
                            </div>
                            <div className="rounded-xl bg-slate-100 p-4">
                                <p className="text-xs font-medium text-slate-600">Status</p>
                                <p className="mt-2 font-semibold text-slate-900">
                                    {isGraded ? 'Sudah dinilai' : 'Menunggu penilaian esai'}
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                            <p className="font-semibold">
                                {pendingEssayCount > 0
                                    ? 'Jawaban esai sedang menunggu penilaian guru.'
                                    : 'Pengaturan ujian tidak menampilkan nilai kepada peserta.'}
                            </p>
                            <p className="mt-1 text-amber-800">
                                Status pengumpulan: {isGraded ? 'Sudah dinilai' : 'Sudah dikumpulkan'}.
                            </p>
                        </div>
                    )}

                    <p className="mt-6 text-xs text-slate-500">
                        Waktu pengumpulan: {exam.submitted_at ?? 'Tercatat di server'}
                    </p>

                    <Link
                        href={route('student.dashboard')}
                        className="mt-7 inline-flex rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        Kembali ke Dashboard
                    </Link>
                </section>
            </main>
        </StudentLayout>
    );
}
