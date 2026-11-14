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

export default function SessionTable({
    sessions,
    onEdit,
    onDelete,
    onToggleStatus,
    togglingId = null,
    searchQuery = '',
    startIndex = 0,
    canManage = true,
}) {
    if (!sessions || sessions.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        access_time
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Data'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada sesi ujian yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menambahkan sesi ujian pertama.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-24">Kode</TableHeadCell>
                <TableHeadCell className="w-56">Nama Sesi</TableHeadCell>
                <TableHeadCell className="w-56">Sekolah</TableHeadCell>
                <TableHeadCell className="w-40">Waktu</TableHeadCell>
                <TableHeadCell className="w-32">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {sessions.map((item, index) => (
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
                            <div className="text-sm font-medium text-on-surface">
                                {item.school_name ?? '-'}
                            </div>
                            {item.school_code && (
                                <div className="text-[10px] font-mono text-on-surface-variant">
                                    {item.school_code}
                                </div>
                            )}
                        </TableCell>
                        <TableCell>
                            <div className="font-mono text-xs font-semibold text-on-surface-variant">
                                {item.start_time} - {item.end_time}
                            </div>
                        </TableCell>
                        <TableCell>
                            {canManage ? (
                                <button
                                    type="button"
                                    onClick={() => onToggleStatus?.(item)}
                                    disabled={togglingId !== null}
                                    title={
                                        item.is_active
                                            ? 'Klik untuk menonaktifkan sesi ini'
                                            : 'Klik untuk mengaktifkan sesi ini'
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
                                <Badge
                                    variant={item.is_active ? 'success' : 'neutral'}
                                >
                                    <span className="material-symbols-outlined text-[13px]">
                                        {item.is_active
                                            ? 'check_circle'
                                            : 'remove_circle'}
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
                                        !item.is_active
                                            ? () => onDelete?.(item)
                                            : undefined
                                    }
                                />
                            ) : (
                                <span className="text-xs text-on-surface-variant">
                                    -
                                </span>
                            )}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
