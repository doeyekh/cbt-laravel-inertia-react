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

export default function RoomTable({
    rooms,
    onEdit,
    onDelete,
    onToggleStatus,
    togglingId = null,
    searchQuery = '',
    startIndex = 0,
    canManage = true,
}) {
    if (!rooms || rooms.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        meeting_room
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Data'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada ruangan ujian yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menambahkan ruangan ujian pertama.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-24">Kode</TableHeadCell>
                <TableHeadCell className="w-56">Nama Ruangan</TableHeadCell>
                <TableHeadCell className="w-56">Sekolah</TableHeadCell>
                <TableHeadCell className="w-40">Gedung</TableHeadCell>
                <TableHeadCell className="w-20">Lantai</TableHeadCell>
                <TableHeadCell className="w-24">Kapasitas</TableHeadCell>
                <TableHeadCell className="w-32">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {rooms.map((item, index) => (
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
                            <span className="font-body text-sm text-on-surface-variant">
                                {item.building}
                            </span>
                        </TableCell>
                        <TableCell>
                            <span className="font-mono text-xs font-semibold text-on-surface-variant">
                                {item.floor}
                            </span>
                        </TableCell>
                        <TableCell>
                            <span className="font-body text-sm text-on-surface-variant">
                                {item.capacity}
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
                                            ? 'Klik untuk menonaktifkan ruangan ini'
                                            : 'Klik untuk mengaktifkan ruangan ini'
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
