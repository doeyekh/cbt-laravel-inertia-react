import React from 'react';

export default function IntegrityStatus({ flaggedCount = 0 }) {
    return (
        <span className={`inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-label ${flaggedCount > 0 ? 'bg-tertiary-container/30 text-tertiary' : 'bg-surface-container text-primary'}`}>
            <span className="material-symbols-outlined text-[15px]">flag</span>
            <span>{flaggedCount > 0 ? `${flaggedCount} ragu-ragu` : 'Tidak ada flag'}</span>
        </span>
    );
}
