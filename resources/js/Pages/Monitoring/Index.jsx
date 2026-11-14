import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import TopBar from '@/Features/Monitoring/Components/TopBar.jsx';
import KPICard from '@/Features/Monitoring/Components/KPICard.jsx';
import FilterBar from '@/Features/Monitoring/Components/FilterBar.jsx';
import StudentTable from '@/Features/Monitoring/Components/StudentTable.jsx';
import DistributionChart from '@/Features/Monitoring/Components/DistributionChart.jsx';
import ActivityFeed from '@/Features/Monitoring/Components/ActivityFeed.jsx';

export default function MonitoringIndex() {
    const [searchQuery, setSearchQuery] = useState('');

    const telemetry = {
        cluster: 'Cluster CBT-Node 01',
        lat: '14ms',
        cpu: '28%',
        ram: '4.2 / 16 GB',
    };

    const actions = [
        {
            icon: 'sync',
            label: 'Refresh Live',
            variant: 'secondary',
            onClick: () => {},
        },
        {
            icon: 'warning',
            label: 'Mulai Sesi Darurat',
            variant: 'primary',
            onClick: () => {},
        },
    ];

    const kpis = [
        {
            title: 'Sesi Sedang Berlangsung',
            value: '03',
            subtitle: 'Paket Ujian Aktif',
            icon: 'layers',
            iconVariant: 'primary',
            trend: {
                label: 'Sinkronisasi Naskah 100%',
                icon: 'check_circle',
                className: 'text-primary',
            },
        },
        {
            title: 'Tingkat Kehadiran Siswa',
            value: '418',
            subtitle: '/ 430 Terdaftar',
            icon: 'how_to_reg',
            iconVariant: 'tertiary',
            children: (
                <div className="w-full bg-surface-container h-1.5 rounded-full overflow-hidden mt-3">
                    <div className="bg-primary h-full rounded-full" style={{ width: '97.2%' }} />
                </div>
            ),
            trend: {
                label: '8 Belum Login',
                icon: 'check_circle',
                className: 'text-on-surface-variant',
                secondary: '4 Selesai Cepat',
                secondaryClass: 'text-on-surface font-semibold',
            },
        },
        {
            title: 'Insiden Integritas',
            value: '07',
            subtitle: 'Anomali Terdeteksi',
            icon: 'gavel',
            iconVariant: 'error',
            children: (
                <div className="flex items-center gap-2 mt-2">
                    <span className="px-2 py-0.5 rounded-md bg-error-container text-on-error-container text-[11px] font-label font-bold">
                        5 Pindah Tab
                    </span>
                    <span className="px-2 py-0.5 rounded-md bg-tertiary-container/40 text-on-tertiary-container text-[11px] font-label font-bold">
                        2 Multi-Wajah
                    </span>
                </div>
            ),
            trend: {
                label: 'Tinjau Rekaman AI',
                icon: 'visibility',
                className: 'text-error font-medium underline cursor-pointer',
            },
        },
        {
            title: 'Sisa Durasi Sesi 01',
            value: '42',
            subtitle: 'Menit Tersisa',
            icon: 'hourglass_top',
            iconVariant: 'surface',
            children: (
                <div className="flex items-center gap-2 mt-2.5">
                    <span className="text-[11px] font-label text-on-surface-variant uppercase">
                        Token Aktif:
                    </span>
                    <span className="px-2 py-0.5 rounded-lg bg-surface-container-high text-on-surface font-mono font-bold text-xs tracking-wider">
                        KXZ-994
                    </span>
                </div>
            ),
            trend: {
                label: 'Otomatis refresh 13m lagi',
                icon: 'update',
                className: 'text-on-surface-variant',
            },
        },
    ];

    const handleSearch = (query) => {
        setSearchQuery(query);
    };

    return (
        <div className="space-y-8 max-w-[1720px] mx-auto w-full">
            <TopBar
                breadcrumb={
                    <>
                        <span>Sistem Evaluasi Terpadu</span>
                        <span>/</span>
                        <span className="text-primary font-semibold">Monitoring Real-time</span>
                    </>
                }
                title="Pusat Kendali Pengawasan Ujian"
                telemetry={telemetry}
                actions={actions}
            />

            {/* 4 KPI Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                {kpis.map((kpi, idx) => (
                    <KPICard key={idx} {...kpi} />
                ))}
            </div>

            {/* Main Content Grid: 70% Table + 30% Sidebar */}
            <div className="grid grid-cols-1 xl:grid-cols-12 gap-8">
                {/* Left Panel: Live Monitoring Data Table (xl:col-span-8) */}
                <div className="xl:col-span-8 flex flex-col gap-5">
                    <FilterBar onSearchChange={handleSearch} />
                    <StudentTable />
                </div>

                {/* Right Panel: Distribution Chart & Live Event Feed (xl:col-span-4) */}
                <div className="xl:col-span-4 flex flex-col gap-6">
                    <DistributionChart />
                    <ActivityFeed />
                </div>
            </div>
        </div>
    );
}
