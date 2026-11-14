import React, { useEffect, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import Button from '@/Components/UI/Button';

const MAX_CODE_LENGTH = 30;
const MAX_NAME_LENGTH = 150;
const MAX_DESCRIPTION_LENGTH = 500;
const CODE_PATTERN = /^[A-Z0-9-]+$/;
const MIN_OPTION_COUNT = 3;
const MAX_OPTION_COUNT = 5;

const questionTypes = [
    {
        count: 'pilihan_ganda_count',
        score: 'pilihan_ganda_score',
        label: 'Pilihan Ganda',
        short: 'PG',
    },
    {
        count: 'pilihan_ganda_kompleks_count',
        score: 'pilihan_ganda_kompleks_score',
        label: 'Pilihan Kompleks',
        short: 'PK',
    },
    {
        count: 'menjodohkan_count',
        score: 'menjodohkan_score',
        label: 'Menjodohkan',
        short: 'MJ',
    },
    { count: 'isian_count', score: 'isian_score', label: 'Isian', short: 'IS' },
    { count: 'esai_count', score: 'esai_score', label: 'Esai', short: 'ES' },
];

const optionCountFields = [
    {
        name: 'pilihan_ganda_option_count',
        label: 'Pilihan Ganda',
        short: 'PG',
    },
    {
        name: 'pilihan_ganda_kompleks_option_count',
        label: 'Pilihan Kompleks',
        short: 'PK',
    },
];

const optionCountOptions = Array.from(
    { length: MAX_OPTION_COUNT - MIN_OPTION_COUNT + 1 },
    (_, index) => MIN_OPTION_COUNT + index
);

const emptyForm = {
    school_id: '',
    subject_id: '',
    exam_type_id: '',
    code: '',
    name: '',
    description: '',
    pilihan_ganda_count: 0,
    pilihan_ganda_kompleks_count: 0,
    menjodohkan_count: 0,
    isian_count: 0,
    esai_count: 0,
    pilihan_ganda_score: 50,
    pilihan_ganda_kompleks_score: 30,
    menjodohkan_score: 40,
    isian_score: 20,
    esai_score: 10,
    pilihan_ganda_option_count: 4,
    pilihan_ganda_kompleks_option_count: 4,
    class_ids: [],
    is_active: true,
};

export default function QuestionBankModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    schools = [],
    subjects = [],
    subjectOptions = [],
    examTypes = [],
    classes = [],
    academicYear = null,
}) {
    const { data, setData, post, put, processing, errors, clearErrors, setError } =
        useForm(emptyForm);
    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);
    const [classSearch, setClassSearch] = useState('');

    const availableClasses = useMemo(
        () =>
            classes.filter(
                (schoolClass) =>
                    (!data.school_id ||
                        String(schoolClass.school_id) === String(data.school_id)) &&
                    (!classSearch.trim() ||
                        schoolClass.name
                            .toLowerCase()
                            .includes(classSearch.trim().toLowerCase()) ||
                        schoolClass.code
                            .toLowerCase()
                            .includes(classSearch.trim().toLowerCase()))
            ),
        [classes, data.school_id, classSearch]
    );

    const mappedSubjectIds = useMemo(() => {
        const option = subjectOptions.find(
            (item) => String(item.school_id) === String(data.school_id)
        );

        return (option?.subject_ids ?? []).map((id) => String(id));
    }, [subjectOptions, data.school_id]);

    const selectableSubjects = useMemo(() => {
        const mapped = subjects.filter((subject) =>
            mappedSubjectIds.includes(String(subject.id))
        );

        const current = subjects.find(
            (subject) => String(subject.id) === String(data.subject_id)
        );

        return current && !mapped.some((subject) => subject.id === current.id)
            ? [current, ...mapped]
            : mapped;
    }, [subjects, mappedSubjectIds, data.subject_id]);

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

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();

        if (initialData) {
            setData({
                school_id: String(initialData.school_id ?? ''),
                subject_id: String(initialData.subject_id ?? ''),
                exam_type_id: initialData.exam_type_id
                    ? String(initialData.exam_type_id)
                    : '',
                code: initialData.code ?? '',
                name: initialData.name ?? '',
                description: initialData.description ?? '',
                pilihan_ganda_count: initialData.composition?.pilihan_ganda ?? 0,
                pilihan_ganda_kompleks_count:
                    initialData.composition?.pilihan_ganda_kompleks ?? 0,
                menjodohkan_count: initialData.composition?.menjodohkan ?? 0,
                isian_count: initialData.composition?.isian_singkat ?? 0,
                esai_count: initialData.composition?.esai ?? 0,
                pilihan_ganda_score: initialData.scores?.pilihan_ganda ?? 50,
                pilihan_ganda_kompleks_score:
                    initialData.scores?.pilihan_ganda_kompleks ?? 30,
                menjodohkan_score: initialData.scores?.menjodohkan ?? 40,
                isian_score: initialData.scores?.isian_singkat ?? 20,
                esai_score: initialData.scores?.esai ?? 10,
                pilihan_ganda_option_count:
                    initialData.option_counts?.pilihan_ganda ?? 4,
                pilihan_ganda_kompleks_option_count:
                    initialData.option_counts?.pilihan_ganda_kompleks ?? 4,
                class_ids: (initialData.class_ids ?? []).map((id) => String(id)),
                is_active: Boolean(initialData.is_active),
            });

            return;
        }

        setData({
            ...emptyForm,
            school_id: schools[0]?.id ? String(schools[0].id) : '',
        });
    }, [isOpen, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

    const setField = (field, value) => {
        setData(field, value);

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSchoolChange = (value) => {
        const mappedSubjectIds = (
            subjectOptions.find(
                (item) => String(item.school_id) === String(value)
            )?.subject_ids ?? []
        ).map((id) => String(id));

        setData({
            ...data,
            school_id: value,
            subject_id: mappedSubjectIds.includes(String(data.subject_id))
                ? data.subject_id
                : '',
            exam_type_id: '',
            class_ids: [],
        });

        clearErrors('school_id', 'subject_id', 'exam_type_id', 'class_ids');
    };

    const toggleClass = (classId) => {
        const selected = data.class_ids.map(String);
        const next = selected.includes(String(classId))
            ? selected.filter((id) => id !== String(classId))
            : [...selected, String(classId)];

        setData('class_ids', next);

        if (errors.class_ids) {
            clearErrors('class_ids');
        }
    };

    const totalQuestionCount = questionTypes.reduce(
        (total, type) => total + (Number(data[type.count]) || 0),
        0
    );

    const totalScore = questionTypes.reduce(
        (total, type) =>
            total +
            (Number(data[type.count]) || 0) * (Number(data[type.score]) || 0),
        0
    );

    const handleSubmit = (event) => {
        event.preventDefault();

        const code = data.code.trim().toUpperCase();
        const name = data.name.trim();
        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!data.subject_id) {
            nextErrors.subject_id = 'Mata pelajaran wajib dipilih.';
        }

        if (!code) {
            nextErrors.code = 'Kode bank soal wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code =
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Nama bank soal wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama bank soal maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (data.description.length > MAX_DESCRIPTION_LENGTH) {
            nextErrors.description = `Deskripsi maksimal ${MAX_DESCRIPTION_LENGTH} karakter.`;
        }

        questionTypes.forEach((type) => {
            const amount = Number(data[type.count]);

            if (Number.isNaN(amount) || amount < 0) {
                nextErrors[type.count] = 'Jumlah soal minimal 0.';
            }

            const score = Number(data[type.score]);

            if (data[type.score] === '' || Number.isNaN(score) || score < 0) {
                nextErrors[type.score] = 'Bobot soal minimal 0.';
            }
        });

        optionCountFields.forEach((field) => {
            const amount = Number(data[field.name]);

            if (
                Number.isNaN(amount) ||
                amount < MIN_OPTION_COUNT ||
                amount > MAX_OPTION_COUNT
            ) {
                nextErrors[field.name] = `Jumlah pilihan jawaban antara ${MIN_OPTION_COUNT} sampai ${MAX_OPTION_COUNT}.`;
            }
        });

        if (totalQuestionCount === 0) {
            nextErrors.composition =
                'Minimal satu jenis soal harus memiliki jumlah lebih dari 0.';
        }

        if (data.class_ids.length === 0) {
            nextErrors.class_ids = 'Minimal satu kelas wajib dipilih.';
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
            put(route('admin.question-banks.update', initialData.id), options);

            return;
        }

        post(route('admin.question-banks.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Edit Bank Soal' : 'Tambah Bank Soal';
    const subtitle = isEdit
        ? 'Perbarui komposisi soal, bobot, dan kelas yang berlaku.'
        : 'Tentukan komposisi soal, bobot, dan kelas yang menggunakan bank soal ini.';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-4xl">
            <div className="flex items-start gap-3 mb-5">
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

            <form onSubmit={handleSubmit} className="space-y-5">
                <div className="flex items-center justify-between gap-3 rounded-xl bg-surface-container-low px-4 py-2.5">
                    <div>
                        <p className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Tahun Pelajaran Aktif
                        </p>
                        <p className="text-sm font-body font-semibold text-on-surface mt-0.5">
                            {academicYear?.name ?? 'Belum ada tahun aktif'}
                        </p>
                    </div>
                    <span className="material-symbols-outlined text-on-surface-variant text-[20px]">
                        calendar_month
                    </span>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <Select
                            id="question-bank-school"
                            label="Sekolah"
                            value={data.school_id}
                            onChange={(event) =>
                                handleSchoolChange(event.target.value)
                            }
                            error={errors.school_id}
                            disabled={isEdit || schools.length === 0}
                            placeholder="Pilih sekolah..."
                        >
                            {schools.map((school) => (
                                <option
                                    key={school.id}
                                    value={String(school.id)}
                                >
                                    {school.name} ({school.code})
                                </option>
                            ))}
                        </Select>
                        {isEdit && (
                            <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                                Sekolah tidak dapat diubah setelah bank soal
                                dibuat.
                            </p>
                        )}
                    </div>

                    <div>
                        <Select
                            id="question-bank-subject"
                            label="Mata Pelajaran"
                            value={data.subject_id}
                            onChange={(event) =>
                                setField('subject_id', event.target.value)
                            }
                            error={errors.subject_id}
                            placeholder="Pilih mata pelajaran..."
                            disabled={!data.school_id}
                        >
                            {selectableSubjects.map((subject) => (
                                <option
                                    key={subject.id}
                                    value={String(subject.id)}
                                >
                                    {subject.name} ({subject.code})
                                </option>
                            ))}
                        </Select>
                        {data.school_id && selectableSubjects.length === 0 && (
                            <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                                Tidak ada mata pelajaran yang dipetakan ke kelas
                                sekolah ini pada tahun pelajaran aktif.
                            </p>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        id="question-bank-code"
                        label="Kode Bank Soal"
                        type="text"
                        value={data.code}
                        onChange={(event) =>
                            setField('code', event.target.value.toUpperCase())
                        }
                        placeholder="Contoh: BSK-MAT-2026"
                        error={errors.code}
                        maxLength={MAX_CODE_LENGTH}
                    />

                    <Input
                        id="question-bank-name"
                        label="Nama Bank Soal"
                        type="text"
                        value={data.name}
                        onChange={(event) => setField('name', event.target.value)}
                        placeholder="Contoh: Bank Soal Matematika"
                        error={errors.name}
                        maxLength={MAX_NAME_LENGTH}
                    />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <Select
                            id="question-bank-exam-type"
                            label="Jenis Ujian"
                            value={data.exam_type_id}
                            onChange={(event) =>
                                setField('exam_type_id', event.target.value)
                            }
                            error={errors.exam_type_id}
                            placeholder="Tanpa jenis ujian"
                            disabled={!data.school_id}
                        >
                            {availableExamTypes.map((examType) => (
                                <option
                                    key={examType.id}
                                    value={String(examType.id)}
                                >
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

                    <Textarea
                        id="question-bank-description"
                        label="Deskripsi"
                        value={data.description}
                        onChange={(event) =>
                            setField('description', event.target.value)
                        }
                        placeholder="Deskripsi singkat bank soal (opsional)"
                        error={errors.description}
                        rows={2}
                        maxLength={MAX_DESCRIPTION_LENGTH}
                    />
                </div>

                <div>
                    <div className="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <p className="text-[11px] font-label font-semibold text-on-surface-variant uppercase tracking-wider">
                            Komposisi Soal
                        </p>
                        <span className="text-[11px] font-label font-semibold text-primary">
                            {totalQuestionCount} soal · {totalScore} poin
                        </span>
                    </div>

                    <div className="rounded-xl border border-outline-variant/40 overflow-hidden bg-surface-container-lowest">
                        <div className="grid grid-cols-[minmax(0,1fr)_5.5rem_6.5rem] gap-3 items-center px-3 py-2 bg-surface-container-low border-b border-outline-variant/30">
                            <span className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                Tipe Soal
                            </span>
                            <span className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                Jumlah
                            </span>
                            <span className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                Bobot
                            </span>
                        </div>

                        {questionTypes.map((type) => (
                            <div
                                key={type.count}
                                className="grid grid-cols-[minmax(0,1fr)_5.5rem_6.5rem] gap-3 items-center px-3 py-2 border-b border-outline-variant/20 last:border-b-0"
                            >
                                <div className="flex items-center gap-2 min-w-0">
                                    <span className="inline-flex h-6 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-[10px] font-label font-bold text-primary">
                                        {type.short}
                                    </span>
                                    <span className="text-sm font-body text-on-surface truncate">
                                        {type.label}
                                    </span>
                                </div>

                                <Input
                                    id={`question-bank-${type.count}`}
                                    type="number"
                                    min="0"
                                    max="500"
                                    value={data[type.count]}
                                    onChange={(event) =>
                                        setField(type.count, event.target.value)
                                    }
                                    error={errors[type.count]}
                                    aria-label={`Jumlah soal ${type.label}`}
                                    className="[&_input]:px-2.5 [&_input]:py-1.5 [&_input]:text-center"
                                />

                                <Input
                                    id={`question-bank-${type.score}`}
                                    type="number"
                                    min="0"
                                    max="999.99"
                                    step="0.01"
                                    value={data[type.score]}
                                    onChange={(event) =>
                                        setField(type.score, event.target.value)
                                    }
                                    error={errors[type.score]}
                                    aria-label={`Bobot soal ${type.label}`}
                                    className="[&_input]:px-2.5 [&_input]:py-1.5 [&_input]:text-center"
                                />
                            </div>
                        ))}
                    </div>

                    {errors.composition && (
                        <p className="mt-1.5 text-[10px] font-label text-error">
                            {errors.composition}
                        </p>
                    )}
                </div>

                <div>
                    <div className="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <p className="text-[11px] font-label font-semibold text-on-surface-variant uppercase tracking-wider">
                            Jumlah Pilihan Jawaban
                        </p>
                        <span className="text-[11px] font-label font-semibold text-on-surface-variant">
                            {MIN_OPTION_COUNT} - {MAX_OPTION_COUNT} pilihan
                        </span>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {optionCountFields.map((field) => (
                            <div
                                key={field.name}
                                className="flex items-center gap-3 rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2"
                            >
                                <span className="inline-flex h-6 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-[10px] font-label font-bold text-primary">
                                    {field.short}
                                </span>
                                <span className="flex-1 min-w-0 text-sm font-body text-on-surface truncate">
                                    {field.label}
                                </span>

                                <Select
                                    id={`question-bank-${field.name}`}
                                    value={data[field.name]}
                                    onChange={(event) =>
                                        setField(
                                            field.name,
                                            event.target.value
                                        )
                                    }
                                    error={errors[field.name]}
                                    aria-label={`Jumlah pilihan jawaban ${field.label}`}
                                    className="w-36 shrink-0"
                                >
                                    {optionCountOptions.map((amount) => (
                                        <option
                                            key={amount}
                                            value={String(amount)}
                                        >
                                            {amount} pilihan (
                                            {String.fromCharCode(65)}
                                            -
                                            {String.fromCharCode(
                                                64 + amount
                                            )}
                                            )
                                        </option>
                                    ))}
                                </Select>
                            </div>
                        ))}
                    </div>

                    <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                        Jumlah pilihan jawaban ditetapkan di sini dan berlaku
                        untuk semua soal pada tipe terkait.
                    </p>
                </div>

                <div>
                    <div className="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <p className="text-[11px] font-label font-semibold text-on-surface-variant uppercase tracking-wider">
                            Berlaku Untuk Kelas
                        </p>
                        <span className="text-[11px] font-label font-semibold text-on-surface-variant">
                            {data.class_ids.length} kelas dipilih
                        </span>
                    </div>

                    <Input
                        type="search"
                        value={classSearch}
                        onChange={(event) => setClassSearch(event.target.value)}
                        placeholder="Cari kelas..."
                        aria-label="Cari kelas"
                        className="mb-2"
                    />

                    <div className="rounded-xl bg-surface-container-low max-h-48 overflow-y-auto divide-y divide-outline-variant/20">
                        {availableClasses.length === 0 ? (
                            <p className="px-3 py-4 text-xs text-on-surface-variant font-body text-center">
                                Tidak ada kelas tersedia untuk sekolah ini.
                            </p>
                        ) : (
                            availableClasses.map((schoolClass) => (
                                <label
                                    key={schoolClass.id}
                                    className="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-surface-container"
                                >
                                    <input
                                        type="checkbox"
                                        className="h-4 w-4 rounded border-outline text-primary focus:ring-primary/30"
                                        checked={data.class_ids.map(String).includes(
                                            String(schoolClass.id)
                                        )}
                                        onChange={() =>
                                            toggleClass(schoolClass.id)
                                        }
                                    />
                                    <span className="font-body text-sm text-on-surface flex-1">
                                        {schoolClass.name}
                                    </span>
                                    <span className="font-mono text-[10px] text-on-surface-variant">
                                        {schoolClass.code}
                                    </span>
                                </label>
                            ))
                        )}
                    </div>

                    {errors.class_ids && (
                        <p className="mt-1.5 text-[10px] font-label text-error">
                            {errors.class_ids}
                        </p>
                    )}
                </div>

                <label className="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        className="h-4 w-4 rounded border-outline text-primary focus:ring-primary/30"
                        checked={data.is_active}
                        onChange={(event) =>
                            setField('is_active', event.target.checked)
                        }
                    />
                    <span className="font-label text-sm text-on-surface">
                        Aktifkan bank soal ini
                    </span>
                </label>

                <div className="flex items-center gap-3 justify-end pt-4 border-t border-outline-variant/30">
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
