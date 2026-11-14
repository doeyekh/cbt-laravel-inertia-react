import React, { useEffect, useMemo, useRef, useState } from 'react';
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
import ExamPackageTable from './Components/ExamPackageTable';
import ExamPackageModal from './Components/ExamPackageModal';
import { route } from '@/Utils/route';

export default function ExamPackagesIndex({
    examPackages,
    schools = [],
    activeAcademicYear = null,
    examTypes = [],
    statuses = [],
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
    const [examTypeFilter, setExamTypeFilter] = useState(
        filters?.exam_type_id ? String(filters.exam_type_id) : ''
    );
    const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = examPackages?.data ?? [];
    const editingItem = rows.find((item) => item.id === editingId) ?? null;
    const deletingItem = rows.find((item) => item.id === deletingId) ?? null;

    const availableExamTypes = useMemo(
        () =>
            examTypes.filter(
                (examType) =>
                    !schoolFilter || String(examType.school_id) === String(schoolFilter)
            ),
        [examTypes, schoolFilter]
    );

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.exam-packages.index'),
                {
                    ...(search ? { search } : {}),
                    ...(schoolFilter ? { school_id: schoolFilter } : {}),
                    ...(examTypeFilter ? { exam_type_id: examTypeFilter } : {}),
                    ...(statusFilter ? { status: statusFilter } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['examPackages', 'filters', 'stats'],
                }
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, schoolFilter, examTypeFilter, statusFilter]);

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleSchoolFilterChange = (value) => {
        if (value !== schoolFilter) {
            setExamTypeFilter('');
        }

        setSchoolFilter(value);
    };

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

    const handleOpenShow = (item) => {
        router.get(route('admin.exam-packages.show', item.id));
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

    const handlePageChange = (page) => {
        router.get(
            route('admin.exam-packages.index'),
            {
                ...(filters?.search ? { search: filters.search } : {}),
                ...(filters?.school_id ? { school_id: filters.school_id } : {}),
                ...(filters?.exam_type_id
                    ? { exam_type_id: filters.exam_type_id }
                    : {}),
                ...(filters?.status ? { status: filters.status } : {}),
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleConfirmDelete = () => {
        if (!deletingItem) {
            return;
        }

        setDeleting(true);

        router.delete(route('admin.exam-packages.destroy', deletingItem.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteOpen(false);
                setDeletingId(null);
            },
            onFinish: () => setDeleting(false),
        });
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
                            Paket Ujian
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Kelola event ujian dan atur jadwal per kelas, mata pelajaran,
                            sesi, serta ruangan.
                        </p>
                    </div>

                    {canManage && (
                        <div className="flex flex-wrap items-center gap-3">
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
                                Tambah Paket Ujian
                            </Button>
                        </div>
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
                                membuat paket ujian.
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Paket
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
                                    Draft
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.draft ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-surface-container flex items-center justify-center">
                                <span className="material-symbols-outlined text-on-surface-variant text-[24px]">
                                    edit_note
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
                                    play_circle
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Selesai
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.completed ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-tertiary-container/30 flex items-center justify-center">
                                <span className="material-symbols-outlined text-tertiary text-[24px]">
                                    task_alt
                                </span>
                            </div>
                        </div>
                    </Card>
                </div>

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Daftar Paket Ujian
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Tahun pelajaran aktif:{' '}
                                <span className="font-semibold text-on-surface">
                                    {activeAcademicYear?.name ?? 'belum diatur'}
                                </span>
                                . Buka detail paket untuk mengelola jadwal
                                ujian.
                            </p>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2 w-full xl:w-auto">
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari nama, kode, sekolah..."
                                aria-label="Cari paket ujian"
                                className="sm:col-span-2 xl:w-60"
                            />
                            <Select
                                id="exam-package-school-filter"
                                value={schoolFilter}
                                onChange={(event) =>
                                    handleSchoolFilterChange(event.target.value)
                                }
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
                                id="exam-package-type-filter"
                                value={examTypeFilter}
                                onChange={(event) =>
                                    setExamTypeFilter(event.target.value)
                                }
                                aria-label="Filter jenis ujian"
                                className="xl:w-44"
                            >
                                <option value="">Semua Jenis</option>
                                {availableExamTypes.map((examType) => (
                                    <option key={examType.id} value={String(examType.id)}>
                                        {examType.name}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                id="exam-package-status-filter"
                                value={statusFilter}
                                onChange={(event) => setStatusFilter(event.target.value)}
                                aria-label="Filter status"
                                className="xl:w-36"
                            >
                                <option value="">Semua Status</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                        </div>
                    </div>

                    <ExamPackageTable
                        examPackages={rows}
                        onShow={handleOpenShow}
                        onEdit={handleOpenEdit}
                        onDelete={handleOpenDelete}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((examPackages?.from ?? 1) - 1, 0)}
                        canManage={canManage}
                    />

                    <Pagination
                        currentPage={examPackages?.current_page ?? 1}
                        lastPage={examPackages?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={examPackages?.from ?? 0}
                        to={examPackages?.to ?? 0}
                        total={examPackages?.total ?? 0}
                        itemLabel="paket ujian"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            <ExamPackageModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                initialData={editingItem}
                schools={schools}
                academicYear={activeAcademicYear}
                examTypes={examTypes}
                statuses={statuses}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={
                    deletingItem
                        ? `${deletingItem.name} (${deletingItem.code}) beserta seluruh jadwalnya`
                        : 'paket ujian ini beserta seluruh jadwalnya'
                }
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
