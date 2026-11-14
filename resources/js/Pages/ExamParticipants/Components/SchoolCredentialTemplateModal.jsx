import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const PLACEHOLDERS = [
    '{no}',
    '{nis}',
    '{nisn}',
    '{kelas}',
    '{ruang}',
    '{sesi}',
    '{tahun}',
    '{tanggal}',
];

const emptyForm = {
    school_id: '',
    username_template: '{nis}',
    participant_number_template: '{ruang}-{no}',
    number_width: 3,
};

export default function SchoolCredentialTemplateModal({
    isOpen,
    onClose,
    schools = [],
    defaultSchoolId = '',
}) {
    const { data, setData, patch, processing, errors, clearErrors, setError } =
        useForm(emptyForm);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();

        const schoolId = defaultSchoolId
            ? String(defaultSchoolId)
            : schools[0]
              ? String(schools[0].id)
              : '';

        const school =
            schools.find((item) => String(item.id) === schoolId) ?? null;

        setData({
            school_id: schoolId,
            username_template: school?.username_template ?? '{nis}',
            participant_number_template:
                school?.participant_number_template ?? '{ruang}-{no}',
            number_width: String(school?.number_width ?? 3),
        });
    }, [isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    const handleSchoolChange = (event) => {
        const schoolId = event.target.value;
        const school = schools.find(
            (item) => String(item.id) === String(schoolId)
        );

        setData('school_id', schoolId);

        if (school) {
            setData({
                school_id: schoolId,
                username_template: school.username_template ?? '{nis}',
                participant_number_template:
                    school.participant_number_template ?? '{ruang}-{no}',
                number_width: String(school.number_width ?? 3),
            });
        }

        clearErrors();
    };

    const appendPlaceholder = (field, placeholder) => {
        setData(field, `${data[field]}${placeholder}`);

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

        if (!data.username_template.trim()) {
            nextErrors.username_template = 'Template username wajib diisi.';
        }

        if (!data.participant_number_template.trim()) {
            nextErrors.participant_number_template =
                'Template nomor peserta wajib diisi.';
        }

        if (Object.keys(nextErrors).length > 0) {
            Object.entries(nextErrors).forEach(([field, message]) =>
                setError(field, message)
            );

            return;
        }

        patch(
            route('admin.schools.credential-template', data.school_id),
            {
                preserveScroll: true,
                onSuccess: () => onClose(),
            }
        );
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
                        tune
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Atur Template Kredensial
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Template berlaku untuk seluruh peserta pada sekolah
                        terpilih.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <Select
                    id="credential-template-school"
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

                <div className="space-y-4">
                    <div>
                        <Input
                            id="credential-username-template"
                            label="Template Username"
                            type="text"
                            value={data.username_template}
                            onChange={(event) =>
                                setData('username_template', event.target.value)
                            }
                            error={errors.username_template}
                            maxLength={120}
                        />

                        <div className="flex flex-wrap items-center gap-1.5 mt-2">
                            <span className="text-[10px] font-label text-on-surface-variant">
                                Placeholder:
                            </span>
                            {PLACEHOLDERS.map((placeholder) => (
                                <button
                                    key={`username-${placeholder}`}
                                    type="button"
                                    onClick={() =>
                                        appendPlaceholder(
                                            'username_template',
                                            placeholder
                                        )
                                    }
                                    className="inline-flex rounded-lg bg-surface-container px-2 py-0.5 font-mono text-[10px] font-semibold text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
                                >
                                    {placeholder}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <Input
                            id="credential-number-template"
                            label="Template Nomor Peserta"
                            type="text"
                            value={data.participant_number_template}
                            onChange={(event) =>
                                setData(
                                    'participant_number_template',
                                    event.target.value
                                )
                            }
                            error={errors.participant_number_template}
                            maxLength={120}
                        />

                        <div className="flex flex-wrap items-center gap-1.5 mt-2">
                            <span className="text-[10px] font-label text-on-surface-variant">
                                Placeholder:
                            </span>
                            {PLACEHOLDERS.map((placeholder) => (
                                <button
                                    key={`number-${placeholder}`}
                                    type="button"
                                    onClick={() =>
                                        appendPlaceholder(
                                            'participant_number_template',
                                            placeholder
                                        )
                                    }
                                    className="inline-flex rounded-lg bg-surface-container px-2 py-0.5 font-mono text-[10px] font-semibold text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
                                >
                                    {placeholder}
                                </button>
                            ))}
                        </div>
                    </div>

                    <Input
                        id="credential-number-width"
                        label="Lebar Nomor"
                        type="number"
                        min={1}
                        max={6}
                        value={data.number_width}
                        onChange={(event) =>
                            setData('number_width', event.target.value)
                        }
                        error={errors.number_width}
                    />
                </div>

                <div className="rounded-xl bg-surface-container-low px-4 py-3">
                    <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                        Keterangan
                    </p>
                    <p className="text-xs font-body text-on-surface-variant mt-1.5">
                        Perubahan template hanya berlaku pada generate
                        berikutnya. Kredensial yang sudah dibangkitkan tidak
                        berubah kecuali dicentang &quot;Timpa kredensial yang
                        sudah ada&quot;.
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
                        Simpan Template
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
