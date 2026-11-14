import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';

export default function ImportUsersModal({ isOpen, onClose }) {
    const { data, setData, post, processing, errors, clearErrors, reset } =
        useForm({ file: null });
    const [localError, setLocalError] = useState('');

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        reset();
        setLocalError('');
    }, [isOpen]);

    const handleFileChange = (event) => {
        const file = event.target.files?.[0] ?? null;

        setData('file', file);
        setLocalError('');
        clearErrors('file');
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (!data.file) {
            setLocalError('Berkas import wajib dipilih.');

            return;
        }

        post(route('admin.users.import'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        upload_file
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Import Pengguna
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Unggah berkas Excel atau CSV berisi data pengguna.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="rounded-xl bg-surface-container-low p-4 space-y-3">
                    <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                        Format Berkas
                    </p>
                    <div className="space-y-1.5 text-xs text-on-surface-variant font-body">
                        <p>
                            <span className="font-semibold text-on-surface">Wajib:</span>{' '}
                            <code className="font-mono text-[11px]">name</code>,{' '}
                            <code className="font-mono text-[11px]">username</code>,{' '}
                            <code className="font-mono text-[11px]">email</code>
                        </p>
                        <p>
                            <span className="font-semibold text-on-surface">Opsional:</span>{' '}
                            <code className="font-mono text-[11px]">role</code>{' '}
                            (administrator/guru),{' '}
                            <code className="font-mono text-[11px]">password</code> (min. 8
                            karakter)
                        </p>
                        <p>
                            Role kosong otomatis menjadi <span className="font-semibold">guru</span>,
                            kata sandi kosong otomatis menjadi{' '}
                            <code className="font-mono text-[11px]">password</code>, dan pengguna
                            langsung aktif.
                        </p>
                    </div>
                </div>

                <div>
                    <Input
                        id="import-file"
                        label="Berkas Import"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        onChange={handleFileChange}
                        error={localError || errors.file}
                        disabled={processing}
                    />
                    <p className="mt-1.5 text-[10px] text-on-surface-variant font-body">
                        Format XLSX, XLS, atau CSV. Ukuran maksimal 5 MB.
                    </p>
                </div>

                <div className="flex items-center gap-3 justify-end pt-4">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={handleClose}
                        disabled={processing}
                        className="flex-1"
                    >
                        Batal
                    </Button>
                    <Button type="submit" loading={processing}>
                        Import
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
