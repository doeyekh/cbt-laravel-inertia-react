import React from 'react';
import Modal from '@/Components/UI/Modal';
import Button from '@/Components/UI/Button';

export default function ClearQuestionsModal({
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
                <div className="w-10 h-10 rounded-xl bg-error-container/20 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-error text-[22px]">
                        delete_sweep
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Kosongkan Soal
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-1 leading-relaxed">
                        Seluruh{' '}
                        <span className="font-semibold text-on-surface">
                            {count} soal
                        </span>{' '}
                        pada bank soal ini akan dihapus sekaligus, termasuk media
                        gambar dan audio pada soal maupun pilihannya.
                    </p>
                </div>
            </div>

            <div className="flex items-start gap-2.5 rounded-xl bg-error-container/20 px-3 py-3 mt-5">
                <span className="material-symbols-outlined text-[18px] text-error shrink-0">
                    warning
                </span>
                <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                    Tindakan ini tidak dapat dibatalkan. Unduh lembar soal (PDF)
                    terlebih dahulu apabila masih diperlukan sebagai arsip.
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
                <Button
                    type="button"
                    variant="danger"
                    onClick={onConfirm}
                    loading={processing}
                >
                    Kosongkan
                </Button>
            </div>
        </Modal>
    );
}
