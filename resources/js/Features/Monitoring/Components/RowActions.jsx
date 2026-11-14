import React from 'react';

export default function RowActions({ student, onLockScreen, onForceSubmit, onResetLogin, onAddTime, onLockSession, onWarn }) {
    return (
        <div className="inline-flex items-center gap-1 text-right">
            {student.status === 'disconnected' ? (
                <button
                    className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-label text-[11px] font-bold transition-all"
                    title="Reset Token Login Siswa"
                    onClick={() => onResetLogin?.(student)}
                >
                    <span className="material-symbols-outlined text-[14px]">restart_alt</span>
                    Reset Login
                </button>
            ) : student.hasViolation || student.status === 'violation' ? (
                <>
                    <button
                        className="px-2.5 py-1 rounded-lg bg-error text-on-error font-label text-[11px] font-bold hover:brightness-110 transition-all shadow-sm"
                        title="Kunci Terminal Sekarang"
                        onClick={() => onLockScreen?.(student)}
                    >
                        Kunci Klien
                    </button>
                    <button
                        className="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant transition-all"
                        title="Peringatkan Peserta"
                        onClick={() => onWarn?.(student)}
                    >
                        <span className="material-symbols-outlined text-[18px]">notification_important</span>
                    </button>
                </>
            ) : (
                <>
                    <button
                        className="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-primary transition-all"
                        title="Tambah Waktu Kompensasi"
                        onClick={() => onAddTime?.(student)}
                    >
                        <span className="material-symbols-outlined text-[18px]">more_time</span>
                    </button>
                    <button
                        className="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant transition-all"
                        title="Kunci Sesi"
                        onClick={() => onLockSession?.(student)}
                    >
                        <span className="material-symbols-outlined text-[18px]">lock</span>
                    </button>
                </>
            )}
        </div>
    );
}
