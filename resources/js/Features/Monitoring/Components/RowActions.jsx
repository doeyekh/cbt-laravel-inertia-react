import React from 'react';

export default function RowActions({ student, onGrade, onReset, onExtendTime, onUnlock }) {
    return (
        <div className="inline-flex items-center justify-end gap-2">
            {student.grade_url ? (
                <button
                    type="button"
                    onClick={() => onGrade?.(student)}
                    className="rounded-lg bg-primary px-2.5 py-1.5 text-[11px] font-bold text-on-primary transition hover:brightness-110"
                >
                    Nilai Esai ({student.pending_essay_count})
                </button>
            ) : student.status === 'not_started' ? (
                <span className="text-xs text-on-surface-variant">
                    Belum mulai
                </span>
            ) : null}
            {student.can_extend_time && (
                <button
                    type="button"
                    onClick={() => onExtendTime?.(student)}
                    className="inline-flex items-center gap-1 rounded-lg bg-primary-fixed px-2.5 py-1.5 text-[11px] font-bold text-on-primary-fixed-variant transition hover:brightness-95"
                    title="Tambahkan waktu untuk peserta"
                >
                    <span className="material-symbols-outlined text-[14px]">more_time</span>
                    Tambah Waktu
                </button>
            )}
            {student.can_unlock && (
                <button
                    type="button"
                    onClick={() => onUnlock?.(student)}
                    className="inline-flex items-center gap-1 rounded-lg bg-amber-100 px-2.5 py-1.5 text-[11px] font-bold text-amber-900 transition hover:brightness-95"
                    title="Buka kembali attempt dengan mempertahankan jawaban"
                >
                    <span className="material-symbols-outlined text-[14px]">lock_open</span>
                    Buka Kunci
                </button>
            )}
            {student.attempt_id ? (
                <button
                    type="button"
                    onClick={() => onReset?.(student)}
                    className="inline-flex items-center gap-1 rounded-lg bg-error-container px-2.5 py-1.5 text-[11px] font-bold text-on-error-container transition hover:brightness-95"
                    title="Hapus seluruh jawaban dan nilai peserta"
                >
                    <span className="material-symbols-outlined text-[14px]">restart_alt</span>
                    Reset
                </button>
            ) : student.status === 'submitted' ? (
                <span className="text-xs text-on-surface-variant">Terkumpul</span>
            ) : null}
        </div>
    );
}
