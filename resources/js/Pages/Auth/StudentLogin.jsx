import React, { useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import { route } from '@/Utils/route';
import StudentLoginForm from './Components/StudentLoginForm';
import StudentRulesModal from './Components/StudentRulesModal';

const GUIDANCE_NOTES = [
    {
        title: 'Kredensial Resmi',
        description:
            'Gunakan username dan kata sandi yang tercantum pada Kartu Peserta Ujian.',
    },
    {
        title: 'Integritas Sesi',
        description:
            'Aktivitas browser tercatat selama ujian berlangsung. Hindari berpindah tab atau membuka aplikasi lain.',
    },
    {
        title: 'Pendampingan Pengawas',
        description:
            'Mengalami kendala teknis atau lupa kredensial? Hubungi pengawas ruangan Anda.',
    },
];

function useClock() {
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), 1000);

        return () => clearInterval(timer);
    }, []);

    return {
        time: now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }),
        date: now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }),
    };
}

export default function StudentLogin({ systemInfo = {} }) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const handledToast = useRef(null);
    const [rulesOpen, setRulesOpen] = useState(false);
    const { time, date } = useClock();

    const appName = systemInfo.appName || 'EduTest CBT';
    const systemName = systemInfo.systemName || 'Sistem Evaluasi Terpadu';
    const serverStatus = systemInfo.serverStatus || 'Online';

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    return (
        <div className="relative min-h-screen overflow-x-hidden bg-surface-container-low font-body text-on-surface selection:bg-primary/15 selection:text-primary">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-x-0 top-0 h-[520px] bg-gradient-to-b from-primary/[0.05] via-primary/[0.02] to-transparent"
            />

            <div className="relative flex min-h-screen flex-col">
                <header className="mx-auto flex w-full max-w-[1520px] items-center justify-between gap-4 px-6 py-7 sm:px-10 lg:px-16 lg:py-9">
                    <Link href={route('student.login')} className="flex items-center gap-3.5">
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

                    <div className="hidden items-center gap-5 lg:flex">
                        <span className="flex items-center gap-2 font-label text-xs text-on-surface-variant">
                            <span className="relative flex h-1.5 w-1.5">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary/60" />
                                <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-primary" />
                            </span>
                            {serverStatus}
                        </span>

                        <span className="h-1 w-1 rounded-full bg-outline-variant" />

                        <span className="font-label text-xs text-on-surface-variant">
                            <span className="font-semibold tabular-nums text-on-surface">
                                {time}
                            </span>{' '}
                            WIB
                        </span>

                        <span className="h-1 w-1 rounded-full bg-outline-variant" />

                        <span className="font-label text-xs text-on-surface-variant">
                            {date}
                        </span>
                    </div>

                    <div className="flex items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            onClick={() => setRulesOpen(true)}
                            className="hidden font-label text-xs font-medium text-on-surface-variant transition-colors hover:text-primary sm:inline-flex"
                        >
                            Tata Tertib
                        </button>

                       
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-[1520px] flex-1 items-center px-6 pb-14 pt-4 sm:px-10 lg:px-16 lg:pb-20">
                    <div className="grid w-full items-center gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 xl:gap-24">
                        <section className="order-2 max-w-xl space-y-10 lg:order-1">
                            <div className="space-y-6">
                                <div className="flex items-center gap-3">
                                    <span className="h-1.5 w-1.5 rounded-full bg-tertiary" />
                                    <p className="font-label text-[11px] font-semibold uppercase tracking-[0.22em] text-tertiary">
                                        Portal Asesmen Digital Siswa
                                    </p>
                                </div>

                                <h1 className="font-headline text-[2.5rem] font-bold leading-[1.12] tracking-tight text-on-surface sm:text-5xl xl:text-[3.4rem]">
                                    Ruang tenang untuk{' '}
                                    <em className="italic">jawaban</em> yang serius.
                                </h1>

                                <p className="max-w-lg text-[15px] leading-[1.85] text-on-surface-variant sm:text-base">
                                    Masuk dengan kredensial resmi pada Kartu Peserta. Sistem
                                    menyiapkan naskah, sisa waktu, dan status sesi Anda secara
                                    otomatis.
                                </p>
                            </div>

                            <ol className="max-w-lg space-y-7">
                                {GUIDANCE_NOTES.map((note, index) => (
                                    <li
                                        key={note.title}
                                        className="grid grid-cols-[2rem_1fr] gap-x-1"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="font-label text-[11px] font-semibold tracking-[0.15em] text-tertiary"
                                        >
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <div>
                                            <h2 className="font-label text-[11px] font-semibold uppercase tracking-[0.16em] text-on-surface">
                                                {note.title}
                                            </h2>
                                            <p className="mt-1.5 text-[13px] leading-[1.75] text-on-surface-variant">
                                                {note.description}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        </section>

                        <div className="order-1 w-full max-w-md justify-self-center lg:order-2 lg:justify-self-end">
                            <StudentLoginForm
                                academicYear={systemInfo.academicYear}
                                onShowRules={() => setRulesOpen(true)}
                            />
                        </div>
                    </div>
                </main>

                <footer className="mx-auto w-full max-w-[1520px] px-6 pb-8 sm:px-10 lg:px-16">
                    <div className="flex flex-col gap-2 font-label text-[11px] text-on-surface-variant sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            © {new Date().getFullYear()} {appName} — {systemName}
                        </p>

                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>Koneksi terenkripsi</span>
                            <span className="h-1 w-1 rounded-full bg-outline-variant" />
                            <span>{systemInfo.clusterNode || 'Cluster Node 01'}</span>
                            <span className="h-1 w-1 rounded-full bg-outline-variant" />
                            <span>Latensi {systemInfo.serverLatency || '12ms'}</span>
                            <span className="h-1 w-1 rounded-full bg-outline-variant" />
                            <span>{systemInfo.version || 'v1.0.0'}</span>
                        </div>
                    </div>
                </footer>
            </div>

            <StudentRulesModal open={rulesOpen} onClose={() => setRulesOpen(false)} />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </div>
    );
}
