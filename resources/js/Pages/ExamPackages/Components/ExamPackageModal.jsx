import React, { useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import Button from '@/Components/UI/Button';

const today = () => new Date().toISOString().slice(0, 10);
const nextWeek = () => {
    const date = new Date();
    date.setDate(date.getDate() + 7);

    return date.toISOString().slice(0, 10);
};

const emptyForm = {
    school_id: '',
    academic_year_id: '',
    exam_type_id: '',
    code: '',
    name: '',
    description: '',
    start_date: today(),
    end_date: nextWeek(),
    status: 'draft',
};

const MAX_CODE_LENGTH = 30;
const MAX_NAME_LENGTH = 150;
const MAX_DESCRIPTION_LENGTH = 500;
const CODE_PATTERN = /^[A-Z0-9-]+$/;

export default function ExamPackageModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    schools = [],
    academicYear = null,
    examTypes = [],
    statuses = [],
}) {
    const { data, setData, post, put, processing, errors, clearErrors, setError } =
        useForm(emptyForm);
    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);

    const academicYearValue = academicYear?.id ? String(academicYear.id) : '';

    const availableExamTypes = useMemo(() => {
        const schoolExamTypes = examTypes.filter(
            (examType) =>
                !data.school_id ||
                String(examType.school_id) === String(data.school_id)
        );

        const activeExamTypes = schoolExamTypes.filter(
            (examType) => examType.is_active
        );

        const currentExamType = schoolExamTypes.find(
            (examType) =>
                String(examType.id) === String(initialData?.exam_type_id)
        );

        if (currentExamType && !currentExamType.is_active) {
            return [...activeExamTypes, currentExamType];
        }

        return activeExamTypes;
    }, [examTypes, data.school_id, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

    const selectedExamType = examTypes.find(
        (examType) => String(examType.id) === String(data.exam_type_id)
    );
    const isSelectedExamTypeActive = selectedExamType
        ? Boolean(selectedExamType.is_active)
        : true;

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
                      academic_year_id: academicYearValue,
                      exam_type_id: initialData.exam_type_id
                          ? String(initialData.exam_type_id)
                          : '',
                      code: initialData.code ?? '',
                      name: initialData.name ?? '',
                      description: initialData.description ?? '',
                      start_date: initialData.start_date ?? today(),
                      end_date: initialData.end_date ?? nextWeek(),
                      status: initialData.status ?? 'draft',
                  }
                : {
                      ...emptyForm,
                      school_id: schools[0]?.id ? String(schools[0].id) : '',
                      academic_year_id: academicYearValue,
                      start_date: today(),
                      end_date: nextWeek(),
                  }
        );
    }, [isOpen, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

    const setField = (field, value) => {
        setData(field, value);

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSchoolChange = (value) => {
        setField('school_id', value);

        if (
            data.exam_type_id &&
            !examTypes.some(
                (examType) =>
                    String(examType.id) === String(data.exam_type_id) &&
                    String(examType.school_id) === String(value)
            )
        ) {
            setField('exam_type_id', '');
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

        if (!data.academic_year_id) {
            nextErrors.academic_year_id = 'Belum ada tahun pelajaran aktif.';
        }

        if (!data.exam_type_id) {
            nextErrors.exam_type_id = 'Jenis ujian wajib dipilih.';
        }

        if (!code) {
            nextErrors.code = 'Kode paket wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code =
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Nama paket wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama paket maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (data.description.length > MAX_DESCRIPTION_LENGTH) {
            nextErrors.description = `Deskripsi maksimal ${MAX_DESCRIPTION_LENGTH} karakter.`;
        }

        if (!data.start_date) {
            nextErrors.start_date = 'Tanggal mulai wajib diisi.';
        }

        if (!data.end_date) {
            nextErrors.end_date = 'Tanggal selesai wajib diisi.';
        } else if (data.start_date && data.end_date < data.start_date) {
            nextErrors.end_date =
                'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
        }

        if (!data.status) {
            nextErrors.status = 'Status paket wajib dipilih.';
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
            put(route('admin.exam-packages.update', initialData.id), options);

            return;
        }

        post(route('admin.exam-packages.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Edit Paket Ujian' : 'Tambah Paket Ujian';
    const subtitle = isEdit
        ? 'Perbarui informasi paket ujian. Sekolah tidak dapat diubah.'
        : 'Buat event ujian yang akan menampung beberapa jadwal.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-2xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isEdit ? 'edit_note' : 'inventory_2'}
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
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <Select
                            id="exam-package-school"
                            label="Sekolah"
                            value={data.school_id}
                            onChange={(event) => handleSchoolChange(event.target.value)}
                            error={errors.school_id}
                            disabled={isEdit || schools.length === 0}
                        >
                            <option value="" disabled>
                                Pilih sekolah
                            </option>
                            {schools.map((school) => (
                                <option key={school.id} value={String(school.id)}>
                                    {school.name} ({school.code})
                                </option>
                            ))}
                        </Select>
                        {isEdit && (
                            <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                                Sekolah tidak dapat diubah setelah paket dibuat.
                            </p>
                        )}
                    </div>

                    <div>
                        <p className="block text-[11px] font-label font-semibold text-on-surface-variant mb-1.5 uppercase tracking-wider">
                            Tahun Pelajaran
                        </p>
                        <div className="rounded-xl bg-surface-container-low px-3 py-2.5">
                            <p className="text-sm font-label font-medium text-on-surface">
                                {academicYear?.name ?? 'Belum ada tahun aktif'}
                            </p>
                            <p className="text-[10px] font-body text-on-surface-variant mt-0.5">
                                Mengikuti tahun pelajaran aktif
                            </p>
                        </div>
                        {errors.academic_year_id && (
                            <p className="mt-1 text-[10px] font-label text-error">
                                {errors.academic_year_id}
                            </p>
                        )}
                    </div>
                </div>

                <div>
                    <Select
                        id="exam-package-exam-type"
                        label="Jenis Ujian"
                        value={data.exam_type_id}
                        onChange={(event) => setField('exam_type_id', event.target.value)}
                        error={errors.exam_type_id}
                        placeholder="Pilih jenis ujian..."
                        disabled={!data.school_id}
                    >
                        {availableExamTypes.map((examType) => (
                            <option key={examType.id} value={String(examType.id)}>
                                {examType.name} ({examType.code})
                                {examType.is_active ? '' : ' - tidak aktif'}
                            </option>
                        ))}
                    </Select>
                    {data.school_id && availableExamTypes.length === 0 && (
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            Tidak ada jenis ujian aktif untuk sekolah ini.
                        </p>
                    )}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <Input
                            id="exam-package-code"
                            label="Kode Paket"
                            type="text"
                            value={data.code}
                            onChange={(event) =>
                                setField('code', event.target.value.toUpperCase())
                            }
                            placeholder="Contoh: PTS-2026"
                            error={errors.code}
                            maxLength={MAX_CODE_LENGTH}
                        />
                        <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                            Huruf kapital, angka, dan tanda hubung.
                        </p>
                    </div>

                    <Input
                        id="exam-package-name"
                        label="Nama Paket"
                        type="text"
                        value={data.name}
                        onChange={(event) => setField('name', event.target.value)}
                        placeholder="Contoh: PTS Ganjil 2026/2027"
                        error={errors.name}
                        maxLength={MAX_NAME_LENGTH}
                    />
                </div>

                <Textarea
                    id="exam-package-description"
                    label="Deskripsi"
                    value={data.description}
                    onChange={(event) => setField('description', event.target.value)}
                    placeholder="Deskripsi singkat paket ujian (opsional)"
                    error={errors.description}
                    rows={3}
                    maxLength={MAX_DESCRIPTION_LENGTH}
                />

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Input
                        id="exam-package-start-date"
                        label="Tanggal Mulai"
                        type="date"
                        value={data.start_date}
                        onChange={(event) => setField('start_date', event.target.value)}
                        error={errors.start_date}
                    />

                    <Input
                        id="exam-package-end-date"
                        label="Tanggal Selesai"
                        type="date"
                        value={data.end_date}
                        onChange={(event) => setField('end_date', event.target.value)}
                        error={errors.end_date}
                    />

                    <div>
                        <Select
                            id="exam-package-status"
                            label="Status"
                            value={data.status}
                            onChange={(event) => setField('status', event.target.value)}
                            error={errors.status}
                        >
                            {statuses.map((status) => (
                                <option
                                    key={status.value}
                                    value={status.value}
                                    disabled={
                                        !isSelectedExamTypeActive &&
                                        status.value !== 'draft'
                                    }
                                >
                                    {status.label}
                                </option>
                            ))}
                        </Select>
                        {!isSelectedExamTypeActive && (
                            <p className="mt-1.5 text-[10px] font-body text-tertiary">
                                Jenis ujian belum aktif, paket hanya dapat berstatus
                                draft.
                            </p>
                        )}
                    </div>
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
