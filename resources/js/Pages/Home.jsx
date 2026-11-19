import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { router } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';

function Home() {
    const quickActions = [
        {
            title: 'Monitoring Ujian',
            description:
                'Pantau progres dan integritas siswa saat ujian berlangsung secara real-time.',
            icon: 'monitoring',
            iconVariant: 'primary',
            variant: 'primary',
            href: route('admin.monitoring.index'),
        },
        {
            title: 'Manajemen Siswa',
            description: 'Kelola data peserta ujian, kelas, dan informasi identitas.',
            icon: 'people',
            iconVariant: 'tertiary',
            variant: 'secondary',
            href: route('admin.students.index'),
        },
        {
            title: 'Laporan & Analitik',
            description: 'Lihat laporan hasil ujian dan analitik performa peserta.',
            icon: 'assessment',
            iconVariant: 'neutral',
            variant: 'outline',
            href: route('admin.reports.index'),
        },
    ];

    return (
        <AppLayout>
            <div className="space-y-8 max-w-[1720px] mx-auto w-full">
                {/* Hero Section */}
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Beranda</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Selamat Datang di CBT Panel
                        </h1>
                        <p className="text-lg text-on-surface-variant font-body leading-relaxed">
                            Pusat kendali pengawasan ujian berbasis komputer. Pantau progres siswa,
                            integritas ujian, dan aktivitas real-time dari satu layar.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            variant="primary"
                            onClick={() => router.visit(route('admin.monitoring.index'))}
                        >
                            <span className="material-symbols-outlined text-[18px]">monitoring</span>
                            Buka Monitoring
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => router.visit(route('admin.reports.index'))}
                        >
                            <span className="material-symbols-outlined text-[18px]">assessment</span>
                            Lihat Laporan
                        </Button>
                    </div>
                </div>

                {/* Quick Action Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {quickActions.map((action, idx) => (
                        <Card key={idx} padding="p-6">
                            <div className="flex items-center gap-3 mb-4">
                                <div
                                    className={`w-10 h-10 rounded-xl flex items-center justify-center ${
                                        action.iconVariant === 'primary'
                                            ? 'bg-primary/10 text-primary'
                                            : action.iconVariant === 'tertiary'
                                            ? 'bg-tertiary-container/30 text-tertiary'
                                            : 'bg-surface-container text-on-surface'
                                    }`}
                                >
                                    <span className="material-symbols-outlined text-[20px]">
                                        {action.icon}
                                    </span>
                                </div>
                                <div>
                                    <h3 className="font-headline font-semibold text-on-surface text-lg">
                                        {action.title}
                                    </h3>
                                    <p className="text-sm text-on-surface-variant font-body">
                                        {action.description}
                                    </p>
                                </div>
                            </div>
                            <Button
                                variant={action.variant}
                                onClick={() => router.visit(action.href)}
                                className="w-full"
                            >
                                {action.variant === 'primary'
                                    ? 'Buka Monitoring'
                                    : action.variant === 'secondary'
                                    ? 'Kelola Siswa'
                                    : 'Lihat Laporan'}
                            </Button>
                        </Card>
                    ))}
                </div>

                {/* System Status */}
                <Card padding="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                                Status Sistem
                            </h3>
                            <p className="text-sm text-on-surface-variant font-body">
                                Sistem siap digunakan untuk pengawasan ujian.
                            </p>
                        </div>
                        <div className="flex items-center gap-4">
                            <Badge variant="success">Sistem Online</Badge>
                            <Badge variant="secondary">3 Lab Aktif</Badge>
                            <Badge variant="neutral">430 Siswa Terdaftar</Badge>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

export default Home;
