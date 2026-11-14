import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';

export default function ImportSubjectsModal({ isOpen, onClose }) {
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

        post(route('admin.subjects.import'), {
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
                        Import Mata Pelajaran
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Unggah berkas Excel atau CSV berisi data mata pelajaran.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="rounded-xl bg-surface-container-low p-4 space-y-3">
                    <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                        Format Berkas Spreadsheet
                    </p>
                    <div className="space-y-1.5 text-xs text-on-surface-variant font-body">
                        <p>
                            <span className="font-semibold text-on-surface">Kolom Wajib:</span>{' '}
                            <code className="font-mono text-[11px]">kode</code> (atau{' '}
                            <code className="font-mono text-[11px]">code</code>),{' '}
                            <code className="font-mono text-[11px]">nama</code> (atau{' '}
                            <code className="font-mono text-[11px]">name</code> /{' '}
                            <code className="font-mono text-[11px]">nama_mapel</code>)
                        </p>
                        <p>
                            <span className="font-semibold text-on-surface">Kolom Opsional:</span>{' '}
                            <code className="font-mono text-[11px]">status</code> (atau{' '}
                            <code className="font-mono text-[11px]">aktif</code> /{' '}
                            <code className="font-mono text-[11px]">is_active</code>)
                        </p>
                        <p className="text-[11px] text-on-surface-variant/80">
                            Status kosong otomatis menjadi <span className="font-semibold">Tidak Aktif</span>.
                            Gunakan huruf kapital, angka, atau tanda hubung untuk kode mapel (contoh: MAT, BIN, FIS-XII).
                        </p>
                    </div>
                </div>

                <div>
                    <Input
                        id="import-subject-file"
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
