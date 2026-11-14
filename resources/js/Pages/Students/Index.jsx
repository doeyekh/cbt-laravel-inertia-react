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
import StudentTable from './Components/StudentTable';
import StudentModal from './Components/StudentModal';
import ImportStudentsModal from './Components/ImportStudentsModal';
import BulkGraduateModal from './Components/BulkGraduateModal';
import PullStudentModal from './Components/PullStudentModal';
import { route } from '@/Utils/route';

export default function StudentsIndex({
    students,
    filters,
    scope = 'all',
    schools = [],
    classes = [],
    genders = [],
    statuses = [],
    academicYears = [],
    stats,
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const user = auth?.user;
    const isAdministrator = Boolean(user?.is_administrator || user?.role === 'administrator');
    const canManage = Boolean(user?.is_administrator || user?.is_school_admin);

    const scopeMeta = {
        active: {
            title: 'Siswa Aktif',
            description:
                'Daftar siswa yang masih memiliki registrasi berstatus aktif pada sekolahnya.',
            tableTitle: 'Daftar Siswa Aktif',
            tableDescription:
                'Hanya siswa dengan registrasi aktif yang ditampilkan. Sekolah dan kelas mengikuti data registrasi serta tahun pelajaran aktif.',
        },
        alumni: {
            title: 'Alumni / Non Aktif',
            description:
                'Daftar siswa yang tidak lagi memiliki registrasi aktif, termasuk lulus, pindah, dan keluar.',
            tableTitle: 'Daftar Alumni / Non Aktif',
            tableDescription:
                'Siswa tanpa registrasi aktif ditampilkan di sini, termasuk yang berstatus lulus, pindah, atau keluar.',
        },
        all: {
            title: 'Manajemen Siswa',
            description: 'Kelola identitas siswa, registrasi sekolah, dan status keaktifannya.',
            tableTitle: 'Daftar Siswa',
            tableDescription:
                'Sekolah dan kelas yang ditampilkan mengikuti registrasi dan tahun pelajaran aktif.',
        },
    }[scope] ?? {
        title: 'Manajemen Siswa',
        description: 'Kelola identitas siswa, registrasi sekolah, dan status keaktifannya.',
        tableTitle: 'Daftar Siswa',
        tableDescription:
            'Sekolah dan kelas yang ditampilkan mengikuti registrasi dan tahun pelajaran aktif.',
    };

    const listRoute =
        scope === 'active'
            ? 'admin.students.active'
            : scope === 'alumni'
              ? 'admin.students.alumni'
              : 'admin.students.index';

    const [search, setSearch] = useState(filters?.search ?? '');
    const [schoolId, setSchoolId] = useState(
        filters?.school_id ? String(filters.school_id) : ''
    );
    const [classId, setClassId] = useState(filters?.class_id ? String(filters.class_id) : '');
    const [status, setStatus] = useState(
        scope === 'active' ? '' : (filters?.status ?? '')
    );
    const [gender, setGender] = useState(filters?.gender ?? '');

    const [modalOpen, setModalOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [pullOpen, setPullOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [selectedIds, setSelectedIds] = useState([]);
    const [graduateOpen, setGraduateOpen] = useState(false);
    const [graduating, setGraduating] = useState(false);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = students?.data ?? [];
    const selectable = scope === 'active' && canManage;
    const allSelected =
        rows.length > 0 && rows.every((student) => selectedIds.includes(student.id));
    const editingItem = rows.find((student) => student.id === editingId) ?? null;
    const deletingItem = rows.find((student) => student.id === deletingId) ?? null;

    const buildParams = (overrides = {}) => {
        const values = {
            search,
            school_id: schoolId,
            class_id: classId,
            status: scope === 'active' ? '' : status,
            gender,
            ...overrides,
        };

        const params = {};

        Object.entries(values).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                params[key] = value;
            }
        });

        return params;
    };

    const visit = (overrides = {}) => {
        router.get(route(listRoute), buildParams(overrides), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['students', 'filters', 'stats'],
        });
    };

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => visit({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]);

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    useEffect(() => {
        setSelectedIds([]);
    }, [students]);

    const handleToggleSelect = (studentId) => {
        setSelectedIds((previous) =>
            previous.includes(studentId)
                ? previous.filter((id) => id !== studentId)
                : [...previous, studentId]
        );
    };

    const handleToggleAll = () => {
        const rowIds = rows.map((student) => student.id);

        setSelectedIds((previous) => {
            if (previous.length > 0 && rowIds.every((id) => previous.includes(id))) {
                return previous.filter((id) => !rowIds.includes(id));
            }

            return Array.from(new Set([...previous, ...rowIds]));
        });
    };

    const handleBulkGraduate = () => {
        if (selectedIds.length === 0) {
            return;
        }

        setGraduating(true);

        router.patch(
            route('admin.students.bulk-graduate'),
            { student_ids: selectedIds },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setGraduateOpen(false);
                    setSelectedIds([]);
                },
                onFinish: () => setGraduating(false),
            }
        );
    };

    const handleFilterChange = (field, value) => {
        const setters = {
            school_id: setSchoolId,
            class_id: setClassId,
            status: setStatus,
            gender: setGender,
        };

        setters[field]?.(value);
        visit({ [field]: value });
    };

    const handlePageChange = (page) => {
        visit({ page });
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

    const handleOpenRegistrations = (item) => {
        router.get(route('admin.student-registrations.index', item.id), {
            preserveScroll: true,
        });
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

        router.delete(route('admin.students.destroy', deletingItem.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteOpen(false);
                setDeletingId(null);
            },
            onFinish: () => setDeleting(false),
        });
    };

    const total = stats?.total ?? students?.total ?? 0;
    const activeCount = stats?.active ?? 0;
    const inactiveCount = stats?.inactive ?? 0;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1720px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Master Data</span>
                            {scope !== 'all' && (
                                <>
                                    <span>/</span>
                                    <span>{scopeMeta.title}</span>
                                </>
                            )}
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            {scopeMeta.title}
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            {scopeMeta.description}
                        </p>
                    </div>

                    {canManage && scope !== 'alumni' && (
                        <div className="flex flex-wrap items-center gap-3">
                            <Button variant="secondary" onClick={() => setImportOpen(true)}>
                                <span className="material-symbols-outlined text-[18px]">
                                    upload_file
                                </span>
                                Import
                            </Button>
                            <Button variant="secondary" onClick={() => setPullOpen(true)}>
                                <span className="material-symbols-outlined text-[18px]">
                                    person_search
                                </span>
                                Tarik Siswa
                            </Button>
                            <Button onClick={handleOpenCreate}>
                                <span className="material-symbols-outlined text-[18px]">add</span>
                                Tambah Siswa
                            </Button>
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Siswa
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
                                    Registrasi Aktif
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 truncate">
                                    {activeCount}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                                <span className="material-symbols-outlined text-primary text-[24px]">
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

                <Card padding="p-4">
                    <div
                        className={`grid grid-cols-1 sm:grid-cols-2 gap-3 ${
                            scope === 'active' ? 'xl:grid-cols-4' : 'xl:grid-cols-5'
                        }`}
                    >
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama, NISN, atau NIS..."
                            aria-label="Cari siswa"
                        />
                        <Select
                            id="filter-school"
                            value={schoolId}
                            onChange={(event) =>
                                handleFilterChange('school_id', event.target.value)
                            }
                            aria-label="Filter sekolah"
                        >
                            <option value="">Semua Sekolah</option>
                            {schools.map((school) => (
                                <option key={school.id} value={String(school.id)}>
                                    {school.name}
                                </option>
                            ))}
                        </Select>
                        <Select
                            id="filter-class"
                            value={classId}
                            onChange={(event) =>
                                handleFilterChange('class_id', event.target.value)
                            }
                            aria-label="Filter kelas"
                        >
                            <option value="">Semua Kelas</option>
                            {classes.map((schoolClass) => (
                                <option key={schoolClass.id} value={String(schoolClass.id)}>
                                    {schoolClass.name}
                                </option>
                            ))}
                        </Select>
                        {scope !== 'active' && (
                            <Select
                                id="filter-status"
                                value={status}
                                onChange={(event) =>
                                    handleFilterChange('status', event.target.value)
                                }
                                aria-label="Filter status"
                            >
                                <option value="">Semua Status</option>
                                {statuses.map((item) => (
                                    <option key={item.value} value={item.value}>
                                        {item.label}
                                    </option>
                                ))}
                            </Select>
                        )}
                        <Select
                            id="filter-gender"
                            value={gender}
                            onChange={(event) => handleFilterChange('gender', event.target.value)}
                            aria-label="Filter jenis kelamin"
                        >
                            <option value="">Semua Jenis Kelamin</option>
                            {genders.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </Select>
                    </div>
                </Card>

                {selectable && selectedIds.length > 0 && (
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-2xl bg-primary/5 ring-1 ring-primary/20 px-4 py-3">
                        <div className="flex items-center gap-2.5">
                            <span className="material-symbols-outlined text-primary text-[20px]">
                                check_box
                            </span>
                            <p className="text-sm font-body text-on-surface">
                                <span className="font-semibold">{selectedIds.length}</span> siswa
                                dipilih
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="secondary"
                                onClick={() => setSelectedIds([])}
                                disabled={graduating}
                            >
                                Batal
                            </Button>
                            <Button onClick={() => setGraduateOpen(true)} disabled={graduating}>
                                <span className="material-symbols-outlined text-[18px]">
                                    workspace_premium
                                </span>
                                Luluskan
                            </Button>
                        </div>
                    </div>
                )}

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60">
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            {scopeMeta.tableTitle}
                        </h3>
                        <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                            {scopeMeta.tableDescription}
                        </p>
                    </div>

                    <StudentTable
                        students={rows}
                        canManage={canManage}
                        onEdit={handleOpenEdit}
                        onDelete={handleOpenDelete}
                        onRegistrations={handleOpenRegistrations}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((students?.from ?? 1) - 1, 0)}
                        selectable={selectable}
                        selectedIds={selectedIds}
                        allSelected={allSelected}
                        onToggleSelect={handleToggleSelect}
                        onToggleAll={handleToggleAll}
                    />

                    <Pagination
                        currentPage={students?.current_page ?? 1}
                        lastPage={students?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={students?.from ?? 0}
                        to={students?.to ?? 0}
                        total={students?.total ?? 0}
                        itemLabel="siswa"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            <StudentModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={editingId ? 'edit' : 'create'}
                initialData={editingItem}
                schools={schools}
                academicYears={academicYears}
                genders={genders}
                statuses={statuses}
                isAdministrator={isAdministrator}
            />

            <ImportStudentsModal
                isOpen={importOpen}
                onClose={() => setImportOpen(false)}
                schools={schools}
                academicYears={academicYears}
                isAdministrator={isAdministrator}
            />

            <PullStudentModal
                isOpen={pullOpen}
                onClose={() => setPullOpen(false)}
                schools={schools}
                academicYears={academicYears}
                isAdministrator={isAdministrator}
            />

            <BulkGraduateModal
                isOpen={graduateOpen}
                onClose={() => setGraduateOpen(false)}
                onConfirm={handleBulkGraduate}
                count={selectedIds.length}
                processing={graduating}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={deletingItem ? deletingItem.name : 'data ini'}
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
