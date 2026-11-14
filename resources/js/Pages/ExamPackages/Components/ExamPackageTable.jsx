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

const statusVariant = (status) => {
    switch (status) {
        case 'aktif':
            return 'success';
        case 'selesai':
            return 'primary';
        default:
            return 'neutral';
    }
};

export default function ExamPackageTable({
    examPackages,
    onShow,
    onEdit,
    onDelete,
    searchQuery = '',
    startIndex = 0,
    canManage = true,
}) {
    if (!examPackages || examPackages.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        inventory_2
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Paket Ujian'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada paket ujian yang cocok dengan "${searchQuery}".`
                        : 'Mulai dengan menambahkan paket ujian pertama.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-64">Paket Ujian</TableHeadCell>
                <TableHeadCell className="w-52">Sekolah</TableHeadCell>
                <TableHeadCell className="w-40">Tahun Pelajaran</TableHeadCell>
                <TableHeadCell className="w-44">Jenis Ujian</TableHeadCell>
                <TableHeadCell className="w-48">Periode</TableHeadCell>
                <TableHeadCell className="w-24 text-center">Jadwal</TableHeadCell>
                <TableHeadCell className="w-32">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {examPackages.map((item, index) => (
                    <TableRow
                        key={item.id}
                        className="even:bg-surface-container-low/40 hover:bg-primary/5"
                    >
                        <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                            {startIndex + index + 1}
                        </TableCell>
                        <TableCell>
                            <div className="font-semibold text-on-surface text-sm">
                                {item.name}
                            </div>
                            <div className="mt-1 inline-flex rounded-lg bg-surface-container-low px-2 py-0.5 font-mono text-[10px] font-semibold text-on-surface-variant">
                                {item.code}
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
                            <div className="text-sm text-on-surface">
                                {item.academic_year_name ?? '-'}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="text-sm text-on-surface">
                                {item.exam_type_name ?? '-'}
                            </div>
                            {item.exam_type_code && (
                                <div className="text-[10px] font-mono text-on-surface-variant">
                                    {item.exam_type_code}
                                </div>
                            )}
                            {item.exam_type_is_active === false && (
                                <div className="text-[10px] font-label font-semibold text-tertiary">
                                    tidak aktif
                                </div>
                            )}
                        </TableCell>
                        <TableCell>
                            <div className="text-xs text-on-surface">
                                {item.start_date ?? '-'}
                            </div>
                            <div className="text-[11px] text-on-surface-variant">
                                s.d. {item.end_date ?? '-'}
                            </div>
                        </TableCell>
                        <TableCell className="text-center">
                            <span className="inline-flex min-w-8 items-center justify-center rounded-lg bg-primary/10 px-2 py-1 text-xs font-label font-semibold text-primary">
                                {item.schedule_count ?? 0}
                            </span>
                        </TableCell>
                        <TableCell>
                            <Badge variant={statusVariant(item.status)}>
                                {item.status_label}
                            </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                            {canManage ? (
                                <RowActions
                                    onShow={() => onShow?.(item)}
                                    onEdit={() => onEdit?.(item)}
                                    onDelete={() => onDelete?.(item)}
                                />
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => onShow?.(item)}
                                    className="text-xs font-label text-primary hover:underline"
                                >
                                    Detail
                                </button>
                            )}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
