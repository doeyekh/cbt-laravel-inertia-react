import React, { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useClock } from '@/Hooks/useClock';
import { useToast } from '@/Hooks/useToast';
import { route } from '@/Utils/route';

const NAV_ITEMS = [
    {
        label: 'Beranda & Jadwal Ujian',
        icon: 'dashboard',
        href: route('student.dashboard'),
        active: true,
    },
    {
        label: 'Ruang Ujian Aktif',
        icon: 'assignment',
        badge: 'Segera',
        disabled: true,
    },
    {
        label: 'Riwayat & Nilai Ujian',
        icon: 'history',
        disabled: true,
    },
    {
        label: 'Uji Kesiapan Perangkat',
        icon: 'laptop_mac',
        href: '#kesiapan',
    },
    {
        label: 'Panduan & Tata Tertib',
        icon: 'menu_book',
        href: '#panduan',
    },
    {
        label: 'Bantuan & Pengawas',
        icon: 'support_agent',
        href: '#bantuan',
    },
];

function getInitials(name) {
    const initials = (name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();

    return initials || '?';
}

function Brand({ appName, systemName, compact = false }) {
    return (
        <Link href={route('student.dashboard')} className="flex items-center gap-3">
            <span
                className={`flex items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-container text-on-primary ${
                    compact ? 'h-9 w-9' : 'h-11 w-11'
                }`}
            >
                <span className={`material-symbols-outlined ${compact ? 'text-[19px]' : 'text-[23px]'}`}>
                    school
                </span>
            </span>
            <span className="flex flex-col">
                <span
                    className={`font-headline font-extrabold leading-none tracking-tight text-on-surface ${
                        compact ? 'text-[15px]' : 'text-lg'
                    }`}
                >
                    {appName}
                </span>
                <span className="mt-1 font-label text-[9px] font-medium uppercase tracking-[0.16em] text-on-surface-variant">
                    {systemName}
                </span>
            </span>
        </Link>
    );
}

function NavItems({ items, layout = 'vertical' }) {
    return (
        <>
            {items.map((item) => {
                const sharedClass =
                    'flex items-center gap-2.5 rounded-xl font-label transition-colors';

                const content = (
                    <>
                        <span
                            className={`material-symbols-outlined ${
                                layout === 'vertical' ? 'text-[20px]' : 'text-[18px]'
                            }`}
                        >
                            {item.icon}
                        </span>
                        <span className={layout === 'vertical' ? 'truncate text-[13px]' : 'text-[12px] whitespace-nowrap'}>
                            {item.label}
                        </span>
                        {item.badge && (
                            <span className="ml-auto rounded-full bg-amber-100 px-2 py-0.5 font-label text-[9px] font-semibold uppercase tracking-[0.06em] text-amber-700">
                                {item.badge}
                            </span>
                        )}
                        {item.active && layout === 'vertical' && (
                            <span className="ml-auto h-1.5 w-1.5 rounded-full bg-primary" />
                        )}
                    </>
                );

                const sizing =
                    layout === 'vertical'
                        ? 'w-full px-3 py-2.5'
                        : 'shrink-0 px-3 py-2';

                if (item.disabled) {
                    return (
                        <li key={item.label} className={layout === 'mobile' ? 'shrink-0' : ''}>
                            <span
                                aria-disabled="true"
                                title={`${item.label} belum tersedia`}
                                className={`${sharedClass} ${sizing} cursor-not-allowed text-on-surface-variant/50`}
                            >
                                {content}
                            </span>
                        </li>
                    );
                }

                const className = `${sharedClass} ${sizing} ${
                    item.active
                        ? 'bg-primary-fixed font-semibold text-on-primary-fixed-variant'
                        : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                }`;

                return (
                    <li key={item.label} className={layout === 'mobile' ? 'shrink-0' : ''}>
                        {item.href.startsWith('#') ? (
                            <a href={item.href} className={className}>
                                {content}
                            </a>
                        ) : (
                            <Link
                                href={item.href}
                                aria-current={item.active ? 'page' : undefined}
                                className={className}
                            >
                                {content}
                            </Link>
                        )}
                    </li>
                );
            })}
        </>
    );
}

export default function StudentLayout({
    children,
    participant = {},
    appName = 'EduTest CBT',
    systemName = 'Sistem Evaluasi Terpadu',
    version = 'v1.0.0',
}) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const handledToast = useRef(null);
    const [loggingOut, setLoggingOut] = useState(false);
    const [isOnline, setIsOnline] = useState(() => navigator.onLine);
    const { time, shortDate } = useClock();

    const placement = [participant.room, participant.session].filter(Boolean).join(' • ');

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    useEffect(() => {
        const goOnline = () => setIsOnline(true);
        const goOffline = () => setIsOnline(false);

        window.addEventListener('online', goOnline);
        window.addEventListener('offline', goOffline);

        return () => {
            window.removeEventListener('online', goOnline);
            window.removeEventListener('offline', goOffline);
        };
    }, []);

    const handleLogout = () => {
        if (loggingOut) {
            return;
        }

        setLoggingOut(true);

        router.post(route('student.logout'), {}, {
            onFinish: () => setLoggingOut(false),
        });
    };

    return (
        <div className="student-portal min-h-screen bg-background font-body text-on-surface selection:bg-primary/15 selection:text-primary">
            <div className="flex min-h-screen">
                <aside className="fixed inset-y-0 left-0 z-30 hidden w-[260px] flex-col border-r border-outline-variant/60 bg-surface-container-lowest lg:flex">
                    <div className="px-6 py-6">
                        <Brand appName={appName} systemName={systemName} />
                    </div>

                    <nav className="flex-1 overflow-y-auto px-3 py-2">
                        <p className="px-3 pb-3 font-label text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant">
                            Menu Navigasi
                        </p>
                        <ul className="space-y-1">
                            <NavItems items={NAV_ITEMS} />
                        </ul>
                    </nav>

                    <div className="space-y-4 px-4 pb-5">
                        <div className="rounded-xl bg-surface-container-low p-4">
                            <div className="flex items-center gap-2 text-primary">
                                <span className="material-symbols-outlined text-[20px]">verified_user</span>
                                <span className="font-headline text-[13px] font-bold text-on-surface">
                                    Integritas Ujian
                                </span>
                            </div>
                            <p className="mt-2 font-body text-[11px] leading-relaxed text-on-surface-variant">
                                Safe Exam Browser v4.6
                                <br />
                                <span className="font-semibold text-tertiary">Terverifikasi &amp; Aman</span>
                            </p>
                        </div>

                        <p className="text-center font-label text-[10px] text-on-surface-variant">
                            {appName} Student {version}
                        </p>
                    </div>
                </aside>

                <div className="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-[260px]">
                    <header className="sticky top-0 z-20 border-b border-outline-variant/60 bg-surface-container-lowest/95 backdrop-blur">
                        <div className="flex items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                            <div className="lg:hidden">
                                <Brand appName={appName} systemName={systemName} compact />
                            </div>

                            <div className="hidden items-center gap-3 lg:flex">
                                <span className="inline-flex items-center gap-2 rounded-full bg-tertiary-fixed/60 px-3 py-1.5 font-label text-[10px] font-semibold uppercase tracking-[0.08em] text-on-tertiary-fixed-variant">
                                    <span className="h-1.5 w-1.5 rounded-full bg-tertiary" />
                                    Mode Siswa Aktif
                                </span>

                                <span className="flex items-center gap-1.5 font-label text-[11px] text-on-surface-variant">
                                    <span className="material-symbols-outlined text-[16px]">schedule</span>
                                    <span className="tabular-nums">{time} WIB</span>
                                    <span className="text-outline">•</span>
                                    <span>{shortDate}</span>
                                </span>
                            </div>

                            <div className="ml-auto flex items-center gap-2 sm:gap-3">
                                <span
                                    className={`inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-label text-[10px] font-semibold ${
                                        isOnline
                                            ? 'bg-primary-fixed/60 text-on-primary-fixed-variant'
                                            : 'bg-error-container text-on-error-container'
                                    }`}
                                >
                                    <span className="material-symbols-outlined text-[15px]">
                                        {isOnline ? 'wifi' : 'wifi_off'}
                                    </span>
                                    <span className="hidden sm:inline">
                                        Koneksi: {isOnline ? 'Stabil' : 'Terputus'}
                                    </span>
                                </span>

                                {placement && (
                                    <span className="hidden items-center gap-1.5 rounded-lg bg-primary-fixed/60 px-2.5 py-1.5 font-label text-[10px] font-semibold text-on-primary-fixed-variant sm:inline-flex">
                                        <span className="material-symbols-outlined text-[15px]">computer</span>
                                        {placement}
                                    </span>
                                )}

                                <span className="hidden h-8 w-px bg-outline-variant/60 sm:block" />

                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-on-primary">
                                        {getInitials(participant.name)}
                                    </span>
                                    <div className="hidden text-left md:block">
                                        <p className="max-w-[180px] truncate font-label text-[12px] font-semibold text-on-surface">
                                            {participant.name || 'Peserta Ujian'}
                                        </p>
                                        <p className="font-label text-[10px] text-on-surface-variant">
                                            {[participant.nisn && `NISN: ${participant.nisn}`, participant.class_name]
                                                .filter(Boolean)
                                                .join(' • ') || 'Portal siswa'}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    onClick={handleLogout}
                                    disabled={loggingOut}
                                    title="Keluar dari portal"
                                    aria-label="Keluar dari portal"
                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-surface-container-low text-on-surface-variant transition-colors hover:bg-error-container hover:text-on-error-container disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <span className="material-symbols-outlined text-[18px]">logout</span>
                                </button>
                            </div>
                        </div>

                        <nav className="px-3 pb-2 lg:hidden">
                            <ul className="flex items-center gap-1 overflow-x-auto">
                                <NavItems items={NAV_ITEMS} layout="mobile" />
                            </ul>
                        </nav>
                    </header>

                    <main className="mx-auto w-full max-w-[1440px] flex-1 px-4 pb-12 pt-6 sm:px-6 lg:px-8 lg:pb-16">
                        {children}
                    </main>

                    <footer className="border-t border-outline-variant/60 bg-surface-container-low">
                        <div className="mx-auto flex w-full max-w-[1440px] flex-col gap-2 px-4 py-4 font-label text-[11px] text-on-surface-variant sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                            <p className="flex items-center gap-2">
                                <span className="material-symbols-outlined text-[16px] text-tertiary">
                                    cloud_done
                                </span>
                                Status Server: Terkoneksi Sinkron &amp; Terenkripsi
                            </p>

                            <p>
                                © {new Date().getFullYear()} {appName} — {systemName}
                            </p>
                        </div>
                    </footer>
                </div>
            </div>

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </div>
    );
}
