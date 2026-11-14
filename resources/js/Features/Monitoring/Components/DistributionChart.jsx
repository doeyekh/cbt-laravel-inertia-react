import React from 'react';

export default function DistributionChart() {
    return (
        <div className="bg-surface-container-lowest p-6 rounded-2xl shadow-sm flex flex-col gap-5">
            <div className="flex items-center justify-between">
                <h3 className="font-headline font-semibold text-base text-on-surface">
                    Distribusi Kemajuan Soal
                </h3>
                <span className="text-[11px] font-label text-on-surface-variant uppercase tracking-wider">
                    Per Menit-42
                </span>
            </div>

            <div className="flex items-center justify-between gap-4">
                <div className="relative w-36 h-36 flex items-center justify-center">
                    <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle
                            className="text-surface-container"
                            cx="50"
                            cy="50"
                            fill="none"
                            r="38"
                            stroke="currentColor"
                            strokeWidth="12"
                        />
                        <circle
                            className="text-primary"
                            cx="50"
                            cy="50"
                            fill="none"
                            r="38"
                            stroke="currentColor"
                            strokeDasharray="238.76"
                            strokeDashoffset="71.6"
                            strokeLinecap="round"
                            strokeWidth="12"
                        />
                        <circle
                            className="text-primary-container"
                            cx="50"
                            cy="50"
                            fill="none"
                            r="38"
                            stroke="currentColor"
                            strokeDasharray="238.76"
                            strokeDashoffset="155.19"
                            strokeLinecap="round"
                            strokeWidth="12"
                        />
                        <circle
                            className="text-tertiary-container"
                            cx="50"
                            cy="50"
                            fill="none"
                            r="38"
                            stroke="currentColor"
                            strokeDasharray="238.76"
                            strokeDashoffset="214.88"
                            strokeLinecap="round"
                            strokeWidth="12"
                        />
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span className="font-headline text-2xl font-bold text-on-surface leading-none">
                            418
                        </span>
                        <span className="text-[10px] font-label text-on-surface-variant mt-0.5">
                            Siswa Sedang Ujian
                        </span>
                    </div>
                </div>

                <div className="flex flex-col gap-2.5 flex-1 text-xs font-label">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-primary" />
                            <span className="text-on-surface-variant">&gt; 75% Selesai</span>
                        </div>
                        <span className="font-bold text-on-surface font-mono">294</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-primary-container" />
                            <span className="text-on-surface-variant">40% - 75%</span>
                        </div>
                        <span className="font-bold text-on-surface font-mono">88</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-tertiary-container" />
                            <span className="text-on-surface-variant">&lt; 40% Lambat</span>
                        </div>
                        <span className="font-bold text-on-surface font-mono">36</span>
                    </div>
                </div>
            </div>

            <div className="bg-surface-container-low p-3.5 rounded-xl text-[11px] font-body text-on-surface-variant leading-relaxed">
                Estimasi waktu selesai rata-rata kelas diprediksi{' '}
                <strong className="text-on-surface font-medium">18 menit sebelum bel</strong> tanda ujian berakhir.
            </div>
        </div>
    );
}
