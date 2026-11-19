import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import { route } from '@/Utils/route';

const TYPE_LABELS = {
    pilihan_ganda: 'Pilihan Ganda',
    pilihan_ganda_kompleks: 'Pilihan Ganda Kompleks',
    menjodohkan: 'Menjodohkan',
    isian_singkat: 'Isian',
    esai: 'Esai',
};

export default function ExamAccess({ exam = {}, participant = {}, systemInfo = {} }) {
    const { data, setData, post, processing, errors } = useForm({ token: '' });

    const handleSubmit = (event) => {
        event.preventDefault();
        post(route('student.exam-access.verify', exam.id), {
            preserveScroll: true,
        });
    };

    const durationMinutes = exam.duration_minutes ?? 0;
    const durationText =
        durationMinutes > 0
            ? `${durationMinutes} menit`
            : '-';

    return (
        <StudentLayout participant={participant} {...systemInfo} hideSidebar>
            <div className="mx-auto flex max-w-3xl flex-col gap-5">
                <Link
                    href={route('student.dashboard')}
                    className="inline-flex w-fit items-center gap-2 font-label text-[12px] font-semibold text-primary transition-colors hover:text-primary-container"
                >
                    <span className="material-symbols-outlined text-[18px]">arrow_back</span>
                    Kembali ke Dashboard
                </Link>

                <section className="rounded-xl bg-surface-container-lowest p-5 shadow-sm ring-1 ring-outline-variant/60 sm:p-7">
                    <div className="flex flex-col gap-2">
                        <span className="inline-flex w-fit items-center gap-1.5 rounded-full bg-primary-fixed px-3 py-1 font-label text-[9px] font-semibold uppercase tracking-[0.08em] text-on-primary-fixed-variant">
                            <span className="material-symbols-outlined text-[15px]">key</span>
                            Otorisasi Ujian
                        </span>
                        <h1 className="font-headline text-2xl font-extrabold tracking-tight text-on-surface">
                            Masukkan Token Ujian
                        </h1>
                        <p className="font-body text-[12px] leading-relaxed text-on-surface-variant">
                            Masukkan token 6 karakter yang diberikan proktor untuk membuka akses
                            ujian.
                        </p>
                    </div>

                    <div className="mt-6 rounded-lg bg-surface-container-low p-4">
                        <div className="flex flex-wrap items-center gap-2">
                            {exam.package_code && (
                                <span className="rounded bg-primary px-2.5 py-0.5 font-label text-[10px] font-bold uppercase tracking-[0.08em] text-on-primary">
                                    {exam.package_code}
                                </span>
                            )}
                            {exam.exam_type && (
                                <span className="font-label text-[10px] font-medium text-secondary">
                                    {exam.exam_type}
                                </span>
                            )}
                        </div>
                        <h2 className="mt-2 font-headline text-lg font-bold text-on-surface">
                            {exam.subject || 'Mata Pelajaran'}
                        </h2>
                        <p className="mt-1 font-label text-[11px] leading-relaxed text-on-surface-variant">
                            {[
                                exam.date,
                                exam.open_time && `${exam.open_time}–${exam.close_time} WIB`,
                                exam.session,
                                exam.room,
                            ]
                                .filter(Boolean)
                                .join(' • ')}
                        </p>
                    </div>

                    <div className="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="rounded-lg bg-surface-container-low p-4 ring-1 ring-outline-variant/50">
                            <p className="font-label text-[9px] font-semibold uppercase tracking-[0.1em] text-secondary">
                                Total Soal
                            </p>
                            <p className="mt-1 font-headline text-2xl font-bold text-on-surface">
                                {exam.total_questions}
                            </p>
                        </div>
                        <div className="rounded-lg bg-surface-container-low p-4 ring-1 ring-outline-variant/50">
                            <p className="font-label text-[9px] font-semibold uppercase tracking-[0.1em] text-secondary">
                                Waktu Pengerjaan
                            </p>
                            <p className="mt-1 font-headline text-2xl font-bold text-on-surface">
                                {durationText}
                            </p>
                        </div>
                        <div className="rounded-lg bg-surface-container-low p-4 ring-1 ring-outline-variant/50">
                            <p className="font-label text-[9px] font-semibold uppercase tracking-[0.1em] text-secondary">
                                Total Poin
                            </p>
                            <p className="mt-1 font-headline text-2xl font-bold text-on-surface">
                                {exam.total_score?.toFixed?.(0) ?? '-'}
                            </p>
                        </div>
                    </div>

                    {Object.keys(exam.question_composition ?? {}).length > 0 && (
                        <div className="mt-6 rounded-lg bg-surface-container-low p-4 ring-1 ring-outline-variant/50">
                            <div className="flex items-center gap-2 mb-3">
                                <span className="material-symbols-outlined text-[16px] text-on-surface-variant">
                                    list_alt
                                </span>
                                <h3 className="font-label text-[11px] font-semibold uppercase tracking-[0.1em] text-on-surface">
                                    Komposisi Soal
                                </h3>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                {Object.entries(exam.question_composition).map(([type, count]) => {
                                    if (Number(count) <= 0) {
                                        return null;
                                    }
                                    return (
                                        <div
                                            key={type}
                                            className="flex items-center justify-between px-3 py-2 rounded bg-surface-container-lowest border border-outline-variant/30"
                                        >
                                            <span className="font-label text-[10px] font-medium text-on-surface">
                                                {TYPE_LABELS[type] ?? type}
                                            </span>
                                            <span className="font-mono text-[11px] font-bold text-primary">
                                                {count}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    {exam.token_access_granted ? (
                        <p className="mt-5 flex items-center gap-2 rounded-lg bg-primary-fixed/70 p-4 font-label text-[12px] font-semibold text-on-primary-fixed-variant">
                            <span className="material-symbols-outlined text-[18px]">verified</span>
                            Token sudah terverifikasi untuk paket ujian ini.
                        </p>
                    ) : !exam.token_available ? (
                        <p className="mt-5 rounded-lg bg-surface-container p-4 font-label text-[12px] text-secondary">
                            Token belum diterbitkan. Silakan hubungi proktor.
                        </p>
                    ) : !exam.can_verify ? (
                        <p className="mt-5 rounded-lg bg-surface-container p-4 font-label text-[12px] text-secondary">
                            Token tidak dapat dimasukkan untuk jadwal ujian ini.
                        </p>
                    ) : (
                        <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-4">
                            <div className="flex flex-col gap-2">
                                <label
                                    htmlFor="student-exam-token"
                                    className="font-label text-[10px] font-semibold uppercase tracking-[0.1em] text-secondary"
                                >
                                    Token dari proktor
                                </label>
                                <input
                                    id="student-exam-token"
                                    type="text"
                                    value={data.token}
                                    onChange={(event) =>
                                        setData('token', event.target.value.toUpperCase())
                                    }
                                    maxLength={6}
                                    autoComplete="one-time-code"
                                    autoCapitalize="characters"
                                    autoFocus
                                    aria-invalid={errors.token ? 'true' : undefined}
                                    aria-describedby={
                                        errors.token ? 'student-token-error' : undefined
                                    }
                                    placeholder="Masukkan 6 karakter"
                                    disabled={processing}
                                    className={`w-full rounded-lg border bg-surface-container-low px-4 py-3 font-mono text-center text-lg font-bold uppercase tracking-[0.3em] text-on-surface outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-60 ${
                                        errors.token
                                            ? 'border-error focus:border-error'
                                            : 'border-outline-variant focus:border-primary'
                                    }`}
                                />
                                {errors.token && (
                                    <p
                                        id="student-token-error"
                                        role="alert"
                                        className="font-label text-[11px] text-error"
                                    >
                                        {errors.token}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing || data.token.length !== 6}
                                className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 font-label text-[12px] font-semibold text-on-primary transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? 'Memeriksa token...' : 'Verifikasi Token'}
                                <span className="material-symbols-outlined text-[18px]">
                                    verified
                                </span>
                            </button>
                        </form>
                    )}
                </section>
            </div>
        </StudentLayout>
    );
}
