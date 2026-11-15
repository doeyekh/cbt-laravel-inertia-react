import React, { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import { route } from '@/Utils/route';

export default function StudentLayout({
    children,
    appName = 'EduTest CBT',
    systemName = 'Sistem Evaluasi Terpadu',
    version = 'v1.0.0',
}) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const handledToast = useRef(null);
    const [loggingOut, setLoggingOut] = useState(false);

    const today = new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

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
        <div className="relative min-h-screen overflow-x-hidden bg-surface-container-low font-body text-on-surface selection:bg-primary/15 selection:text-primary">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-x-0 top-0 h-[420px] bg-gradient-to-b from-primary/[0.05] via-primary/[0.02] to-transparent"
            />

            <div className="relative flex min-h-screen flex-col">
                <header className="mx-auto flex w-full max-w-[1520px] items-center justify-between gap-4 px-6 py-8 sm:px-10 lg:px-16 lg:py-10">
                    <Link href={route('student.dashboard')} className="flex items-center gap-3.5">
                        <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-container text-on-primary">
                            <span className="material-symbols-outlined text-[22px]">
                                school
                            </span>
                        </span>
                        <span className="flex flex-col">
                            <span className="font-headline text-lg font-bold leading-none tracking-tight text-on-surface">
                                {appName}
                            </span>
                            <span className="mt-1.5 font-label text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">
                                {systemName}
                            </span>
                        </span>
                    </Link>

                    <div className="flex items-center gap-4">
                        <span className="hidden font-label text-xs text-on-surface-variant md:inline">
                            {today}
                        </span>

                        <button
                            type="button"
                            onClick={handleLogout}
                            disabled={loggingOut}
                            className="inline-flex items-center gap-2 rounded-xl bg-surface-container-high px-4 py-2.5 font-label text-xs font-semibold text-primary transition-colors hover:bg-surface-container-highest disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span className="material-symbols-outlined text-[17px]">
                                logout
                            </span>
                            {loggingOut ? 'Mengakhiri...' : 'Keluar'}
                        </button>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-[1520px] flex-1 px-6 pb-20 sm:px-10 sm:pb-24 lg:px-16">
                    {children}
                </main>

                <footer className="mx-auto w-full max-w-[1520px] px-6 pb-10 sm:px-10 lg:px-16">
                    <div className="flex flex-col gap-2 font-label text-[11px] text-on-surface-variant sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            © {new Date().getFullYear()} {appName} — {systemName}
                        </p>

                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>Koneksi terenkripsi</span>
                            <span className="h-1 w-1 rounded-full bg-outline-variant" />
                            <span>Portal Siswa</span>
                            <span className="h-1 w-1 rounded-full bg-outline-variant" />
                            <span>{version}</span>
                        </div>
                    </div>
                </footer>
            </div>

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </div>
    );
}
