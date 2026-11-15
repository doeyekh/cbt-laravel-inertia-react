import React, { useEffect, useState } from 'react';
import {
    formatExamDate,
    getExamDateParts,
    getExamStatus,
    getExamSummary,
} from '@/Utils/examSchedule';

const STATUS_BADGES = {
    ongoing: {
        label: 'Berlangsung',
        className: 'bg-primary/10 text-primary',
    },
    upcoming: {
        label: 'Akan datang',
        className: 'bg-tertiary-fixed/40 text-tertiary',
    },
    finished: {
        label: 'Selesai',
        className: 'bg-surface-container text-on-surface-variant',
    },
    unknown: {
        label: 'Terjadwal',
        className: 'bg-surface-container text-on-surface-variant',
    },
};

const SUMMARY_ITEMS = [
    { key: 'total', label: 'Total Ujian', accent: 'text-on-surface' },
    { key: 'ongoing', label: 'Berlangsung', accent: 'text-primary' },
    { key: 'upcoming', label: 'Akan Datang', accent: 'text-tertiary' },
    { key: 'finished', label: 'Selesai', accent: 'text-on-surface-variant' },
];

function EmptyState() {
    return (
        <div className="rounded-3xl bg-surface-container-lowest px-8 py-16 text-center">
            <span className="material-symbols-outlined text-[32px] text-on-surface-variant/50">
                event_busy
            </span>
            <h3 className="mt-3 font-headline text-xl font-bold tracking-tight text-on-surface">
                Belum ada jadwal ujian
            </h3>
            <p className="mx-auto mt-2 max-w-sm text-[13px] leading-relaxed text-on-surface-variant">
                Jadwal akan tampil setelah panitia menerbitkan paket dan jadwal ujian untuk
                kelas Anda.
            </p>
        </div>
    );
}

function ExamCard({ exam, status }) {
    const badge = STATUS_BADGES[status];
    const date = getExamDateParts(exam.date);
    const examLabel = [exam.exam_type, exam.package].filter(Boolean).join(' · ');

    return (
        <li className="rounded-2xl bg-surface-container-lowest p-5 sm:p-6">
            <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
                <div className="flex h-20 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-surface-container-low">
                    <span className="font-headline text-xl font-bold leading-none text-on-surface">
                        {date.day}
                    </span>
                    <span className="mt-1.5 font-label text-[10px] font-semibold uppercase tracking-[0.12em] text-on-surface-variant">
                        {date.month}
                    </span>
                </div>

                <div className="min-w-0 flex-1 space-y-3">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h3 className="font-headline text-lg font-semibold leading-snug tracking-tight text-on-surface">
                                {exam.subject || 'Mata Pelajaran'}
                            </h3>
                            <p className="mt-1 font-label text-[10px] uppercase tracking-[0.1em] text-on-surface-variant">
                                {examLabel || 'Paket ujian'}
                            </p>
                        </div>

                        <span
                            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-label text-[10px] font-semibold uppercase tracking-[0.08em] ${badge.className}`}
                        >
                            {status === 'ongoing' && (
                                <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                            )}
                            {badge.label}
                        </span>
                    </div>

                    <p className="text-[13px] text-on-surface-variant">
                        <span className="font-semibold text-on-surface">
                            {exam.open_time} – {exam.close_time}
                        </span>
                        {exam.duration_minutes ? ` · ${exam.duration_minutes} menit` : ''}
                    </p>

                    <div className="flex flex-wrap gap-x-5 gap-y-1.5 font-label text-[11px] text-on-surface-variant">
                        {exam.session && <span>Sesi: {exam.session}</span>}
                        {exam.room && <span>Ruang: {exam.room}</span>}
                        <span>{formatExamDate(exam.date)}</span>
                    </div>
                </div>
            </div>
        </li>
    );
}

export default function ExamAgenda({ exams = [] }) {
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), 60_000);

        return () => clearInterval(timer);
    }, []);

    const summary = getExamSummary(exams, now);

    return (
        <section className="space-y-7">
            <div className="flex items-end justify-between gap-4">
                <div className="space-y-1">
                    <p className="font-label text-[10px] font-semibold uppercase tracking-[0.2em] text-tertiary">
                        Agenda
                    </p>
                    <h2 className="font-headline text-2xl font-bold tracking-tight text-on-surface">
                        Jadwal Ujian
                    </h2>
                </div>

                <p className="font-label text-[11px] text-on-surface-variant">
                    {summary.total} ujian terjadwal
                </p>
            </div>

            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                {SUMMARY_ITEMS.map((item) => (
                    <div key={item.key} className="rounded-2xl bg-surface-container-lowest p-5">
                        <p className="font-label text-[10px] font-semibold uppercase tracking-[0.12em] text-on-surface-variant">
                            {item.label}
                        </p>
                        <p className={`mt-3 font-headline text-2xl font-bold ${item.accent}`}>
                            {summary[item.key]}
                        </p>
                    </div>
                ))}
            </div>

            {exams.length === 0 ? (
                <EmptyState />
            ) : (
                <ul className="space-y-4">
                    {exams.map((exam) => (
                        <ExamCard
                            key={exam.id}
                            exam={exam}
                            status={getExamStatus(exam, now)}
                        />
                    ))}
                </ul>
            )}
        </section>
    );
}
