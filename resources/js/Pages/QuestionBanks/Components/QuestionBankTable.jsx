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

const compositionLabels = [
    { key: 'pilihan_ganda', label: 'PG' },
    { key: 'pilihan_ganda_kompleks', label: 'PK' },
    { key: 'menjodohkan', label: 'MJ' },
    { key: 'isian_singkat', label: 'IS' },
    { key: 'esai', label: 'ES' },
];

export default function QuestionBankTable({
    questionBanks,
    onQuestions,
    onPreview,
    onImport,
    onEdit,
    onDelete,
    searchQuery = '',
    startIndex = 0,
    canManage = true,
}) {
    if (!questionBanks || questionBanks.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        inventory_2
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery ? 'Data Tidak Ditemukan' : 'Belum Ada Bank Soal'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada bank soal yang cocok dengan "${searchQuery}".`
                        : 'Buat paket bank soal untuk menentukan komposisi soal ujian.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell className="w-64">Bank Soal</TableHeadCell>
                <TableHeadCell className="w-48">Sekolah</TableHeadCell>
                <TableHeadCell className="w-40">Tahun Pelajaran</TableHeadCell>
                <TableHeadCell className="w-44">Mata Pelajaran</TableHeadCell>
                <TableHeadCell className="w-44">Jenis Ujian</TableHeadCell>
                <TableHeadCell className="w-64">Kelas</TableHeadCell>
                <TableHeadCell>Komposisi Soal</TableHeadCell>
                <TableHeadCell className="w-24 text-center">Total</TableHeadCell>
                <TableHeadCell className="w-24 text-center">Total Poin</TableHeadCell>
                <TableHeadCell className="w-24 text-center">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {questionBanks.map((item, index) => (
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
                            <div className="text-sm text-on-surface">
                                {item.school_name ?? '-'}
                            </div>
                            <div className="text-[10px] font-mono text-on-surface-variant">
                                {item.school_code}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="text-sm font-medium text-on-surface">
                                {item.academic_year_name ?? '-'}
                            </div>
                            <div className="text-[10px] font-mono text-on-surface-variant">
                                {item.academic_year_code ?? ''}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="text-sm font-medium text-on-surface">
                                {item.subject_name ?? '-'}
                            </div>
                            <div className="text-[10px] font-mono text-on-surface-variant">
                                {item.subject_code}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="text-sm font-medium text-on-surface">
                                {item.exam_type_name ?? '-'}
                            </div>
                            <div className="text-[10px] font-mono text-on-surface-variant">
                                {item.exam_type_code ?? '-'}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="flex flex-wrap gap-1">
                                {item.classes?.length > 0 ? (
                                    item.classes.map((schoolClass) => (
                                        <span
                                            key={schoolClass.id}
                                            className="inline-flex rounded-lg bg-surface-container px-2 py-0.5 text-[10px] font-label font-medium text-on-surface-variant"
                                        >
                                            {schoolClass.code}
                                        </span>
                                    ))
                                ) : (
                                    <span className="text-xs text-on-surface-variant">
                                        -
                                    </span>
                                )}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="flex flex-wrap gap-1.5">
                                {compositionLabels.map((composition) => {
                                    const amount =
                                        item.composition?.[composition.key] ?? 0;

                                    if (amount === 0) {
                                        return null;
                                    }

                                    return (
                                        <span
                                            key={composition.key}
                                            className="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-2 py-0.5 text-[10px] font-label font-semibold text-primary"
                                        >
                                            {composition.label}
                                            <span className="font-mono">
                                                {amount}
                                            </span>
                                        </span>
                                    );
                                })}
                            </div>
                        </TableCell>
                        <TableCell className="text-center font-mono font-semibold text-on-surface">
                            {item.total_question_count ?? 0}
                        </TableCell>
                        <TableCell className="text-center font-mono font-semibold text-primary">
                            {item.total_score ?? 0}
                        </TableCell>
                        <TableCell className="text-center">
                            <Badge variant={item.is_active ? 'success' : 'neutral'}>
                                {item.is_active ? 'Aktif' : 'Nonaktif'}
                            </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                            <div className="inline-flex items-center gap-1">
                                <button
                                    type="button"
                                    onClick={() => onPreview?.(item)}
                                    title="Lihat soal"
                                    aria-label={`Lihat soal ${item.name}`}
                                    className="p-2 rounded-lg text-primary hover:bg-primary/10 transition-colors"
                                >
                                    <span className="material-symbols-outlined text-[18px]">
                                        visibility
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => onQuestions?.(item)}
                                    title="Kelola soal"
                                    aria-label={`Kelola soal ${item.name}`}
                                    className="p-2 rounded-lg text-primary hover:bg-primary/10 transition-colors"
                                >
                                    <span className="material-symbols-outlined text-[18px]">
                                        quiz
                                    </span>
                                </button>
                                {canManage && (
                                    <button
                                        type="button"
                                        onClick={() => onImport?.(item)}
                                        title="Import soal"
                                        aria-label={`Import soal ${item.name}`}
                                        className="p-2 rounded-lg text-primary hover:bg-primary/10 transition-colors"
                                    >
                                        <span className="material-symbols-outlined text-[18px]">
                                            upload_file
                                        </span>
                                    </button>
                                )}
                                {canManage ? (
                                    <RowActions
                                        onEdit={() => onEdit?.(item)}
                                        onDelete={() => onDelete?.(item)}
                                    />
                                ) : (
                                    <span className="text-xs text-on-surface-variant">
                                        -
                                    </span>
                                )}
                            </div>
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
