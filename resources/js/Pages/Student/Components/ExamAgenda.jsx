import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { route } from '@/Utils/route';
import {
    formatCountdown,
    formatExamDate,
    getExamCountdown,
    getExamDateParts,
    getExamStatus,
    isExamToday,
    sortExams,
} from '@/Utils/examSchedule';

const STATUS_BADGES = {
    ongoing: {
        label: 'Sedang Berlangsung',
        className: 'bg-primary-fixed text-on-primary-fixed-variant',
        icon: null,
    },
    upcoming: {
        label: 'Akan datang',
        className: 'bg-secondary-container text-on-secondary-fixed',
        icon: 'hourglass_empty',
    },
    finished: {
        label: 'Selesai Dikerjakan',
        className: 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        icon: 'task_alt',
    },
    unknown: {
        label: 'Terjadwal',
        className: 'bg-surface-container text-secondary',
        icon: 'event',
    },
};

const EMPTY_STATES = {
    today: {
        icon: 'event_available',
        title: 'Tidak ada ujian hari ini',
        description: 'Sesi yang dijadwalkan hari ini akan tampil di sini beserta status tokennya.',
    },
    upcoming: {
        icon: 'event_upcoming',
        title: 'Belum ada jadwal mendatang',
        description: 'Jadwal akan tampil setelah panitia menerbitkan paket ujian untuk kelas Anda.',
    },
    finished: {
        icon: 'history',
        title: 'Belum ada ujian selesai',
        description: 'Ujian yang telah dikerjakan akan tercatat pada tab ini.',
    },
};

function getSessionNumber(exam) {
    const digits = (exam.session || '').replace(/\D/g, '');

    return digits ? digits.slice(-2).padStart(2, '0') : null;
}

function DateBlock({ exam, status, isToday }) {
    if (status === 'finished') {
        return (
            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-tertiary-fixed text-on-tertiary-fixed-variant">
                <span className="material-symbols-outlined text-[24px]">task_alt</span>
            </div>
        );
    }

    if (isToday) {
        const sessionNumber = getSessionNumber(exam);

        return (
            <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-surface-container text-on-surface">
                <span className="font-label text-[9px] font-semibold uppercase leading-none tracking-[0.08em] text-secondary">
                    {sessionNumber ? 'Sesi' : 'Hari'}
                </span>
                <span className="mt-1 font-headline text-base font-bold leading-none">
                    {sessionNumber || getExamDateParts(exam.date).day}
                </span>
            </div>
        );
    }

    const date = getExamDateParts(exam.date);

    return (
        <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-surface-container-low text-on-surface">
            <span className="font-label text-[10px] font-semibold uppercase leading-none text-secondary">
                {date.day}
            </span>
            <span className="mt-1 font-headline text-[13px] font-bold uppercase leading-none">
                {date.month}
            </span>
        </div>
    );
}

function ActionButton({ exam, status }) {
    if (status === 'finished') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-lg bg-tertiary-fixed/70 px-4 py-2 font-label text-[11px] font-semibold text-on-tertiary-fixed-variant">
                <span className="material-symbols-outlined text-[17px]">cloud_done</span>
                Jawaban Tersimpan
            </span>
        );
    }

    if (status === 'ongoing' && exam.token_access_granted) {
        return (
            <Link
                href={route('student.exam', exam.id)}
                className="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 font-label text-[11px] font-semibold text-on-primary transition-opacity hover:opacity-90"
            >
                <span className="material-symbols-outlined text-[17px]">play_arrow</span>
                Lanjutkan Ujian
            </Link>
        );
    }

    if (status === 'ongoing') {
        if (exam.token_available) {
            return (
                <Link
                    href={route('student.exam-access.create', exam.id)}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 font-label text-[11px] font-semibold text-on-primary transition-opacity hover:opacity-90"
                >
                    <span className="material-symbols-outlined text-[17px]">key</span>
                    Kerjakan
                </Link>
            );
        }

        return (
            <button
                type="button"
                disabled
                title="Token paket belum diterbitkan"
                className="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg bg-surface-container px-4 py-2 font-label text-[11px] font-medium text-secondary opacity-80"
            >
                <span className="material-symbols-outlined text-[17px]">key</span>
                Token Belum Rilis
            </button>
        );
    }

    return (
        <button
            type="button"
            disabled
            title="Token belum dirilis proktor"
            className="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg bg-surface-container px-4 py-2 font-label text-[11px] font-medium text-secondary opacity-80"
        >
            <span className="material-symbols-outlined text-[17px]">lock_clock</span>
            Token Belum Rilis
        </button>
    );
}

