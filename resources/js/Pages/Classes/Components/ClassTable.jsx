import React from 'react';
import Badge from '@/Components/UI/Badge';
import RowActions from '@/Components/UI/RowActions';
import Table, {
    TableHead,
    TableHeadCell,
    TableBody,
    TableRow,
    TableCell,
} from '@/Components/UI/Table';

export default function ClassTable({
    classes,
    onEdit,
    onDelete,
    onToggleStatus,
    togglingId = null,
    searchQuery = '',
    startIndex = 0,
    canManage = true,
    onOpenLearning,
    onOpenStudents,
}) {
    if (!classes || classes.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        groups
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Data'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada kelas yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menambahkan kelas pertama.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-24">Kode</TableHeadCell>
                <TableHeadCell className="w-56">Nama Kelas</TableHeadCell>
                <TableHeadCell className="w-48">Sekolah</TableHeadCell>
                <TableHeadCell className="w-56">Tahun Pelajaran</TableHeadCell>
                <TableHeadCell className="w-24">Siswa</TableHeadCell>
                <TableHeadCell className="w-32">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {classes.map((item, index) => (
                    <TableRow
                        key={item.id}
                        className="even:bg-surface-container-low/40 hover:bg-primary/5"
                    >
                        <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                            {startIndex + index + 1}
                        </TableCell>
                        <TableCell>
                            <span className="inline-flex rounded-lg bg-surface-container-low px-2 py-1 font-mono text-xs font-semibold text-on-surface">
                                {item.code}
                            </span>
                        </TableCell>
                        <TableCell>
                            <div className="font-semibold text-on-surface text-sm">
                                {item.name}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="font-semibold text-on-surface text-sm">
                                {item.school_name}
                            </div>
                            <div className="font-mono text-xs font-semibold text-on-surface-variant">
                                {item.school_code}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="font-semibold text-on-surface text-sm">
                                {item.academic_year_name}
                            </div>
                            <div className="font-body text-xs text-on-surface-variant">
                                {item.academic_year_semester_label}
                            </div>
                        </TableCell>
                        <TableCell>
                            <span className="inline-flex items-center gap-1.5 rounded-lg bg-surface-container-low px-2 py-1 font-mono text-xs font-semibold text-on-surface">
                                <span className="material-symbols-outlined text-[14px]">
                                    groups
                                </span>
                                {item.students_count ?? 0}
                            </span>
                        </TableCell>
                        <TableCell>
                            {canManage ? (
                                <button
                                    type="button"
                                    onClick={() => onToggleStatus?.(item)}
                                    disabled={togglingId !== null}
                                    title={
                                        item.is_active
                                            ? 'Klik untuk menonaktifkan kelas ini'
                                            : 'Klik untuk mengaktifkan kelas ini'
                                    }
                                    className="group inline-flex items-center rounded-xl transition
            enabled:cursor-pointer
            disabled:cursor-not-allowed
            disabled:opacity-60"
                                >
                                    <Badge
                                        variant={item.is_active ? 'success' : 'neutral'}
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
                                                : item.is_active
                                                  ? 'check_circle'
                                                  : 'remove_circle'}
                                        </span>
                                        {item.is_active ? 'Aktif' : 'Tidak Aktif'}
                                    </Badge>
                                </button>
                            ) : (
                                <Badge variant={item.is_active ? 'success' : 'neutral'}>
                                    <span className="material-symbols-outlined text-[13px]">
                                        {item.is_active ? 'check_circle' : 'remove_circle'}
                                    </span>
                                    {item.is_active ? 'Aktif' : 'Tidak Aktif'}
                                </Badge>
                            )}
                        </TableCell>
                        <TableCell className="text-right">
                            <div className="inline-flex items-center gap-1">
                                {onOpenStudents && (
                                    <button
                                        type="button"
                                        onClick={() => onOpenStudents(item)}
                                        title="Buka Anggota Kelas"
                                        aria-label="Buka Anggota Kelas"
                                        className="p-2 rounded-lg transition-colors text-primary hover:bg-primary/10"
                                    >
                                        <span className="material-symbols-outlined text-[18px]">groups</span>
                                    </button>
                                )}
                                {onOpenLearning && (
                                    <button
                                        type="button"
                                        onClick={() => onOpenLearning(item)}
                                        title="Buka Pembelajaran (Guru Pengampuh)"
                                        aria-label="Buka Pembelajaran (Guru Pengampuh)"
                                        className="p-2 rounded-lg transition-colors text-primary hover:bg-primary/10"
                                    >
                                        <span className="material-symbols-outlined text-[18px]">route</span>
                                    </button>
                                )}
                                {canManage ? (
                                    <RowActions
                                        onEdit={() => onEdit?.(item)}
                                        onDelete={
                                            !item.is_active ? () => onDelete?.(item) : undefined
                                        }
                                    />
                                ) : null}
                            </div>
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
