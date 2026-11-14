import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';

const emptyForm = {
    code: '',
    name: '',
};

const MAX_CODE_LENGTH = 20;
const MAX_NAME_LENGTH = 100;
const CODE_PATTERN = /^[A-Z0-9-]+$/;

export default function SubjectModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    onSwitchToEdit = null,
}) {
    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm(emptyForm);
    const [clientErrors, setClientErrors] = useState({});

    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);
    const isShow = mode === 'show' && Boolean(initialData);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setClientErrors({});
        setData(
            initialData
                ? {
                      code: initialData.code ?? '',
                      name: initialData.name ?? '',
                  }
                : emptyForm
        );
    }, [isOpen, initialDataId]);

    const fieldError = (field) => clientErrors[field] ?? errors[field];

    const setField = (field, value) => {
        setData(field, value);

        if (clientErrors[field]) {
            setClientErrors((previous) => {
                const next = { ...previous };
                delete next[field];

                return next;
            });
        }

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const code = data.code.trim().toUpperCase();
        const name = data.name.trim();

        const nextErrors = {};

        if (!code) {
            nextErrors.code = 'Kode mata pelajaran wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code =
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Nama mata pelajaran wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (Object.keys(nextErrors).length > 0) {
            setClientErrors(nextErrors);

            return;
        }

        setData({
            code,
            name,
        });

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.subjects.update', initialData.id), options);

            return;
        }

        post(route('admin.subjects.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isShow
        ? 'Detail Mata Pelajaran'
        : isEdit
          ? 'Edit Mata Pelajaran'
          : 'Tambah Mata Pelajaran';
    const subtitle = isShow
        ? 'Informasi lengkap mata pelajaran.'
        : isEdit
          ? 'Perbarui kode atau nama mata pelajaran.'
          : 'Tambahkan mata pelajaran baru ke dalam sistem.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isShow ? 'visibility' : isEdit ? 'edit_note' : 'menu_book'}
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        {title}
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        {subtitle}
                    </p>
                </div>
            </div>

            {isShow ? (
                <div className="space-y-4">
                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Kode Mata Pelajaran
                        </p>
                        <span className="inline-flex mt-1 rounded-lg bg-surface-container-low px-2 py-1 font-mono text-xs font-semibold text-on-surface">
                            {initialData?.code}
                        </span>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Nama Mata Pelajaran
                        </p>
                        <p className="font-headline font-semibold text-on-surface text-base mt-1">
                            {initialData?.name}
                        </p>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Status
                        </p>
                        <div className="mt-1.5">
                            <Badge variant={initialData?.is_active ? 'success' : 'neutral'}>
                                <span className="material-symbols-outlined text-[13px]">
                                    {initialData?.is_active ? 'check_circle' : 'remove_circle'}
                                </span>
                                {initialData?.is_active ? 'Aktif' : 'Tidak Aktif'}
                            </Badge>
                        </div>
                    </div>

                    <div className="flex items-center gap-3 justify-end pt-4">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handleClose}
                            className="flex-1"
                        >
                            Tutup
                        </Button>
                        <Button
                            type="button"
                            onClick={() => {
                                if (onSwitchToEdit) {
                                    onSwitchToEdit(initialData);
                                } else {
                                    handleClose();
                                }
                            }}
                        >
                            Edit
                        </Button>
                    </div>
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Input
                            id="subject-code"
                            label="Kode"
                            type="text"
                            value={data.code}
                            onChange={(event) =>
                                setField('code', event.target.value.toUpperCase())
                            }
                            placeholder="Contoh: MAT, BIN, FIS-XII"
                            error={fieldError('code')}
                            maxLength={MAX_CODE_LENGTH}
                            autoFocus
                        />
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            Gunakan huruf kapital, angka, atau tanda hubung.
                        </p>
                    </div>

                    <div>
                        <Input
                            id="subject-name"
                            label="Nama Mata Pelajaran"
                            type="text"
                            value={data.name}
                            onChange={(event) => setField('name', event.target.value)}
                            placeholder="Contoh: Matematika, Bahasa Indonesia"
                            error={fieldError('name')}
                            maxLength={MAX_NAME_LENGTH}
                        />
                    </div>

                    {!isEdit && (
                        <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                            <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                                info
                            </span>
                            <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                                Mata pelajaran baru dibuat dengan status{' '}
                                <span className="font-semibold text-on-surface">Tidak Aktif</span>.
                                Aktifkan dengan mengklik badge status pada tabel.
                            </p>
                        </div>
                    )}

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
                            {submitLabel}
                        </Button>
                    </div>
                </form>
            )}
        </Modal>
    );
}
