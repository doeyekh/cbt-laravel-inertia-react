import React from 'react';
import { Link } from '@inertiajs/react';

export default function RowActions({ student }) {
    return (
        <div className="inline-flex items-center justify-end">
            {student.grade_url ? (
                <Link
                    href={student.grade_url}
                    className="rounded-lg bg-primary px-2.5 py-1.5 text-[11px] font-bold text-on-primary transition hover:brightness-110"
                >
                    Nilai Esai ({student.pending_essay_count})
                </Link>
            ) : (
                <span className="text-xs text-on-surface-variant">
                    {student.status === 'submitted'
                        ? 'Terkumpul'
                        : student.status === 'not_started'
                            ? 'Belum mulai'
                            : '—'}
                </span>
            )}
        </div>
    );
}
