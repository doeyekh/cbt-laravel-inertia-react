import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';

const emptyForm = {
    school_id: '',
    code: '',
    name: '',
    description: '',
};

const MAX_CODE_LENGTH = 20;
const MAX_NAME_LENGTH = 100;
const MAX_DESCRIPTION_LENGTH = 255;
const CODE_PATTERN = /^[A-Z0-9-]+$/;

export default function ExamTypeModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    schools = [],
}) {
    const { data, setData, post, put, processing, errors, clearErrors, setError } =
        useForm(emptyForm);
    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);
    const isShow = mode === 'show' && Boolean(initialData);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setData(
            initialData
                ? {
                      school_id: initialData.school_id
                          ? String(initialData.school_id)
                          : '',
                      code: initialData.code ?? '',
                      name: initialData.name,
                      description: initialData.description ?? '',
                  }
                : {
                      ...emptyForm,
                      school_id: schools[0]?.id ? String(schools[0].id) : '',
                  }
        );
    }, [isOpen, initialDataId]);

    const setField = (field, value) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const code = data.code.trim().toUpperCase();
        const name = data.name.trim();

        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!code) {
            nextErrors.code = 'Kode wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code =
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Jenis ujian wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Jenis ujian maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (data.description.length > MAX_DESCRIPTION_LENGTH) {
            nextErrors.description = `Deskripsi maksimal ${MAX_DESCRIPTION_LENGTH} karakter.`;
        }

        if (Object.keys(nextErrors).length > 0) {
            Object.entries(nextErrors).forEach(([field, message]) => {
                setError(field, message);
            });

            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.exam-types.update', initialData.id), options);

            return;
        }

        post(route('admin.exam-types.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isShow
        ? 'Detail Jenis Ujian'
        : isEdit
          ? 'Edit Jenis Ujian'
          : 'Tambah Jenis Ujian';
    const subtitle = isShow
        ? 'Informasi lengkap jenis ujian.'
        : isEdit
          ? 'Perbarui kode, nama, atau deskripsi jenis ujian.'
          : 'Tambahkan kategori ujian baru.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isShow ? 'visibility' : isEdit ? 'edit_note' : 'quiz'}
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
                            Sekolah
                        </p>
                        <p className="font-headline font-semibold text-on-surface text-base mt-1">
                            {initialData?.school_name ?? '-'}
                        </p>
                        {initialData?.school_code && (
                            <p className="text-xs font-mono text-on-surface-variant mt-0.5">
                                {initialData.school_code}
                            </p>
                        )}
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Kode
                        </p>
                        <span className="inline-flex mt-1 rounded-lg bg-surface-container-low px-2 py-1 font-mono text-xs font-semibold text-on-surface">
                            {initialData?.code}
                        </span>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Jenis Ujian
                        </p>
                        <p className="font-headline font-semibold text-on-surface text-base mt-1">
                            {initialData?.name}
                        </p>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Deskripsi
                        </p>
                        <p className="text-sm font-body text-on-surface-variant mt-1 leading-relaxed whitespace-pre-line">
                            {initialData?.description || '-'}
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
                        {onEdit && (
                            <Button type="button" onClick={handleClose}>
                                Edit
                            </Button>
                        )}
                    </div>
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Select
                            id="exam-type-school"
                            label="Sekolah"
                            value={data.school_id}
                            onChange={(event) =>
                                setField('school_id', event.target.value)
                            }
                            error={errors.school_id}
                            disabled={isEdit || schools.length === 0}
                        >
                            <option value="" disabled>
                                Pilih sekolah
                            </option>
                            {schools.map((school) => (
                                <option key={school.id} value={school.id}>
                                    {school.name} ({school.code})
                                </option>
                            ))}
                        </Select>
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            {isEdit
                                ? 'Sekolah tidak dapat diubah setelah data dibuat.'
                                : 'Jenis ujian hanya berlaku untuk sekolah yang dipilih.'}
                        </p>
                    </div>

                    <div>
                        <Input
                            id="exam-type-code"
                            label="Kode"
                            type="text"
                            value={data.code}
                            onChange={(event) =>
                                setField('code', event.target.value.toUpperCase())
                            }
                            placeholder="Contoh: UTS"
                            error={errors.code}
                            maxLength={MAX_CODE_LENGTH}
                            autoFocus
                        />
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            Gunakan huruf kapital, angka, atau tanda hubung. Contoh: UTS, UAS, TO-UN.
                        </p>
                    </div>

                    <div>
                        <Input
                            id="exam-type-name"
                            label="Jenis Ujian"
                            type="text"
                            value={data.name}
                            onChange={(event) => setField('name', event.target.value)}
                            placeholder="Contoh: Ujian Tengah Semester"
                            error={errors.name}
                            maxLength={MAX_NAME_LENGTH}
                        />
                    </div>

                    <div>
                        <Textarea
                            id="exam-type-description"
                            label="Deskripsi"
                            value={data.description}
                            onChange={(event) =>
                                setField('description', event.target.value)
                            }
                            placeholder="Jelaskan tujuan atau cakupan jenis ujian ini..."
                            rows={4}
                            error={errors.description}
                            maxLength={MAX_DESCRIPTION_LENGTH}
                        />
                        <div className="flex items-center justify-between mt-1.5">
                            <p className="text-[10px] font-body text-on-surface-variant">
                                Deskripsi bersifat opsional.
                            </p>
                            <p
                                className={`text-[10px] font-label ${
                                    data.description.length > MAX_DESCRIPTION_LENGTH
                                        ? 'text-error'
                                        : 'text-on-surface-variant'
                                }`}
                            >
                                {data.description.length}/{MAX_DESCRIPTION_LENGTH}
                            </p>
                        </div>
                    </div>

                    {!isEdit && (
                        <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                            <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                                info
                            </span>
                            <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                                Jenis ujian baru dibuat dengan status{' '}
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