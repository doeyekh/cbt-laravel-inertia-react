import React, { useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    school_id: '',
    academic_year_id: '',
    class_ids: [],
};

export default function SyncFromClassModal({
    isOpen,
    onClose,
    schools = [],
    classes = [],
    academicYear = null,
    defaultSchoolId = '',
}) {
    const { data, setData, post, processing, errors, clearErrors, setError } =
        useForm(emptyForm);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();

        setData({
            ...emptyForm,
            school_id: defaultSchoolId ? String(defaultSchoolId) : '',
            academic_year_id: academicYear?.id
                ? String(academicYear.id)
                : '',
        });
    }, [isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    const filteredClasses = useMemo(
        () =>
            classes.filter(
                (item) =>
                    (!data.school_id ||
                        String(item.school_id) === String(data.school_id)) &&
                    (!data.academic_year_id ||
                        String(item.academic_year_id) ===
                            String(data.academic_year_id))
            ),
        [classes, data.school_id, data.academic_year_id]
    );

    const handleSchoolChange = (event) => {
        setData({
            school_id: event.target.value,
            academic_year_id: data.academic_year_id,
            class_ids: [],
        });

        clearErrors('school_id', 'class_ids');
    };

    const handleToggleClass = (classId) => {
        setData(
            'class_ids',
            data.class_ids.includes(classId)
                ? data.class_ids.filter((id) => id !== classId)
                : [...data.class_ids, classId]
        );

        if (errors.class_ids) {
            clearErrors('class_ids');
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!data.academic_year_id) {
            nextErrors.academic_year_id = 'Tahun pelajaran wajib dipilih.';
        }

        if (data.class_ids.length === 0) {
            nextErrors.class_ids = 'Pilih minimal satu kelas.';
        }

        if (Object.keys(nextErrors).length > 0) {
            Object.entries(nextErrors).forEach(([field, message]) =>
                setError(field, message)
            );

            return;
        }

        post(route('admin.exam-participants.sync'), {
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
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-2xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        group_add
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Tarik Peserta dari Kelas
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Seluruh anggota kelas terpilih menjadi peserta ujian pada
                        tahun pelajaran aktif.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        id="sync-school"
                        label="Sekolah"
                        value={data.school_id}
                        onChange={handleSchoolChange}
                        error={errors.school_id}
                    >
                        <option value="" disabled>
                            Pilih sekolah
                        </option>
                        {schools.map((school) => (
                            <option key={school.id} value={school.id}>
                                {school.name}
                            </option>
                        ))}
                    </Select>

                    <div className="self-end">
                        <div className="rounded-xl bg-surface-container-low px-4 py-2.5">
                            <p className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                Tahun Pelajaran Aktif
                            </p>
                            <p className="text-sm font-body font-semibold text-on-surface mt-0.5">
                                {academicYear?.name ?? 'Belum ada tahun aktif'}
                            </p>
                        </div>

                        {errors.academic_year_id && (
                            <p className="text-[11px] font-body text-error mt-1.5">
                                {errors.academic_year_id}
                            </p>
                        )}
                    </div>
                </div>

                <div>
                    <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant mb-2">
                        Kelas
                    </p>

                    {filteredClasses.length === 0 ? (
                        <div className="rounded-xl bg-surface-container-low px-4 py-3">
                            <p className="text-xs text-on-surface-variant font-body">
                                Tidak ada kelas pada sekolah dan tahun pelajaran
                                aktif.
                            </p>
                        </div>
                    ) : (
                        <div className="max-h-64 overflow-y-auto rounded-xl border border-outline-variant/40 divide-y divide-outline-variant/30">
                            {filteredClasses.map((schoolClass) => (
                                <label
                                    key={schoolClass.id}
                                    className="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-primary/5 transition-colors"
                                >
                                    <input
                                        type="checkbox"
                                        checked={data.class_ids.includes(
                                            schoolClass.id
                                        )}
                                        onChange={() =>
                                            handleToggleClass(schoolClass.id)
                                        }
                                        className="h-4 w-4 rounded border-outline text-primary focus:ring-primary"
                                    />
                                    <span className="min-w-0">
                                        <span className="block text-sm font-body font-medium text-on-surface">
                                            {schoolClass.name}
                                        </span>
                                        <span className="block text-[10px] font-mono text-on-surface-variant">
                                            {schoolClass.code}
                                        </span>
                                    </span>
                                </label>
                            ))}
                        </div>
                    )}

                    {errors.class_ids && (
                        <p className="text-[11px] font-body text-error mt-1.5">
                            {errors.class_ids}
                        </p>
                    )}
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
                        Tarik Peserta
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
