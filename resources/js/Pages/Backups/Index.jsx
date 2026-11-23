import React, { useEffect, useRef, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import DeleteConfirmation from '@/Components/UI/DeleteConfirmation';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import { route } from '@/Utils/route';

const formatBytes = (value) => {
    const bytes = Number(value ?? 0);

    if (!bytes) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

    return `${(bytes / 1024 ** index).toFixed(index ? 1 : 0)} ${units[index]}`;
};

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('id-ID');
};

export default function BackupsIndex({ backups, settings, types }) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const handledToast = useRef(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [restoring, setRestoring] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [deletingProcess, setDeletingProcess] = useState(false);

    const createForm = useForm({
        type: 'database',
    });

    const restoreForm = useForm({
        current_password: '',
        confirmation: '',
    });

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleOpenCreate = () => {
        createForm.reset();
        createForm.clearErrors();
        setCreateOpen(true);
    };

    const handleCloseCreate = () => {
        if (createForm.processing) {
            return;
        }

        setCreateOpen(false);
    };

    const handleCreate = (event) => {
        event.preventDefault();

        createForm.post(route('admin.backups.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const handleOpenRestore = (backup) => {
        restoreForm.reset();
        restoreForm.clearErrors();
        setRestoring(backup);
    };

    const handleCloseRestore = () => {
        if (restoreForm.processing) {
            return;
        }

        setRestoring(null);
    };

    const handleRestore = (event) => {
        event.preventDefault();

        if (!restoring) {
            return;
        }

        restoreForm.post(route('admin.backups.restore', restoring.filename), {
            preserveScroll: true,
            onSuccess: () => {
                setRestoring(null);
                restoreForm.reset();
            },
        });
    };

    const handleConfirmDelete = () => {
        if (!deleting) {
            return;
        }

        setDeletingProcess(true);

        router.delete(route('admin.backups.destroy', deleting.filename), {
            preserveScroll: true,
            onSuccess: () => setDeleting(null),
            onFinish: () => setDeletingProcess(false),
        });
    };

    const rows = backups ?? [];
    const backupTypeOptions = (types ?? []).map((type) => ({
        ...type,
        icon: type.value === 'full' ? 'folder_zip' : 'database',
    }));

    return (
        <AppLayout>
            <div className="space-y-6">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Pengaturan</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Backup &amp; Restore Database
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Pilih backup database saja atau database beserta file aplikasi. Restore dibungkus dengan perlindungan kata sandi.
                        </p>
                    </div>

                    <Button onClick={handleOpenCreate}>
                        <span className="material-symbols-outlined text-[18px]">backup</span>
                        Buat Backup
                    </Button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <Card padding="p-6">
                        <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                            Koneksi Aktif
                        </p>
                        <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 uppercase">
                            {settings?.connection ?? '-'}
                        </h3>
                    </Card>
                    <Card padding="p-6">
                        <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                            Disk Backup
                        </p>
                        <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                            {settings?.disk ?? '-'}
                        </h3>
                    </Card>
                    <Card padding="p-6">
                        <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                            Retensi
                        </p>
                        <h3 className="font-headline text-2xl font-bold text-on-surface mt-1">
                            {settings?.keep_days ?? 7} hari
                        </h3>
                    </Card>
                </div>

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60">
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            Riwayat Backup
                        </h3>
                        <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                            Restore mengganti seluruh database. Satu safety backup otomatis dibuat sebelum restore.
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left font-body text-sm">
                            <thead className="bg-surface-container-low/40 text-[11px] font-label uppercase tracking-wider text-on-surface-variant">
                                <tr>
                                    <th className="px-6 py-3">Nama Berkas</th>
                                    <th className="px-6 py-3">Jenis</th>
                                    <th className="px-6 py-3">Dibuat</th>
                                    <th className="px-6 py-3">Ukuran</th>
                                    <th className="px-6 py-3">Koneksi</th>
                                    <th className="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-outline/20">
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-10 text-center text-on-surface-variant">
                                            Belum ada backup. Klik &ldquo;Buat Backup&rdquo; untuk memulai.
                                        </td>
                                    </tr>
                                )}

                                {rows.map((backup) => (
                                    <tr key={backup.filename} className="hover:bg-surface-container-low/40">
                                        <td className="px-6 py-3 font-mono text-[11px] break-all text-on-surface">
                                            {backup.filename}
                                        </td>
                                        <td className="px-6 py-3">
                                            <span
                                                className={`inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-label font-semibold uppercase tracking-wider ${
                                                    backup.type === 'full'
                                                        ? 'bg-primary/10 text-primary'
                                                        : 'bg-surface-container text-on-surface-variant'
                                                }`}
                                            >
                                                {backup.type_label ?? 'Database'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-3 text-on-surface-variant">
                                            {formatDate(backup.created_at)}
                                        </td>
                                        <td className="px-6 py-3 text-on-surface-variant">
                                            {formatBytes(backup.size_bytes)}
                                        </td>
                                        <td className="px-6 py-3 text-on-surface-variant uppercase">
                                            {backup.connection}
                                        </td>
                                        <td className="px-6 py-3">
                                            <div className="flex items-center justify-end gap-2">
                                                <a
                                                    href={route('admin.backups.download', backup.filename)}
                                                    className="inline-flex items-center gap-1 rounded-xl px-3 py-1.5 text-xs font-label bg-surface-container hover:bg-surface-container-high text-on-surface"
                                                >
                                                    <span className="material-symbols-outlined text-[16px]">download</span>
                                                    Unduh
                                                </a>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleOpenRestore(backup)}
                                                >
                                                    <span className="material-symbols-outlined text-[16px]">restore</span>
                                                    Restore
                                                </Button>
                                                <button
                                                    type="button"
                                                    onClick={() => setDeleting(backup)}
                                                    className="p-1.5 rounded-lg text-error hover:bg-error-container/20"
                                                    title="Hapus backup"
                                                >
                                                    <span className="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <Modal isOpen={createOpen} onClose={handleCloseCreate} maxWidth="max-w-lg">
                <form onSubmit={handleCreate} className="space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center">
                            <span className="material-symbols-outlined text-primary text-[20px]">backup</span>
                        </div>
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Buat Backup
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body">
                                Pilih data yang akan dicadangkan.
                            </p>
                        </div>
                    </div>

                    <div className="space-y-3">
                        {backupTypeOptions.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => createForm.setData('type', option.value)}
                                disabled={createForm.processing}
                                className={`w-full text-left rounded-2xl border p-4 transition ${
                                    createForm.data.type === option.value
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                                        : 'border-outline/40 hover:bg-surface-container-low'
                                }`}
                            >
                                <div className="flex items-start gap-3">
                                    <div className="w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
                                        <span className="material-symbols-outlined text-[20px] text-primary">
                                            {option.icon}
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-label font-semibold text-on-surface text-sm">
                                            {option.label}
                                        </p>
                                        <p className="text-[11px] text-on-surface-variant font-body mt-1 leading-relaxed">
                                            {option.description}
                                        </p>
                                    </div>
                                    {createForm.data.type === option.value && (
                                        <span className="material-symbols-outlined text-primary text-[20px]">
                                            check_circle
                                        </span>
                                    )}
                                </div>
                            </button>
                        ))}
                    </div>

                    {createForm.errors.type && (
                        <p className="text-[11px] font-label text-error">
                            {createForm.errors.type}
                        </p>
                    )}

                    <div className="flex items-center gap-3 justify-end pt-2">
                        <Button variant="secondary" onClick={handleCloseCreate} disabled={createForm.processing}>
                            Batal
                        </Button>
                        <Button type="submit" loading={createForm.processing}>
                            Mulai Backup
                        </Button>
                    </div>
                </form>
            </Modal>

            <Modal isOpen={restoring !== null} onClose={handleCloseRestore} maxWidth="max-w-md">
                <form onSubmit={handleRestore} className="space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-error-container/20 flex items-center justify-center">
                            <span className="material-symbols-outlined text-error text-[20px]">warning</span>
                        </div>
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            Pulihkan Database
                        </h3>
                    </div>

                    <p className="text-sm text-on-surface-variant font-body leading-relaxed">
                        Seluruh data saat ini akan diganti dari backup{' '}
                        <span className="font-mono text-[11px] text-on-surface">{restoring?.filename}</span>.
                        Pastikan tidak ada ujian yang sedang berlangsung. Restore hanya memulihkan database, bukan file aplikasi. Anda mungkin akan keluar dari sesi setelah restore.
                    </p>

                    <Input
                        id="current_password"
                        label="Kata Sandi Saat Ini"
                        type="password"
                        autoComplete="current-password"
                        value={restoreForm.data.current_password}
                        onChange={(event) => restoreForm.setData('current_password', event.target.value)}
                        error={restoreForm.errors.current_password}
                        disabled={restoreForm.processing}
                    />

                    <Input
                        id="confirmation"
                        label="Ketik RESTORE untuk Konfirmasi"
                        value={restoreForm.data.confirmation}
                        onChange={(event) => restoreForm.setData('confirmation', event.target.value)}
                        error={restoreForm.errors.confirmation}
                        disabled={restoreForm.processing}
                        placeholder="RESTORE"
                    />

                    <div className="flex items-center gap-3 justify-end pt-2">
                        <Button variant="secondary" onClick={handleCloseRestore} disabled={restoreForm.processing}>
                            Batal
                        </Button>
                        <Button type="submit" variant="danger" loading={restoreForm.processing}>
                            Ya, Pulihkan
                        </Button>
                    </div>
                </form>
            </Modal>

            <DeleteConfirmation
                isOpen={deleting !== null}
                itemName={deleting?.filename ?? 'backup ini'}
                onConfirm={handleConfirmDelete}
                onCancel={() => setDeleting(null)}
                loading={deletingProcess}
            />

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
