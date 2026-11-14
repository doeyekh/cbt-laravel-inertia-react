import React, { useEffect, useMemo, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import { route } from '@/Utils/route';
import { typesetMath } from '@/Utils/mathjax';

const typeShortLabels = {
    pilihan_ganda: 'PG',
    pilihan_ganda_kompleks: 'PK',
    menjodohkan: 'MJ',
    isian_singkat: 'IS',
    esai: 'ES',
};

const FORBIDDEN_TAGS = 'script, style, iframe, object, embed, link, meta, base';

const sanitizeHtml = (html) => {
    if (!html) {
        return '';
    }

    const doc = new DOMParser().parseFromString(html, 'text/html');

    doc.querySelectorAll(FORBIDDEN_TAGS).forEach((node) => node.remove());

    doc.querySelectorAll('*').forEach((node) => {
        Array.from(node.attributes).forEach((attribute) => {
            const name = attribute.name.toLowerCase();

            if (name.startsWith('on')) {
                node.removeAttribute(attribute.name);

                return;
            }

            if (
                (name === 'href' || name === 'src') &&
                /^\s*javascript:/i.test(attribute.value)
            ) {
                node.removeAttribute(attribute.name);
            }
        });
    });

    return doc.body.innerHTML;
};

function RichText({ html, className = '' }) {
    const sanitized = useMemo(() => sanitizeHtml(html), [html]);

    return (
        <div
            className={`text-sm font-body text-on-surface leading-relaxed break-words [&_a]:text-primary [&_a]:underline [&_img]:max-w-full [&_img]:h-auto [&_img]:rounded-lg [&_p]:my-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 ${className}`}
            dangerouslySetInnerHTML={{ __html: sanitized }}
        />
    );
}

function QuestionMedia({ question }) {
    return (
        <>
            {question.image_url && (
                <img
                    src={question.image_url}
                    alt="Gambar soal"
                    className="max-h-64 rounded-lg border border-outline-variant/30 bg-surface-container-lowest"
                />
            )}

            {question.audio_url && (
                <audio
                    src={question.audio_url}
                    controls
                    className="w-full"
                    aria-label="Audio soal"
                />
            )}
        </>
    );
}

function QuestionAnswers({ question }) {
    if (question.options?.length > 0) {
        return (
            <ol className="space-y-2">
                {question.options.map((option) => (
                    <li
                        key={option.id}
                        className={`flex items-start gap-3 rounded-lg border px-3 py-2.5 ${
                            option.is_correct
                                ? 'border-primary/40 bg-primary/5'
                                : 'border-outline-variant/30 bg-surface-container-lowest'
                        }`}
                    >
                        <span
                            className={`inline-flex h-6 w-7 shrink-0 items-center justify-center rounded-lg text-[11px] font-label font-bold ${
                                option.is_correct
                                    ? 'bg-primary text-on-primary'
                                    : 'bg-surface-container text-on-surface-variant'
                            }`}
                        >
                            {option.label}
                        </span>

                        <div className="flex-1 min-w-0 space-y-2">
                            <RichText html={option.text} />

                            {option.image_url && (
                                <img
                                    src={option.image_url}
                                    alt={`Gambar opsi ${option.label}`}
                                    className="max-h-48 rounded-lg border border-outline-variant/30 bg-surface-container-lowest"
                                />
                            )}

                            {option.audio_url && (
                                <audio
                                    src={option.audio_url}
                                    controls
                                    className="w-full"
                                    aria-label={`Audio opsi ${option.label}`}
                                />
                            )}
                        </div>

                        {option.is_correct && (
                            <span className="inline-flex shrink-0 items-center gap-1 rounded-lg bg-primary/10 px-2 py-0.5 text-[10px] font-label font-semibold text-primary">
                                <span className="material-symbols-outlined text-[12px]">
                                    check
                                </span>
                                Jawaban Benar
                            </span>
                        )}
                    </li>
                ))}
            </ol>
        );
    }

    if (question.pairs?.length > 0) {
        return (
            <div className="rounded-lg border border-outline-variant/30 overflow-hidden">
                {question.pairs.map((pair) => (
                    <div
                        key={pair.id}
                        className="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 px-3 py-2.5 border-b border-outline-variant/20 last:border-b-0"
                    >
                        <span className="text-sm font-body text-on-surface">
                            {pair.left_text}
                        </span>
                        <span className="material-symbols-outlined text-[18px] text-on-surface-variant">
                            arrow_forward
                        </span>
                        <span className="text-sm font-body text-on-surface">
                            {pair.right_text}
                        </span>
                    </div>
                ))}
            </div>
        );
    }

    if (question.blanks?.length > 0) {
        return (
            <div className="space-y-2">
                {question.blanks.map((blank) => (
                    <div
                        key={blank.id}
                        className="rounded-lg border border-outline-variant/30 bg-surface-container-low px-3 py-2"
                    >
                        <p className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Jawaban yang Diterima
                        </p>
                        <p className="text-sm font-body text-on-surface mt-1">
                            {(blank.answers ?? []).join(' / ') || '-'}
                        </p>
                    </div>
                ))}
            </div>
        );
    }

    return (
        <p className="rounded-lg bg-surface-container-low px-3 py-2 text-[11px] font-body text-on-surface-variant">
            Soal esai dinilai secara manual oleh guru.
        </p>
    );
}

export default function QuestionsPreview({
    questionBank,
    questions = {},
    counts = {},
    types = [],
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const contentRef = useRef(null);
    const handledToast = useRef(null);
    const canManage = Boolean(
        auth?.user?.is_administrator || auth?.user?.is_school_admin
    );

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    useEffect(() => {
        typesetMath(contentRef.current);
    }, []);

    const totalQuestionCount = Object.values(counts).reduce(
        (total, amount) => total + (Number(amount) || 0),
        0
    );

    const totalScore = types.reduce(
        (total, type) =>
            total +
            (Number(counts?.[type.value]) || 0) *
                (Number(questionBank?.scores?.[type.value]) || 0),
        0
    );

    const handleBack = () => {
        router.get(route('admin.question-banks.index'), { preserveScroll: true });
    };

    const handleManage = () => {
        router.get(route('admin.question-banks.questions.index', questionBank.id));
    };

    const handlePrint = () => {
        window.open(
            route('admin.question-banks.questions.print', questionBank.id),
            '_blank',
            'noopener'
        );
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1200px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Pengelolaan Ujian</span>
                            <span>/</span>
                            <span>Bank Soal</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">
                                Lihat Soal
                            </span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            {questionBank?.name}
                        </h1>
                        <div className="flex flex-wrap items-center gap-2 text-xs text-on-surface-variant font-body">
                            <span className="inline-flex rounded-lg bg-surface-container-low px-2 py-0.5 font-mono text-[11px] font-semibold">
                                {questionBank?.code}
                            </span>
                            <span>{questionBank?.subject_name}</span>
                            <span>/</span>
                            <span>{questionBank?.school_name}</span>
                            {questionBank?.exam_type_name && (
                                <>
                                    <span>/</span>
                                    <span>{questionBank.exam_type_name}</span>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button variant="secondary" onClick={handleBack}>
                            <span className="material-symbols-outlined text-[18px]">
                                arrow_back
                            </span>
                            Kembali ke Bank Soal
                        </Button>
                        <Button variant="outline" onClick={handlePrint}>
                            <span className="material-symbols-outlined text-[18px]">
                                print
                            </span>
                            Cetak Soal
                        </Button>
                        {canManage && (
                            <Button onClick={handleManage}>
                                <span className="material-symbols-outlined text-[18px]">
                                    quiz
                                </span>
                                Kelola Soal
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex items-start gap-3 rounded-2xl bg-primary/5 border border-primary/20 px-4 py-3">
                    <span className="material-symbols-outlined text-primary text-[20px] shrink-0">
                        visibility
                    </span>
                    <div>
                        <p className="font-label font-semibold text-sm text-on-surface">
                            Halaman Pratinjau
                        </p>
                        <p className="text-xs text-on-surface-variant font-body mt-0.5">
                            Halaman ini hanya menampilkan soal. Soal tidak dapat
                            ditambah, diubah, atau dihapus dari sini.
                        </p>
                    </div>
                </div>

                <Card padding="p-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="text-[11px] font-label font-semibold text-on-surface-variant uppercase tracking-wider">
                                Ringkasan Soal
                            </p>
                            <p className="font-headline text-2xl font-bold text-on-surface mt-1">
                                {totalQuestionCount}
                                <span className="text-sm font-medium text-on-surface-variant ml-2">
                                    soal · {totalScore} poin
                                </span>
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {types.map((type) => {
                                const amount = counts?.[type.value] ?? 0;

                                if (amount === 0) {
                                    return null;
                                }

                                return (
                                    <span
                                        key={type.value}
                                        className="inline-flex items-center gap-1.5 rounded-lg bg-surface-container px-2.5 py-1 text-[11px] font-label font-semibold text-on-surface-variant"
                                    >
                                        {typeShortLabels[type.value] ?? ''} ·{' '}
                                        {type.label}
                                        <span className="font-mono text-primary">
                                            {amount}
                                        </span>
                                    </span>
                                );
                            })}
                        </div>
                    </div>
                </Card>

                {totalQuestionCount === 0 ? (
                    <Card padding="p-0">
                        <div className="flex flex-col items-center justify-center py-16 text-center">
                            <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                                <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                                    quiz
                                </span>
                            </div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                                Belum Ada Soal
                            </h3>
                            <p className="text-sm text-on-surface-variant font-body">
                                Bank soal ini belum memiliki soal untuk
                                ditampilkan.
                            </p>
                        </div>
                    </Card>
                ) : (
                    <div ref={contentRef} className="space-y-6">
                        {types.map((type) => {
                            const items = questions?.[type.value] ?? [];

                            if (items.length === 0) {
                                return null;
                            }

                            return (
                                <Card key={type.value} padding="p-0">
                                    <div className="px-5 py-4 bg-surface-container-low/60 border-b border-outline-variant/20">
                                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                                            {type.label}
                                        </h3>
                                        <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                            {items.length} soal ·{' '}
                                            {
                                                questionBank?.scores?.[
                                                    type.value
                                                ] ?? 0
                                            }{' '}
                                            poin per soal
                                        </p>
                                    </div>

                                    <div className="divide-y divide-outline-variant/20">
                                        {items.map((question) => (
                                            <div
                                                key={question.id}
                                                className="px-5 py-4 space-y-3"
                                            >
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <span className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-label font-bold text-primary">
                                                        {question.number}
                                                    </span>
                                                    <span className="font-label font-semibold text-sm text-on-surface">
                                                        Soal {question.number}
                                                    </span>
                                                    <span className="text-[11px] font-mono text-on-surface-variant">
                                                        {question.score} poin
                                                    </span>
                                                    <Badge
                                                        variant={
                                                            question.is_active
                                                                ? 'success'
                                                                : 'neutral'
                                                        }
                                                    >
                                                        {question.is_active
                                                            ? 'Aktif'
                                                            : 'Nonaktif'}
                                                    </Badge>
                                                </div>

                                                <RichText
                                                    html={question.body}
                                                />

                                                <QuestionMedia
                                                    question={question}
                                                />

                                                <QuestionAnswers
                                                    question={question}
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
