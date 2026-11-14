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

export default function UserTable({
    users,
    onEdit,
    onDelete,
    onToggleStatus,
    togglingId = null,
    currentUserId = null,
    searchQuery = '',
    startIndex = 0,
}) {
    if (!users || users.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        person
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Data'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada pengguna yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menambahkan pengguna pertama.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-24">Username</TableHeadCell>
                <TableHeadCell className="w-56">Nama Lengkap</TableHeadCell>
                <TableHeadCell className="w-56">Email</TableHeadCell>
                <TableHeadCell className="w-40">Role</TableHeadCell>
                <TableHeadCell className="w-32">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {users.map((item, index) => {
                    const isCurrentUser = item.id === currentUserId;

                    return (
                        <TableRow
                            key={item.id}
                            className="even:bg-surface-container-low/40 hover:bg-primary/5"
                        >
                            <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                                {startIndex + index + 1}
                            </TableCell>
                            <TableCell>
                                <span className="inline-flex rounded-lg bg-surface-container-low px-2 py-1 font-mono text-xs font-semibold text-on-surface">
                                    {item.username}
                                </span>
                                {isCurrentUser && (
                                    <span className="ml-2 text-[10px] font-label uppercase tracking-wider text-primary font-semibold">
                                        Anda
                                    </span>
                                )}
                            </TableCell>
                            <TableCell>
                                <div className="font-semibold text-on-surface text-sm">
                                    {item.name}
                                </div>
                            </TableCell>
                            <TableCell>
                                <span className="font-body text-sm text-on-surface-variant truncate max-w-xs">
                                    {item.email}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span className="font-body text-sm text-on-surface-variant capitalize">
                                    {item.role_label}
                                </span>
                            </TableCell>
                            <TableCell>
                                <button
                                    type="button"
                                    onClick={() => onToggleStatus?.(item)}
                                    disabled={togglingId !== null}
                                    title={
                                        item.is_active
                                            ? 'Klik untuk menonaktifkan pengguna ini'
                                            : 'Klik untuk mengaktifkan pengguna ini'
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
                            </TableCell>
                            <TableCell className="text-right">
                                <RowActions
                                    onEdit={() => onEdit?.(item)}
                                    onDelete={
                                        !item.is_active && !isCurrentUser
                                            ? () => onDelete?.(item)
                                            : undefined
                                    }
                                />
                            </TableCell>
                        </TableRow>
                    );
                })}
            </TableBody>
        </Table>
    );
}
