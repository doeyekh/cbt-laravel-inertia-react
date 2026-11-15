import React from 'react';
import {
    formatCountdown,
    formatExamDate,
    getExamCountdown,
    getExamStatus,
    sortExams,
} from '@/Utils/examSchedule';

const STATUS_META = {
    ongoing: {
        label: 'Ujian Sedang Berlangsung',
        chip: 'bg-error-container text-error',
        dot: 'bg-error animate-pulse',
        timerIcon: 'timer',
        timerLabel: 'Sisa Waktu Sesi',
        timerClass: 'text-error',
    },
    upcoming: {
        label: 'Sesi Berikutnya',
        chip: 'bg-primary-fixed text-on-primary-fixed-variant',
        dot: 'bg-primary',
        timerIcon: 'hourglass_top',
        timerLabel: 'Mulai dalam',
        timerClass: 'text-primary',
    },
    finished: {
        label: 'Sesi Telah Selesai',
        chip: 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        dot: 'bg-tertiary',
        timerIcon: 'task_alt',
        timerLabel: 'Selesai',
        timerClass: 'text-tertiary',
    },
};

function InfoTile({ label, value, className = '' }) {
    return (
        <div className={`flex flex-col rounded-lg bg-surface-container-low p-3 ${className}`}>
            <span className="font-label text-[9px] font-semibold uppercase tracking-[0.1em] text-secondary">
                {label}
            </span>
            <span className="mt-1 truncate font-label text-[13px] font-bold text-on-surface">
                {value}
            </span>
        </div>
    );
}

function TokenPanel({ status }) {
    const meta = {
        ongoing: {
            badge: 'Menunggu Token',
            badgeClass: 'bg-amber-100 text-amber-700',
            note: 'Token sesi aktif hanya dibagikan proktor di ruang ujian.',
        },
        upcoming: {
            badge: 'Belum Dirilis',
            badgeClass: 'bg-surface-container text-secondary',
            note: 'Token akan dirilis proktor saat sesi dimulai.',
        },
        finished: {
            badge: 'Sesi Ditutup',
            badgeClass: 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
            note: 'Sesi telah berakhir. Hubungi proktor untuk melihat hasil ujian.',
        },
    }[status];

    if (status === 'finished') {
        return (
            <div className="flex flex-col gap-3 rounded-xl bg-surface-container-low p-5 ring-1 ring-outline-variant/50 lg:col-span-5">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <span className="flex items-center gap-1.5 font-label text-[13px] font-bold text-on-surface">
                        <span className="material-symbols-outlined text-[19px] text-tertiary">
                            task_alt
                        </span>
                        Sesi Ujian Ditutup
                    </span>
                    <span
                        className={`rounded-full px-2.5 py-1 font-label text-[9px] font-semibold uppercase tracking-[0.08em] ${meta.badgeClass}`}
                    >
                        {meta.badge}
                    </span>
                </div>

                <p className="flex items-start gap-1.5 font-label text-[10px] leading-relaxed text-on-surface-variant">
                    <span className="material-symbols-outlined text-[15px] text-outline">
                        info
                    </span>
                    {meta.note}
                </p>
            </div>
        );
    }

    return (
        <div className="rounded-xl bg-surface-container-low p-5 ring-1 ring-outline-variant/50 lg:col-span-5">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="flex items-center gap-1.5 font-label text-[13px] font-bold text-on-surface">
                    <span className="material-symbols-outlined text-[19px] text-primary">key</span>
                    Otorisasi Token Ujian
                </span>
                <span
                    className={`rounded-full px-2.5 py-1 font-label text-[9px] font-semibold uppercase tracking-[0.08em] ${meta.badgeClass}`}
                >
                    {meta.badge}
                </span>
            </div>

            <div className="mt-4 flex flex-col gap-2">
                <label
                    htmlFor="student-exam-token"
                    className="font-label text-[9px] font-semibold uppercase tracking-[0.1em] text-secondary"
                >
                    Masukkan 6 karakter token dari proktor:
                </label>
                <div className="flex items-center gap-2">
                    <input
                        id="student-exam-token"
                        type="text"
                        disabled
                        maxLength={7}
                        placeholder="XXX-XXX"
                        aria-label="Token ujian"
                        className="h-11 w-full rounded-lg bg-surface-container-lowest text-center font-label text-base font-bold uppercase tracking-[0.2em] text-on-surface placeholder:text-on-surface-variant/50 disabled:cursor-not-allowed"
                    />
                    <button
                        type="button"
                        disabled
                        title="Ruang ujian dibuka oleh proktor ruangan"
                        className="flex h-11 shrink-0 items-center gap-2 rounded-lg bg-primary px-4 font-label text-[12px] font-semibold text-on-primary disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Masuk Ujian
                        <span className="material-symbols-outlined text-[18px]">login</span>
                    </button>
                </div>
            </div>

            <p className="mt-3 flex items-start gap-1.5 font-label text-[10px] leading-relaxed text-on-surface-variant">
                <span className="material-symbols-outlined text-[15px] text-outline">lock_clock</span>
                {meta.note}
            </p>
        </div>
    );
}

