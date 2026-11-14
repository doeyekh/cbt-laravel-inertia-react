import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import RowActions from '@/Components/UI/RowActions';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import ExamScheduleModal from './Components/ExamScheduleModal';
import { route } from '@/Utils/route';
import Table, {
    TableHead,
    TableHeadCell,
    TableBody,
    TableRow,
    TableCell,
} from '@/Components/UI/Table';

const statusVariant = (status) => {
    switch (status) {
        case 'aktif':
            return 'success';
        case 'selesai':
            return 'primary';
        default:
            return 'neutral';
    }
};

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
};

const ScheduleSettingChip = ({ active, children }) => (
    <span
        className={`
            inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px]
            font-label font-semibold
            ${
                active
                    ? 'bg-primary/10 text-primary'
                    : 'bg-surface-container-low text-on-surface-variant'
            }
        `}
    >
        {children}
    </span>
);

export default function ExamPackageShow({
    examPackage,
    schedules = [],
    classes = [],
    subjectsByClass = {},
    sessions = [],
    rooms = [],
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(auth?.user?.is_administrator || auth?.user?.is_school_admin);
    const canAddSchedule = canManage && examPackage?.status !== 'selesai';

    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingId, setEditingId] = useState(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const handledToast = useRef(null);

    const editingItem = schedules.find((item) => item.id === editingId) ?? null;
    const deletingItem = schedules.find((item) => item.id === deletingId) ?? null;

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleBack = () => {
        router.get(route('admin.exam-packages.index'), { preserveScroll: true });
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

        router.delete(
            route('admin.exam-schedules.destroy', {
                examPackage: examPackage.id,
                examSchedule: deletingItem.id,
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

    const handleToggleStatus = (item) => {
        if (togglingId !== null || examPackage?.status === 'selesai') {
            return;
        }

        setTogglingId(item.id);

        router.patch(
            route('admin.exam-schedules.toggle-status', {
                examPackage: examPackage.id,
                examSchedule: item.id,
            }),
            {},
            {
                preserveScroll: true,
                onFinish: () => setTogglingId(null),
            }
        );
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1440px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Pengelolaan Ujian</span>
                            <span>/</span>
                            <span>Paket Ujian</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Detail</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            {examPackage?.name}
                        </h1>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="inline-flex rounded-lg bg-surface-container-low px-2 py-1 font-mono text-xs font-semibold text-on-surface-variant">
                                {examPackage?.code}
                            </span>
                            <Badge variant={statusVariant(examPackage?.status)}>
                                {examPackage?.status_label}
                            </Badge>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button variant="secondary" onClick={handleBack}>
                            <span className="material-symbols-outlined text-[18px]">
                                arrow_back
                            </span>
                            Kembali
                        </Button>
                        {canAddSchedule && (
                            <Button onClick={handleOpenCreate}>
                                <span className="material-symbols-outlined text-[18px]">
                                    add
                                </span>
                                Tambah Jadwal
                            </Button>
                        )}
                    </div>
                </div>

                {examPackage?.exam_type_is_active === false && (
                    <div className="flex items-start gap-3 rounded-2xl bg-tertiary-container/20 border border-tertiary-container/40 px-4 py-3">
                        <span className="material-symbols-outlined text-tertiary text-[20px] shrink-0">
                            warning
                        </span>
                        <div>
                            <p className="font-label font-semibold text-sm text-on-surface">
                                Jenis ujian sedang tidak aktif
                            </p>
                            <p className="text-xs text-on-surface-variant font-body mt-0.5">
                                Paket ujian ini otomatis dikembalikan ke status draft.
                                Pilih jenis ujian aktif saat mengubah paket.
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <Card padding="p-6" className="lg:col-span-2">
                        <h3 className="font-headline font-semibold text-on-surface text-lg mb-4">
                            Informasi Paket
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Sekolah
                                </p>
                                <p className="font-body text-sm text-on-surface mt-1">
                                    {examPackage?.school_name ?? '-'}
                                </p>
                                {examPackage?.school_code && (
                                    <p className="font-mono text-[11px] text-on-surface-variant">
                                        {examPackage.school_code}
                                    </p>
                                )}
                            </div>
                            <div>
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Tahun Pelajaran
                                </p>
                                <p className="font-body text-sm text-on-surface mt-1">
                                    {examPackage?.academic_year_name ?? '-'}
                                </p>
                            </div>
                            <div>
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Jenis Ujian
                                </p>
                                <p className="font-body text-sm text-on-surface mt-1">
                                    {examPackage?.exam_type_name ?? '-'}
                                </p>
                                {examPackage?.exam_type_code && (
                                    <p className="font-mono text-[11px] text-on-surface-variant">
                                        {examPackage.exam_type_code}
                                    </p>
                                )}
                            </div>
                            <div>
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Periode
                                </p>
                                <p className="font-body text-sm text-on-surface mt-1">
                                    {formatDate(examPackage?.start_date)} s.d.{' '}
                                    {formatDate(examPackage?.end_date)}
                                </p>
                            </div>
                            <div className="sm:col-span-2">
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Deskripsi
                                </p>
                                <p className="font-body text-sm text-on-surface-variant mt-1 leading-relaxed">
                                    {examPackage?.description || '-'}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <Card padding="p-6">
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Total Jadwal
                        </p>
                        <div className="flex items-end gap-3 mt-2">
                            <h3 className="font-headline text-4xl font-bold text-on-surface">
                                {schedules.length}
                            </h3>
                            <span className="mb-1 text-xs text-on-surface-variant font-body">
                                jadwal ujian
                            </span>
                        </div>
                        <div className="mt-4 pt-4 border-t border-outline-variant/30">
                            <p className="text-[11px] text-on-surface-variant font-body leading-relaxed">
                                {examPackage?.status === 'selesai'
                                    ? 'Paket sudah selesai. Jadwal tidak dapat diubah lagi.'
                                    : 'Jadwal dapat ditambah atau diubah selama paket belum selesai.'}
                            </p>
                        </div>
                    </Card>
                </div>

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Jadwal Ujian
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Satu kelas tidak dapat memiliki dua jadwal pada tanggal dan
                                sesi yang sama.
                            </p>
                        </div>
                    </div>

                    {schedules.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 text-center">
                            <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                                <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                                    calendar_month
                                </span>
                            </div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                                Belum Ada Jadwal
                            </h3>
                            <p className="text-sm text-on-surface-variant font-body">
                                Tambahkan jadwal pertama untuk paket ujian ini.
                            </p>
                        </div>
                    ) : (
                        <Table>
                            <TableHead>
                                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                                <TableHeadCell className="w-36">Tanggal</TableHeadCell>
                                <TableHeadCell className="w-44">Sesi</TableHeadCell>
                                <TableHeadCell className="w-44">Kelas</TableHeadCell>
                                <TableHeadCell>Mata Pelajaran</TableHeadCell>
                                <TableHeadCell className="w-40">Ruangan</TableHeadCell>
                                <TableHeadCell className="w-24 text-center">Durasi</TableHeadCell>
                                <TableHeadCell className="w-32 text-center">Jam Soal</TableHeadCell>
                                <TableHeadCell className="w-56">Pengaturan</TableHeadCell>
                                <TableHeadCell className="w-24 text-center">Status</TableHeadCell>
                                {canManage && (
                                    <TableHeadCell className="text-right">Aksi</TableHeadCell>
                                )}
                            </TableHead>
                            <TableBody>
                                {schedules.map((item, index) => (
                                    <TableRow
                                        key={item.id}
                                        className="even:bg-surface-container-low/40 hover:bg-primary/5"
                                    >
                                        <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                                            {index + 1}
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-body text-sm text-on-surface">
                                                {formatDate(item.exam_date)}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm text-on-surface">
                                                {item.session_name ?? '-'}
                                            </div>
                                            <div className="text-[11px] text-on-surface-variant font-mono">
                                                {item.session_start_time?.slice(0, 5)} -{' '}
                                                {item.session_end_time?.slice(0, 5)}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm font-medium text-on-surface">
                                                {item.class_name ?? '-'}
                                            </div>
                                            <div className="text-[10px] font-mono text-on-surface-variant">
                                                {item.class_code}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm text-on-surface">
                                                {item.subject_name ?? '-'}
                                            </div>
                                            <div className="text-[10px] font-mono text-on-surface-variant">
                                                {item.subject_code}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm text-on-surface">
                                                {item.room_name ?? '-'}
                                            </div>
                                            <div className="text-[10px] font-mono text-on-surface-variant">
                                                {item.room_code}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-center text-on-surface-variant">
                                            {item.duration_minutes} mnt
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <span className="font-mono text-xs text-on-surface">
                                                {item.open_time?.slice(0, 5)} -{' '}
                                                {item.close_time?.slice(0, 5)}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                <ScheduleSettingChip
                                                    active={item.shuffle_questions}
                                                >
                                                    Acak soal
                                                </ScheduleSettingChip>
                                                <ScheduleSettingChip
                                                    active={item.shuffle_options}
                                                >
                                                    Acak opsi
                                                </ScheduleSettingChip>
                                                <ScheduleSettingChip
                                                    active={item.show_score_to_student}
                                                >
                                                    Nilai tampil
                                                </ScheduleSettingChip>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-center">
                                            {canManage ? (
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleStatus(item)}
                                                    disabled={
                                                        togglingId !== null ||
                                                        examPackage?.status === 'selesai'
                                                    }
                                                    title={
                                                        examPackage?.status === 'selesai'
                                                            ? 'Paket ujian sudah selesai.'
                                                            : item.status === 'aktif'
                                                              ? 'Klik untuk menonaktifkan jadwal ini'
                                                              : 'Klik untuk mengaktifkan jadwal ini'
                                                    }
                                                    className="group inline-flex items-center rounded-xl transition enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    <Badge
                                                        variant={
                                                            item.status === 'aktif'
                                                                ? 'success'
                                                                : 'neutral'
                                                        }
                                                        className="transition group-hover:brightness-95"
                                                    >
                                                        <span
                                                            className={`material-symbols-outlined text-[13px] ${
                                                                togglingId === item.id
                                                                    ? 'animate-spin'
                                                                    : ''
                                                            }`}
                                                        >
                                                            {togglingId === item.id
                                                                ? 'sync'
                                                                : item.status === 'aktif'
                                                                  ? 'check_circle'
                                                                  : 'remove_circle'}
                                                        </span>
                                                        {item.status_label}
                                                    </Badge>
                                                </button>
                                            ) : (
                                                <Badge
                                                    variant={
                                                        item.status === 'aktif'
                                                            ? 'success'
                                                            : 'neutral'
                                                    }
                                                >
                                                    <span className="material-symbols-outlined text-[13px]">
                                                        {item.status === 'aktif'
                                                            ? 'check_circle'
                                                            : 'remove_circle'}
                                                    </span>
                                                    {item.status_label}
                                                </Badge>
                                            )}
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <RowActions
                                                    onEdit={
                                                        examPackage?.status !== 'selesai'
                                                            ? () => handleOpenEdit(item)
                                                            : undefined
                                                    }
                                                    onDelete={
                                                        examPackage?.status !== 'selesai'
                                                            ? () => handleOpenDelete(item)
                                                            : undefined
                                                    }
                                                />
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Card>
            </div>

            <ExamScheduleModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                initialData={editingItem}
                examPackage={examPackage}
                classes={classes}
                subjectsByClass={subjectsByClass}
                sessions={sessions}
                rooms={rooms}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={
                    deletingItem
                        ? `${deletingItem.subject_name} — ${deletingItem.class_name}`
                        : 'jadwal ujian ini'
                }
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
