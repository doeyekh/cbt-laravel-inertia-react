import React, { useEffect, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import RichTextEditor from './RichTextEditor';
import MediaInput from './MediaInput';
import useSpoofedMethod from '@/Hooks/useSpoofedMethod';

const OPTION_LABELS = ['A', 'B', 'C', 'D', 'E'];
const DEFAULT_OPTION_COUNT = 4;

const emptyForm = {
    type: '',
    number: 1,
    body: '',
    is_active: true,
    image: null,
    audio: null,
    remove_image: false,
    remove_audio: false,
    options: [],
    pairs: [],
    blanks: [],
};

const buildOptions = (options = [], count = DEFAULT_OPTION_COUNT) =>
    OPTION_LABELS.slice(0, count).map((label, index) => {
        const option = options[index];

        return {
            id: option?.id ?? null,
            label,
            text: option?.text ?? '',
            is_correct: Boolean(option?.is_correct),
            score: option?.score ?? '',
            image: null,
            audio: null,
            remove_image: false,
            remove_audio: false,
        };
    });

const buildPairs = (count = 2) =>
    Array.from({ length: count }, () => ({ left_text: '', right_text: '' }));

const buildBlanks = (count = 1) =>
    Array.from({ length: count }, () => ({ answers: [''], score: '' }));

const defaultAnswersFor = (type, optionCount) => {
    switch (type) {
        case 'pilihan_ganda':
        case 'pilihan_ganda_kompleks':
            return { options: buildOptions([], optionCount), pairs: [], blanks: [] };
        case 'menjodohkan':
            return { options: [], pairs: buildPairs(), blanks: [] };
        case 'isian_singkat':
            return { options: [], pairs: [], blanks: buildBlanks(1) };
        default:
            return { options: [], pairs: [], blanks: [] };
    }
};

export default function QuestionForm({
    questionBank,
    activeType,
    activeTypeLabel,
    slotNumber,
    initialData = null,
    onSaved,
    onDeleted,
}) {
    const {
        data,
        setData,
        post,
        transform,
        processing,
        errors,
        clearErrors,
        setError,
    } = useForm(emptyForm);
    const isEdit = Boolean(initialData);

    const [expandedOption, setExpandedOption] = useState(0);

    const isKompleks = activeType === 'pilihan_ganda_kompleks';
    const usesOptions =
        activeType === 'pilihan_ganda' || activeType === 'pilihan_ganda_kompleks';

    const optionCount = Math.min(
        Math.max(
            Number(questionBank?.option_counts?.[activeType]) ||
                DEFAULT_OPTION_COUNT,
            3
        ),
        OPTION_LABELS.length
    );
    const questionScore = Number(questionBank?.scores?.[activeType]) || 0;

    useSpoofedMethod(transform, isEdit);

    useEffect(() => {
        clearErrors();

        if (initialData) {
            setData({
                type: initialData.type,
                number: initialData.number,
                body: initialData.body ?? '',
                is_active: Boolean(initialData.is_active),
                image: null,
                audio: null,
                remove_image: false,
                remove_audio: false,
                options: usesOptions
                    ? buildOptions(initialData.options ?? [], optionCount)
                    : [],
                pairs: (initialData.pairs ?? []).map((pair) => ({
                    left_text: pair.left_text,
                    right_text: pair.right_text,
                })),
                blanks: (initialData.blanks ?? []).map((blank) => ({
                    answers: (blank.answers ?? []).map((answer) => String(answer)),
                    score: blank.score ?? '',
                })),
            });

            const correctIndex = (initialData.options ?? []).findIndex(
                (option) => option.is_correct
            );

            setExpandedOption(correctIndex >= 0 ? correctIndex : 0);

            return;
        }

        setData({
            ...emptyForm,
            type: activeType,
            number: slotNumber,
            ...defaultAnswersFor(activeType, optionCount),
        });

        setExpandedOption(0);
    }, [initialData, activeType, slotNumber]); // eslint-disable-line react-hooks/exhaustive-deps

    const setField = (field, value) => {
        setData((current) => ({ ...current, [field]: value }));

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const updateOption = (index, field, value) => {
        setData((current) => {
            const options = current.options ?? [];

            if (field === 'is_correct' && value && !isKompleks) {
                return {
                    ...current,
                    options: options.map((option, optionIndex) => ({
                        ...option,
                        is_correct: optionIndex === index,
                    })),
                };
            }

            return {
                ...current,
                options: options.map((option, optionIndex) => {
                    if (optionIndex !== index) {
                        return option;
                    }

                    const updated = { ...option, [field]: value };

                    if (isMediaField(field) && value) {
                        updated[`remove_${field}`] = false;
                    }

                    return updated;
                }),
            };
        });
    };

    const removeOptionMedia = (index, field) => {
        setData((current) => ({
            ...current,
            options: (current.options ?? []).map((option, optionIndex) => {
                if (optionIndex !== index) {
                    return option;
                }

                const removing = !option[`remove_${field}`];

                return {
                    ...option,
                    [field]: removing ? null : option[field],
                    [`remove_${field}`]: removing,
                };
            }),
        }));
    };

    const removeQuestionMedia = (field) => {
        setData((current) => {
            const removing = !current[`remove_${field}`];

            return {
                ...current,
                [field]: removing ? null : current[field],
                [`remove_${field}`]: removing,
            };
        });
    };

    const setQuestionMedia = (field, file) => {
        setData((current) => ({
            ...current,
            [field]: file,
            [`remove_${field}`]: file ? false : current[`remove_${field}`],
        }));
    };

    const toggleOptionEditor = (index) => {
        setExpandedOption((current) => (current === index ? null : index));
    };

    const markOptionAsCorrect = (index) => {
        setData((current) => {
            const options = current.options ?? [];

            if (!options[index]) {
                return current;
            }

            if (isKompleks) {
                return {
                    ...current,
                    options: options.map((option, optionIndex) =>
                        optionIndex === index
                            ? { ...option, is_correct: !option.is_correct }
                            : option
                    ),
                };
            }

            return {
                ...current,
                options: options.map((option, optionIndex) => ({
                    ...option,
                    is_correct: optionIndex === index,
                })),
            };
        });
    };

    const updatePair = (index, field, value) => {
        setData((current) => ({
            ...current,
            pairs: (current.pairs ?? []).map((pair, pairIndex) =>
                pairIndex === index ? { ...pair, [field]: value } : pair
            ),
        }));
    };

    const addPair = () => {
        setData((current) => {
            const pairs = current.pairs ?? [];

            if (pairs.length >= 10) {
                return current;
            }

            return {
                ...current,
                pairs: [...pairs, { left_text: '', right_text: '' }],
            };
        });
    };

    const removePair = (index) => {
        setData((current) => ({
            ...current,
            pairs: (current.pairs ?? []).filter(
                (_, pairIndex) => pairIndex !== index
            ),
        }));
    };

    const updateBlank = (index, field, value) => {
        setData((current) => ({
            ...current,
            blanks: (current.blanks ?? []).map((blank, blankIndex) =>
                blankIndex === index ? { ...blank, [field]: value } : blank
            ),
        }));
    };

    const updateBlankAnswer = (blankIndex, answerIndex, value) => {
        setData((current) => ({
            ...current,
            blanks: (current.blanks ?? []).map((blank, index) => {
                if (index !== blankIndex) {
                    return blank;
                }

                return {
                    ...blank,
                    answers: blank.answers.map((answer, currentIndex) =>
                        currentIndex === answerIndex ? value : answer
                    ),
                };
            }),
        }));
    };

    const addBlankAnswer = (blankIndex) => {
        setData((current) => ({
            ...current,
            blanks: (current.blanks ?? []).map((blank, index) =>
                index === blankIndex
                    ? { ...blank, answers: [...blank.answers, ''] }
                    : blank
            ),
        }));
    };

    const removeBlankAnswer = (blankIndex, answerIndex) => {
        setData((current) => ({
            ...current,
            blanks: (current.blanks ?? []).map((blank, index) => {
                if (index !== blankIndex) {
                    return blank;
                }

                return {
                    ...blank,
                    answers: blank.answers.filter(
                        (_, currentIndex) => currentIndex !== answerIndex
                    ),
                };
            }),
        }));
    };

    const validateAnswers = () => {
        const nextErrors = {};

        if (usesOptions) {
            if (data.options.length !== optionCount) {
                nextErrors.options = `Jumlah pilihan jawaban harus tepat ${optionCount} pilihan.`;
            }

            if (data.options.some((option) => !stripHtml(option.text).trim())) {
                nextErrors.options = 'Teks setiap opsi wajib diisi.';
            }

            const correctCount = data.options.filter(
                (option) => option.is_correct
            ).length;

            if (activeType === 'pilihan_ganda' && correctCount !== 1) {
                nextErrors.options =
                    'Pilihan ganda harus memiliki tepat satu jawaban benar.';
            }

            if (activeType === 'pilihan_ganda_kompleks' && correctCount < 2) {
                nextErrors.options =
                    'Pilihan ganda kompleks harus memiliki minimal dua jawaban benar.';
            }
        }

        if (activeType === 'menjodohkan') {
            if (data.pairs.length < 2 || data.pairs.length > 10) {
                nextErrors.pairs = 'Jumlah pasangan harus antara 2 sampai 10.';
            }

            if (
                data.pairs.some(
                    (pair) =>
                        !pair.left_text.trim() || !pair.right_text.trim()
                )
            ) {
                nextErrors.pairs = 'Kolom kiri dan kanan wajib diisi.';
            }
        }

        if (activeType === 'isian_singkat') {
            if (data.blanks.length !== 1) {
                nextErrors.blanks = 'Isian singkat harus memiliki tepat satu isian.';
            }

            if (
                data.blanks.some((blank) =>
                    blank.answers.every((answer) => !answer.trim())
                )
            ) {
                nextErrors.blanks =
                    'Minimal satu jawaban yang diterima wajib diisi.';
            }
        }

        return nextErrors;
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const nextErrors = {};

        if (!stripHtml(data.body).trim()) {
            nextErrors.body = 'Teks soal wajib diisi.';
        }

        Object.assign(nextErrors, validateAnswers());

        if (Object.keys(nextErrors).length > 0) {
            Object.entries(nextErrors).forEach(([field, message]) => {
                setError(field, message);
            });

            return;
        }

        const options = {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => onSaved?.(),
        };

        if (isEdit) {
            post(
                route('admin.question-banks.questions.update', {
                    questionBank: questionBank.id,
                    question: initialData.id,
                }),
                options
            );

            return;
        }

        post(route('admin.question-banks.questions.store', questionBank.id), options);
    };

    const existingOptionFiles = useMemo(() => {
        const files = new Map();

        (initialData?.options ?? []).forEach((option) => {
            files.set(option.id, option);
        });

        return files;
    }, [initialData]);

    return (
        <form onSubmit={handleSubmit} className="space-y-5">
            <div className="flex flex-wrap items-end gap-4">
                <div className="flex-1 min-w-[180px]">
                    <p className="text-[11px] font-label font-semibold text-on-surface-variant mb-1.5 uppercase tracking-wider">
                        Nomor Soal
                    </p>
                    <p className="font-headline text-2xl font-bold text-on-surface">
                        {slotNumber}
                        <span className="text-sm font-medium text-on-surface-variant ml-2">
                            {initialData?.type_label ?? activeTypeLabel}
                        </span>
                    </p>
                </div>

                <div className="rounded-xl bg-surface-container-low px-4 py-2.5">
                    <p className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                        Bobot Soal
                    </p>
                    <p className="font-headline text-lg font-bold text-primary">
                        {questionScore}
                        <span className="ml-1 text-[11px] font-medium text-on-surface-variant">
                            poin
                        </span>
                    </p>
                </div>

                <label className="flex items-center gap-2 cursor-pointer pb-2">
                    <input
                        type="checkbox"
                        className="h-4 w-4 rounded border-outline text-primary focus:ring-primary/30"
                        checked={data.is_active}
                        onChange={(event) =>
                            setField('is_active', event.target.checked)
                        }
                    />
                    <span className="font-label text-sm text-on-surface">
                        Aktifkan soal
                    </span>
                </label>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="space-y-4">
                    <div>
                        <p className="text-[11px] font-label font-semibold text-on-surface-variant mb-1.5 uppercase tracking-wider">
                            Soal
                        </p>
                        <RichTextEditor
                            id="question-body"
                            value={data.body}
                            onChange={(html) => setField('body', html)}
                            placeholder="Tuliskan pertanyaan soal..."
                            minHeight={220}
                        />
                        {errors.body && (
                            <p className="mt-1 text-[10px] font-label text-error">
                                {errors.body}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <MediaInput
                            id="question-image"
                            label="Gambar Soal"
                            kind="image"
                            accept="image/*"
                            file={data.image}
                            onChange={(file) => setQuestionMedia('image', file)}
                            onRemove={() => removeQuestionMedia('image')}
                            existingUrl={initialData?.image_url}
                            existingName={initialData?.image_path}
                            removed={Boolean(data.remove_image)}
                            error={errors.image}
                        />
                        <MediaInput
                            id="question-audio"
                            label="Audio Soal"
                            kind="audio"
                            accept="audio/*"
                            file={data.audio}
                            onChange={(file) => setQuestionMedia('audio', file)}
                            onRemove={() => removeQuestionMedia('audio')}
                            existingUrl={initialData?.audio_url}
                            existingName={initialData?.audio_path}
                            removed={Boolean(data.remove_audio)}
                            error={errors.audio}
                        />
                    </div>
                </div>

                <div className="space-y-3">
                    {usesOptions && (
                        <>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="text-[10px] font-body text-on-surface-variant">
                                    {isKompleks
                                        ? 'Klik tombol Pilihan X di kanan untuk menandai / melepas jawaban benar. Klik baris pilihan untuk membuka editor teks.'
                                        : 'Klik tombol Pilihan X di kanan untuk menandai sebagai jawaban benar. Klik baris pilihan untuk membuka editor teks.'}
                                </p>

                                <span className="inline-flex items-center gap-1.5 rounded-lg bg-surface-container px-2 py-0.5 text-[10px] font-label font-semibold text-on-surface-variant">
                                    <span className="material-symbols-outlined text-[12px]">
                                        format_list_numbered
                                    </span>
                                    {optionCount} pilihan jawaban
                                </span>
                            </div>

                            {data.options.map((option, index) => {
                                const isExpanded = expandedOption === index;
                                const existing = option.id
                                    ? existingOptionFiles.get(option.id)
                                    : null;

                                return (
                                    <div
                                        key={index}
                                        className={`rounded-xl border transition-colors ${
                                            isExpanded
                                                ? 'border-primary/40 bg-surface-container-low'
                                                : 'border-outline-variant/40 bg-surface-container-lowest'
                                        }`}
                                    >
                                        <div className="flex items-stretch">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    toggleOptionEditor(index)
                                                }
                                                aria-expanded={isExpanded}
                                                title={`${
                                                    isExpanded ? 'Tutup' : 'Buka'
                                                } editor pilihan jawaban ${
                                                    option.label
                                                }`}
                                                className={`flex-1 flex flex-wrap items-center gap-2 px-4 py-3.5 text-left transition-colors ${
                                                    option.is_correct
                                                        ? 'bg-primary/5'
                                                        : 'hover:bg-surface-container'
                                                }`}
                                            >
                                                <span className="material-symbols-outlined text-[18px] text-on-surface-variant">
                                                    {isExpanded
                                                        ? 'expand_less'
                                                        : 'expand_more'}
                                                </span>

                                                <span className="font-label font-semibold text-sm uppercase tracking-wider text-primary">
                                                    Pilihan Jawaban {option.label}
                                                </span>

                                                {option.is_correct && (
                                                    <span className="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-2 py-0.5 text-[10px] font-label font-semibold text-primary">
                                                        <span className="material-symbols-outlined text-[12px]">
                                                            check
                                                        </span>
                                                        Jawaban Benar
                                                    </span>
                                                )}
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    markOptionAsCorrect(index)
                                                }
                                                aria-pressed={Boolean(
                                                    option.is_correct
                                                )}
                                                title={
                                                    isKompleks
                                                        ? `Tandai / lepas pilihan ${option.label} sebagai jawaban benar`
                                                        : `Tandai pilihan ${option.label} sebagai jawaban benar`
                                                }
                                                className={`flex items-center gap-2.5 px-4 py-3.5 border-l border-outline-variant/30 transition-colors ${
                                                    option.is_correct
                                                        ? 'bg-primary/5'
                                                        : 'hover:bg-surface-container'
                                                }`}
                                            >
                                                <span
                                                    className={`flex h-7 w-7 shrink-0 items-center justify-center border-2 transition-colors ${
                                                        isKompleks
                                                            ? 'rounded-lg'
                                                            : 'rounded-full'
                                                    } ${
                                                        option.is_correct
                                                            ? 'border-primary bg-primary'
                                                            : 'border-outline bg-white'
                                                    }`}
                                                >
                                                    {option.is_correct && (
                                                        <span
                                                            className={`h-3 w-3 ${
                                                                isKompleks
                                                                    ? 'rounded-[3px]'
                                                                    : 'rounded-full'
                                                            } bg-on-primary`}
                                                        />
                                                    )}
                                                </span>

                                                <span className="text-xs font-label font-medium text-on-surface-variant whitespace-nowrap">
                                                    Pilihan {option.label}
                                                </span>
                                            </button>
                                        </div>

                                        {isExpanded && (
                                            <div className="px-4 pb-4 pt-3 space-y-3 border-t border-outline-variant/30">
                                                <RichTextEditor
                                                    value={option.text}
                                                    onChange={(html) =>
                                                        updateOption(
                                                            index,
                                                            'text',
                                                            html
                                                        )
                                                    }
                                                    placeholder={`Teks pilihan jawaban ${option.label}...`}
                                                    minHeight={120}
                                                />

                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <MediaInput
                                                        id={`question-option-${index}-image`}
                                                        label="Gambar Opsi"
                                                        kind="image"
                                                        accept="image/*"
                                                        file={option.image}
                                                        onChange={(file) =>
                                                            updateOption(
                                                                index,
                                                                'image',
                                                                file
                                                            )
                                                        }
                                                        onRemove={() =>
                                                            removeOptionMedia(
                                                                index,
                                                                'image'
                                                            )
                                                        }
                                                        existingUrl={
                                                            existing?.image_url
                                                        }
                                                        existingName={
                                                            existing?.image_path
                                                        }
                                                        removed={Boolean(
                                                            option.remove_image
                                                        )}
                                                        error={
                                                            errors[
                                                                `options.${index}.image`
                                                            ]
                                                        }
                                                    />
                                                    <MediaInput
                                                        id={`question-option-${index}-audio`}
                                                        label="Audio Opsi"
                                                        kind="audio"
                                                        accept="audio/*"
                                                        file={option.audio}
                                                        onChange={(file) =>
                                                            updateOption(
                                                                index,
                                                                'audio',
                                                                file
                                                            )
                                                        }
                                                        onRemove={() =>
                                                            removeOptionMedia(
                                                                index,
                                                                'audio'
                                                            )
                                                        }
                                                        existingUrl={
                                                            existing?.audio_url
                                                        }
                                                        existingName={
                                                            existing?.audio_path
                                                        }
                                                        removed={Boolean(
                                                            option.remove_audio
                                                        )}
                                                        error={
                                                            errors[
                                                                `options.${index}.audio`
                                                            ]
                                                        }
                                                    />
                                                </div>

                                                <div className="flex items-center justify-between">
                                                    {isKompleks ? (
                                                        <Input
                                                            id={`question-option-${index}-score`}
                                                            label="Bobot Opsi"
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={option.score}
                                                            onChange={(event) =>
                                                                updateOption(
                                                                    index,
                                                                    'score',
                                                                    event.target.value
                                                                )
                                                            }
                                                            error={
                                                                errors[
                                                                    `options.${index}.score`
                                                                ]
                                                            }
                                                            className="w-40"
                                                        />
                                                    ) : (
                                                        <span />
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}

                            {errors.options && (
                                <p className="text-[10px] font-label text-error">
                                    {errors.options}
                                </p>
                            )}
                        </>
                    )}

                    {activeType === 'menjodohkan' && (
                        <>
                            {data.pairs.map((pair, index) => (
                                <div
                                    key={index}
                                    className="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3 space-y-2"
                                >
                                    <p className="font-label font-semibold text-sm uppercase tracking-wider text-primary">
                                        Pasangan {index + 1}
                                    </p>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="text"
                                            className="w-full bg-surface-container-low text-on-surface placeholder:text-on-surface-variant font-body text-sm px-3 py-2 rounded-xl focus:outline-none focus:bg-surface-container-high focus:ring-2 focus:ring-primary/20 transition-colors"
                                            value={pair.left_text}
                                            onChange={(event) =>
                                                updatePair(
                                                    index,
                                                    'left_text',
                                                    event.target.value
                                                )
                                            }
                                            placeholder="Kolom kiri..."
                                        />
                                        <span className="material-symbols-outlined text-[18px] text-on-surface-variant">
                                            arrow_forward
                                        </span>
                                        <input
                                            type="text"
                                            className="w-full bg-surface-container-low text-on-surface placeholder:text-on-surface-variant font-body text-sm px-3 py-2 rounded-xl focus:outline-none focus:bg-surface-container-high focus:ring-2 focus:ring-primary/20 transition-colors"
                                            value={pair.right_text}
                                            onChange={(event) =>
                                                updatePair(
                                                    index,
                                                    'right_text',
                                                    event.target.value
                                                )
                                            }
                                            placeholder="Kolom kanan..."
                                        />
                                        <button
                                            type="button"
                                            onClick={() => removePair(index)}
                                            disabled={data.pairs.length <= 2}
                                            className="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/20 hover:text-error disabled:opacity-40"
                                            title="Hapus pasangan"
                                        >
                                            <span className="material-symbols-outlined text-[18px]">
                                                delete
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            ))}

                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={addPair}
                                disabled={data.pairs.length >= 10}
                            >
                                <span className="material-symbols-outlined text-[16px]">
                                    add
                                </span>
                                Tambah Pasangan
                            </Button>

                            {errors.pairs && (
                                <p className="text-[10px] font-label text-error">
                                    {errors.pairs}
                                </p>
                            )}
                        </>
                    )}

                    {activeType === 'isian_singkat' && (
                        <>
                            {data.blanks.map((blank, blankIndex) => (
                                <div
                                    key={blankIndex}
                                    className="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3 space-y-2"
                                >
                                    <p className="font-label font-semibold text-sm uppercase tracking-wider text-primary">
                                        Jawaban yang Diterima
                                    </p>

                                    {blank.answers.map((answer, answerIndex) => (
                                        <div
                                            key={answerIndex}
                                            className="flex items-center gap-2"
                                        >
                                            <input
                                                type="text"
                                                className="w-full bg-surface-container-low text-on-surface placeholder:text-on-surface-variant font-body text-sm px-3 py-2 rounded-xl focus:outline-none focus:bg-surface-container-high focus:ring-2 focus:ring-primary/20 transition-colors"
                                                value={answer}
                                                onChange={(event) =>
                                                    updateBlankAnswer(
                                                        blankIndex,
                                                        answerIndex,
                                                        event.target.value
                                                    )
                                                }
                                                placeholder="Jawaban yang diterima..."
                                            />
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    removeBlankAnswer(
                                                        blankIndex,
                                                        answerIndex
                                                    )
                                                }
                                                disabled={blank.answers.length <= 1}
                                                className="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/20 hover:text-error disabled:opacity-40"
                                                title="Hapus jawaban"
                                            >
                                                <span className="material-symbols-outlined text-[18px]">
                                                    close
                                                </span>
                                            </button>
                                        </div>
                                    ))}

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => addBlankAnswer(blankIndex)}
                                    >
                                        <span className="material-symbols-outlined text-[16px]">
                                            add
                                        </span>
                                        Tambah Jawaban Alternatif
                                    </Button>

                                    {errors[`blanks.${blankIndex}.answers`] && (
                                        <p className="text-[10px] font-label text-error">
                                            {errors[`blanks.${blankIndex}.answers`]}
                                        </p>
                                    )}
                                </div>
                            ))}

                            {errors.blanks && (
                                <p className="text-[10px] font-label text-error">
                                    {errors.blanks}
                                </p>
                            )}
                        </>
                    )}

                    {activeType === 'esai' && (
                        <div className="rounded-xl bg-surface-container-low px-4 py-3">
                            <p className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                Soal esai tidak memerlukan kunci jawaban. Jawaban
                                dinilai secara manual oleh guru.
                            </p>
                        </div>
                    )}
                </div>
            </div>

            <div className="flex items-center gap-3 justify-end pt-4 border-t border-outline-variant/30">
                <Button type="submit" loading={processing}>
                    {isEdit ? 'Perbarui Soal' : 'Simpan Soal'}
                </Button>

                {isEdit && (
                    <Button
                        type="button"
                        variant="danger"
                        onClick={() => onDeleted?.(initialData)}
                        disabled={processing}
                    >
                        <span className="material-symbols-outlined text-[18px]">
                            delete
                        </span>
                        Hapus Soal
                    </Button>
                )}
            </div>
        </form>
    );
}

const stripHtml = (value) =>
    String(value ?? '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .trim();

const MEDIA_FIELDS = ['image', 'audio'];

const isMediaField = (field) => MEDIA_FIELDS.includes(field);
