import React, { useCallback, useEffect, useState } from 'react';

const INITIAL_ROWS = [
    {
        key: 'seb',
        icon: 'shield',
        title: 'Safe Exam Browser',
        detail: 'Memeriksa mode kios...',
        status: 'pending',
    },
    {
        key: 'camera',
        icon: 'videocam',
        title: 'AI Proctor Webcam',
        detail: 'Memeriksa kamera...',
        status: 'pending',
    },
    {
        key: 'microphone',
        icon: 'mic',
        title: 'Audio & Mikrofon',
        detail: 'Memeriksa mikrofon...',
        status: 'pending',
    },
    {
        key: 'network',
        icon: 'network_check',
        title: 'Jaringan CBT',
        detail: 'Mengukur latensi...',
        status: 'pending',
    },
];

async function measureLatency() {
    if (!navigator.onLine) {
        return null;
    }

    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    const startedAt = performance.now();

    try {
        await fetch(`/favicon.ico?diagnostic=${Date.now()}`, {
            method: 'HEAD',
            cache: 'no-store',
            credentials: 'same-origin',
            signal: controller.signal,
        });

        return Math.round(performance.now() - startedAt);
    } catch {
        return null;
    } finally {
        clearTimeout(timeout);
    }
}

async function countMediaDevices() {
    if (!navigator.mediaDevices?.enumerateDevices) {
        return { supported: false, camera: 0, microphone: 0 };
    }

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();

        return {
            supported: true,
            camera: devices.filter((device) => device.kind === 'videoinput').length,
            microphone: devices.filter((device) => device.kind === 'audioinput').length,
        };
    } catch {
        return { supported: false, camera: 0, microphone: 0 };
    }
}

function StatusIcon({ status }) {
    if (status === 'pending') {
        return (
            <span className="material-symbols-outlined animate-spin text-[19px] text-outline">
                progress_activity
            </span>
        );
    }

    if (status === 'ok') {
        return (
            <span className="material-symbols-outlined text-[19px] text-tertiary">check_circle</span>
        );
    }

    return <span className="material-symbols-outlined text-[19px] text-amber-500">error</span>;
}

export default function DeviceReadiness() {
    const [rows, setRows] = useState(INITIAL_ROWS);
    const [checking, setChecking] = useState(true);
    const [checkedAt, setCheckedAt] = useState(null);

    const runDiagnostics = useCallback(async () => {
        setChecking(true);
        setRows(INITIAL_ROWS.map((row) => ({ ...row, status: 'pending' })));

        const [latency, media] = await Promise.all([measureLatency(), countMediaDevices()]);
        const isSafeExamBrowser = /SafeExamBrowser|SEB\//i.test(navigator.userAgent);
        const isOnline = navigator.onLine;

        let networkDetail = 'Koneksi tidak tersedia';

        if (isOnline) {
            networkDetail =
                latency === null
                    ? 'Latensi tidak terukur'
                    : `Latensi: ${latency}ms (${latency < 100 ? 'Optimal' : 'Lambat'})`;
        }

        setRows([
            {
                key: 'seb',
                icon: 'shield',
                title: 'Safe Exam Browser',
                detail: isSafeExamBrowser
                    ? 'Terdeteksi • Mode terkunci'
                    : 'Browser standar • SEB belum aktif',
                status: isSafeExamBrowser ? 'ok' : 'warning',
            },
            {
                key: 'camera',
                icon: 'videocam',
                title: 'AI Proctor Webcam',
                detail: !media.supported
                    ? 'Perangkat tidak mendukung'
                    : media.camera > 0
                      ? `${media.camera} kamera terdeteksi`
                      : 'Kamera tidak terdeteksi',
                status: media.supported && media.camera > 0 ? 'ok' : 'warning',
            },
            {
                key: 'microphone',
                icon: 'mic',
                title: 'Audio & Mikrofon',
                detail: !media.supported
                    ? 'Perangkat tidak mendukung'
                    : media.microphone > 0
                      ? `${media.microphone} mikrofon terdeteksi`
                      : 'Mikrofon tidak terdeteksi',
                status: media.supported && media.microphone > 0 ? 'ok' : 'warning',
            },
            {
                key: 'network',
                icon: 'network_check',
                title: 'Jaringan CBT',
                detail: networkDetail,
                status: isOnline && latency !== null && latency < 100 ? 'ok' : 'warning',
            },
        ]);

        setCheckedAt(new Date());
        setChecking(false);
    }, []);

    useEffect(() => {
        runDiagnostics();
    }, [runDiagnostics]);

    const allReady = rows.every((row) => row.status === 'ok');

    return (
        <section
            id="kesiapan"
            className="flex scroll-mt-24 flex-col gap-4 rounded-xl bg-surface-container-lowest p-5 shadow-sm ring-1 ring-outline-variant/60"
        >
            <div className="flex items-start justify-between gap-2">
                <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-[21px] text-primary">
                        laptop_mac
                    </span>
                    <h3 className="font-headline text-[15px] font-bold text-on-surface">
                        Kesiapan Perangkat
                    </h3>
                </div>
                <span
                    className={`rounded-full px-2.5 py-0.5 font-label text-[9px] font-semibold uppercase tracking-[0.06em] ${
                        checking
                            ? 'bg-surface-container text-secondary'
                            : allReady
                              ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant'
                              : 'bg-amber-100 text-amber-700'
                    }`}
                >
                    {checking ? 'Memeriksa' : allReady ? 'Normal' : 'Perlu Dicek'}
                </span>
            </div>

            <p className="font-body text-[11px] leading-relaxed text-secondary">
                Pemeriksaan diagnostik otomatis sebelum paket soal didekripsi pada workstation
                Anda.
            </p>

            <div className="flex flex-col gap-2">
                {rows.map((row) => (
                    <div
                        key={row.key}
                        className="flex items-center justify-between gap-3 rounded-lg bg-surface-container-low p-2.5"
                    >
                        <div className="flex min-w-0 items-center gap-2.5">
                            <span className="material-symbols-outlined text-[19px] text-tertiary">
                                {row.icon}
                            </span>
                            <div className="flex min-w-0 flex-col">
                                <span className="font-label text-[11px] font-semibold text-on-surface">
                                    {row.title}
                                </span>
                                <span className="truncate font-body text-[10px] text-secondary">
                                    {row.detail}
                                </span>
                            </div>
                        </div>
                        <StatusIcon status={row.status} />
                    </div>
                ))}
            </div>

            <button
                type="button"
                onClick={runDiagnostics}
                disabled={checking}
                className="flex w-full items-center justify-center gap-2 rounded-lg bg-surface-container px-4 py-2.5 font-label text-[11px] font-semibold text-on-surface transition-colors hover:bg-surface-container-high disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span className={`material-symbols-outlined text-[17px] ${checking ? 'animate-spin' : ''}`}>
                    {checking ? 'progress_activity' : 'replay'}
                </span>
                {checking ? 'Menjalankan Diagnostik...' : 'Jalankan Diagnostik Ulang'}
            </button>

            {checkedAt && !checking && (
                <p className="text-center font-label text-[9px] text-on-surface-variant">
                    Terakhir diperiksa{' '}
                    {checkedAt.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    })}{' '}
                    WIB
                </p>
            )}
        </section>
    );
}
