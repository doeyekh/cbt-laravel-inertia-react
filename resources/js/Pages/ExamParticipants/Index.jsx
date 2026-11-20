import React, { useEffect, useMemo, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Badge from '@/Components/UI/Badge';
import Pagination from '@/Components/UI/Pagination';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import ExamParticipantTable from './Components/ExamParticipantTable';
import ExamParticipantModal from './Components/ExamParticipantModal';
import SyncFromClassModal from './Components/SyncFromClassModal';
import CredentialTemplateModal from './Components/CredentialTemplateModal';
import SchoolCredentialTemplateModal from './Components/SchoolCredentialTemplateModal';
import PrintCardsModal from './Components/PrintCardsModal';
import BulkPlacementModal from './Components/BulkPlacementModal';
import { route } from '@/Utils/route';
import PrintParticipantsModal from './Components/PrintParticipantsModal';

const buildQuery = (filters, extra = {}) => ({
    ...(filters?.search ? { search: filters.search } : {}),
    ...(filters?.school_id ? { school_id: filters.school_id } : {}),
    ...(filters?.academic_year_id
        ? { academic_year_id: filters.academic_year_id }
        : {}),
    ...(filters?.class_id ? { class_id: filters.class_id } : {}),
    ...(filters?.session_id ? { session_id: filters.session_id } : {}),
    ...(filters?.room_id ? { room_id: filters.room_id } : {}),
    ...(filters?.status ? { status: filters.status } : {}),
    ...extra,
});

export default function ExamParticipantsIndex({
    participants,
    schools = [],
    classes = [],
    sessions = [],
    rooms = [],
    activeAcademicYear = null,
    filters,
    stats,
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(
        auth?.user?.is_administrator || auth?.user?.is_school_admin
    );

    const [search, setSearch] = useState(filters?.search ?? '');
    const [schoolFilter, setSchoolFilter] = useState(
        filters?.school_id ? String(filters.school_id) : ''
    );
    const [classFilter, setClassFilter] = useState(
        filters?.class_id ? String(filters.class_id) : ''
    );
    const [sessionFilter, setSessionFilter] = useState(
        filters?.session_id ? String(filters.session_id) : ''
    );
    const [roomFilter, setRoomFilter] = useState(
        filters?.room_id ? String(filters.room_id) : ''
    );
    const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');

    const [syncOpen, setSyncOpen] = useState(false);
    const [generateOpen, setGenerateOpen] = useState(false);
    const [templateOpen, setTemplateOpen] = useState(false);
    const [printOpen, setPrintOpen] = useState(false);
    const [participantsPrintModalOpen, setParticipantsPrintModalOpen] = useState(false);
    const [bulkPlacementOpen, setBulkPlacementOpen] = useState(false);
    const [participantModalOpen, setParticipantModalOpen] = useState(false);
    const [editingParticipant, setEditingParticipant] = useState(null);
    const [selectedIds, setSelectedIds] = useState([]);
    const [deletingParticipant, setDeletingParticipant] = useState(null);
    const [deletingParticipantOpen, setDeletingParticipantOpen] =
        useState(false);
    const [deletingParticipantBusy, setDeletingParticipantBusy] =
        useState(false);

    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = participants?.data ?? [];

    const defaultSchoolId = schoolFilter || schools[0]?.id || '';
    const hasActiveYear = Boolean(activeAcademicYear?.id);

    const filteredClasses = useMemo(
        () =>
            classes.filter(
                (item) =>
                    !schoolFilter ||
                    String(item.school_id) === String(schoolFilter)
            ),
        [classes, schoolFilter]
    );

    const filteredSessions = useMemo(
        () =>
            sessions.filter(
                (item) =>
                    !schoolFilter ||
                    String(item.school_id) === String(schoolFilter)
            ),
        [sessions, schoolFilter]
    );

    const filteredRooms = useMemo(
        () =>
            rooms.filter(
                (item) =>
                    !schoolFilter ||
                    String(item.school_id) === String(schoolFilter)
            ),
        [rooms, schoolFilter]
    );

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                route('admin.exam-participants.index'),
                {
                    ...(search ? { search } : {}),
                    ...(schoolFilter ? { school_id: schoolFilter } : {}),
                    ...(classFilter ? { class_id: classFilter } : {}),
                    ...(sessionFilter ? { session_id: sessionFilter } : {}),
                    ...(roomFilter ? { room_id: roomFilter } : {}),
                    ...(statusFilter ? { status: statusFilter } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['participants', 'filters', 'stats'],
                }
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [
        search,
        schoolFilter,
        classFilter,
        sessionFilter,
        roomFilter,
        statusFilter,
    ]);

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    useEffect(() => {
        setSelectedIds([]);
    }, [participants]);

    const handleToggleSelect = (participantId) => {
        setSelectedIds((previous) =>
            previous.includes(participantId)
                ? previous.filter((id) => id !== participantId)
                : [...previous, participantId]
        );
    };

    const handleToggleAll = (checked) => {
        const rowIds = rows.map((participant) => participant.id);

        setSelectedIds((previous) =>
            checked
                ? Array.from(new Set([...previous, ...rowIds]))
                : previous.filter((id) => !rowIds.includes(id))
        );
    };

    const handlePageChange = (page) => {
        router.get(
            route('admin.exam-participants.index'),
            buildQuery(filters, { page }),
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    

    const handleOpenEditParticipant = (participant) => {
        setEditingParticipant(participant);
        setParticipantModalOpen(true);
    };

    const handleOpenDeleteParticipant = (participant) => {
        setDeletingParticipant(participant);
        setDeletingParticipantOpen(true);
    };

    const handleConfirmDeleteParticipant = () => {
        if (!deletingParticipant) {
            return;
        }

        setDeletingParticipantBusy(true);

        router.delete(
            route('admin.exam-participants.destroy', deletingParticipant.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDeletingParticipantOpen(false);
                    setDeletingParticipant(null);
                },
                onFinish: () => setDeletingParticipantBusy(false),
            }
        );
    };

    const handleResetPassword = (participant) => {
        router.patch(
            route('admin.exam-participants.reset-password', participant.id),
            {},
            { preserveScroll: true }
        );
    };

    const totalParticipants = stats?.total ?? 0;
    const withCredential = stats?.with_credential ?? 0;
    const withoutPlacement = stats?.without_placement ?? 0;

    const selectedRows = rows.filter((row) =>
        selectedIds.includes(row.id)
    );
    const selectedSchoolIds = Array.from(
        new Set(selectedRows.map((row) => String(row.school_id)))
    );
    const selectedYearIds = Array.from(
        new Set(selectedRows.map((row) => String(row.academic_year_id)))
    );
    const bulkSchoolId =
        selectedSchoolIds.length === 1 ? selectedSchoolIds[0] : '';
    const bulkYearId = selectedYearIds.length === 1 ? selectedYearIds[0] : '';
    const bulkScopeConflict = selectedSchoolIds.length > 1;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1720px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span>Pengelolaan Ujian</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">
                                Peserta Ujian
                            </span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Peserta Ujian
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Satu siswa hanya dapat memiliki satu kredensial pada
                            satu tahun pelajaran di setiap sekolah.
                        </p>
                    </div>

                    {canManage && (
                        <div className="flex flex-wrap items-center gap-3">
                            <Button
                                variant="outline"
                                onClick={() => setPrintOpen(true)}
                                disabled={!hasActiveYear}
                                title={
                                    hasActiveYear
                                        ? undefined
                                        : 'Belum ada tahun pelajaran aktif'
                                }
                            >
                                <span className="material-symbols-outlined text-[18px]">
                                    print
                                </span>
                                Cetak Kartu
                            </Button>

                            <Button
                                variant="outline"
                                onClick={() => setParticipantsPrintModalOpen(true)}
                                disabled={!hasActiveYear}
                                title={
                                    hasActiveYear
                                        ? undefined
                                        : 'Belum ada tahun pelajaran aktif'
                                }
                            >
                                <span className="material-symbols-outlined text-[18px]">
                                    download
                                </span>
                                Cetak Daftar Nominatif
                            </Button>

                            <Button
                                variant="outline"
                                onClick={() => setTemplateOpen(true)}
                            >
                                <span className="material-symbols-outlined text-[18px]">
                                    tune
                                </span>
                                Atur Template
                            </Button>

                            <Button
                                variant="outline"
                                onClick={() => setSyncOpen(true)}
                                disabled={!hasActiveYear}
                                title={
                                    hasActiveYear
                                        ? undefined
                                        : 'Belum ada tahun pelajaran aktif'
                                }
                            >
                                <span className="material-symbols-outlined text-[18px]">
                                    group_add
                                </span>
                                Tarik dari Kelas
                            </Button>

                            <Button
                                onClick={() => setGenerateOpen(true)}
                                disabled={!hasActiveYear}
                                title={
                                    hasActiveYear
                                        ? undefined
                                        : 'Belum ada tahun pelajaran aktif'
                                }
                            >
                                <span className="material-symbols-outlined text-[18px]">
                                    key
                                </span>
                                Generate Kredensial
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
                                menarik kelas, menghasilkan kredensial, atau
                                mencetak kartu peserta.
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Card padding="p-6">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Total Peserta
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 truncate">
                                    {totalParticipants}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-secondary-container/40 flex items-center justify-center shrink-0">
                                <span className="material-symbols-outlined text-secondary text-[24px]">
                                    how_to_reg
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Kredensial Lengkap
                                </p>
                                <div className="flex items-end gap-2 mt-1">
                                    <h3 className="font-headline text-2xl font-bold text-on-surface truncate">
                                        {withCredential}
                                    </h3>
                                    <span className="mb-0.5 text-[11px] text-on-surface-variant font-body">
                                        dari {totalParticipants}
                                    </span>
                                </div>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                                <span className="material-symbols-outlined text-primary text-[24px]">
                                    task_alt
                                </span>
                            </div>
                        </div>
                        <div className="w-full bg-surface-container h-1.5 rounded-full overflow-hidden mt-4">
                            <div
                                className="bg-primary h-full rounded-full transition-all"
                                style={{
                                    width: `${
                                        totalParticipants > 0
                                            ? Math.round(
                                                  (withCredential /
                                                      totalParticipants) *
                                                      100
                                              )
                                            : 0
                                    }%`,
                                }}
                            />
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                                    Belum Penempatan
                                </p>
                                <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 truncate">
                                    {withoutPlacement}
                                </h3>
                            </div>
                            <div className="w-12 h-12 rounded-xl bg-tertiary-container/30 flex items-center justify-center shrink-0">
                                <span className="material-symbols-outlined text-tertiary text-[24px]">
                                    location_searching
                                </span>
                            </div>
                        </div>
                    </Card>
                </div>

                {withoutPlacement > 0 && (
                    <div className="flex items-start gap-3 rounded-2xl bg-tertiary-container/20 border border-tertiary-container/40 px-4 py-3">
                        <span className="material-symbols-outlined text-tertiary text-[20px] shrink-0">
                            warning
                        </span>
                        <div>
                            <p className="font-label font-semibold text-sm text-on-surface">
                                {withoutPlacement} peserta belum memiliki
                                ruangan atau sesi
                            </p>
                            <p className="text-xs text-on-surface-variant font-body mt-0.5">
                                Pilih peserta pada tabel lalu tekan
                                &quot;Atur Ruangan &amp; Sesi&quot; untuk
                                menetapkan lokasi ujian.
                            </p>
                        </div>
                    </div>
                )}

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Daftar Peserta
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Tahun pelajaran aktif:{' '}
                                <span className="font-semibold text-on-surface">
                                    {activeAcademicYear?.name ?? 'belum diatur'}
                                </span>
                            </p>
                        </div>

                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                            <Select
                                id="participant-school-filter"
                                value={schoolFilter}
                                onChange={(event) =>
                                    setSchoolFilter(event.target.value)
                                }
                                className="w-full sm:w-48"
                                aria-label="Filter sekolah"
                            >
                                <option value="">Semua Sekolah</option>
                                {schools.map((school) => (
                                    <option key={school.id} value={school.id}>
                                        {school.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="participant-class-filter"
                                value={classFilter}
                                onChange={(event) =>
                                    setClassFilter(event.target.value)
                                }
                                className="w-full sm:w-44"
                                aria-label="Filter kelas"
                            >
                                <option value="">Semua Kelas</option>
                                {filteredClasses.map((schoolClass) => (
                                    <option
                                        key={schoolClass.id}
                                        value={schoolClass.id}
                                    >
                                        {schoolClass.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="participant-session-filter"
                                value={sessionFilter}
                                onChange={(event) =>
                                    setSessionFilter(event.target.value)
                                }
                                className="w-full sm:w-40"
                                aria-label="Filter sesi"
                            >
                                <option value="">Semua Sesi</option>
                                {filteredSessions.map((session) => (
                                    <option
                                        key={session.id}
                                        value={session.id}
                                    >
                                        {session.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="participant-room-filter"
                                value={roomFilter}
                                onChange={(event) =>
                                    setRoomFilter(event.target.value)
                                }
                                className="w-full sm:w-40"
                                aria-label="Filter ruangan"
                            >
                                <option value="">Semua Ruangan</option>
                                {filteredRooms.map((room) => (
                                    <option key={room.id} value={room.id}>
                                        {room.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="participant-status-filter"
                                value={statusFilter}
                                onChange={(event) =>
                                    setStatusFilter(event.target.value)
                                }
                                className="w-full sm:w-44"
                                aria-label="Filter status"
                            >
                                <option value="">Semua Status</option>
                                <option value="lengkap">
                                    Kredensial Lengkap
                                </option>
                                <option value="belum">
                                    Kredensial Belum Lengkap
                                </option>
                            </Select>

                            <Input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Cari nama, NIS, username..."
                                className="w-full sm:w-64"
                                aria-label="Cari peserta"
                            />
                        </div>
                    </div>

                    {canManage && selectedIds.length > 0 && (
                        <div className="px-6 py-3 bg-primary/5 ring-1 ring-inset ring-primary/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div className="flex items-center gap-2.5">
                                <span className="material-symbols-outlined text-primary text-[20px]">
                                    check_box
                                </span>
                                <p className="text-sm font-body text-on-surface">
                                    <span className="font-semibold">
                                        {selectedIds.length}
                                    </span>{' '}
                                    peserta dipilih
                                </p>
                                {bulkScopeConflict ? (
                                    <Badge variant="error">
                                        <span className="material-symbols-outlined text-[13px]">
                                            error
                                        </span>
                                        Pilih peserta dari satu sekolah saja
                                    </Badge>
                                ) : (
                                    <Badge variant="primary">
                                        <span className="material-symbols-outlined text-[13px]">
                                            school
                                        </span>
                                        {schools.find(
                                            (school) =>
                                                String(school.id) ===
                                                bulkSchoolId
                                        )?.name ?? '-'}
                                    </Badge>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="secondary"
                                    onClick={() => setSelectedIds([])}
                                    disabled={bulkPlacementOpen}
                                >
                                    Batal Pilih
                                </Button>
                                <Button
                                    onClick={() => setBulkPlacementOpen(true)}
                                    disabled={bulkScopeConflict}
                                >
                                    <span className="material-symbols-outlined text-[18px]">
                                        swap_horiz
                                    </span>
                                    Atur Ruangan &amp; Sesi
                                </Button>
                            </div>
                        </div>
                    )}

                    <ExamParticipantTable
                        participants={rows}
                        onEdit={handleOpenEditParticipant}
                        onDelete={handleOpenDeleteParticipant}
                        onResetPassword={handleResetPassword}
                        searchQuery={filters?.search ?? ''}
                        startIndex={Math.max((participants?.from ?? 1) - 1, 0)}
                        canManage={canManage}
                        selectedIds={selectedIds}
                        onToggleSelect={handleToggleSelect}
                        onToggleAll={handleToggleAll}
                    />

                    <Pagination
                        currentPage={participants?.current_page ?? 1}
                        lastPage={participants?.last_page ?? 1}
                        onPageChange={handlePageChange}
                        from={participants?.from ?? 0}
                        to={participants?.to ?? 0}
                        total={participants?.total ?? 0}
                        itemLabel="peserta"
                        className="px-6 py-4 bg-surface-container-low/40"
                    />
                </Card>
            </div>

            <SyncFromClassModal
                isOpen={syncOpen}
                onClose={() => setSyncOpen(false)}
                schools={schools}
                classes={classes}
                academicYear={activeAcademicYear}
                defaultSchoolId={defaultSchoolId}
            />

            <CredentialTemplateModal
                isOpen={generateOpen}
                onClose={() => setGenerateOpen(false)}
                schools={schools}
                participants={rows}
                academicYear={activeAcademicYear}
                defaultSchoolId={defaultSchoolId}
            />

            <SchoolCredentialTemplateModal
                isOpen={templateOpen}
                onClose={() => setTemplateOpen(false)}
                schools={schools}
                defaultSchoolId={defaultSchoolId}
            />

            <PrintCardsModal
                isOpen={printOpen}
                onClose={() => setPrintOpen(false)}
                schools={schools}
                sessions={sessions}
                rooms={rooms}
                classes={classes}
                academicYear={activeAcademicYear}
                defaultSchoolId={defaultSchoolId}
            />

            <PrintParticipantsModal
                isOpen={participantsPrintModalOpen}
                onClose={() => setParticipantModalOpen(false)}
                schools={schools}
                sessions={sessions}
                rooms={rooms}
                classes={classes}
                academicYear={activeAcademicYear}
                defaultSchoolId={defaultSchoolId}
            />

            <BulkPlacementModal
                isOpen={bulkPlacementOpen}
                onClose={() => setBulkPlacementOpen(false)}
                schoolId={bulkSchoolId}
                academicYearId={bulkYearId}
                participantIds={selectedIds}
                sessions={sessions}
                rooms={rooms}
            />

            <ExamParticipantModal
                isOpen={participantModalOpen}
                onClose={() => {
                    setParticipantModalOpen(false);
                    setEditingParticipant(null);
                }}
                initialData={editingParticipant}
                sessions={sessions}
                rooms={rooms}
            />

            <DeleteConfirmation
                isOpen={deletingParticipantOpen}
                itemName={
                    deletingParticipant
                        ? `peserta "${deletingParticipant.student_name}"`
                        : 'data ini'
                }
                onConfirm={handleConfirmDeleteParticipant}
                onCancel={() => {
                    setDeletingParticipantOpen(false);
                    setDeletingParticipant(null);
                }}
                loading={deletingParticipantBusy}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
