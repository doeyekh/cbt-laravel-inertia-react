import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Badge from '@/Components/UI/Badge';
import Pagination from '@/Components/UI/Pagination';
import AcademicYearTable from './Components/AcademicYearTable';
import AcademicYearModal from './Components/AcademicYearModal';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import { route } from '@/Utils/route';

export default function AcademicYearsIndex({ academicYears, filters, semesters, stats }) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();

    const [search, setSearch] = useState(filters?.search ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [generating, setGenerating] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = academicYears?.data ?? [];
    const activeYear = stats?.active ?? null;
    const editingItem = rows.find((year) => year.id === editingId) ?? null;
    const deletingItem = rows.find((year) => year.id === deletingId) ?? null;

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.academic-years.index'),
                search ? { search } : {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['academicYears', 'filters'],
                }
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleGenerate = () => {
        setGenerating(true);

        router.post(
            route('admin.academic-years.generate'),
            {},
            {
                preserveScroll: true,
                onFinish: () => setGenerating(false),
            }
        );
    };

    const handleToggleStatus = (item) => {
        if (togglingId !== null) {
            return;
        }

        setTogglingId(item.id);

        router.patch(
            route('admin.academic-years.toggle-status', item.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setTogglingId(null),
            }
        );
    };

    const handleOpenCreate = () => {
        setEditingId(null);
        setModalOpen(true);
    };

    const handleOpenEdit = (item) => {
        setEditingId(item.id);
        setModalOpen(true);
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
            route('admin.academic-years.index'),
            {
                ...(filters?.search ? { search: filters.search } : {}),
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

        router.delete(route('admin.academic-years.destroy', deletingItem.id), {
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
                {/* Header Page */}
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Master Data</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Manajemen Tahun Pelajaran
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Kelola data tahun pelajaran untuk periode ujian.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Badge
                            variant={activeYear ? 'success' : 'secondary'}
                            className="flex items-center gap-1.5"
                        >
                            <span className="material-symbols-outlined text-[14px]">
                                {activeYear ? 'check_circle' : 'event_busy'}
                            </span>
                            {activeYear
                                ? `Aktif: ${activeYear.name} - ${activeYear.semester_label}`
                                : 'Tidak ada tahun aktif'}
                        </Badge>
                        <Button
                            variant="secondary"
                            onClick={handleGenerate}
                            loading={generating}
                        >
                            <span className="material-symbols-outlined text-[18px]">
                                auto_awesome
                            </span>
                            <span className="hidden sm:inline">Generate Otomatis</span>
                            <span className="sm:hidden">Generate</span>
                        </Button>
                        <Button
                            onClick={handleOpenCreate}
                            className="hidden sm:flex"
                        >
                            <span className="material-symbols-outlined text-[18px]">add</span>
                            Tambah Baru
                        </Button>
                        <Button variant="outline" onClick={handleOpenCreate} className="sm:hidden">
                            <span className="material-symbols-outlined text-[18px]">add</span>
                            Tambah
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Tahun Pelajaran
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.total ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                <span className="material-symbols-outlined text-primary text-[24px]">
                                    school
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Tahun Aktif
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 truncate">
                                    {activeYear ? activeYear.name : '-'}
                                </h3>
                                {activeYear && (
                                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                                        {activeYear.semester_label} - Kode {activeYear.code}
                                    </p>
                                )}
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                                <span className="material-symbols-outlined text-success text-[24px]">
                                    check_circle
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Tidak Aktif
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {stats?.inactive ?? 0}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-tertiary-container/30 flex items-center justify-center">
                                <span className="material-symbols-outlined text-tertiary text-[24px]">
                                    history
                                </span>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Table */}
                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            Daftar Tahun Pelajaran
                        </h3>
                        <div className="flex items-center gap-2">
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari nama atau kode..."
                                className="w-full sm:w-64"
                                aria-label="Cari tahun pelajaran"
                            />
                        </div>
                    </div>
                    <div>
                        <AcademicYearTable
                            academicYears={rows}
                            onEdit={handleOpenEdit}
                            onDelete={handleOpenDelete}
                            onToggleStatus={handleToggleStatus}
                            togglingId={togglingId}
                            searchQuery={filters?.search ?? ''}
                            startIndex={Math.max((academicYears?.from ?? 1) - 1, 0)}
                        />
                    </div>
                    <Pagination
                        currentPage={academicYears?.current_page ?? 1}
                        lastPage={academicYears?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={academicYears?.from ?? 0}
                        to={academicYears?.to ?? 0}
                        total={academicYears?.total ?? 0}
                        itemLabel="data"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            {/* Modals */}
            <AcademicYearModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={editingId ? 'edit' : 'create'}
                initialData={editingItem}
                semesters={semesters ?? []}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={
                    deletingItem
                        ? `${deletingItem.name} (${deletingItem.semester_label})`
                        : 'data ini'
                }
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            {/* Toast Notifications */}
            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
