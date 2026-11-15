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
import SubjectTable from './Components/SubjectTable';
import SubjectModal from './Components/SubjectModal';
import ImportSubjectsModal from './Components/ImportSubjectsModal';
import { route } from '@/Utils/route';

const PER_PAGE = 5;

export default function SubjectsIndex({ subjects, filters, stats, permissions }) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();

    const [search, setSearch] = useState(filters?.search ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [importOpen, setImportOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = subjects?.data ?? [];
    const selectedItem = rows.find((item) => item.id === editingId) ?? null;
    const deletingItem = rows.find((item) => item.id === deletingId) ?? null;

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.subjects.index'),
                search ? { search } : {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['subjects', 'filters'],
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
        setModalMode('create');
        setModalOpen(true);
    };

    const handleOpenEdit = (item) => {
        setEditingId(item.id);
        setModalMode('edit');
        setModalOpen(true);
    };

    const handleOpenShow = (item) => {
        setEditingId(item.id);
        setModalMode('show');
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
            route('admin.subjects.index'),
            {
                ...(filters?.search ? { search: filters.search } : {}),
                page,
                per_page: PER_PAGE,
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

        router.delete(route('admin.subjects.destroy', deletingItem.id), {
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
            route('admin.subjects.toggle-status', item.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setTogglingId(null),
            }
        );
    };

    const total = stats?.total ?? subjects?.total ?? 0;
    const activeSubject = stats?.active ?? null;
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
                            Manajemen Mata Pelajaran
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            {permissions?.can_create
                                ? 'Kelola daftar mata pelajaran yang diujikan dalam sistem evaluasi.'
                                : 'Lihat daftar mata pelajaran yang diujikan dalam sistem evaluasi.'}
                        </p>
                    </div>

                    {permissions?.can_create && (
                        <div className="flex flex-wrap items-center gap-3">
                            <Button
                                variant="secondary"
                                onClick={() => setImportOpen(true)}
                            >
                                <span className="material-symbols-outlined text-[18px]">upload_file</span>
                                Import Excel
                            </Button>
                            <Button onClick={handleOpenCreate}>
                                <span className="material-symbols-outlined text-[18px]">add</span>
                                Tambah Mata Pelajaran
                            </Button>
                        </div>
                    )}
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Mata Pelajaran
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {total}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                <span className="material-symbols-outlined text-primary text-[24px]">
                                    menu_book
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Aktif
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 truncate">
                                    {activeSubject?.name ?? '-'}
                                </h3>
                                {activeSubject && (
                                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                                        Kode {activeSubject.code}
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
                                    {inactiveCount}
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
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Daftar Mata Pelajaran
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                {permissions?.can_create
                                    ? 'Klik badge status untuk mengaktifkan atau menonaktifkan.'
                                    : 'Informasi mata pelajaran.'}
                            </p>
                        </div>
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari kode atau nama mata pelajaran..."
                            className="w-full sm:w-72"
                            aria-label="Cari mata pelajaran"
                        />
                    </div>

                    <SubjectTable
                        subjects={rows}
                        onShow={handleOpenShow}
                        onEdit={handleOpenEdit}
                        onDelete={handleOpenDelete}
                        onToggleStatus={handleToggleStatus}
                        togglingId={togglingId}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((subjects?.from ?? 1) - 1, 0)}
                    />

                    <Pagination
                        currentPage={subjects?.current_page ?? 1}
                        lastPage={subjects?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={subjects?.from ?? 0}
                        to={subjects?.to ?? 0}
                        total={subjects?.total ?? 0}
                        itemLabel="mata pelajaran"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            {/* Modals */}
            <SubjectModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                initialData={selectedItem}
                onSwitchToEdit={selectedItem?.can_edit ? handleOpenEdit : null}
            />

            {permissions?.can_create && (
                <ImportSubjectsModal
                    isOpen={importOpen}
                    onClose={() => setImportOpen(false)}
                />
            )}

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

            {/* Toast Notifications */}
            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
