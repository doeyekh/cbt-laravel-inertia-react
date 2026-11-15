import React from 'react';

export default function ParticipantIdentity({ participant = {} }) {
    const academicYear =
        [
            participant.semester ? `Semester ${participant.semester}` : null,
            participant.academic_year,
        ]
            .filter(Boolean)
            .join(' ') || null;
    const placement =
        [participant.school, participant.room].filter(Boolean).join(' • ') ||
        'Penempatan belum ditentukan';

    return (
        <section className="flex w-full items-center gap-3 rounded-xl bg-surface-container-lowest p-3 shadow-sm ring-1 ring-outline-variant/60 md:w-auto">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-surface-container-high text-primary">
                <span className="material-symbols-outlined text-[24px]">badge</span>
            </span>

            <div className="min-w-0 pr-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-label text-[12px] font-bold text-on-surface">
                        {participant.nisn
                            ? `NISN: ${participant.nisn}`
                            : participant.username
                              ? `Username: ${participant.username}`
                              : 'Peserta Ujian'}
                    </span>
                    {participant.class_name && (
                        <span className="rounded-full bg-primary-fixed px-2 py-0.5 font-label text-[9px] font-semibold text-on-primary-fixed-variant">
                            {participant.class_name}
                        </span>
                    )}
                </div>

                <p className="mt-1 flex items-center gap-1 font-body text-[10.5px] text-secondary">
                    <span className="material-symbols-outlined text-[14px] text-tertiary">
                        location_on
                    </span>
                    <span className="truncate">{placement}</span>
                </p>

                {(academicYear || participant.participant_number) && (
                    <p className="mt-0.5 font-label text-[9px] text-on-surface-variant">
                        {[
                            academicYear,
                            participant.participant_number
                                ? `No. Peserta ${participant.participant_number}`
                                : null,
                        ]
                            .filter(Boolean)
                            .join(' • ')}
                    </p>
                )}
            </div>
        </section>
    );
}
