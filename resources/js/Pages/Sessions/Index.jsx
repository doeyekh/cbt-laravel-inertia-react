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
import SessionTable from './Components/SessionTable';
import SessionModal from './Components/SessionModal';
import { route } from '@/Utils/route';

export default function SessionsIndex({ sessions, schools = [], filters, stats }) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(auth?.user?.is_administrator || auth?.user?.is_school_admin);

    const [search, setSearch] = useState(filters?.search ?? '');
    const [schoolFilter, setSchoolFilter] = useState(
        filters?.school_id ? String(filters.school_id) : ''
    );
    const [modalOpen, setModalOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = sessions?.data ?? [];
    const editingItem = rows.find((session) => session.id === editingId) ?? null;
    const deletingItem = rows.find((session) => session.id === deletingId) ?? null;

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.sessions.index'),
                {
                    ...(search ? { search } : {}),
                    ...(schoolFilter ? { school_id: schoolFilter } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['sessions', 'filters', 'stats'],
                }
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, schoolFilter]);

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

    const handleCloseDelete = () => {
        if (deleting) {
            return;
        }

        setDeleteOpen(false);
        setDeletingId(null);
    };

    const handlePageChange = (page) => {
        router.get(
            route('admin.sessions.index'),
            {
                ...(filters?.search ? { search: filters.search } : {}),
                ...(filters?.school_id ? { school_id: filters.school_id } : {}),
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

        router.delete(route('admin.sessions.destroy', deletingItem.id), {
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
            route('admin.sessions.toggle-status', item.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setTogglingId(null),
            }
        );
    };

    const total = stats?.total ?? sessions?.total ?? 0;
    const activeType = stats?.active ?? null;
    const activeCount = activeType ? 1 : 0;
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
                            <span className="text-primary font-semibold">Pengelolaan Ujian</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Manajemen Sesi Ujian
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Kelola sesi waktu ujian yang digunakan pada paket dan jadwal ujian.
                        </p>
                    </div>

                    {canManage && (
                        <div className="flex flex-wrap items-center gap-3">
                            <Button onClick={handleOpenCreate}>
                                <span className="material-symbols-outlined text-[18px]">add</span>
                                Tambah Sesi
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
                                    Total Sesi
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {total}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                <span className="material-symbols-outlined text-primary text-[24px]">
                                    access_time
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
                                    {activeCount}
                                </h3>
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
                                Daftar Sesi Ujian
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Klik badge status untuk mengaktifkan atau menonaktifkan.
                            </p>
                        </div>
                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                            <Select
                                id="session-school-filter"
                                value={schoolFilter}
                                onChange={(event) => setSchoolFilter(event.target.value)}
                                className="w-full sm:w-56"
                                aria-label="Filter sekolah"
                            >
                                <option value="">Semua Sekolah</option>
                                {schools.map((school) => (
                                    <option key={school.id} value={school.id}>
                                        {school.name}
                                    </option>
                                ))}
                            </Select>
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari kode atau nama sesi..."
                                className="w-full sm:w-72"
                                aria-label="Cari sesi ujian"
                            />
                        </div>
                    </div>

                    <SessionTable
                        sessions={rows}
                        onEdit={handleOpenEdit}
                        onDelete={handleOpenDelete}
                        onToggleStatus={handleToggleStatus}
                        togglingId={togglingId}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((sessions?.from ?? 1) - 1, 0)}
                        canManage={canManage}
                    />

                    <Pagination
                        currentPage={sessions?.current_page ?? 1}
                        lastPage={sessions?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={sessions?.from ?? 0}
                        to={sessions?.to ?? 0}
                        total={sessions?.total ?? 0}
                        itemLabel="sesi"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            {/* Modals */}
            <SessionModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={editingId ? 'edit' : 'create'}
                initialData={editingItem}
                schools={schools}
            />

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
