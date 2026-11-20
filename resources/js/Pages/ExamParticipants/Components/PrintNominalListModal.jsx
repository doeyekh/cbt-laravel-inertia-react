import React, { useEffect, useMemo, useState } from 'react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    school_id: '',
    academic_year_id: '',
    class_id: '',
};

export default function PrintNominalListModal({
    isOpen,
    onClose,
    schools = [],
    classes = [],
    academicYear = null,
    defaultSchoolId = '',
}) {
    const [data, setData] = useState(emptyForm);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        setErrors({});
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

    const setField = (field, value) => {
        setData((previous) => ({ ...previous, [field]: value }));

        if (errors[field]) {
            setErrors((previous) => {
                const next = { ...previous };
                delete next[field];

                return next;
            });
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

        if (!data.class_id) {
            nextErrors.class_id = 'Kelas wajib dipilih.';
        }

        if (Object.keys(nextErrors).length > 0) {
            setErrors(nextErrors);

            return;
        }

        window.open(
            route('admin.nominal-list.pdf', {
                school_id: data.school_id,
                academic_year_id: data.academic_year_id,
                class_id: data.class_id,
            }),
            '_blank',
            'noopener',
        );

        onClose();
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} maxWidth="max-w-xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        list_alt
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Cetak Daftar Nominatif
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Cetak daftar peserta per kelas dengan NAMA PESERTA,
                        USERNAME, SESI, dan RUANG.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        id="print-nominal-school"
                        label="Sekolah"
                        value={data.school_id}
                        onChange={(event) =>
                            setField('school_id', event.target.value)
                        }
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

                    <Select
                        id="print-nominal-class"
                        label="Kelas"
                        value={data.class_id}
                        onChange={(event) =>
                            setField('class_id', event.target.value)
                        }
                        error={errors.class_id}
                    >
                        <option value="" disabled>
                            Pilih kelas
                        </option>
                        {filteredClasses.map((schoolClass) => (
                            <option key={schoolClass.id} value={schoolClass.id}>
                                {schoolClass.name}
                            </option>
                        ))}
                    </Select>
                </div>

                <div className="flex items-center gap-3 justify-end pt-4">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={onClose}
                        className="flex-1"
                    >
                        Batal
                    </Button>
                    <Button type="submit">
                        <span className="material-symbols-outlined text-[18px]">
                            picture_as_pdf
                        </span>
                        Cetak PDF
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
