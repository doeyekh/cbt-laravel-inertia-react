import React, { useEffect, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';

const emptyForm = {
    code: '',
    name: '',
    headmaster_id: '',
    admin_id: '',
    address: '',
};

const MAX_CODE_LENGTH = 20;
const MAX_NAME_LENGTH = 150;
const MAX_ADDRESS_LENGTH = 255;
const CODE_PATTERN = /^[A-Z0-9-]+$/;

export default function SchoolModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    teachers = [],
    onSwitchToEdit = null,
}) {
    const { auth } = usePage().props;
    const isAdministrator = auth?.user?.is_administrator ?? (auth?.user?.role === 'administrator');

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
                      headmaster_id: initialData.headmaster_id
                          ? String(initialData.headmaster_id)
                          : '',
                      admin_id: initialData.admin_id
                          ? String(initialData.admin_id)
                          : '',
                      address: initialData.address ?? '',
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

    const teacherOptions = teachers ?? [];
    const teacherLabel = (teacher) => `${teacher.name} (${teacher.username})`;

    const handleSubmit = (event) => {
        event.preventDefault();

        const code = data.code.trim().toUpperCase();
        const name = data.name.trim();
        const address = data.address.trim();
        const nextErrors = {};

        if (!code) {
            nextErrors.code = 'Kode sekolah wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code =
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Nama sekolah wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama sekolah maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (address.length > MAX_ADDRESS_LENGTH) {
            nextErrors.address = `Alamat maksimal ${MAX_ADDRESS_LENGTH} karakter.`;
        }

        if (Object.keys(nextErrors).length > 0) {
            setClientErrors(nextErrors);

            return;
        }

        setData({
            ...data,
            code,
            name,
            headmaster_id: data.headmaster_id || null,
            admin_id: data.admin_id || null,
            address,
        });

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.schools.update', initialData.id), options);

            return;
        }

        post(route('admin.schools.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isShow
        ? 'Detail Sekolah'
        : isEdit
          ? 'Edit Sekolah'
          : 'Tambah Sekolah';
    const subtitle = isShow
        ? 'Informasi lengkap sekolah.'
        : isEdit
          ? 'Perbarui kode, nama, kepala sekolah, admin, atau alamat.'
          : 'Tambahkan sekolah baru di bawah yayasan.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isShow ? 'visibility' : isEdit ? 'edit_note' : 'school'}
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
                            Kode
                        </p>
                        <span className="inline-flex mt-1 rounded-lg bg-surface-container-low px-2 py-1 font-mono text-xs font-semibold text-on-surface">
                            {initialData?.code}
                        </span>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Nama Sekolah
                        </p>
                        <p className="font-headline font-semibold text-on-surface text-base mt-1">
                            {initialData?.name}
                        </p>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Kepala Sekolah
                        </p>
                        <p className="text-sm font-body text-on-surface-variant mt-1">
                            {initialData?.headmaster_name || '-'}
                        </p>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Admin Sekolah
                        </p>
                        <p className="text-sm font-body text-on-surface-variant mt-1">
                            {initialData?.admin_name || '-'}
                        </p>
                    </div>

                    <div>
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Alamat
                        </p>
                        <p className="text-sm font-body text-on-surface-variant mt-1 leading-relaxed whitespace-pre-line">
                            {initialData?.address || '-'}
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
                        {initialData?.can_edit && (
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
                        )}
                    </div>
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Input
                            id="school-code"
                            label="Kode"
                            type="text"
                            value={data.code}
                            onChange={(event) =>
                                setField('code', event.target.value.toUpperCase())
                            }
                            placeholder="Contoh: YAS-001"
                            error={fieldError('code')}
                            maxLength={MAX_CODE_LENGTH}
                            disabled={isEdit && !isAdministrator}
                            autoFocus={!isEdit || isAdministrator}
                        />
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            {isEdit && !isAdministrator
                                ? 'Kode sekolah hanya dapat diubah oleh Administrator.'
                                : 'Gunakan huruf kapital, angka, atau tanda hubung.'}
                        </p>
                    </div>

                    <Input
                        id="school-name"
                        label="Nama Sekolah"
                        type="text"
                        value={data.name}
                        onChange={(event) => setField('name', event.target.value)}
                        placeholder="Contoh: SMA Yayasan Nusantara"
                        error={fieldError('name')}
                        maxLength={MAX_NAME_LENGTH}
                    />

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Select
                            id="school-headmaster"
                            label="Kepala Sekolah"
                            value={data.headmaster_id}
                            onChange={(event) =>
                                setField('headmaster_id', event.target.value)
                            }
                            error={fieldError('headmaster_id')}
                            disabled={isEdit && !isAdministrator}
                        >
                            <option value="">Belum ditentukan</option>
                            {teacherOptions.map((teacher) => (
                                <option key={teacher.id} value={teacher.id}>
                                    {teacherLabel(teacher)}
                                </option>
                            ))}
                        </Select>

                        <Select
                            id="school-admin"
                            label="Admin Sekolah"
                            value={data.admin_id}
                            onChange={(event) =>
                                setField('admin_id', event.target.value)
                            }
                            error={fieldError('admin_id')}
                            disabled={isEdit && !isAdministrator}
                        >
                            <option value="">Belum ditentukan</option>
                            {teacherOptions.map((teacher) => (
                                <option key={teacher.id} value={teacher.id}>
                                    {teacherLabel(teacher)}
                                </option>
                            ))}
                        </Select>
                    </div>

                    <p className="text-[10px] font-body text-on-surface-variant -mt-2">
                        {isEdit && !isAdministrator
                            ? 'Penetapan kepala sekolah dan admin sekolah hanya dapat diatur oleh Administrator.'
                            : 'Kepala sekolah dan admin sekolah dipilih dari pengguna dengan role Guru.'}
                    </p>

                    <div>
                        <Textarea
                            id="school-address"
                            label="Alamat"
                            value={data.address}
                            onChange={(event) => setField('address', event.target.value)}
                            placeholder="Alamat lengkap sekolah..."
                            rows={3}
                            error={fieldError('address')}
                            maxLength={MAX_ADDRESS_LENGTH}
                        />
                    </div>

                    {!isEdit && (
                        <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                            <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                                info
                            </span>
                            <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                                Sekolah baru dibuat dengan status{' '}
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
