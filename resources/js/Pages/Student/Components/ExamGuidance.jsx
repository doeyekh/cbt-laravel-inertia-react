import React from 'react';

const PRE_EXAM_NOTES = [
    'Bawa Kartu Peserta Ujian dan dokumen identitas yang diminta panitia.',
    'Hadir di ruang ujian 15 menit sebelum sesi dimulai.',
    'Laporkan kendala teknis kepada pengawas ruangan, jangan berpindah tab.',
];

export default function ExamGuidance() {
    return (
        <section
            id="panduan"
            className="flex scroll-mt-24 flex-col gap-4 rounded-xl bg-surface-container-lowest p-5 shadow-sm ring-1 ring-outline-variant/60"
        >
            <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-[21px] text-primary">menu_book</span>
                <h3 className="font-headline text-[15px] font-bold text-on-surface">
                    Panduan Sebelum Ujian
                </h3>
            </div>

            <ol className="space-y-3">
                {PRE_EXAM_NOTES.map((note, index) => (
                    <li key={note} className="grid grid-cols-[1.5rem_1fr] gap-x-1.5">
                        <span className="font-label text-[10px] font-semibold tracking-[0.1em] text-tertiary">
                            {String(index + 1).padStart(2, '0')}
                        </span>
                        <p className="font-body text-[11px] leading-relaxed text-on-surface-variant">
                            {note}
                        </p>
                    </li>
                ))}
            </ol>

            <div
                id="bantuan"
                className="scroll-mt-24 rounded-lg bg-amber-50 p-3.5 ring-1 ring-amber-200"
            >
                <div className="flex items-center gap-1.5 font-label text-[11px] font-bold text-amber-900">
                    <span className="material-symbols-outlined text-[18px]">support_agent</span>
                    Butuh bantuan pengawas?
                </div>
                <p className="mt-1.5 font-body text-[10.5px] leading-relaxed text-amber-900/85">
                    Angkat tangan atau panggil pengawas ruang. Jangan me-refresh halaman atau
                    menutup browser selama sesi ujian berlangsung.
                </p>
            </div>
        </section>
    );
}