function ExamRow({ exam, now }) {
    const status = getExamStatus(exam, now);
    const badge = STATUS_BADGES[status] ?? STATUS_BADGES.unknown;
    const countdown = getExamCountdown(exam, now);
    const isToday = isExamToday(exam, now);
    const code = exam.package_code || exam.exam_type || exam.package;

    const statusLabel =
        status === 'upcoming' && countdown?.target === 'start'
            ? `Mulai dlm ${formatCountdown(countdown.milliseconds)}`
            : badge.label;

    return (
        <li className="rounded-xl bg-surface-container-lowest p-4 shadow-sm ring-1 ring-outline-variant/60 transition-shadow hover:shadow-md sm:p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="flex min-w-0 items-start gap-3.5">
                    <DateBlock exam={exam} status={status} isToday={isToday} />

                    <div className="flex min-w-0 flex-col gap-1.5">
                        <div className="flex flex-wrap items-center gap-2">
                            {code && (
                                <span className="rounded bg-surface-container-high px-2 py-0.5 font-label text-[9px] font-semibold uppercase tracking-[0.04em] text-on-surface">
                                    {code}
                                </span>
                            )}
                            <span
                                className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-label text-[9px] font-semibold uppercase tracking-[0.06em] ${badge.className}`}
                            >
                                {status === 'ongoing' && (
                                    <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-primary" />
                                )}
                                {badge.icon && (
                                    <span className="material-symbols-outlined text-[13px]">
                                        {badge.icon}
                                    </span>
                                )}
                                {statusLabel}
                            </span>
                        </div>

                        <h4 className="font-headline text-[15px] font-bold leading-snug text-on-surface">
                            {exam.subject || 'Mata Pelajaran'}
                        </h4>

                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 font-body text-[11px] text-secondary">
                            <span className="flex items-center gap-1">
                                <span className="material-symbols-outlined text-[15px]">
                                    schedule
                                </span>
                                {exam.open_time} – {exam.close_time} WIB
                                {exam.duration_minutes ? ` (${exam.duration_minutes} Menit)` : ''}
                            </span>
                            {exam.session && (
                                <>
                                    <span className="text-outline">•</span>
                                    <span>{exam.session}</span>
                                </>
                            )}
                            {exam.room && (
                                <>
                                    <span className="text-outline">•</span>
                                    <span>{exam.room}</span>
                                </>
                            )}
                            <span className="text-outline">•</span>
                            <span>{formatExamDate(exam.date)}</span>
                        </div>
                    </div>
                </div>

                <div className="flex shrink-0 items-center md:self-center">
                    <ActionButton exam={exam} status={status} />
                </div>
            </div>
        </li>
    );
}

function EmptyState({ tab }) {
    const empty = EMPTY_STATES[tab];

    return (
        <div className="rounded-xl bg-surface-container-lowest px-6 py-10 text-center shadow-sm ring-1 ring-outline-variant/60">
            <span className="material-symbols-outlined text-[28px] text-on-surface-variant/50">
                {empty.icon}
            </span>
            <h4 className="mt-2 font-headline text-base font-bold text-on-surface">{empty.title}</h4>
            <p className="mx-auto mt-1 max-w-sm font-body text-[12px] leading-relaxed text-on-surface-variant">
                {empty.description}
            </p>
        </div>
    );
}

export default function ExamAgenda({ exams = [], now = new Date() }) {
    const sortedExams = sortExams(exams);
    const todayExams = sortedExams.filter((exam) => isExamToday(exam, now));
    const upcomingExams = sortedExams.filter((exam) => getExamStatus(exam, now) === 'upcoming');
    const finishedExams = [...sortedExams]
        .reverse()
        .filter((exam) => getExamStatus(exam, now) === 'finished');

    const tabs = [
        {
            key: 'today',
            label: `Hari Ini (${now.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })})`,
            exams: todayExams,
        },
        { key: 'upcoming', label: 'Jadwal Mendatang', exams: upcomingExams },
        { key: 'finished', label: 'Selesai', exams: finishedExams },
    ];

    const [activeTab, setActiveTab] = useState(() => {
        if (todayExams.length > 0) {
            return 'today';
        }

        if (upcomingExams.length > 0) {
            return 'upcoming';
        }

        return finishedExams.length > 0 ? 'finished' : 'today';
    });

    const activeExams = tabs.find((tab) => tab.key === activeTab)?.exams ?? [];

    return (
        <section className="flex flex-col gap-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="font-headline text-lg font-bold tracking-tight text-on-surface">
                        Jadwal &amp; Agenda Ujian Siswa
                    </h3>
                    <p className="font-body text-[11px] text-secondary">
                        Kalender asesmen terintegrasi dengan penjadwalan pusat proktor.
                    </p>
                </div>

                <div className="flex items-center gap-1 self-start rounded-lg bg-surface-container p-1 sm:self-auto">
                    {tabs.map((tab) => (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => setActiveTab(tab.key)}
                            aria-pressed={activeTab === tab.key}
                            className={`whitespace-nowrap rounded-lg px-3 py-1.5 font-label text-[10px] transition-all ${
                                activeTab === tab.key
                                    ? 'bg-surface-container-lowest font-bold text-primary shadow-sm'
                                    : 'font-medium text-secondary hover:text-on-surface'
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>
            </div>

            {activeExams.length === 0 ? (
                <EmptyState tab={activeTab} />
            ) : (
                <ul className="flex flex-col gap-3">
                    {activeExams.map((exam) => (
                        <ExamRow key={exam.id} exam={exam} now={now} />
                    ))}
                </ul>
            )}
        </section>
    );
}
