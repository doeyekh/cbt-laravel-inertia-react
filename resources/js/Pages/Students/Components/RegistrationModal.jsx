import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    school_id: '',
    nis: '',
    entry_academic_year_id: '',
    exit_academic_year_id: '',
    status: 'aktif',
};

export default function RegistrationModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    studentId,
    schools = [],
    academicYears = [],
    isAdministrator = false,
}) {
    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm(emptyForm);
    const [clientErrors, setClientErrors] = useState({});

    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);

    const activeAcademicYear =
        academicYears.find((academicYear) => academicYear.is_active) ?? null;
    const hasActiveAcademicYear = Boolean(activeAcademicYear);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setClientErrors({});

        if (isEdit && initialData) {
            setData({
                school_id: String(initialData.school_id ?? ''),
                nis: initialData.nis ?? '',
                entry_academic_year_id: String(initialData.entry_academic_year_id ?? ''),
                exit_academic_year_id: String(initialData.exit_academic_year_id ?? ''),
                status: initialData.status ?? 'aktif',
            });

            return;
        }

        setData({
            school_id: schools[0]?.id ? String(schools[0].id) : '',
            nis: '',
            entry_academic_year_id: activeAcademicYear
                ? String(activeAcademicYear.id)
                : '',
            exit_academic_year_id: '',
            status: 'aktif',
        });
    }, [isOpen, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

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

        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!data.nis.trim()) {
            nextErrors.nis = 'NIS wajib diisi.';
        }

        if (!data.entry_academic_year_id && isEdit) {
            nextErrors.entry_academic_year_id = 'Tahun masuk wajib dipilih.';
        }

        if (!isEdit && !hasActiveAcademicYear) {
            nextErrors.academic_year =
                'Tahun pelajaran aktif belum diatur. Aktifkan tahun pelajaran terlebih dahulu.';
        }

        if (!data.status) {
            nextErrors.status = 'Status registrasi wajib dipilih.';
        }

        if (data.status !== 'aktif' && !data.exit_academic_year_id) {
            nextErrors.exit_academic_year_id = 'Tahun keluar wajib dipilih.';
        }

        if (Object.keys(nextErrors).length > 0) {
            setClientErrors(nextErrors);

            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(
                route('admin.student-registrations.update', {
                    student: studentId,
                    registration: initialData.id,
                }),
                options
            );

            return;
        }

        post(route('admin.student-registrations.store', { student: studentId }), options);
    };

    const handleStatusChange = (value) => {
        setField('status', value);

        if (value === 'aktif') {
            setField('exit_academic_year_id', '');
        }
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Edit Registrasi' : 'Tambah Registrasi';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isEdit ? 'edit_note' : 'badge'}
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        {title}
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Tentukan sekolah tempat siswa terdaftar dan statusnya.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <Select
                    id="registration-school"
                    label="Sekolah"
                    value={data.school_id}
                    onChange={(event) => setField('school_id', event.target.value)}
                    error={fieldError('school_id')}
                    disabled={!isAdministrator && schools.length <= 1}
                    placeholder="Pilih sekolah..."
                >
                    {schools.map((school) => (
                        <option key={school.id} value={String(school.id)}>
                            {school.is_active ? school.name : `${school.name} (Tidak Aktif)`}
                        </option>
                    ))}
                </Select>

                {isEdit ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Input
                            id="registration-nis"
                            label="NIS"
                            type="text"
                            value={data.nis}
                            onChange={(event) => setField('nis', event.target.value)}
                            placeholder="Nomor induk sekolah"
                            error={fieldError('nis')}
                            maxLength={30}
                        />

                        <Select
                            id="registration-entry-year"
                            label="Tahun Masuk"
                            value={data.entry_academic_year_id}
                            onChange={(event) =>
                                setField('entry_academic_year_id', event.target.value)
                            }
                            error={fieldError('entry_academic_year_id')}
                            placeholder="Pilih tahun pelajaran..."
                        >
                            {academicYears.map((academicYear) => (
                                <option key={academicYear.id} value={String(academicYear.id)}>
                                    {academicYear.name} · {academicYear.semester_label}
                                    {academicYear.is_active ? ' (Aktif)' : ''}
                                </option>
                            ))}
                        </Select>
                    </div>
                ) : (
                    <>
                        <Input
                            id="registration-nis"
                            label="NIS"
                            type="text"
                            value={data.nis}
                            onChange={(event) => setField('nis', event.target.value)}
                            placeholder="Nomor induk sekolah"
                            error={fieldError('nis')}
                            maxLength={30}
                        />

                        <div className="flex items-start gap-2.5 rounded-xl bg-primary/5 px-3 py-3">
                            <span className="material-symbols-outlined text-[18px] text-primary shrink-0">
                                calendar_month
                            </span>
                            <div className="min-w-0">
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Tahun Masuk
                                </p>
                                <p className="text-xs font-body text-on-surface mt-0.5">
                                    {hasActiveAcademicYear
                                        ? `${activeAcademicYear.name} · ${activeAcademicYear.semester_label} (otomatis mengikuti tahun pelajaran aktif)`
                                        : 'Tahun pelajaran aktif belum diatur.'}
                                </p>
                            </div>
                        </div>

                        {fieldError('academic_year') && (
                            <p className="text-[11px] font-label text-error">
                                {fieldError('academic_year')}
                            </p>
                        )}
                    </>
                )}

                <Select
                    id="registration-status"
                    label="Status Registrasi"
                    value={data.status}
                    onChange={(event) => handleStatusChange(event.target.value)}
                    error={fieldError('status')}
                >
                    <option value="aktif">Aktif</option>
                    <option value="lulus">Lulus</option>
                    <option value="pindah">Pindah</option>
                    <option value="keluar">Keluar</option>
                </Select>

                {data.status !== 'aktif' && (
                    <Select
                        id="registration-exit-year"
                        label="Tahun Keluar"
                        value={data.exit_academic_year_id}
                        onChange={(event) =>
                            setField('exit_academic_year_id', event.target.value)
                        }
                        error={fieldError('exit_academic_year_id')}
                        placeholder="Pilih tahun pelajaran..."
                    >
                        {academicYears.map((academicYear) => (
                            <option key={academicYear.id} value={String(academicYear.id)}>
                                {academicYear.name} · {academicYear.semester_label}
                                {academicYear.is_active ? ' (Aktif)' : ''}
                            </option>
                        ))}
                    </Select>
                )}

                <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                    <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                        info
                    </span>
                    <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                        Menyimpan status{' '}
                        <span className="font-semibold text-on-surface">Aktif</span> akan otomatis
                        mengubah registrasi aktif siswa lainnya menjadi{' '}
                        <span className="font-semibold text-on-surface">Pindah</span>.
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
