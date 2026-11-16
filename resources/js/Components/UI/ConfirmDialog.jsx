import React from 'react';
import Modal from '@/Components/UI/Modal';
import Button from '@/Components/UI/Button';

export default function ConfirmDialog({
    isOpen,
    onConfirm,
    onCancel,
    title = 'Konfirmasi',
    description = '',
    confirmLabel = 'Ya, lanjutkan',
    cancelLabel = 'Batal',
    variant = 'danger',
    loading = false,
    icon = 'warning',
    iconColor = 'text-error',
    iconBg = 'bg-error-container/20',
}) {
    return (
        <Modal isOpen={isOpen} onClose={onCancel} maxWidth="max-w-sm">
            <div className="flex items-center gap-3 mb-4">
                <div className="w-8 h-8 rounded-full flex items-center justify-center">
                    <span className={`material-symbols-outlined ${iconColor} text-[20px]`}>
                        {icon}
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg">
                    {title}
                </h3>
            </div>

            {description && (
                <p className="text-on-surface-variant font-body text-sm leading-relaxed mb-6">
                    {description}
                </p>
            )}

            <div className="flex items-center gap-3 justify-end">
                <Button
                    variant="secondary"
                    onClick={onCancel}
                    disabled={loading}
                    className="flex-1"
                >
                    {cancelLabel}
                </Button>
                <Button variant={variant} onClick={onConfirm} loading={loading}>
                    {confirmLabel}
                </Button>
            </div>
        </Modal>
    );
}
