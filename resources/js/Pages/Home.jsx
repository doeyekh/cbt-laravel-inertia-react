import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { router, usePage } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';

function Home({ dashboardStats = {}, upcomingSchedules = [] }) {
    const { props } = usePage();
    const user = props.auth?.user;
    const isRestrictedGuru = (user?.is_guru ?? (user?.role === 'guru'))
        && ! user?.is_school_admin
        && ! user?.is_headmaster;

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

    const visibleQuickActions = isRestrictedGuru
        ? quickActions.filter((action) => action.title === 'Laporan & Analitik')
        : quickActions;

    const stats = [
        {
            label: 'Peserta Terdaftar',
            value: dashboardStats.participant_count ?? 0,
            icon: 'groups',
            iconClass: 'bg-primary/10 text-primary',
        },
        {
            label: 'Ruang Ujian Aktif',
            value: dashboardStats.active_room_count ?? 0,
            icon: 'meeting_room',
            iconClass: 'bg-tertiary-container/30 text-tertiary',
        },
        {
            label: 'Jadwal Hari Ini',
            value: dashboardStats.today_schedule_count ?? 0,
            icon: 'calendar_month',
            iconClass: 'bg-surface-container text-on-surface',
        },
        {
            label: 'Peserta Sedang Ujian',
            value: dashboardStats.active_attempt_count ?? 0,
            icon: 'edit_document',
            iconClass: 'bg-primary/10 text-primary',
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
                        {!isRestrictedGuru && (
                            <Button
                                variant="primary"
                                onClick={() => router.visit(route('admin.monitoring.index'))}
                            >
                                <span className="material-symbols-outlined text-[18px]">monitoring</span>
                                Buka Monitoring
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            onClick={() => router.visit(route('admin.reports.index'))}
                        >
                            <span className="material-symbols-outlined text-[18px]">assessment</span>
                            Lihat Laporan
                        </Button>
                    </div>
                </div>

                <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Statistik dashboard">
                    {stats.map((stat) => (
                        <Card key={stat.label} padding="p-5">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <p className="text-sm font-label text-on-surface-variant">{stat.label}</p>
                                    <p className="mt-2 text-3xl font-bold tracking-tight text-on-surface">
                                        {stat.value.toLocaleString('id-ID')}
                                    </p>
                                </div>
                                <span className={`material-symbols-outlined rounded-xl p-3 text-[22px] ${stat.iconClass}`} aria-hidden="true">
                                    {stat.icon}
                                </span>
                            </div>
                        </Card>
                    ))}
                </section>

                {/* Quick Action Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {visibleQuickActions.map((action) => (
                        <Card key={action.title} padding="p-6">
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

                {/* Jadwal terdekat */}
                <Card padding="p-6">
                    <div className="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Jadwal Ujian Terdekat
                            </h3>
                            <p className="mt-1 text-sm text-on-surface-variant">
                                Jadwal aktif hari ini dan yang akan datang.
                            </p>
                        </div>
                        {!isRestrictedGuru && (
                            <Button variant="outline" onClick={() => router.visit(route('admin.exam-packages.index'))}>
                                Kelola Jadwal
                            </Button>
                        )}
                    </div>
                    {upcomingSchedules.length > 0 ? (
                        <div className="divide-y divide-surface-container-high">
                            {upcomingSchedules.map((schedule) => (
                                <div key={schedule.id} className="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold text-on-surface">{schedule.package}</p>
                                            {schedule.is_ongoing && <Badge variant="success">Berlangsung</Badge>}
                                        </div>
                                        <p className="mt-1 text-sm text-on-surface-variant">
                                            {schedule.subject} · {schedule.class}
                                        </p>
                                    </div>
                                    <div className="text-sm text-on-surface-variant sm:text-right">
                                        <p>{schedule.exam_date}</p>
                                        <p className="mt-1">{schedule.open_time}–{schedule.close_time}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-2xl bg-surface-container-low px-5 py-8 text-center">
                            <span className="material-symbols-outlined text-3xl text-on-surface-variant" aria-hidden="true">
                                event_busy
                            </span>
                            <p className="mt-2 text-sm text-on-surface-variant">
                                Belum ada jadwal ujian aktif hari ini atau mendatang.
                            </p>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}

export default Home;
