import React from 'react';
import Modal from '@/Components/UI/Modal';
import Button from '@/Components/UI/Button';

export default function DeleteConfirmation({
    isOpen,
    onConfirm,
    onCancel,
    itemName = 'data ini',
    loading = false,
}) {
    return (
        <Modal isOpen={isOpen} onClose={onCancel} maxWidth="max-w-sm">
            <div className="flex items-center gap-3 mb-4">
                <div className="w-8 h-8 rounded-full bg-error-container/20 flex items-center justify-center">
                    <span className="material-symbols-outlined text-error text-[20px]">
                        warning
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg">
                    Konfirmasi Hapus
                </h3>
            </div>

            <p className="text-on-surface-variant font-body text-sm leading-relaxed mb-6">
                Apakah Anda yakin ingin menghapus{' '}
                <strong className="text-on-surface font-medium">
                    &quot;{itemName}&quot;
                </strong>
                ? Tindakan ini tidak dapat dibatalkan.
            </p>

            <div className="flex items-center gap-3 justify-end">
                <Button
                    variant="secondary"
                    onClick={onCancel}
                    disabled={loading}
                    className="flex-1"
                >
                    Batal
                </Button>
                <Button variant="danger" onClick={onConfirm} loading={loading}>
                    Hapus
                </Button>
            </div>
        </Modal>
    );
}
