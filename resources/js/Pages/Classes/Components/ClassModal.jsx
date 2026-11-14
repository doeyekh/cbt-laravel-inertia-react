import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import { route } from '@/Utils/route';

const emptyForm = {
    code: '',
    name: '',
    school_id: '',
};

const MAX_CODE_LENGTH = 20;
const MAX_NAME_LENGTH = 100;
const CODE_PATTERN = /^[A-Z0-9-]+$/;

export default function ClassModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    schools = [],
}) {
    const { data, setData, post, put, processing, errors, clearErrors } = useForm(emptyForm);
    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setData(
            initialData
                ? {
                      code: initialData.code ?? '',
                      name: initialData.name ?? '',
                      school_id: initialData.school_id?.toString() ?? '',
                  }
                : {
                      ...emptyForm,
                      school_id: schools[0]?.id?.toString() ?? '',
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

        if (!code) {
            nextErrors.code = 'Kode kelas wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code = 'Kode hanya berisi huruf kapital, angka, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Nama kelas wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama kelas maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (Object.keys(nextErrors).length > 0) {
            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.classes.update', initialData.id), options);

            return;
        }

        post(route('admin.classes.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Edit Kelas' : 'Tambah Kelas';
    const subtitle = isEdit
        ? 'Perbarui kode dan nama kelas.'
        : 'Tambahkan kelas baru.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    const schoolOptions = schools.map((school) => ({
        value: school.id.toString(),
        label: school.is_active ? school.name : `${school.name} (Tidak Aktif)`,
    }));

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isEdit ? 'edit_square' : 'groups'}
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

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Input
                        id="class-code"
                        label="Kode Kelas"
                        type="text"
                        value={data.code}
                        onChange={(event) => setField('code', event.target.value.toUpperCase())}
                        placeholder="Contoh: KLS-01"
                        error={errors.code}
                        maxLength={MAX_CODE_LENGTH}
                        autoFocus
                    />
                    <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                        Gunakan huruf kapital, angka, atau tanda hubung. Contoh: KLS-01, SMA-IPA-1.
                    </p>
                </div>

                <div>
                    <Input
                        id="class-name"
                        label="Nama Kelas"
                        type="text"
                        value={data.name}
                        onChange={(event) => setField('name', event.target.value)}
                        placeholder="Contoh: X IPA 1"
                        error={errors.name}
                        maxLength={MAX_NAME_LENGTH}
                    />
                </div>

                <Select
                    id="class-school"
                    label="Sekolah"
                    value={data.school_id}
                    onChange={(event) => setField('school_id', event.target.value)}
                    error={errors.school_id}
                >
                    {schoolOptions.map((school) => (
                        <option key={school.value} value={school.value}>
                            {school.label}
                        </option>
                    ))}
                </Select>

                <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                    <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                        info
                    </span>
                    <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                        Tahun pelajaran otomatis mengikuti tahun pelajaran <span className="font-semibold text-on-surface">aktif</span>.
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
                        {submitLabel}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
