import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import RegistrationModal from './Components/RegistrationModal';
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
        case 'lulus':
            return 'primary';
        case 'pindah':
            return 'warning';
        default:
            return 'neutral';
    }
};

export default function StudentRegistrations({
    student,
    registrations = [],
    schools = [],
    academicYears = [],
    canManage = false,
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const user = auth?.user;
    const isAdministrator = Boolean(user?.is_administrator || user?.role === 'administrator');

    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingId, setEditingId] = useState(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const handledToast = useRef(null);

    const editingItem = registrations.find((item) => item.id === editingId) ?? null;
    const deletingItem = registrations.find((item) => item.id === deletingId) ?? null;

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleBack = () => {
        router.get(route('admin.students.index'), { preserveScroll: true });
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
            route('admin.student-registrations.destroy', {
                student: student.id,
                registration: deletingItem.id,
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

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1200px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Master Data</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Siswa</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Registrasi</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Registrasi Sekolah
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            {student.name}
                            {student.nisn ? ` · NISN ${student.nisn}` : ''}
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
                            <Button onClick={handleOpenCreate}>
                                <span className="material-symbols-outlined text-[18px]">add</span>
                                Tambah Registrasi
                            </Button>
                        )}
                    </div>
                </div>

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60">
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            Daftar Registrasi
                        </h3>
                        <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                            Hanya satu registrasi yang boleh berstatus Aktif pada satu waktu.
                        </p>
                    </div>

                    {registrations.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 text-center">
                            <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                                <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                                    badge
                                </span>
                            </div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                                Belum Ada Registrasi
                            </h3>
                            <p className="text-sm text-on-surface-variant font-body">
                                Siswa ini belum terdaftar di sekolah mana pun.
                            </p>
                        </div>
                    ) : (
                        <Table>
                            <TableHead>
                                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                                <TableHeadCell>Sekolah</TableHeadCell>
                                <TableHeadCell className="w-32">NIS</TableHeadCell>
                                <TableHeadCell className="w-48">Tahun Masuk</TableHeadCell>
                                <TableHeadCell className="w-48">Tahun Keluar</TableHeadCell>
                                <TableHeadCell className="w-32">Status</TableHeadCell>
                                <TableHeadCell className="text-right">Aksi</TableHeadCell>
                            </TableHead>
                            <TableBody>
                                {registrations.map((item, index) => (
                                    <TableRow
                                        key={item.id}
                                        className="even:bg-surface-container-low/40 hover:bg-primary/5"
                                    >
                                        <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                                            {index + 1}
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-semibold text-on-surface text-sm">
                                                {item.school_name}
                                            </div>
                                            <div className="font-mono text-[11px] text-on-surface-variant">
                                                {item.school_code}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-mono text-xs text-on-surface-variant">
                                                {item.nis}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-body text-sm text-on-surface-variant">
                                                {item.entry_academic_year_name}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-body text-sm text-on-surface-variant">
                                                {item.exit_academic_year_name ?? '—'}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={statusVariant(item.status)}>
                                                {item.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {canManage ? (
                                                <div className="inline-flex items-center gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenEdit(item)}
                                                        title="Edit registrasi"
                                                        aria-label="Edit registrasi"
                                                        className="p-2 rounded-lg transition-colors text-primary hover:bg-primary/10"
                                                    >
                                                        <span className="material-symbols-outlined text-[18px]">
                                                            edit
                                                        </span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenDelete(item)}
                                                        title="Hapus registrasi"
                                                        aria-label="Hapus registrasi"
                                                        className="p-2 rounded-lg transition-colors text-error hover:bg-error-container/20"
                                                    >
                                                        <span className="material-symbols-outlined text-[18px]">
                                                            delete
                                                        </span>
                                                    </button>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-on-surface-variant">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Card>
            </div>

            <RegistrationModal
                isOpen={modalOpen}
                onClose={handleCloseModal}
                mode={modalMode}
                initialData={editingItem}
                studentId={student.id}
                schools={schools}
                academicYears={academicYears}
                isAdministrator={isAdministrator}
            />

            <DeleteConfirmation
                isOpen={deleteOpen}
                itemName={
                    deletingItem
                        ? `${deletingItem.school_name} — NIS ${deletingItem.nis}`
                        : 'registrasi ini'
                }
                onConfirm={handleConfirmDelete}
                onCancel={handleCloseDelete}
                loading={deleting}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
