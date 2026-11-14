import React from 'react';
import StudentAvatar from '@/Pages/Students/Components/StudentAvatar';
import Table, {
    TableHead,
    TableHeadCell,
    TableBody,
    TableRow,
    TableCell,
} from '@/Components/UI/Table';

export default function ClassMemberTable({
    members,
    canManage = true,
    searchQuery = '',
    startIndex = 0,
    onRemove,
}) {
    if (!members || members.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        groups
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Anggota'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada siswa yang cocok dengan "${searchQuery}".`
                        : 'Tambahkan siswa ke kelas ini melalui tombol Tambah Siswa.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-32">NIS</TableHeadCell>
                <TableHeadCell className="w-64">Nama Siswa</TableHeadCell>
                <TableHeadCell className="w-32">NISN</TableHeadCell>
                <TableHeadCell className="w-28">Jenis Kelamin</TableHeadCell>
                {canManage && (
                    <TableHeadCell className="text-right">Aksi</TableHeadCell>
                )}
            </TableHead>
            <TableBody>
                {members.map((item, index) => (
                    <TableRow
                        key={item.id}
                        className="even:bg-surface-container-low/40 hover:bg-primary/5"
                    >
                        <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                            {startIndex + index + 1}
                        </TableCell>
                        <TableCell>
                            <span className="font-mono text-xs text-on-surface-variant">
                                {item.nis ?? '—'}
                            </span>
                        </TableCell>
                        <TableCell>
                            <div className="flex items-center gap-3">
                                <StudentAvatar name={item.name} photoUrl={item.photo_url} />
                                <span className="font-semibold text-on-surface text-sm">
                                    {item.name}
                                </span>
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
                        {canManage && (
                            <TableCell className="text-right">
                                <button
                                    type="button"
                                    onClick={() => onRemove?.(item)}
                                    title="Keluarkan dari kelas"
                                    aria-label="Keluarkan dari kelas"
                                    className="p-2 rounded-lg transition-colors text-error hover:bg-error-container/20"
                                >
                                    <span className="material-symbols-outlined text-[18px]">
                                        person_remove
                                    </span>
                                </button>
                            </TableCell>
                        )}
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
