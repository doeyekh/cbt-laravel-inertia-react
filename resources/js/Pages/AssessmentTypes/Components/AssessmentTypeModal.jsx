import React, { useEffect, useState } from 'react';
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
    weight: '',
};

const MAX_CODE_LENGTH = 20;
const MAX_NAME_LENGTH = 100;
const MAX_DESCRIPTION_LENGTH = 255;
const CODE_PATTERN = /^[A-Z0-9_-]+$/;

export default function AssessmentTypeModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    schools = [],
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
                      school_id: initialData.school_id
                          ? String(initialData.school_id)
                          : '',
                      code: initialData.code ?? '',
                      name: initialData.name ?? '',
                      description: initialData.description ?? '',
                      weight:
                          initialData.weight !== null &&
                          initialData.weight !== undefined
                              ? String(initialData.weight)
                              : '',
                  }
                : {
                      ...emptyForm,
                      school_id: schools[0]?.id ? String(schools[0].id) : '',
                  }
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
        const description = data.description.trim();
        const weight = Number(data.weight);

        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!code) {
            nextErrors.code = 'Kode jenis penilaian wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code =
                'Kode hanya boleh berisi huruf kapital, angka, garis bawah, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Nama jenis penilaian wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (description.length > MAX_DESCRIPTION_LENGTH) {
            nextErrors.description = `Deskripsi maksimal ${MAX_DESCRIPTION_LENGTH} karakter.`;
        }

        if (data.weight === '' || Number.isNaN(weight)) {
            nextErrors.weight = 'Bobot nilai wajib diisi.';
        } else if (weight < 0 || weight > 100) {
            nextErrors.weight = 'Bobot nilai harus antara 0% dan 100%.';
        }

        if (Object.keys(nextErrors).length > 0) {
            setClientErrors(nextErrors);

            return;
        }

        setData({
            school_id: data.school_id,
            code,
            name,
            description,
            weight: String(weight),
        });

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.assessment-types.update', initialData.id), options);

            return;
        }

        post(route('admin.assessment-types.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isShow
        ? 'Detail Jenis Penilaian'
        : isEdit
          ? 'Edit Jenis Penilaian'
          : 'Tambah Jenis Penilaian';
    const subtitle = isShow
        ? 'Informasi lengkap jenis penilaian.'
        : isEdit
          ? 'Perbarui kode, nama, atau deskripsi jenis penilaian.'
          : 'Tambahkan kategori penilaian baru ke sistem.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isShow ? 'visibility' : isEdit ? 'edit_note' : 'fact_check'}
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
                            Nama Jenis Penilaian
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
                            Bobot Nilai
                        </p>
                        <p className="font-headline font-semibold text-on-surface text-base mt-1">
                            {Number(initialData?.weight ?? 0).toLocaleString('id-ID', {
                                maximumFractionDigits: 2,
                            })}
                            %
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
                        <Select
                            id="assessment-type-school"
                            label="Sekolah"
                            value={data.school_id}
                            onChange={(event) =>
                                setField('school_id', event.target.value)
                            }
                            error={fieldError('school_id')}
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
                                : 'Jenis penilaian hanya berlaku untuk sekolah yang dipilih.'}
                        </p>
                    </div>

                    <div>
                        <Input
                            id="assessment-type-code"
                            label="Kode"
                            type="text"
                            value={data.code}
                            onChange={(event) =>
                                setField('code', event.target.value.toUpperCase())
                            }
                            placeholder="Contoh: FORMATIF, SUM_LM, PTS"
                            error={fieldError('code')}
                            maxLength={MAX_CODE_LENGTH}
                            autoFocus
                        />
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            Gunakan huruf kapital, angka, garis bawah, atau tanda hubung.
                        </p>
                    </div>

                    <div>
                        <Input
                            id="assessment-type-name"
                            label="Nama Jenis Penilaian"
                            type="text"
                            value={data.name}
                            onChange={(event) => setField('name', event.target.value)}
                            placeholder="Contoh: Penilaian Formatif, Sumatif Lingkup Materi"
                            error={fieldError('name')}
                            maxLength={MAX_NAME_LENGTH}
                        />
                    </div>

                    <div>
                        <Textarea
                            id="assessment-type-description"
                            label="Deskripsi"
                            value={data.description}
                            onChange={(event) => setField('description', event.target.value)}
                            placeholder="Keterangan cakupan atau tujuan penilaian..."
                            rows={3}
                            error={fieldError('description')}
                            maxLength={MAX_DESCRIPTION_LENGTH}
                        />
                    </div>

                    <div>
                        <Input
                            id="assessment-type-weight"
                            label="Bobot Nilai (%)"
                            type="number"
                            value={data.weight}
                            onChange={(event) => setField('weight', event.target.value)}
                            placeholder="Contoh: 25"
                            error={fieldError('weight')}
                            min={0}
                            max={100}
                            step="0.01"
                        />
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            Bobot digunakan untuk menghitung nilai akhir siswa. Total bobot seluruh jenis penilaian sebaiknya 100% per sekolah.
                        </p>
                    </div>

                    {!isEdit && (
                        <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                            <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                                info
                            </span>
                            <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                                Jenis penilaian baru dibuat dengan status{' '}
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
