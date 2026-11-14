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

export default function SubjectTeacherTable({
    subjectTeachers,
    onEdit,
    onDelete,
    onToggleStatus,
    togglingId = null,
    searchQuery = '',
    startIndex = 0,
    canManage = true,
}) {
    if (!subjectTeachers || subjectTeachers.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        supervisor_account
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Data'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada data yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menentukan guru pengampuh mata pelajaran untuk kelas ini.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-56">Mata Pelajaran</TableHeadCell>
                <TableHeadCell className="w-64">Guru Pengampuh</TableHeadCell>
                <TableHeadCell className="w-32">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {subjectTeachers.map((item, index) => (
                    <TableRow
                        key={item.id}
                        className="even:bg-surface-container-low/40 hover:bg-primary/5"
                    >
                        <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                            {startIndex + index + 1}
                        </TableCell>
                        <TableCell>
                            <div className="font-semibold text-on-surface text-sm">
                                {item.subject_name ?? '—'}
                            </div>
                            {item.subject_code ? (
                                <div className="font-mono text-xs font-semibold text-on-surface-variant">
                                    {item.subject_code}
                                </div>
                            ) : null}
                        </TableCell>
                        <TableCell>
                            <div className="font-semibold text-on-surface text-sm">
                                {item.teacher_name ?? 'Belum ditentukan'}
                            </div>
                            {item.teacher_email ? (
                                <div className="font-body text-xs text-on-surface-variant mt-0.5">
                                    {item.teacher_email}
                                </div>
                            ) : null}
                        </TableCell>
                        <TableCell>
                            {canManage ? (
                                <button
                                    type="button"
                                    onClick={() => onToggleStatus?.(item)}
                                    disabled={togglingId !== null}
                                    title={
                                        item.is_active
                                            ? 'Klik untuk menonaktifkan penugasan ini'
                                            : 'Klik untuk mengaktifkan penugasan ini'
                                    }
                                    className="group inline-flex items-center rounded-xl transition enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <Badge
                                        variant={item.is_active ? 'success' : 'neutral'}
                                        className="transition group-hover:brightness-95"
                                    >
                                        <span
                                            className={`material-symbols-outlined text-[13px] ${
                                                togglingId === item.id ? 'animate-spin' : ''
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
                            {canManage ? (
                                <RowActions
                                    onEdit={() => onEdit?.(item)}
                                    onDelete={
                                        !item.is_active ? () => onDelete?.(item) : undefined
                                    }
                                />
                            ) : null}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
