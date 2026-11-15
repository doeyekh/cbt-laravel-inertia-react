import React, { useEffect } from 'react';

const EXAM_RULES = [
    'Masuk ke ruang ujian 15 menit sebelum sesi dimulai dengan membawa Kartu Peserta resmi.',
    'Dilarang membawa alat komunikasi, catatan, atau perangkat digital tambahan selain komputer yang ditentukan.',
    'Dilarang berpindah tab, meminimalkan browser, atau menggunakan tombol pintas. Setiap pelanggaran tercatat pada layar pengawas.',
    'Jika koneksi terputus, jawaban tersimpan otomatis. Segera beri tahu pengawas ruangan.',
];

export default function StudentRulesModal({ open, onClose }) {
    useEffect(() => {
        if (!open) {
            return;
        }

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    return (
        <div
            role="presentation"
            onClick={onClose}
            className="fixed inset-0 z-50 flex items-center justify-center bg-on-surface/30 px-4 py-8 backdrop-blur-sm"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="student-rules-title"
                onClick={(event) => event.stopPropagation()}
                className="w-full max-w-lg rounded-3xl bg-surface-container-lowest p-7 shadow-[0_24px_48px_-16px_rgba(27,28,29,0.18)] sm:p-9"
            >
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1.5">
                        <p className="font-label text-[10px] font-semibold uppercase tracking-[0.2em] text-tertiary">
                            Panduan Peserta
                        </p>
                        <h3
                            id="student-rules-title"
                            className="font-headline text-2xl font-bold tracking-tight text-on-surface"
                        >
                            Tata Tertib Ujian
                        </h3>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        autoFocus
                        aria-label="Tutup tata tertib"
                        className="flex h-9 w-9 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant transition-colors hover:bg-surface-container hover:text-on-surface"
                    >
                        <span className="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <ol className="mt-6 max-h-[55vh] space-y-2.5 overflow-y-auto pr-1">
                    {EXAM_RULES.map((rule, index) => (
                        <li
                            key={rule}
                            className="flex items-start gap-3.5 rounded-xl bg-surface-container-low p-3.5"
                        >
                            <span
                                aria-hidden="true"
                                className="font-label text-[11px] font-semibold tracking-[0.1em] text-tertiary"
                            >
                                {String(index + 1).padStart(2, '0')}
                            </span>
                            <p className="text-[12.5px] leading-relaxed text-on-surface-variant">
                                {rule}
                            </p>
                        </li>
                    ))}
                </ol>

                <div className="mt-7 flex items-center justify-between gap-3">
                    <p className="font-label text-[10px] uppercase tracking-[0.12em] text-on-surface-variant">
                        Pelanggaran dicatat otomatis
                    </p>

                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-xl bg-surface-container-high px-5 py-2.5 font-label text-xs font-semibold text-primary transition-colors hover:bg-surface-container-highest"
                    >
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    );
}
