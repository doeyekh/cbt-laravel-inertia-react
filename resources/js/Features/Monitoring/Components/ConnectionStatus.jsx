import React from 'react';

export default function ConnectionStatus({ status = 'stable' }) {
    const statuses = {
        stable: {
            label: 'Stabil',
            dotColor: 'bg-primary',
            textColor: 'text-on-surface',
        },
        fluctuating: {
            label: 'Fluktuatif',
            dotColor: 'bg-tertiary',
            textColor: 'text-on-surface',
        },
        disconnected: {
            label: 'Terputus',
            dotColor: 'bg-error',
            textColor: 'text-on-error-container',
            bg: 'bg-error-container',
        },
    };

    const s = statuses[status];

    return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-surface-container text-[11px] font-label font-medium ${s.bg || ''}`}>
            <span className={`w-2 h-2 rounded-full ${s.dotColor}`} />
            <span className={s.textColor}>{s.label}</span>
        </span>
    );
}
