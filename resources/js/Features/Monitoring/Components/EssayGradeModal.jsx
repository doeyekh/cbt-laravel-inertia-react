import React, { useEffect, useState } from 'react';
import { useForm, useHttp } from '@inertiajs/react';
import Button from '@/Components/UI/Button';
import Modal from '@/Components/UI/Modal';
import { route } from '@/Utils/route';

function EssayAnswer({ attemptId, essay, onSaved }) {
    const { data, setData, patch, processing, errors } = useForm({
        score_awarded: essay.score_awarded ?? '',
    });

    const submit = (event) => {
        event.preventDefault();
        patch(route('admin.exam-attempts.essays.update', {
            examAttempt: attemptId,
            examAnswer: essay.id,
        }), {
            preserveScroll: true,
            onSuccess: () => onSaved(essay.id, Number(data.score_awarded)),
        });
    };

    return (
        <article className="space-y-4 rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-4 sm:p-5">
            <div>
                <h3 className="font-semibold text-on-surface">Soal {essay.question_no}</h3>
                <p className="mt-2 whitespace-pre-wrap text-sm text-on-surface-variant">
                    {essay.question}
                </p>
            </div>

            <div className="rounded-xl bg-surface-container-low p-4">
                <p className="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
                    Jawaban peserta
                </p>
                <p className="mt-2 whitespace-pre-wrap text-sm text-on-surface">
                    {essay.answer}
                </p>
            </div>

            <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                <label className="block">
                    <span className="mb-1 block text-xs font-medium text-on-surface">
                        Nilai (maks. {essay.max_score})
                    </span>
                    <input
                        type="number"
                        min="0"
                        max={essay.max_score}
                        step="0.01"
                        value={data.score_awarded}
                        onChange={(event) => setData('score_awarded', event.target.value)}
                        className="w-36 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-sm text-on-surface"
                    />
                    {errors.score_awarded && (
                        <span className="mt-1 block text-xs text-error">
                            {errors.score_awarded}
                        </span>
                    )}
                </label>
                <Button
                    type="submit"
                    disabled={data.score_awarded === ''}
                    loading={processing}
                    size="sm"
                >
                    Simpan Nilai
                </Button>
                {essay.score_awarded !== null && (
                    <span className="text-xs font-medium text-primary">
                        Sudah dinilai
                    </span>
                )}
            </form>
        </article>
    );
}

export default function EssayGradeModal({ isOpen, onClose, student }) {
    const [gradeData, setGradeData] = useState(null);
    const [loadError, setLoadError] = useState('');
    const { get, processing, cancel } = useHttp();

    useEffect(() => {
        if (!isOpen || !student?.grade_url) {
            setGradeData(null);
            setLoadError('');

            return undefined;
        }

        let isCurrentRequest = true;
        setGradeData(null);
        setLoadError('');

        get(student.grade_url, {
            onSuccess: (response) => {
                if (isCurrentRequest) {
                    setGradeData(response);
                }
            },
        }).catch((error) => {
            if (isCurrentRequest) {
                const status = error?.response?.status;
                const message = status === 404
                    ? 'Data penilaian esai tidak ditemukan.'
                    : status
                        ? 'Penilaian esai gagal dimuat.'
                        : 'Tidak dapat terhubung untuk memuat penilaian esai.';

                setLoadError(message);
            }
        });

        return () => {
            isCurrentRequest = false;
            cancel();
        };
    }, [isOpen, student?.grade_url, get, cancel]);

    const handleEssaySaved = (essayId, scoreAwarded) => {
        setGradeData((currentData) => ({
            ...currentData,
            essays: currentData.essays.map((essay) => (
                essay.id === essayId
                    ? { ...essay, score_awarded: scoreAwarded }
                    : essay
            )),
        }));
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} maxWidth="max-w-3xl">
            <div className="space-y-5">
                <header className="flex items-start justify-between gap-4 border-b border-outline-variant/40 pb-4">
                    <div>
                        <h2 className="font-headline text-xl font-bold text-on-surface">
                            Penilaian Esai
                        </h2>
                        {student && (
                            <p className="mt-1 text-sm text-on-surface-variant">
                                {student.name} · {student.subject}
                            </p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Tutup penilaian esai"
                        className="rounded-full p-2 text-on-surface-variant transition hover:bg-surface-container-low hover:text-on-surface"
                    >
                        <span className="material-symbols-outlined">close</span>
                    </button>
                </header>

                {processing && !gradeData && (
                    <div className="animate-pulse space-y-4" aria-label="Memuat penilaian esai">
                        <div className="h-24 rounded-xl bg-surface-container-low" />
                        <div className="h-24 rounded-xl bg-surface-container-low" />
                    </div>
                )}

                {loadError && (
                    <p role="alert" className="rounded-xl bg-error-container p-4 text-sm text-on-error-container">
                        {loadError}
                    </p>
                )}

                {gradeData && (
                    <div className="max-h-[65vh] space-y-4 overflow-y-auto pr-1">
                        {gradeData.essays.length > 0 ? gradeData.essays.map((essay) => (
                            <EssayAnswer
                                key={essay.id}
                                attemptId={gradeData.attempt.id}
                                essay={essay}
                                onSaved={handleEssaySaved}
                            />
                        )) : (
                            <p className="rounded-xl bg-surface-container-low p-5 text-sm text-on-surface-variant">
                                Tidak ada jawaban esai yang tersedia untuk dinilai.
                            </p>
                        )}
                    </div>
                )}
            </div>
        </Modal>
    );
}
