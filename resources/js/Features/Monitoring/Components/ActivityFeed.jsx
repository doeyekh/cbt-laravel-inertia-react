import React from 'react';

const ACTIVITIES = [
    {
        id: 1,
        icon: 'vpn_key',
        iconColor: 'text-primary',
        badgeBg: 'bg-surface-container-high',
        title: 'Rotasi Token Sukses',
        titleColor: 'text-on-surface',
        time: '08:40:02',
        description: 'Token baru <code className="font-mono font-bold text-on-surface">KXZ-994</code> disiarkan ke semua proktor lab.',
        variant: 'normal',
    },
    {
        id: 2,
        icon: 'screen_search_desktop',
        iconColor: 'text-error',
        badgeBg: 'bg-error-container',
        title: 'Pelanggaran Integritas',
        titleColor: 'text-error',
        time: '08:38:14',
        description: 'Dimas Wahyu (XII MIPA 1) memicu peringatan browser blur 3 kali berturut-turut.',
        variant: 'flag',
    },
    {
        id: 3,
        icon: 'wifi_off',
        iconColor: 'text-secondary',
        badgeBg: 'bg-surface-container-high',
        title: 'Klien Disconnected',
        titleColor: 'text-on-surface',
        time: '08:35:50',
        description: 'Siti Aisyah (Lab 02, PC-12) kehilangan heartbeat koneksi jaringan lokal.',
        variant: 'normal',
    },
    {
        id: 4,
        icon: 'task_alt',
        iconColor: 'text-primary',
        badgeBg: 'bg-surface-container-high',
        title: 'Lembar Jawaban Disimpan',
        titleColor: 'text-on-surface',
        time: '08:31:22',
        description: 'Faris Zulkarnain menyelesaikan ujian Matematika &amp; enkripsi hash tervalidasi.',
        variant: 'normal',
    },
];

export default function ActivityFeed({ className = '' }) {
    return (
        <div
            className={`
                bg-surface-container-lowest p-6 rounded-2xl shadow-sm flex flex-col gap-4 flex-1
                ${className}
            `}
        >
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-primary text-[20px]">
                        find_replace
                    </span>
                    <h3 className="font-headline font-semibold text-base text-on-surface">
                        Log Aktivitas CBT
                    </h3>
                </div>
                <span className="text-[10px] font-label font-bold text-primary uppercase tracking-widest px-2 py-0.5 rounded-full bg-primary/10">
                    Streaming
                </span>
            </div>

            <div className="space-y-3.5 mt-1 overflow-y-auto max-h-[380px] pr-1">
                {ACTIVITIES.map((activity) => (
                    <div
                        key={activity.id}
                        className={`flex items-start gap-3 p-3 rounded-xl text-xs ${
                            activity.variant === 'flag'
                                ? 'bg-error-container/20'
                                : 'bg-surface-container-low'
                        }`}
                    >
                        <span className={`p-1.5 rounded-lg mt-0.5 ${activity.badgeBg}`}>
                            <span className={`material-symbols-outlined text-[16px] ${activity.iconColor}`}>
                                {activity.icon}
                            </span>
                        </span>
                        <div className="flex-1">
                            <div className="flex items-center justify-between">
                                <span className={`font-semibold text-on-surface font-label ${activity.titleColor === 'text-error' ? 'text-error' : ''}`}>
                                    {activity.title}
                                </span>
                                <span className={`text-[10px] font-mono text-on-surface-variant ${activity.titleColor === 'text-error' ? 'text-error' : ''}`}>
                                    {activity.time}
                                </span>
                            </div>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                {activity.description}
                            </p>
                        </div>
                    </div>
                ))}
            </div>

            <div className="pt-2">
                <button
                    className="w-full py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label text-xs font-semibold tracking-wide transition-all text-center"
                    type="button"
                >
                    Lihat Riwayat Audit Lengkap (Syslog)
                </button>
            </div>
        </div>
    );
}
