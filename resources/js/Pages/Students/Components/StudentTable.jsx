import React from 'react';
import Badge from '@/Components/UI/Badge';
import RowActions from '@/Components/UI/RowActions';
import StudentAvatar from './StudentAvatar';
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

export default function StudentTable({
    students,
    canManage = true,
    searchQuery = '',
    startIndex = 0,
    onEdit,
    onDelete,
    onRegistrations,
    selectable = false,
    selectedIds = [],
    allSelected = false,
    onToggleSelect,
    onToggleAll,
}) {
    if (!students || students.length === 0) {
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
                        ? `Tidak ada siswa yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menambahkan siswa pertama.'}
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
                            className="h-4 w-4 rounded border-outline-variant accent-primary cursor-pointer"
                            aria-label="Pilih semua siswa pada halaman ini"
                        />
                    </TableHeadCell>
                )}
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-52">Siswa</TableHeadCell>
                <TableHeadCell className="w-32">NISN</TableHeadCell>
                <TableHeadCell className="w-20">JK</TableHeadCell>
                <TableHeadCell className="w-48">Sekolah</TableHeadCell>
                <TableHeadCell className="w-40">Kelas</TableHeadCell>
                <TableHeadCell className="w-28">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {students.map((item, index) => (
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
                                    className="h-4 w-4 rounded border-outline-variant accent-primary cursor-pointer"
                                    aria-label={`Pilih ${item.name}`}
                                />
                            </TableCell>
                        )}
                        <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                            {startIndex + index + 1}
                        </TableCell>
                        <TableCell>
                            <div className="flex items-center gap-3">
                                <StudentAvatar name={item.name} photoUrl={item.photo_url} />
                                <div className="min-w-0">
                                    <div className="font-semibold text-on-surface text-sm truncate">
                                        {item.name}
                                    </div>
                                    <div className="font-mono text-[11px] text-on-surface-variant">
                                        {item.registration?.nis ?? 'Belum ada NIS'}
                                    </div>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>
                            <span className="font-mono text-xs text-on-surface-variant">
                                {item.nisn ?? '—'}
                            </span>
                        </TableCell>
                        <TableCell>
                            <span className="font-body text-sm text-on-surface-variant">
                                {item.gender_label}
                            </span>
                        </TableCell>
                        <TableCell>
                            {item.registration ? (
                                <>
                                    <div className="font-semibold text-on-surface text-sm">
                                        {item.registration.school_name}
                                    </div>
                                    <div className="font-mono text-[11px] text-on-surface-variant">
                                        {item.registration.school_code}
                                    </div>
                                </>
                            ) : (
                                <span className="text-xs text-on-surface-variant">
                                    Belum terdaftar
                                </span>
                            )}
                        </TableCell>
                        <TableCell>
                            {item.current_class ? (
                                <>
                                    <div className="font-semibold text-on-surface text-sm">
                                        {item.current_class.name}
                                    </div>
                                    <div className="font-mono text-[11px] text-on-surface-variant">
                                        {item.current_class.code}
                                    </div>
                                </>
                            ) : (
                                <span className="text-xs text-on-surface-variant">—</span>
                            )}
                        </TableCell>
                        <TableCell>
                            {item.registration ? (
                                <Badge variant={statusVariant(item.registration.status)}>
                                    {item.registration.status_label}
                                </Badge>
                            ) : (
                                <Badge variant="neutral">Belum Terdaftar</Badge>
                            )}
                        </TableCell>
                        <TableCell className="text-right">
                            {canManage ? (
                                <div className="inline-flex items-center gap-1">
                                    {onRegistrations && (
                                        <button
                                            type="button"
                                            onClick={() => onRegistrations(item)}
                                            title="Kelola Registrasi Sekolah"
                                            aria-label="Kelola Registrasi Sekolah"
                                            className="p-2 rounded-lg transition-colors text-primary hover:bg-primary/10"
                                        >
                                            <span className="material-symbols-outlined text-[18px]">
                                                badge
                                            </span>
                                        </button>
                                    )}
                                    <RowActions
                                        onEdit={() => onEdit?.(item)}
                                        onDelete={() => onDelete?.(item)}
                                    />
                                </div>
                            ) : (
                                <span className="text-xs text-on-surface-variant">—</span>
                            )}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
