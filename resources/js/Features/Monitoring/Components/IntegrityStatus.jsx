import React from 'react';

export default function IntegrityStatus({ status = 'clean' }) {
    const statuses = {
        clean: {
            label: 'Bersih (0 Anomali)',
            icon: 'verified_user',
            color: 'text-primary',
            bg: 'bg-surface-container',
        },
        tabSwitch: {
            label: 'Pindah Tab 3x',
            icon: 'hard_drive',
            color: 'text-error font-bold tracking-tight',
            bg: 'bg-error-container',
        },
        multipleFace: {
            label: 'Multiple Face (2 Org)',
            icon: 'person_alert',
            color: 'text-tertiary font-bold',
            bg: 'bg-tertiary-container/30',
        },
    };

    const s = statuses[status];

    return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-label ${s.bg}`}>
            <span className={`material-symbols-outlined text-[15px] ${s.color}`}>{s.icon}</span>
            <span className={s.color === 'text-error font-bold tracking-tight' ? s.color : s.color}>{s.label}</span>
        </span>
    );
}
