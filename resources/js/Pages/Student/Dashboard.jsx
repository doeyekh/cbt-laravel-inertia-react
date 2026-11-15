import React from 'react';
import StudentLayout from '@/Layouts/StudentLayout';
import ExamAgenda from './Components/ExamAgenda';
import ParticipantIdentity from './Components/ParticipantIdentity';

const PRE_EXAM_NOTES = [
    'Bawa Kartu Peserta Ujian dan dokumen identitas yang diminta panitia.',
    'Hadir di ruang ujian 15 menit sebelum sesi dimulai.',
    'Laporkan kendala teknis kepada pengawas ruangan, jangan berpindah tab.',
];

export default function StudentDashboard({ participant = {}, exams = [], systemInfo = {} }) {
    const firstName = participant.name?.split(' ')[0] ?? 'Siswa';

    const intro = participant.academic_year
        ? `Tahun pelajaran ${participant.academic_year}. Tinjau agenda ujian dan pastikan sesi, ruang, serta identitas peserta Anda sudah sesuai.`
        : 'Tinjau agenda ujian dan pastikan sesi, ruang, serta identitas peserta Anda sudah sesuai.';

    return (
        <StudentLayout {...systemInfo}>
            <section className="max-w-3xl space-y-6 pb-12 pt-4 lg:pb-16">
                <div className="flex items-center gap-3">
                    <span className="h-1.5 w-1.5 rounded-full bg-tertiary" />
                    <p className="font-label text-[11px] font-semibold uppercase tracking-[0.22em] text-tertiary">
                        Portal Siswa
                    </p>
                </div>

                <h1 className="font-headline text-[2.5rem] font-bold leading-[1.15] tracking-tight text-on-surface sm:text-5xl">
                    Selamat datang, <em className="italic">{firstName}</em>.
                </h1>

                <p className="max-w-xl text-[15px] leading-[1.9] text-on-surface-variant">
                    {intro}
                </p>
            </section>

            <div className="grid gap-12 lg:grid-cols-[1.6fr_1fr] lg:items-start lg:gap-16">
                <ExamAgenda exams={exams} />

                <aside className="space-y-6">
                    <ParticipantIdentity participant={participant} />

                    <section className="rounded-3xl bg-surface-container-lowest p-7 sm:p-8">
                        <p className="font-label text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">
                            Sebelum Ujian
                        </p>

                        <ol className="mt-5 space-y-5">
                            {PRE_EXAM_NOTES.map((note, index) => (
                                <li
                                    key={note}
                                    className="grid grid-cols-[1.5rem_1fr] gap-x-1"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="font-label text-[10px] font-semibold tracking-[0.1em] text-tertiary"
                                    >
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    <p className="text-[12.5px] leading-relaxed text-on-surface-variant">
                                        {note}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>
                </aside>
            </div>
        </StudentLayout>
    );
}
