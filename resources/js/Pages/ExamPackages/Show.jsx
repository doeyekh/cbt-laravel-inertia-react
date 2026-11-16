import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import ExamScheduleModal from './Components/ExamScheduleModal';
import ExamScheduleBulkModal from './Components/ExamScheduleBulkModal';
import ExamScheduleList from './Components/ExamScheduleList';
import { route } from '@/Utils/route';

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
    const canGenerateAccessToken = canManage && examPackage?.status !== 'selesai';

    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingId, setEditingId] = useState(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);
    const [accessTokenProcessing, setAccessTokenProcessing] = useState(false);
    const [bulkModalOpen, setBulkModalOpen] = useState(false);
    const [bulkSubmitting, setBulkSubmitting] = useState(false);
    const [tokenConfirmOpen, setTokenConfirmOpen] = useState(false);
    const [tokenConfirmAction, setTokenConfirmAction] = useState(null);

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

    const handleOpenBulk = () => {
        setBulkModalOpen(true);
    };

    const handleCloseBulk = () => {
        setBulkModalOpen(false);
    };

    const handleBulkSubmit = (payload) => {
        setBulkSubmitting(true);

        router.patch(
            route('admin.exam-schedules.bulk-update', { examPackage: examPackage.id }),
            payload,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setBulkModalOpen(false);
                    setBulkSubmitting(false);
                },
                onError: () => setBulkSubmitting(false),
                onFinish: () => setBulkSubmitting(false),
            }
        );
    };

    const handleGenerateAccessToken = () => {
        if (accessTokenProcessing) {
            return;
        }

        if (examPackage?.has_access_token) {
            setTokenConfirmAction('generate');
            setTokenConfirmOpen(true);
            return;
        }

        setAccessTokenProcessing(true);
        router.post(
            route('admin.exam-packages.access-token.store', examPackage.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setAccessTokenProcessing(false),
            }
        );
    };

    const handleRevokeAccessToken = () => {
        if (accessTokenProcessing) {
            return;
        }

        setTokenConfirmAction('revoke');
        setTokenConfirmOpen(true);
    };

    const handleConfirmToken = () => {
        setTokenConfirmOpen(false);

        if (tokenConfirmAction === 'generate') {
            setAccessTokenProcessing(true);
            router.post(
                route('admin.exam-packages.access-token.store', examPackage.id),
                {},
                {
                    preserveScroll: true,
                    onFinish: () => setAccessTokenProcessing(false),
                }
            );
            return;
        }

        if (tokenConfirmAction === 'revoke') {
            setAccessTokenProcessing(true);
            router.delete(
                route('admin.exam-packages.access-token.destroy', examPackage.id),
                {
                    preserveScroll: true,
                    onFinish: () => setAccessTokenProcessing(false),
                }
            );
        }

        setTokenConfirmAction(null);
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
                            <>
                                <Button onClick={handleOpenCreate}>
                                    <span className="material-symbols-outlined text-[18px]">
                                        add
                                    </span>
                                    Tambah Jadwal
                                </Button>
                                <Button variant="outline" onClick={handleOpenBulk}>
                                    <span className="material-symbols-outlined text-[18px]">
                                        group_add
                                    </span>
                                    Atur Sesi &amp; Ruang
                                </Button>
                            </>
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
                        <div className="mt-4 border-t border-outline-variant/30 pt-4">
                            <div>
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Token Akses Paket
                                </p>
                                <p className="mt-1 text-xs text-on-surface-variant font-body leading-relaxed">
                                    {examPackage?.has_access_token
                                        ? 'Satu token berlaku untuk semua jadwal, sesi, dan ruangan dalam paket ini.'
                                        : 'Belum ada token yang diterbitkan untuk paket ini.'}
                                </p>
                            </div>
                            {canManage && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {canGenerateAccessToken && (
                                        <Button
                                            onClick={handleGenerateAccessToken}
                                            disabled={accessTokenProcessing}
                                        >
                                            <span className="material-symbols-outlined text-[18px]">
                                                key
                                            </span>
                                            {examPackage?.has_access_token
                                                ? 'Ganti Token'
                                                : 'Buat Token'}
                                        </Button>
                                    )}
                                    {examPackage?.has_access_token && (
                                        <Button
                                            variant="secondary"
                                            onClick={handleRevokeAccessToken}
                                            disabled={accessTokenProcessing}
                                        >
                                            Cabut Token
                                        </Button>
                                    )}
                                </div>
                            )}
                            {canManage && examPackage?.access_token && (
                                <div className="mt-3 rounded-xl border border-primary/20 bg-primary/5 p-3">
                                    <p className="text-xs font-semibold text-on-surface">
                                        Token ini tetap tersedia selama aktif. Bagikan kode ini kepada peserta ujian.
                                    </p>
                                    <code className="mt-2 inline-flex rounded-lg bg-surface-container-low px-4 py-2 font-mono text-xl font-bold tracking-[0.3em] text-primary">
                                        {examPackage.access_token}
                                    </code>
                                </div>
                            )}
                            {canManage &&
                                examPackage?.has_access_token &&
                                !examPackage?.access_token && (
                                    <p className="mt-3 text-xs text-on-surface-variant">
                                        Token lama tidak dapat ditampilkan. Ganti token untuk menampilkan kode baru.
                                    </p>
                                )}
                        </div>
                    </Card>
                </div>

                <ExamScheduleList
                    schedules={schedules}
                    examPackage={examPackage}
                    canManage={canManage}
                    togglingId={togglingId}
                    formatDate={formatDate}
                    onToggleStatus={handleToggleStatus}
                    onEdit={handleOpenEdit}
                    onDelete={handleOpenDelete}
                />
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

            <ExamScheduleBulkModal
                isOpen={bulkModalOpen}
                onClose={handleCloseBulk}
                schedules={schedules}
                sessions={sessions}
                rooms={rooms}
                onSubmit={handleBulkSubmit}
                submitting={bulkSubmitting}
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

            <ConfirmDialog
                isOpen={tokenConfirmOpen}
                onCancel={() => {
                    setTokenConfirmOpen(false);
                    setTokenConfirmAction(null);
                }}
                onConfirm={handleConfirmToken}
                loading={accessTokenProcessing}
                title={
                    tokenConfirmAction === 'generate'
                        ? 'Terbitkan Token Akses Baru'
                        : 'Cabut Token Akses'
                }
                description={
                    tokenConfirmAction === 'generate'
                        ? 'Menerbitkan token baru akan membatalkan token yang lama. Token lama tidak akan dapat digunakan lagi. Lanjutkan?'
                        : 'Token ini akan langsung tidak berlaku bagi semua peserta ujian. Lanjutkan?'
                }
                confirmLabel={
                    tokenConfirmAction === 'generate' ? 'Terbitkan Token' : 'Cabut Token'
                }
                variant={
                    tokenConfirmAction === 'generate' ? 'primary' : 'danger'
                }
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
