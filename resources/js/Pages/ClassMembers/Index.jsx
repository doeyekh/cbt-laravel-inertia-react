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
import ClassMemberTable from './Components/ClassMemberTable';
import AddClassMemberModal from './Components/AddClassMemberModal';
import { route } from '@/Utils/route';

export default function ClassMembersIndex({
    schoolClass,
    members,
    filters,
    canManage = false,
    stats,
}) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();

    const [search, setSearch] = useState(filters?.search ?? '');
    const [addOpen, setAddOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = members?.data ?? [];
    const deletingItem = rows.find((member) => member.id === deletingId) ?? null;

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.class-members.index', { schoolClass: schoolClass?.id }),
                search ? { search } : {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['members', 'filters', 'stats'],
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

    const handleBack = () => {
        router.get(route('admin.classes.index'), { preserveScroll: true });
    };

    const handlePageChange = (page) => {
        router.get(
            route('admin.class-members.index', { schoolClass: schoolClass?.id }),
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

        router.delete(
            route('admin.class-members.destroy', {
                schoolClass: schoolClass?.id,
                classMember: deletingItem.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteOpen(false);
                    setDeletingId(null);
                },
                onFinish: () => setDeleting(false),
            }
        );
    };

    const total = stats?.total ?? members?.total ?? 0;
    const maleCount = stats?.male ?? 0;
    const femaleCount = stats?.female ?? 0;

    const className = schoolClass
        ? `${schoolClass.code} · ${schoolClass.name}`
        : 'Kelas';

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1720px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Master Data</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Kelas</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Anggota Kelas</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Anggota Kelas
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            {className} · {schoolClass?.school_name} · Tahun{' '}
                            {schoolClass?.academic_year_name}
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button variant="secondary" onClick={handleBack}>
                            <span className="material-symbols-outlined text-[18px]">
                                arrow_back
                            </span>
                            Kembali
                        </Button>
                        {canManage && (
                            <Button onClick={() => setAddOpen(true)}>
                                <span className="material-symbols-outlined text-[18px]">
                                    person_add
                                </span>
                                Tambah Siswa
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Anggota
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {total}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                <span className="material-symbols-outlined text-primary text-[24px]">
                                    groups
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Laki-laki
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 truncate">
                                    {maleCount}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-secondary-container flex items-center justify-center shrink-0">
                                <span className="material-symbols-outlined text-secondary text-[24px]">
                                    male
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Perempuan
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {femaleCount}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-tertiary-container/30 flex items-center justify-center">
                                <span className="material-symbols-outlined text-tertiary text-[24px]">
                                    female
                                </span>
                            </div>
                        </div>
                    </Card>
                </div>

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Daftar Anggota
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Siswa kelas ini pada tahun pelajaran yang sama.
                            </p>
                        </div>
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama, NIS, atau NISN..."
                            className="w-full sm:w-72"
                            aria-label="Cari anggota kelas"
                        />
                    </div>

                    <ClassMemberTable
                        members={rows}
                        canManage={canManage}
                        onRemove={handleOpenDelete}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((members?.from ?? 1) - 1, 0)}
                    />

                    <Pagination
                        currentPage={members?.current_page ?? 1}
                        lastPage={members?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={members?.from ?? 0}
                        to={members?.to ?? 0}
                        total={members?.total ?? 0}
                        itemLabel="siswa"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            <AddClassMemberModal
                isOpen={addOpen}
                onClose={() => setAddOpen(false)}
                schoolClassId={schoolClass?.id}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={deletingItem ? deletingItem.name : 'siswa ini'}
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
