import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import QuestionForm from './Components/QuestionForm';
import ClearQuestionsModal from './Components/ClearQuestionsModal';
import { route } from '@/Utils/route';

const typeShortLabels = {
    pilihan_ganda: 'PG',
    pilihan_ganda_kompleks: 'PK',
    menjodohkan: 'MJ',
    isian_singkat: 'IS',
    esai: 'ES',
};

export default function QuestionsIndex({
    questionBank,
    question = null,
    slots = [],
    types = [],
    counts = {},
    filters,
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(auth?.user?.is_administrator || auth?.user?.is_school_admin);

    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [deletingQuestion, setDeletingQuestion] = useState(null);

    const [clearOpen, setClearOpen] = useState(false);
    const [clearing, setClearing] = useState(false);

    const handledToast = useRef(null);

    const activeType = filters?.type ?? '';
    const activeNumber = filters?.number ?? 1;

    const composition = questionBank?.composition ?? {};
    const totalRequired = questionBank?.total_question_count ?? 0;
    const totalFilled = Object.values(counts ?? {}).reduce(
        (total, amount) => total + (amount ?? 0),
        0
    );

    const tabs = types
        .filter(
            (type) =>
                (composition[type.value] ?? 0) > 0 ||
                (counts?.[type.value] ?? 0) > 0
        )
        .map((type) => ({
            value: type.value,
            label: type.label,
            count: counts?.[type.value] ?? 0,
        }));

    const activeTypeLabel =
        types.find((type) => type.value === activeType)?.label ?? '';

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleTabChange = (value) => {
        if (value === activeType) {
            return;
        }

        router.get(
            route('admin.question-banks.questions.index', questionBank.id),
            { type: value, number: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['question', 'slots', 'filters'],
            }
        );
    };

    const handleSlotChange = (number) => {
        if (number === activeNumber) {
            return;
        }

        router.get(
            route('admin.question-banks.questions.index', questionBank.id),
            { type: activeType, number },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['question', 'slots', 'filters'],
            }
        );
    };

    const handleBack = () => {
        router.get(route('admin.question-banks.index'), { preserveScroll: true });
    };

    const handleOpenDelete = (item) => {
        setDeletingQuestion(item);
        setDeleteOpen(true);
    };

    const handleCloseDelete = () => {
        if (deleting) {
            return;
        }

        setDeleteOpen(false);
        setDeletingQuestion(null);
    };

    const handleConfirmDelete = () => {
        if (!deletingQuestion) {
            return;
        }

        setDeleting(true);

        router.delete(
            route('admin.question-banks.questions.destroy', {
                questionBank: questionBank.id,
                question: deletingQuestion.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteOpen(false);
                    setDeletingQuestion(null);
                },
                onFinish: () => setDeleting(false),
            }
        );
    };

    const handleOpenClear = () => {
        setClearOpen(true);
    };

    const handleCloseClear = () => {
        if (clearing) {
            return;
        }

        setClearOpen(false);
    };

    const handleConfirmClear = () => {
        setClearing(true);

        router.delete(
            route('admin.question-banks.questions.destroy-all', questionBank.id),
            {
                preserveScroll: true,
                onSuccess: () => setClearOpen(false),
                onFinish: () => setClearing(false),
            }
        );
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1600px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Pengelolaan Ujian</span>
                            <span>/</span>
                            <span>Bank Soal</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Soal</span>
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
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">

                        <Button variant="secondary" onClick={handleBack}>
                            <span className="material-symbols-outlined text-[18px]">
                                arrow_back
                            </span>
                            Bank Soal
                        </Button>
                        {canManage && totalFilled > 0 && (
                            <Button
                                variant="outline"
                                onClick={handleOpenClear}
                                className="text-error!"
                            >
                                <span className="material-symbols-outlined text-[18px]">
                                    delete_sweep
                                </span>
                                Kosongkan Soal
                            </Button>
                        )}
                    </div>
                </div>

                <Card padding="p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Komposisi Soal
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                {totalFilled} dari {totalRequired} soal sudah tersusun.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {questionBank?.classes?.map((schoolClass) => (
                                <span
                                    key={schoolClass.id}
                                    className="inline-flex rounded-xl bg-surface-container px-2.5 py-1 text-[11px] font-label font-medium text-on-surface-variant"
                                >
                                    {schoolClass.code}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        {types.map((type) => {
                            const required = composition[type.value] ?? 0;
                            const filled = counts?.[type.value] ?? 0;
                            const percentage =
                                required > 0
                                    ? Math.min((filled / required) * 100, 100)
                                    : 0;

                            return (
                                <div
                                    key={type.value}
                                    className="rounded-xl bg-surface-container-low px-3 py-3"
                                >
                                    <p className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                        {typeShortLabels[type.value] ?? ''} · {type.label}
                                    </p>
                                    <p className="font-headline text-xl font-bold text-on-surface mt-1">
                                        {filled}
                                        <span className="text-sm font-medium text-on-surface-variant">
                                            /{required}
                                        </span>
                                    </p>

                                    <p className="mt-1 inline-flex items-center gap-1 rounded-lg bg-primary/10 px-2 py-0.5 text-[10px] font-label font-semibold text-primary">
                                        <span className="material-symbols-outlined text-[12px]">
                                            workspace_premium
                                        </span>
                                        {questionBank?.scores?.[type.value] ?? 0} poin/soal
                                    </p>

                                    <div className="mt-2 h-1.5 rounded-full bg-surface-container overflow-hidden">
                                        <div
                                            className={`h-full rounded-full ${
                                                filled > required
                                                    ? 'bg-error'
                                                    : 'bg-primary'
                                            }`}
                                            style={{ width: `${percentage}%` }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </Card>

                <Card padding="p-0">
                    <div className="px-6 pt-4 bg-surface-container-low/60">
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            Daftar Soal
                        </h3>
                        <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                            Soal tersimpan otomatis berlaku untuk seluruh kelas pada bank
                            soal ini.
                        </p>

                        <div className="flex flex-wrap gap-1 mt-4 -mb-px overflow-x-auto">
                            {tabs.map((tab) => {
                                const isActive = tab.value === activeType;

                                return (
                                    <button
                                        key={tab.value}
                                        type="button"
                                        onClick={() => handleTabChange(tab.value)}
                                        className={`flex items-center gap-2 px-4 py-2.5 rounded-t-xl text-xs font-label font-semibold border-b-2 transition-colors whitespace-nowrap ${
                                            isActive
                                                ? 'bg-surface-container-lowest text-primary border-primary'
                                                : 'text-on-surface-variant border-transparent hover:text-on-surface hover:bg-surface-container'
                                        }`}
                                    >
                                        {typeShortLabels[tab.value] ?? ''} · {tab.label}
                                        <span
                                            className={`inline-flex min-w-6 items-center justify-center rounded-lg px-1.5 py-0.5 font-mono text-[10px] ${
                                                isActive
                                                    ? 'bg-primary/10 text-primary'
                                                    : 'bg-surface-container text-on-surface-variant'
                                            }`}
                                        >
                                            {tab.count}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <div className="px-6 py-4 border-b border-outline-variant/20">
                        <div className="flex flex-wrap items-center gap-2">
                            {slots.map((slot) => {
                                const isActive = slot.number === activeNumber;

                                return (
                                    <button
                                        key={slot.number}
                                        type="button"
                                        onClick={() =>
                                            handleSlotChange(slot.number)
                                        }
                                        title={`Soal nomor ${slot.number}${
                                            slot.filled
                                                ? ' (terisi)'
                                                : ' (belum diisi)'
                                        }`}
                                        className={`w-8 h-8 rounded-lg text-xs font-mono font-semibold transition-colors ${
                                            isActive
                                                ? 'bg-error text-white shadow-sm'
                                                : slot.filled
                                                  ? 'bg-primary text-on-primary hover:brightness-110'
                                                  : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'
                                        }`}
                                    >
                                        {slot.number}
                                    </button>
                                );
                            })}
                        </div>

                        <div className="flex flex-wrap items-center gap-4 mt-3 text-[10px] font-body text-on-surface-variant">
                            <span className="inline-flex items-center gap-1.5">
                                <span className="w-3 h-3 rounded bg-primary" />
                                Soal sudah terisi
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <span className="w-3 h-3 rounded bg-error" />
                                Soal yang sedang dibuka
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <span className="w-3 h-3 rounded bg-surface-container" />
                                Belum diisi
                            </span>
                        </div>
                    </div>

                    {canManage ? (
                        <div className="p-6">
                            <QuestionForm
                                key={`${activeType}-${activeNumber}`}
                                questionBank={questionBank}
                                activeType={activeType}
                                activeTypeLabel={activeTypeLabel}
                                slotNumber={activeNumber}
                                initialData={question}
                                onDeleted={handleOpenDelete}
                            />
                        </div>
                    ) : (
                        <div className="px-6 py-10 text-center">
                            <p className="text-sm font-body text-on-surface-variant">
                                Anda hanya dapat melihat soal pada bank soal ini.
                            </p>
                        </div>
                    )}
                </Card>
            </div>

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={
                    deletingQuestion
                        ? `soal nomor ${deletingQuestion.number}`
                        : 'soal ini'
                }
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            <ClearQuestionsModal
                isOpen={clearOpen}
                onClose={handleCloseClear}
                onConfirm={handleConfirmClear}
                count={totalFilled}
                processing={clearing}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
