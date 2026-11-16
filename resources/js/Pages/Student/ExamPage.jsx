import React from 'react';
import { usePage, Link } from '@inertiajs/react';

export default function ExamPage({ exam }) {
    const { now } = usePage().props.now || { now: Date.now() };
    const verifiedPackages = JSON.parse(
        sessionStorage.getItem('student_exam_packages') || '{}'
    );
    const isTokenVerified = verifiedPackages[exam.id] === exam.exam_access_token_hash;

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
                    <div className="bg-slate-50 p-4 rounded-lg">
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
                                <span className="font-medium">Waktu:</span>
                                <span>{exam.duration_minutes} menit</span>
                            </li>
                            <li>
                                <span className="font-medium">Ruangan:</span>
                                <span>{exam.room?.name || '-'}</span>
                            </li>
                        </ul>
                    </div>

                    {isTokenVerified ? (
                        <div>
                            <h2 className="font-semibold text-on-surface mb-2">✅ Token Sudah Diverifikasi</h2>
                            <p className="font-body text-[13px] leading-relaxed text-emerald-700">
                                Token ujian telah diverifikasi successfully. Anda dapat mulai
                                mengerjakan soal di bawah ini.
                            </p>
                            <Link
                                href="javascript:void(0)"
                                onClick={() => {
                                    // Navigate to the question page or show exam interface
                                    // For now, alert that the exam is ready
                                    alert('Ujian siap dikerjakan! Silakan kerjakan soal.');
                                }}
                                className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-3 font-bold text-white text-lg hover:bg-emerald-700 transition-shadow shadow-md">
                                <span className="material-symbols-outlined text-[18px]">remove_circle</span>
                                Mulai Ujian
                            </Link>
                        </div>
                    ) : (
                        <div>
                            <h2 className="font-semibold text-on-surface mb-2">Masukkan Token Ujian</h2>
                            <p className="font-body text-[13px] leading-relaxed text-on-surface-variant">
                                Masukkan token 6 karakter yang diberikan proktor untuk membuka
                                akses ujian.
                            </p>
                            <Link
                                href={route('student.exam-access.create', exam.id)}
                                className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 font-bold text-white text-lg hover:bg-blue-700 transition-shadow shadow-md">
                                <span className="material-symbols-outlined text-[18px]">input</span>
                                Masukkan Token
                            </Link>
                        </div>
                    )}
                </div>
            </section>
        </div>
    );
}