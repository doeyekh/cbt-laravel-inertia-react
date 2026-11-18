import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    class_id: '',
    subject_id: '',
    question_bank_id: '',
    session_id: '',
    room_id: '',
    exam_date: '',
    duration_minutes: 90,
    open_time: '07:00:00',
    close_time: '08:30:00',
    shuffle_questions: true,
    shuffle_options: true,
    show_score_to_student: true,
};

const toTimeValue = (value) => {
    if (!value) {
        return '';
    }

    return /^\d{1,2}:\d{2}$/.test(value) ? `${value}:00` : value;
};

const settings = [
    {
        field: 'shuffle_questions',
        label: 'Soal diacak',
        hint: 'Urutan nomor soal diacak untuk setiap peserta.',
    },
    {
        field: 'shuffle_options',
        label: 'Pilihan jawaban diacak',
        hint: 'Urutan pilihan jawaban diacak untuk setiap peserta.',
    },
    {
        field: 'show_score_to_student',
        label: 'Nilai ditampilkan ke siswa',
        hint: 'Peserta dapat melihat nilainya setelah ujian selesai.',
    },
];

export default function ExamScheduleModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    examPackage,
    classes = [],
    subjectsByClass = {},
    sessions = [],
    rooms = [],
}) {
    const { data, setData, post, put, processing, errors, clearErrors, setError } =
        useForm(emptyForm);
    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();

        if (initialData) {
            setData({
                class_id: String(initialData.class_id ?? ''),
                subject_id: String(initialData.subject_id ?? ''),
                question_bank_id: String(initialData.question_bank_id ?? ''),
                session_id: String(initialData.session_id ?? ''),
                room_id: String(initialData.room_id ?? ''),
                exam_date: initialData.exam_date ?? '',
                duration_minutes: initialData.duration_minutes ?? 90,
                open_time: toTimeValue(initialData.open_time) || emptyForm.open_time,
                close_time: toTimeValue(initialData.close_time) || emptyForm.close_time,
                shuffle_questions: Boolean(initialData.shuffle_questions),
                shuffle_options: Boolean(initialData.shuffle_options),
                show_score_to_student: Boolean(initialData.show_score_to_student),
            });

            return;
        }

        const firstClassId = classes[0]?.id ? String(classes[0].id) : '';
        const firstSubjects = firstClassId
            ? (subjectsByClass[firstClassId] ?? [])
            : [];

        setData({
            ...emptyForm,
            class_id: firstClassId,
            subject_id: firstSubjects[0]?.id ? String(firstSubjects[0].id) : '',
            question_bank_id: firstSubjects[0]?.question_banks?.[0]?.id
                ? String(firstSubjects[0].question_banks[0].id)
                : '',
            exam_date: examPackage?.start_date ?? '',
            open_time: emptyForm.open_time,
            close_time: emptyForm.close_time,
        });
    }, [isOpen, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

    const availableSubjects = subjectsByClass[String(data.class_id)] ?? [];
    const selectedSubject = availableSubjects.find(
        (subject) => String(subject.id) === String(data.subject_id),
    );
    const availableQuestionBanks = selectedSubject?.question_banks ?? [];

    const setField = (field, value) => {
        setData(field, value);

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleClassChange = (event) => {
        const classId = event.target.value;
        const options = subjectsByClass[classId] ?? [];
        const next = { class_id: classId };
        let nextSubject = options.find(
            (subject) => String(subject.id) === String(data.subject_id),
        );

        if (!nextSubject) {
            nextSubject = options[0];
            next.subject_id = nextSubject?.id ? String(nextSubject.id) : '';
        }

        const nextBanks = nextSubject?.question_banks ?? [];
        if (!nextBanks.some((bank) => String(bank.id) === String(data.question_bank_id))) {
            next.question_bank_id = nextBanks[0]?.id ? String(nextBanks[0].id) : '';
        }

        setData(next);
        clearErrors(['class_id', 'subject_id', 'question_bank_id']);
    };

    const handleSubjectChange = (event) => {
        const subjectId = event.target.value;
        const subject = availableSubjects.find(
            (item) => String(item.id) === String(subjectId),
        );

        setData({
            subject_id: subjectId,
            question_bank_id: subject?.question_banks?.[0]?.id
                ? String(subject.question_banks[0].id)
                : '',
        });
        clearErrors(['subject_id', 'question_bank_id']);
    };

    

    const handleSubmit = (event) => {
        event.preventDefault();

        const nextErrors = {};

        if (!data.class_id) {
            nextErrors.class_id = 'Kelas wajib dipilih.';
        }

        if (!data.subject_id) {
            nextErrors.subject_id = 'Mata pelajaran wajib dipilih.';
        }

        if (!data.question_bank_id) {
            nextErrors.question_bank_id = 'Bank soal wajib dipilih.';
        }

        if (!data.exam_date) {
            nextErrors.exam_date = 'Tanggal ujian wajib diisi.';
        } else if (
            examPackage?.start_date &&
            examPackage?.end_date &&
            (data.exam_date < examPackage.start_date ||
                data.exam_date > examPackage.end_date)
        ) {
            nextErrors.exam_date =
                'Tanggal ujian harus berada di dalam periode paket ujian.';
        }

        if (!data.duration_minutes || Number(data.duration_minutes) < 1) {
            nextErrors.duration_minutes = 'Durasi ujian minimal 1 menit.';
        }

        if (!data.open_time) {
            nextErrors.open_time = 'Jam soal dibuka wajib diisi.';
        }

        if (!data.close_time) {
            nextErrors.close_time = 'Jam soal ditutup wajib diisi.';
        } else if (data.open_time && data.close_time <= data.open_time) {
            nextErrors.close_time =
                'Jam soal ditutup harus setelah jam soal dibuka.';
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
            put(
                route('admin.exam-schedules.update', {
                    examPackage: examPackage.id,
                    examSchedule: initialData.id,
                }),
                options
            );

            return;
        }

        post(
            route('admin.exam-schedules.store', { examPackage: examPackage.id }),
            options
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
                        {isEdit ? 'edit_calendar' : 'calendar_add_on'}
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        {isEdit ? 'Edit Jadwal Ujian' : 'Tambah Jadwal Ujian'}
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Atur kelas, mata pelajaran, tanggal, jam soal, dan
                        pengaturan ujian.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                {errors.exam_package && (
                    <div className="rounded-xl bg-error-container/40 px-3 py-2.5 text-xs font-body text-on-error-container">
                        {errors.exam_package}
                    </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        id="exam-schedule-class"
                        label="Kelas"
                        value={data.class_id}
                        onChange={handleClassChange}
                        error={errors.class_id}
                        placeholder="Pilih kelas..."
                    >
                        {classes.map((schoolClass) => (
                            <option key={schoolClass.id} value={String(schoolClass.id)}>
                                {schoolClass.name} ({schoolClass.code})
                            </option>
                        ))}
                    </Select>

                    <div>
                        <Select
                            id="exam-schedule-subject"
                            label="Mata Pelajaran"
                            value={data.subject_id}
                            onChange={handleSubjectChange}
                            error={errors.subject_id}
                            placeholder="Pilih mata pelajaran..."
                        >
                            {availableSubjects.map((subject) => (
                                <option key={subject.id} value={String(subject.id)}>
                                    {subject.name} ({subject.code})
                                </option>
                            ))}
                        </Select>
                        {availableSubjects.length === 0 && (
                            <p className="mt-1.5 text-[10px] text-on-surface-variant font-body">
                                Belum ada bank soal untuk mata pelajaran di kelas ini.
                                Tetapkan bank soal ke kelas tersebut terlebih dahulu.
                            </p>
                        )}
                    </div>
                </div>

                <Select
                    id="exam-schedule-question-bank"
                    label="Bank Soal"
                    value={data.question_bank_id}
                    onChange={(event) => setField('question_bank_id', event.target.value)}
                    error={errors.question_bank_id}
                    placeholder="Pilih bank soal..."
                >
                    {availableQuestionBanks.map((questionBank) => (
                        <option key={questionBank.id} value={String(questionBank.id)}>
                            {questionBank.code} — {questionBank.name}
                        </option>
                    ))}
                </Select>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        id="exam-schedule-session"
                        label="Sesi Ujian"
                        value={data.session_id}
                        onChange={(event) => setField('session_id', event.target.value)}
                        placeholder="Pilih sesi ujian..."
                    >
                        {sessions.map((session) => (
                            <option key={session.id} value={String(session.id)}>
                                {session.code} — {session.name} ({session.start_time?.slice(0, 5)} s.d. {session.end_time?.slice(0, 5)})
                            </option>
                        ))}
                    </Select>

                    <Select
                        id="exam-schedule-room"
                        label="Ruangan Ujian"
                        value={data.room_id}
                        onChange={(event) => setField('room_id', event.target.value)}
                        placeholder="Pilih ruangan ujian..."
                    >
                        {rooms.map((room) => (
                            <option key={room.id} value={String(room.id)}>
                                {room.code} — {room.name}
                                {room.building && ` (${room.building})`}
                                {room.floor && ` Lantai ${room.floor}`}
                            </option>
                        ))}
                    </Select>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <Input
                            id="exam-schedule-date"
                            label="Tanggal Ujian"
                            type="date"
                            value={data.exam_date}
                            min={examPackage?.start_date}
                            max={examPackage?.end_date}
                            onChange={(event) =>
                                setField('exam_date', event.target.value)
                            }
                            error={errors.exam_date}
                        />
                        <p className="mt-1.5 text-[10px] text-on-surface-variant font-body">
                            Periode paket: {examPackage?.start_date} s.d.{' '}
                            {examPackage?.end_date}
                        </p>
                    </div>

                    <Input
                        id="exam-schedule-duration"
                        label="Durasi (Menit)"
                        type="number"
                        min="1"
                        max="600"
                        value={data.duration_minutes}
                        onChange={(event) =>
                            setField('duration_minutes', event.target.value)
                        }
                        error={errors.duration_minutes}
                    />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        id="exam-schedule-open-time"
                        label="Soal Dibuka"
                        type="time"
                        value={data.open_time?.slice(0, 5) ?? ''}
                        onChange={(event) =>
                            setField('open_time', toTimeValue(event.target.value))
                        }
                        error={errors.open_time}
                    />

                    <Input
                        id="exam-schedule-close-time"
                        label="Soal Ditutup"
                        type="time"
                        value={data.close_time?.slice(0, 5) ?? ''}
                        onChange={(event) =>
                            setField('close_time', toTimeValue(event.target.value))
                        }
                        error={errors.close_time}
                    />
                </div>

                <div className="rounded-xl bg-surface-container-low/70 border border-outline-variant/30 px-4 py-3.5 space-y-3">
                    <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                        Pengaturan Soal
                    </p>

                    {settings.map((setting) => (
                        <label
                            key={setting.field}
                            className="flex items-start gap-2.5 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                className="mt-0.5 h-4 w-4 shrink-0 rounded border-outline text-primary focus:ring-primary/30"
                                checked={Boolean(data[setting.field])}
                                onChange={(event) =>
                                    setField(setting.field, event.target.checked)
                                }
                            />
                            <span className="min-w-0">
                                <span className="block font-body text-sm text-on-surface">
                                    {setting.label}
                                </span>
                                <span className="block text-[11px] text-on-surface-variant font-body">
                                    {setting.hint}
                                </span>
                            </span>
                        </label>
                    ))}
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
                        {isEdit ? 'Perbarui' : 'Simpan'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
