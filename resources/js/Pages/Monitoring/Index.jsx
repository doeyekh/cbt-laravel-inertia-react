import React, { useState } from 'react';
import { usePoll } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FilterBar from '@/Features/Monitoring/Components/FilterBar.jsx';
import StudentTable from '@/Features/Monitoring/Components/StudentTable.jsx';
import CardHeaderPage from '@/Components/Layout/CardHeaderPage';

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
                <CardHeaderPage
                    subTitle="Monitoring Ujian"
                    title="Pantau ujian, tetap terkendali."
                    description="Satu ruang untuk melihat kehadiran, progres pengerjaan, kondisi koneksi, dan aktivitas peserta selama ujian berlangsung."
                >
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
                </CardHeaderPage>

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