function EmptyHero() {
    return (
        <section className="rounded-xl bg-surface-container-lowest px-6 py-12 text-center shadow-sm ring-1 ring-outline-variant/60">
            <span className="material-symbols-outlined text-[34px] text-on-surface-variant/50">
                event_busy
            </span>
            <h2 className="mt-3 font-headline text-xl font-bold tracking-tight text-on-surface">
                Belum ada sesi ujian
            </h2>
            <p className="mx-auto mt-2 max-w-md font-body text-[13px] leading-relaxed text-on-surface-variant">
                Jadwal akan tampil setelah panitia menerbitkan paket dan jadwal ujian untuk kelas
                Anda. Pastikan perangkat sudah siap sebelum sesi pertama dimulai.
            </p>
        </section>
    );
}

export default function ExamHero({ exams = [], now = new Date() }) {
    const sortedExams = sortExams(exams);
    const ongoingExam = sortedExams.find((exam) => getExamStatus(exam, now) === 'ongoing');
    const upcomingExam = sortedExams.find((exam) => getExamStatus(exam, now) === 'upcoming');
    const finishedExam = [...sortedExams]
        .reverse()
        .find((exam) => getExamStatus(exam, now) === 'finished');

    const exam = ongoingExam || upcomingExam || finishedExam;

    if (!exam) {
        return <EmptyHero />;
    }

    const status = ongoingExam ? 'ongoing' : upcomingExam ? 'upcoming' : 'finished';
    const meta = STATUS_META[status];
    const countdown = getExamCountdown(exam, now);
    const timeWindow =
        exam.open_time && exam.close_time ? `${exam.open_time} – ${exam.close_time} WIB` : null;
    const sessionLabel = [exam.session, timeWindow].filter(Boolean).join(' • ');
    const duration = exam.duration_minutes ? `${exam.duration_minutes} Menit` : '—';
    const helperText = {
        ongoing:
            'Pastikan Safe Exam Browser tetap fokus aktif dan kamera AI proctoring menyala tanpa terhalang.',
        upcoming: `Dijadwalkan pada ${formatExamDate(exam.date)}. Siapkan perangkat dan Kartu Peserta sebelum sesi dibuka.`,
        finished: `Sesi telah selesai pada ${formatExamDate(exam.date)}. Jawaban Anda tersimpan pada sistem proktor.`,
    }[status];

    return (
        <section className="relative overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/60">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -right-16 -top-16 h-80 w-80 rounded-full bg-primary/5 blur-3xl"
            />

            <div className="relative flex flex-col gap-5 p-5 sm:p-6 lg:p-7">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <span
                            className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-label text-[9px] font-semibold uppercase tracking-[0.08em] ${meta.chip}`}
                        >
                            <span className={`h-1.5 w-1.5 rounded-full ${meta.dot}`} />
                            {meta.label}
                        </span>

                        {sessionLabel && (
                            <span className="rounded-full bg-surface-container px-3 py-1 font-label text-[10px] font-semibold text-secondary">
                                {sessionLabel}
                            </span>
                        )}
                    </div>

                    {countdown && status !== 'finished' && (
                        <div className={`flex items-center gap-2 font-label text-[12px] ${meta.timerClass}`}>
                            <span className="material-symbols-outlined text-[20px]">
                                {meta.timerIcon}
                            </span>
                            <span className="text-on-surface">
                                {meta.timerLabel}:{' '}
                                <strong className={`tabular-nums ${meta.timerClass}`}>
                                    {formatCountdown(countdown.milliseconds)}
                                </strong>
                            </span>
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-1 items-center gap-6 lg:grid-cols-12">
                    <div className="flex flex-col gap-3 lg:col-span-7">
                        <div className="flex flex-wrap items-center gap-2">
                            {(exam.package_code || exam.package) && (
                                <span className="rounded bg-primary px-2.5 py-0.5 font-label text-[10px] font-bold uppercase tracking-[0.08em] text-on-primary">
                                    {exam.package_code || exam.package}
                                </span>
                            )}
                            <span className="font-label text-[11px] font-medium text-secondary">
                                {exam.exam_type || 'Paket ujian sekolah'}
                            </span>
                        </div>

                        <h2 className="font-headline text-[1.75rem] font-extrabold leading-tight tracking-tight text-on-surface sm:text-[2rem]">
                            {exam.subject || 'Mata Pelajaran'}
                        </h2>

                        <div className="grid grid-cols-2 gap-3 pt-1 sm:grid-cols-3">
                            <InfoTile label="Durasi Ujian" value={duration} />
                            <InfoTile label="Sesi Ujian" value={exam.session || '—'} />
                            <InfoTile
                                label="Ruang Ujian"
                                value={exam.room || '—'}
                                className="col-span-2 sm:col-span-1"
                            />
                        </div>

                        <p className="mt-1 flex items-start gap-2 font-body text-[11px] leading-relaxed text-on-surface-variant">
                            <span className="material-symbols-outlined text-[18px] text-tertiary">
                                security
                            </span>
                            {helperText}
                        </p>
                    </div>

                    <TokenPanel status={status} />
                </div>
            </div>
        </section>
    );
}
