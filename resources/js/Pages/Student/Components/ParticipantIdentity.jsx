import React from 'react';

function getInitials(name) {
    const initials = (name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();

    return initials || '?';
}

export default function ParticipantIdentity({ participant = {} }) {
    const academicYear = [participant.academic_year, participant.semester]
        .filter(Boolean)
        .join(' · ');

    const details = [
        { label: 'Username', value: participant.username },
        { label: 'NISN', value: participant.nisn },
        { label: 'Kelas', value: participant.class_name },
        { label: 'Tahun Pelajaran', value: academicYear },
    ].filter((detail) => detail.value);

    return (
        <section className="rounded-3xl bg-surface-container-lowest p-7 sm:p-8">
            <div className="flex items-start justify-between gap-3">
                <p className="font-label text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">
                    Identitas Peserta
                </p>

                {participant.participant_number && (
                    <span className="rounded-full bg-tertiary-fixed/40 px-3 py-1 font-label text-[10px] font-semibold text-tertiary">
                        {participant.participant_number}
                    </span>
                )}
            </div>

            <div className="mt-6 flex items-center gap-4">
                <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 font-headline text-lg font-bold text-primary">
                    {getInitials(participant.name)}
                </span>

                <div className="min-w-0">
                    <h2 className="font-headline text-xl font-bold leading-tight tracking-tight text-on-surface">
                        {participant.name || 'Peserta Ujian'}
                    </h2>
                    <p className="mt-1 truncate font-label text-[11px] text-on-surface-variant">
                        {participant.school || 'Sekolah belum ditentukan'}
                    </p>
                </div>
            </div>

            {details.length > 0 && (
                <dl className="mt-8 space-y-4">
                    {details.map((detail) => (
                        <div
                            key={detail.label}
                            className="grid grid-cols-[7.5rem_1fr] items-baseline gap-3"
                        >
                            <dt className="font-label text-[10px] font-semibold uppercase tracking-[0.12em] text-on-surface-variant">
                                {detail.label}
                            </dt>
                            <dd className="text-[13px] leading-snug text-on-surface">
                                {detail.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}

            <div className="mt-8 grid grid-cols-2 gap-4">
                <div className="rounded-2xl bg-surface-container-low p-4">
                    <p className="font-label text-[10px] font-semibold uppercase tracking-[0.12em] text-on-surface-variant">
                        Sesi
                    </p>
                    <p className="mt-1 text-[13px] font-medium text-on-surface">
                        {participant.session || 'Belum ditentukan'}
                    </p>
                </div>

                <div className="rounded-2xl bg-surface-container-low p-4">
                    <p className="font-label text-[10px] font-semibold uppercase tracking-[0.12em] text-on-surface-variant">
                        Ruang
                    </p>
                    <p className="mt-1 text-[13px] font-medium text-on-surface">
                        {participant.room || 'Belum ditentukan'}
                    </p>
                </div>
            </div>
        </section>
    );
}
