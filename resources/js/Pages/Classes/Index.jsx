import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Pagination from '@/Components/UI/Pagination';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import ClassTable from './Components/ClassTable';
import ClassModal from './Components/ClassModal';
import { route } from '@/Utils/route';
import StatCardGroup from '../../Components/UI/stat-card-group';
import useClassStatistic from './Components/useClassStatistuc';

export default function ClassesIndex({ classes, filters, schools, academicYears, stats }) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const user = usePage().props.auth?.user;
    const canManageClasses = Boolean(user?.is_administrator || user?.is_school_admin);

    const [search, setSearch] = useState(filters?.search ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = classes?.data ?? [];
    const editingItem = rows.find((schoolClass) => schoolClass.id === editingId) ?? null;
    const deletingItem = rows.find((schoolClass) => schoolClass.id === deletingId) ?? null;

    const statistics = useClassStatistic({
        total,
        activeCount,
        inactiveCount,
    })

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.classes.index'),
                search ? { search } : {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['classes', 'filters'],
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

    const handleOpenLearning = (item) => {
        router.get(route('admin.subject-teachers.index', { schoolClass: item.id }), {
            preserveScroll: true,
        });
    };

    const handleOpenStudents = (item) => {
        router.get(route('admin.class-members.index', { schoolClass: item.id }), {
            preserveScroll: true,
        });
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

        router.delete(route('admin.classes.destroy', deletingItem.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteOpen(false);
                setDeletingId(null);
            },
            onFinish: () => setDeleting(false),
        });
    };

    const handleToggleStatus = (item) => {
        if (togglingId !== null) {
            return;
        }

        setTogglingId(item.id);

        router.patch(
            route('admin.classes.toggle-status', item.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setTogglingId(null),
            }
        );
    };

    const handlePageChange = (page) => {
        router.get(
            route('admin.classes.index'),
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

    const total = stats?.total ?? classes?.total ?? 0;
    const activeCount = stats?.active ?? 0;
    const inactiveCount = stats?.inactive ?? 0;

    

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
                            Manajemen Kelas
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Kelola kelas per sekolah dan tahun pelajaran.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        {canManageClasses && (
                            <Button onClick={handleOpenCreate}>
                                <span className="material-symbols-outlined text-[18px]">add</span>
                                Tambah Kelas
                            </Button>
                        )}
                    </div>
                </div>

                {/* Stats Cards */}
                <div>
                    <StatCardGroup items={statistics} columns={3} />
                </div>

                {/* Table */}
                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Daftar Kelas
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Klik badge status untuk mengaktifkan atau menonaktifkan.
                            </p>
                        </div>
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari kode, nama, atau sekolah..."
                            className="w-full sm:w-72"
                            aria-label="Cari kelas"
                        />
                    </div>

                    <ClassTable
                        classes={rows}
                        canManage={canManageClasses}
                        onEdit={handleOpenEdit}
                        onDelete={handleOpenDelete}
                        onToggleStatus={handleToggleStatus}
                        togglingId={togglingId}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((classes?.from ?? 1) - 1, 0)}
                        onOpenLearning={handleOpenLearning}
                        onOpenStudents={handleOpenStudents}
                    />

                    <Pagination
                        currentPage={classes?.current_page ?? 1}
                        lastPage={classes?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={classes?.from ?? 0}
                        to={classes?.to ?? 0}
                        total={classes?.total ?? 0}
                        itemLabel="kelas"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            {/* Modals */}
            {canManageClasses && (
                <ClassModal
                    isOpen={modalOpen}
                    onClose={handleCloseModal}
                    mode={editingId ? 'edit' : 'create'}
                    initialData={editingItem}
                    schools={schools}
                />
            )}

            {canManageClasses && (
                <DeleteConfirmation
                    isOpen={deleteOpen}
                    itemName={
                        deletingItem
                            ? `${deletingItem.name} (${deletingItem.code})`
                            : 'data ini'
                    }
                    onConfirm={handleConfirmDelete}
                    onCancel={handleCloseDelete}
                    loading={deleting}
                />
            )}

            {/* Toast Notifications */}
            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
