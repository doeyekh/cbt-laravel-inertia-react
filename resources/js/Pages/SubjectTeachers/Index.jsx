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
import SubjectTeacherTable from './Components/SubjectTeacherTable';
import SubjectTeacherModal from './Components/SubjectTeacherModal';
import { route } from '@/Utils/route';

const PER_PAGE = 5;

export default function SubjectTeachersIndex({
    subjectTeachers,
    schoolClass,
    subjects,
    teachers,
    filters,
    stats,
}) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();

    const [search, setSearch] = useState(filters?.search ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = subjectTeachers?.data ?? [];
    const selectedItem = rows.find((item) => item.id === editingId) ?? null;
    const deletingItem = rows.find((item) => item.id === deletingId) ?? null;

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.subject-teachers.index', { schoolClass: schoolClass?.id }),
                search ? { search } : {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['subjectTeachers', 'filters'],
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
            route('admin.subject-teachers.index', { schoolClass: schoolClass?.id }),
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

        router.delete(
            route('admin.subject-teachers.destroy', {
                schoolClass: schoolClass?.id,
                subjectTeacher: deletingItem.id,
            }),
            {
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
            route('admin.subject-teachers.toggle-status', {
                schoolClass: schoolClass?.id,
                subjectTeacher: item.id,
            }),
            {},
            {
                preserveScroll: true,
                onFinish: () => setTogglingId(null),
            }
        );
    };

    const total = stats?.total ?? subjectTeachers?.total ?? 0;
    const activeCount = stats?.active ?? 0;
    const inactiveCount = stats?.inactive ?? 0;

    const schoolClassName = schoolClass
        ? `${schoolClass.code} · ${schoolClass.name}`
        : 'Kelas';

    const schoolClassYearLabel = schoolClass?.academic_year_name
        ? `Tahun ${schoolClass.academic_year_name}`
        : '';

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
                            Pembelajaran
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Penentuan guru pengampuh mata pelajaran untuk {schoolClassName} ·{' '}
                            {schoolClassYearLabel}.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button onClick={handleOpenCreate}>
                            <span className="material-symbols-outlined text-[18px]">person_add</span>
                            Tentukan Guru
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Penugasan
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                                    {total}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                <span className="material-symbols-outlined text-primary text-[24px]">
                                    assignment_ind
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
                                Guru Pengampuh Mata Pelajaran
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Daftar mata pelajaran dan guru yang mengampuh di kelas ini.
                            </p>
                        </div>
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari mata pelajaran atau guru..."
                            className="w-full sm:w-72"
                            aria-label="Cari mata pelajaran atau guru"
                        />
                    </div>

                    <SubjectTeacherTable
                        subjectTeachers={rows}
                        onEdit={handleOpenEdit}
                        onDelete={handleOpenDelete}
                        onToggleStatus={handleToggleStatus}
                        togglingId={togglingId}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((subjectTeachers?.from ?? 1) - 1, 0)}
                    />

                    <Pagination
                        currentPage={subjectTeachers?.current_page ?? 1}
                        lastPage={subjectTeachers?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={subjectTeachers?.from ?? 0}
                        to={subjectTeachers?.to ?? 0}
                        total={subjectTeachers?.total ?? 0}
                        itemLabel="penugasan"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            {/* Modals */}
            <SubjectTeacherModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                initialData={selectedItem}
                subjects={subjects}
                teachers={teachers}
                schoolClassId={schoolClass?.id}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={
                    deletingItem
                        ? `${deletingItem.subject_name ?? 'Mata Pelajaran'} — ${deletingItem.teacher_name ?? 'Guru'}`
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
