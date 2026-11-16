import React from 'react';
import { usePage, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function ExamStart({ exam }) {
    const { now } = usePage().props.now || { now: Date.now() };

    // Store verified exam package in session storage for later use
    useEffect(() => {
        const verifiedPackages = JSON.parse(
            sessionStorage.getItem('student_exam_packages') || '{}'
        );
        verifiedPackages[exam.id] = exam.exam_access_token_hash;
        sessionStorage.setItem(
            'student_exam_packages',
            JSON.stringify(verifiedPackages)
        );
    }, [exam]);

    return (
        <div className="min-h-screen flex items-center justify-center p-4">
            <div className="rounded-xl bg-surface-container-lowest p-8 shadow-sm text-center max-w-md mx-auto">
                <div className="flex items-center justify-center mb-8">
                    <span className="material-symbols-outlined text-5xl text-primary">verified</span>
                </div>

                <h1 className="font-headline text-2xl font-bold text-on-surface mb-2">
                    Token Terverifikasi
                </h1>

                <p className="font-body text-[13px] leading-relaxed text-on-surface-variant mb-8">
                    Token ujian Anda telah diverifikasi successfully. Anda dapat mulai
                    mengerjakan soal di bawah ini.
                </p>

                <div className="bg-slate-50 p-4 rounded-lg mb-6">
                    <h2 className="font-semibold text-on-surface mb-2">Detail Ujian</h2>
                    <ul className="space-y-2 text-left text-sm text-on-surface-variant">
                        <li>
                            <span className="font-medium">Mata Pelajaran:</span>
                            <span>{exam.subject || '-'}</span>
                        </li>
                        <li>
                            <span className="font-medium">Kelas:</span>
                            <span>{exam.schoolClass?.name || '-'}</span>
                        </li>
                        <li>
                            <span className="font-medium">Tanggal:</span>
                            <span>{exam.exam_date?.format('d M Y') || '-'}</span>
                        </li>
                        <li>
                            <span className="font-medium">Waktu Pengerjaan:</span>
                            <span>{exam.duration_minutes} menit</span>
                        </li>
                    </ul>
                </div>

                <div className="space-y-4">
                    {/* Tombol Mulai Ujian */}
                    <Link
                        href={route('student.exam-access.create', exam.id)}
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 font-bold text-white text-lg hover:bg-blue-700 transition-shadow shadow-md mb-4">
                        <span className="material-symbols-outlined text-[18px]">play_arrow</span>
                        Mulai Ujian
                    </Link>

                    {/* Atau langsung ke dashboard */}
                    <p className="text-sm text-on-surface-variant">
                        <Link
                            href={route('student.dashboard')}
                            className="text-primary hover:text-primary-container">
                            Kembali ke Dashboard
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}