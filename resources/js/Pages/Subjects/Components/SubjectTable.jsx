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

export default function SubjectTable({
    subjects,
    selectable = false,
    selectedIds = [],
    allSelected = false,
    onToggleSelect,
    onToggleAll,
    onShow,
    onEdit,
    onDelete,
    onToggleStatus,
    togglingId = null,
    searchQuery = '',
    startIndex = 0,
}) {
    if (!subjects || subjects.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        menu_book
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Data'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada mata pelajaran yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menambahkan mata pelajaran pertama.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                {selectable && (
                    <TableHeadCell className="w-10 text-center">
                        <input
                            type="checkbox"
                            checked={allSelected}
                            onChange={onToggleAll}
                            disabled={!subjects.some((item) => item.can_delete && !item.is_active)}
                            className="h-4 w-4 rounded border-outline-variant accent-primary cursor-pointer disabled:cursor-not-allowed"
                            aria-label="Pilih semua mata pelajaran yang dapat dihapus pada halaman ini"
                        />
                    </TableHeadCell>
                )}
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-28">Kode</TableHeadCell>
                <TableHeadCell>Nama Mata Pelajaran</TableHeadCell>
                <TableHeadCell className="w-36">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {subjects.map((item, index) => (
                    <TableRow
                        key={item.id}
                        className="even:bg-surface-container-low/40 hover:bg-primary/5"
                    >
                        {selectable && (
                            <TableCell className="text-center">
                                <input
                                    type="checkbox"
                                    checked={selectedIds.includes(item.id)}
                                    onChange={() => onToggleSelect?.(item.id)}
                                    disabled={!item.can_delete || item.is_active}
                                    className="h-4 w-4 rounded border-outline-variant accent-primary cursor-pointer disabled:cursor-not-allowed"
                                    aria-label={`Pilih ${item.name}`}
                                />
                            </TableCell>
                        )}
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
                            {item.can_toggle_status ? (
                                <button
                                    type="button"
                                    onClick={() => onToggleStatus?.(item)}
                                    disabled={togglingId !== null}
                                    title={
                                        item.is_active
                                            ? 'Klik untuk menonaktifkan mata pelajaran ini'
                                            : 'Klik untuk mengaktifkan mata pelajaran ini'
                                    }
                                    className="group inline-flex items-center rounded-xl transition enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
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
                                    <span
                                        className="material-symbols-outlined text-[13px]"
                                    >
                                        {item.is_active ? 'check_circle' : 'remove_circle'}
                                    </span>
                                    {item.is_active ? 'Aktif' : 'Tidak Aktif'}
                                </Badge>
                            )}
                        </TableCell>
                        <TableCell className="text-right">
                            <RowActions
                                onShow={() => onShow?.(item)}
                                onEdit={item.can_edit ? () => onEdit?.(item) : undefined}
                                onDelete={
                                    item.can_delete && !item.is_active
                                        ? () => onDelete?.(item)
                                        : undefined
                                }
                            />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
