import React, { useState } from 'react';
import { usePoll } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FilterBar from '@/Features/Monitoring/Components/FilterBar.jsx';
import StudentTable from '@/Features/Monitoring/Components/StudentTable.jsx';

export default function MonitoringIndex({ classes = [], participants = [] }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedClassId, setSelectedClassId] = useState('');
    usePoll(10000, { only: ['participants'] });

    const handleSearch = (query) => {
        setSearchQuery(query);
    };

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1720px] space-y-6">
                <section className="relative isolate overflow-hidden rounded-[28px] bg-primary px-6 py-7 text-on-primary shadow-sm sm:px-8 sm:py-9">
                    <div
                        className="absolute -right-16 -top-24 -z-10 h-72 w-72 rounded-full bg-white/10 blur-3xl"
                        aria-hidden="true"
                    />
                    <div
                        className="absolute -bottom-32 right-1/3 -z-10 h-64 w-64 rounded-full bg-white/5 blur-3xl"
                        aria-hidden="true"
                    />

                    <div className="flex flex-col justify-between gap-8 xl:flex-row xl:items-end">
                        <div className="max-w-3xl space-y-4">
                            <div className="flex flex-wrap items-center gap-2 text-xs font-label font-semibold uppercase tracking-[0.18em] text-on-primary/70">
                                <span>Sistem Evaluasi Terpadu</span>
                                <span aria-hidden="true">/</span>
                                <span className="text-on-primary">Monitoring Ujian</span>
                            </div>
                            <div className="space-y-2">
                                <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                                    Pantau ujian, tetap terkendali.
                                </h1>
                                <p className="max-w-2xl text-sm leading-relaxed text-on-primary/75 sm:text-base">
                                    Satu ruang untuk melihat kehadiran, progres pengerjaan, kondisi koneksi, dan
                                    aktivitas peserta selama ujian berlangsung.
                                </p>
                            </div>
                        </div>

                        <div className="flex shrink-0 items-center gap-3 self-start rounded-2xl border border-white/15 bg-white/10 px-4 py-3 xl:self-auto">
                            <span className="relative flex h-2.5 w-2.5 shrink-0" aria-hidden="true">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/70" />
                                <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-white" />
                            </span>
                            <div>
                                <p className="text-xs font-semibold">Sesi ujian berlangsung</p>
                                <p className="mt-0.5 text-[11px] text-on-primary/70">
                                    Ringkasan pemantauan peserta
                                </p>
                            </div>
                            <span className="material-symbols-outlined ml-2 text-[20px] text-on-primary/80" aria-hidden="true">
                                monitoring
                            </span>
                        </div>
                    </div>
                </section>

                <section aria-labelledby="participants-heading" className="flex min-w-0 flex-col gap-4">
                    <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                        <div>
                            <p className="text-xs font-label font-semibold uppercase tracking-widest text-primary">
                                Pengawasan peserta
                            </p>
                            <h2 id="participants-heading" className="mt-1 font-headline text-xl font-bold tracking-tight text-on-surface">
                                Aktivitas siswa
                            </h2>
                        </div>
                        <p className="max-w-md text-xs leading-relaxed text-on-surface-variant sm:text-right">
                            Cari peserta dan tinjau progres serta status koneksi dari daftar ujian.
                        </p>
                    </div>
                    <FilterBar
                        classes={classes}
                        selectedClassId={selectedClassId}
                        onClassChange={setSelectedClassId}
                        onSearchChange={handleSearch}
                    />
                    <StudentTable
                        students={participants}
                        searchQuery={searchQuery}
                        classFilter={selectedClassId}
                    />
                </section>
            </div>
        </AppLayout>
    );
}
