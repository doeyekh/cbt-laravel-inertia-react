import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';

const emptyForm = {
    subject_id: '',
    teacher_id: '',
};

export default function SubjectTeacherModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    subjects = [],
    teachers = [],
    schoolClassId = null,
}) {
    const { data, setData, post, put, processing, errors, clearErrors } = useForm(emptyForm);
    const [clientErrors, setClientErrors] = useState({});

    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setClientErrors({});

        if (isEdit && initialData) {
            setData({
                subject_id: initialData.subject_id?.toString() ?? '',
                teacher_id: initialData.teacher_id?.toString() ?? '',
            });
        } else {
            setData({ subject_id: '', teacher_id: '' });
        }
    }, [isOpen, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

    const fieldError = (field) => clientErrors[field] ?? errors[field];

    const setField = (field, value) => {
        setData({ ...data, [field]: value });

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

        if (!isEdit && !data.subject_id) {
            nextErrors.subject_id = 'Mata pelajaran wajib dipilih.';
        }

        if (!data.teacher_id) {
            nextErrors.teacher_id = 'Guru pengampuh wajib dipilih.';
        }

        if (Object.keys(nextErrors).length > 0) {
            setClientErrors(nextErrors);

            return;
        }

        setData({
            ...data,
            subject_id: data.subject_id || null,
            teacher_id: data.teacher_id || null,
        });

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(
                route('admin.subject-teachers.update', {
                    schoolClass: schoolClassId,
                    subjectTeacher: initialData.id,
                }),
                options
            );

            return;
        }

        post(route('admin.subject-teachers.store', { schoolClass: schoolClassId }), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Ubah Guru Pengampuh' : 'Tentukan Guru Pengampuh';
    const subtitle = isEdit
        ? 'Ubah guru yang mengampuh mata pelajaran ini di kelas tersebut.'
        : 'Pilih mata pelajaran dan guru yang mengampuh di kelas tersebut.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        supervisor_account
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
                {isEdit ? (
                    <Input
                        id="subject-teacher-subject"
                        label="Mata Pelajaran"
                        type="text"
                        value={initialData?.subject_name ?? ''}
                        disabled
                        readOnly
                    />
                ) : (
                    <Select
                        id="subject-teacher-subject"
                        label="Mata Pelajaran"
                        value={data.subject_id ?? ''}
                        onChange={(event) => setField('subject_id', event.target.value)}
                        error={fieldError('subject_id')}
                        placeholder="Pilih mata pelajaran..."
                    >
                        {subjects.map((subject) => (
                            <option key={subject.id} value={subject.id.toString()}>
                                {subject.code ? `${subject.code} · ${subject.name}` : subject.name}
                            </option>
                        ))}
                    </Select>
                )}

                <Select
                    id="subject-teacher-teacher"
                    label="Guru Pengampuh"
                    value={data.teacher_id ?? ''}
                    onChange={(event) => setField('teacher_id', event.target.value)}
                    error={fieldError('teacher_id')}
                    placeholder="Pilih guru..."
                >
                    {teachers.map((teacher) => (
                        <option key={teacher.id} value={teacher.id.toString()}>
                            {teacher.name}
                            {teacher.email ? ` (${teacher.email})` : ''}
                        </option>
                    ))}
                </Select>

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
