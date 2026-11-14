import React from 'react';
import Modal from '@/Components/UI/Modal';
import Button from '@/Components/UI/Button';

export default function BulkGraduateModal({
    isOpen,
    onClose,
    onConfirm,
    count = 0,
    processing = false,
}) {
    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-md">
            <div className="flex items-start gap-3">
                <div className="w-10 h-10 rounded-xl bg-tertiary-container/40 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-tertiary text-[22px]">
                        workspace_premium
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Luluskan Siswa
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-1 leading-relaxed">
                        Status registrasi aktif dari{' '}
                        <span className="font-semibold text-on-surface">{count} siswa</span> yang
                        dipilih akan diubah menjadi{' '}
                        <span className="font-semibold text-on-surface">Lulus</span>.
                    </p>
                </div>
            </div>

            <div className="flex items-start gap-2.5 rounded-xl bg-tertiary-container/20 px-3 py-3 mt-5">
                <span className="material-symbols-outlined text-[18px] text-tertiary shrink-0">
                    info
                </span>
                <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                    Siswa akan berpindah ke daftar Alumni / Non Aktif. Keanggotaan kelas tidak
                    dihapus otomatis.
                </p>
            </div>

            <div className="flex items-center gap-3 justify-end pt-6">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={handleClose}
                    disabled={processing}
                    className="flex-1"
                >
                    Batal
                </Button>
                <Button type="button" onClick={onConfirm} loading={processing}>
                    Luluskan
                </Button>
            </div>
        </Modal>
    );
}
