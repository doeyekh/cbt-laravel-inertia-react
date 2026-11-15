import React from 'react';
import StudentLayout from '@/Layouts/StudentLayout';
import { useClock } from '@/Hooks/useClock';
import DeviceReadiness from './Components/DeviceReadiness';
import ExamAgenda from './Components/ExamAgenda';
import ExamGuidance from './Components/ExamGuidance';
import ExamHero from './Components/ExamHero';
import ParticipantIdentity from './Components/ParticipantIdentity';

export default function StudentDashboard({ participant = {}, exams = [], systemInfo = {} }) {
    const { now } = useClock();
    const academicLabel =
        [
            participant.semester ? `Semester ${participant.semester}` : null,
            participant.academic_year,
        ]
            .filter(Boolean)
            .join(' ') || 'Tahun pelajaran aktif';

    return (
        <StudentLayout participant={participant} {...systemInfo}>
            <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2 font-label text-[9px] font-bold uppercase tracking-[0.12em]">
                        <span className="text-primary">Portal Siswa CBT</span>
                        <span className="text-outline">•</span>
                        <span className="font-medium text-secondary">{academicLabel}</span>
                    </div>

                    <h1 className="mt-2 font-headline text-[1.85rem] font-extrabold leading-tight tracking-tight text-on-surface sm:text-[2.1rem]">
                        Selamat Datang, {participant.name || 'Peserta Ujian'}!
                    </h1>

                    <p className="mt-1 max-w-xl font-body text-[13px] leading-relaxed text-on-surface-variant">
                        Pastikan kesiapan koneksi dan token telah valid sebelum memasuki ruang
                        asesmen digital.
                    </p>
                </div>

                <ParticipantIdentity participant={participant} />
            </div>

            <div className="mt-6">
                <ExamHero exams={exams} now={now} />
            </div>

            <div className="mt-7 grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div className="lg:col-span-8">
                    <ExamAgenda exams={exams} now={now} />
                </div>

                <aside className="flex flex-col gap-5 lg:col-span-4">
                    <DeviceReadiness />
                    <ExamGuidance />
                </aside>
            </div>
        </StudentLayout>
    );
}
