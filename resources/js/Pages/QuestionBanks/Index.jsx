import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Pagination from '@/Components/UI/Pagination';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import QuestionBankTable from './Components/QuestionBankTable';
import QuestionBankModal from './Components/QuestionBankModal';
import { route } from '@/Utils/route';

export default function QuestionBanksIndex({
    questionBanks,
    schools = [],
    subjects = [],
    subjectOptions = [],
    examTypes = [],
    classes = [],
    activeAcademicYear = null,
    filters,
    stats,
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(auth?.user?.is_administrator || auth?.user?.is_school_admin);
    const hasActiveYear = Boolean(activeAcademicYear?.id);

    const [search, setSearch] = useState(filters?.search ?? '');
    const [schoolFilter, setSchoolFilter] = useState(
        filters?.school_id ? String(filters.school_id) : ''
    );
    const [subjectFilter, setSubjectFilter] = useState(
        filters?.subject_id ? String(filters.subject_id) : ''
    );
    const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');

    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingId, setEditingId] = useState(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = questionBanks?.data ?? [];
    const editingItem = rows.find((item) => item.id === editingId) ?? null;
    const deletingItem = rows.find((item) => item.id === deletingId) ?? null;

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.question-banks.index'),
                {
                    ...(search ? { search } : {}),
                    ...(schoolFilter ? { school_id: schoolFilter } : {}),
                    ...(subjectFilter ? { subject_id: subjectFilter } : {}),
                    ...(statusFilter ? { status: statusFilter } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['questionBanks', 'filters', 'stats'],
                }
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, schoolFilter, subjectFilter, statusFilter]);

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleOpenCreate = () => {
        setEditingId(null);
        setModalMode('create');
        setModalOpen(true);
    };

    const handleOpenEdit = (item) => {
        setEditingId(item.id);
        setModalMode('edit');
        setModalOpen(true);
    };

    const handleOpenQuestions = (item) => {
        router.get(route('admin.question-banks.questions.index', item.id));
    };

    const handleOpenPreview = (item) => {
        router.get(route('admin.question-banks.questions.preview', item.id));
    };

    const handleOpenImport = (item) => {
        router.get(route('admin.question-banks.questions.import', item.id));
    };

    const handleCloseModal = () => {
        setModalOpen(false);
        setEditingId(null);
    };

    const handleOpenDelete = (item) => {
        setDeletingId(item.id);
        setDeleteOpen(true);
    };

    const handleCloseDelete = () => {
        if (deleting) {
            return;
        }

        setDeleteOpen(false);
        setDeletingId(null);
    };

    const handleConfirmDelete = () => {
        if (!deletingItem) {
            return;
        }

        setDeleting(true);

        router.delete(route('admin.question-banks.destroy', deletingItem.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteOpen(false);
                setDeletingId(null);
            },
            onFinish: () => setDeleting(false),
        });
    };

    const handlePageChange = (page) => {
        router.get(
            route('admin.question-banks.index'),
            {
                ...(filters?.search ? { search: filters.search } : {}),
                ...(filters?.school_id ? { school_id: filters.school_id } : {}),
                ...(filters?.subject_id ? { subject_id: filters.subject_id } : {}),
                ...(filters?.status ? { status: filters.status } : {}),
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1720px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">
                                Pengelolaan Ujian
                            </span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Bank Soal
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Buat paket bank soal yang menentukan komposisi jumlah soal
                            per tipe dan berlaku untuk beberapa kelas.
                        </p>
                    </div>

                    {canManage && (
                        <Button
                            onClick={handleOpenCreate}
                            disabled={!hasActiveYear}
                            title={
                                hasActiveYear
                                    ? undefined
                                    : 'Belum ada tahun pelajaran aktif'
                            }
                        >
                            <span className="material-symbols-outlined text-[18px]">
                                add
                            </span>
                            Tambah Bank Soal
                        </Button>
                    )}
                </div>

                {!hasActiveYear && (
                    <div className="flex items-start gap-3 rounded-2xl bg-error-container/30 border border-error-container/60 px-4 py-3">
                        <span className="material-symbols-outlined text-error text-[20px] shrink-0">
                            error
                        </span>
                        <div>
                            <p className="font-label font-semibold text-sm text-on-surface">
                                Belum ada tahun pelajaran aktif
                            </p>
                            <p className="text-xs text-on-surface-variant font-body mt-0.5">
                                Aktifkan salah satu tahun pelajaran sebelum
                                membuat bank soal.
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Bank Soal
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.total ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                <span className="material-symbols-outlined text-primary text-[24px]">
                                    inventory_2
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Aktif
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.active ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center">
                                <span className="material-symbols-outlined text-success text-[24px]">
                                    task_alt
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Nonaktif
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.inactive ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-surface-container flex items-center justify-center">
                                <span className="material-symbols-outlined text-on-surface-variant text-[24px]">
                                    visibility_off
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Soal Dirancang
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.question_total ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-tertiary-container/30 flex items-center justify-center">
                                <span className="material-symbols-outlined text-tertiary text-[24px]">
                                    quiz
                                </span>
                            </div>
                        </div>
                    </Card>
                </div>

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Daftar Bank Soal
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Tahun pelajaran aktif:{' '}
                                <span className="font-semibold text-on-surface">
                                    {activeAcademicYear?.name ?? 'belum diatur'}
                                </span>
                                . PG = Pilihan Ganda, PK = Pilihan Kompleks, MJ =
                                Menjodohkan, IS = Isian, ES = Esai.
                            </p>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2 w-full xl:w-auto">
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari nama, kode, mapel..."
                                aria-label="Cari bank soal"
                                className="sm:col-span-2 xl:w-60"
                            />
                            <Select
                                id="question-bank-school-filter"
                                value={schoolFilter}
                                onChange={(event) => setSchoolFilter(event.target.value)}
                                aria-label="Filter sekolah"
                                className="xl:w-44"
                            >
                                <option value="">Semua Sekolah</option>
                                {schools.map((school) => (
                                    <option key={school.id} value={String(school.id)}>
                                        {school.name}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                id="question-bank-subject-filter"
                                value={subjectFilter}
                                onChange={(event) => setSubjectFilter(event.target.value)}
                                aria-label="Filter mata pelajaran"
                                className="xl:w-44"
                            >
                                <option value="">Semua Mapel</option>
                                {subjects.map((subject) => (
                                    <option key={subject.id} value={String(subject.id)}>
                                        {subject.name}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                id="question-bank-status-filter"
                                value={statusFilter}
                                onChange={(event) => setStatusFilter(event.target.value)}
                                aria-label="Filter status"
                                className="xl:w-32"
                            >
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </Select>
                        </div>
                    </div>

                    <QuestionBankTable
                        questionBanks={rows}
                        onQuestions={handleOpenQuestions}
                        onPreview={handleOpenPreview}
                        onImport={handleOpenImport}
                        onEdit={handleOpenEdit}
                        onDelete={handleOpenDelete}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((questionBanks?.from ?? 1) - 1, 0)}
                        canManage={canManage}
                    />

                    <Pagination
                        currentPage={questionBanks?.current_page ?? 1}
                        lastPage={questionBanks?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={questionBanks?.from ?? 0}
                        to={questionBanks?.to ?? 0}
                        total={questionBanks?.total ?? 0}
                        itemLabel="bank soal"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            <QuestionBankModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                initialData={editingItem}
                schools={schools}
                subjects={subjects}
                subjectOptions={subjectOptions}
                examTypes={examTypes}
                classes={classes}
                academicYear={activeAcademicYear}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={
                    deletingItem
                        ? `${deletingItem.name} (${deletingItem.code})`
                        : 'bank soal ini'
                }
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
