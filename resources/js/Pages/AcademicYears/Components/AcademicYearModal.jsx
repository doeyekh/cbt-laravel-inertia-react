import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import { route } from '@/Utils/route';

const emptyForm = {
    name: '',
    semester: 'ganjil',
};

export default function AcademicYearModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    semesters = [],
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
                      name: initialData.name,
                      semester: initialData.semester,
                  }
                : emptyForm
        );
    }, [isOpen, initialDataId]);

    const semesterOptions =
        semesters.length > 0
            ? semesters
            : [
                  { value: 'ganjil', label: 'Ganjil' },
                  { value: 'genap', label: 'Genap' },
              ];

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.academic-years.update', initialData.id), options);

            return;
        }

        post(route('admin.academic-years.store'), options);
    };

    const title = isEdit ? 'Edit Tahun Pelajaran' : 'Tambah Tahun Pelajaran';
    const subtitle = isEdit
        ? 'Perbarui nama atau semester tahun pelajaran.'
        : 'Tambahkan periode tahun pelajaran baru.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-md">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isEdit ? 'edit_calendar' : 'calendar_add_on'}
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
                {isEdit && initialData?.code && (
                    <div className="flex items-center justify-between rounded-xl bg-surface-container-low px-3 py-2.5">
                        <span className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Kode
                        </span>
                        <span className="font-mono text-sm font-semibold text-on-surface">
                            {initialData.code}
                        </span>
                    </div>
                )}

                <div>
                    <Input
                        id="academic-year-name"
                        label="Nama Tahun Pelajaran"
                        type="text"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        placeholder="Contoh: 2024/2025"
                        error={errors.name}
                        maxLength={20}
                        autoFocus
                    />
                    <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                        Gunakan format YYYY/YYYY dengan tahun akhir satu tahun setelah tahun awal.
                    </p>
                </div>

                <Select
                    id="academic-year-semester"
                    label="Semester"
                    value={data.semester}
                    onChange={(event) => setData('semester', event.target.value)}
                    error={errors.semester}
                >
                    {semesterOptions.map((semester) => (
                        <option key={semester.value} value={semester.value}>
                            {semester.label}
                        </option>
                    ))}
                </Select>

                {!isEdit && (
                    <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                        <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                            info
                        </span>
                        <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                            Tahun pelajaran baru dibuat dengan status{' '}
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
        </Modal>
    );
}
