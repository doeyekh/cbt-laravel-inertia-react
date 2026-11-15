import React from 'react';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import RowActions from '@/Components/UI/RowActions';
import Table, {
    TableHead,
    TableHeadCell,
    TableBody,
    TableRow,
    TableCell,
} from '@/Components/UI/Table';

const ScheduleSettingChip = ({ active, children }) => (
    <span
        className={`
            inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px]
            font-label font-semibold
            ${
                active
                    ? 'bg-primary/10 text-primary'
                    : 'bg-surface-container-low text-on-surface-variant'
            }
        `}
    >
        {children}
    </span>
);

export default function ExamScheduleList({
    schedules,
    examPackage,
    canManage,
    togglingId,
    formatDate,
    onToggleStatus,
    onEdit,
    onDelete,
}) {
    return (
        <Card padding="p-0">
            <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 className="font-headline font-semibold text-on-surface text-lg">
                        Jadwal Ujian
                    </h3>
                    <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                        Satu kelas tidak dapat memiliki dua jadwal pada tanggal dan
                        sesi yang sama.
                    </p>
                </div>
            </div>

            {schedules.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-16 text-center">
                    <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                        <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                            calendar_month
                        </span>
                    </div>
                    <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                        Belum Ada Jadwal
                    </h3>
                    <p className="text-sm text-on-surface-variant font-body">
                        Tambahkan jadwal pertama untuk paket ujian ini.
                    </p>
                </div>
            ) : (
                <Table>
                    <TableHead>
                        <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                        <TableHeadCell className="w-36">Tanggal</TableHeadCell>
                        <TableHeadCell className="w-44">Sesi</TableHeadCell>
                        <TableHeadCell className="w-44">Kelas</TableHeadCell>
                        <TableHeadCell>Mata Pelajaran</TableHeadCell>
                        <TableHeadCell className="w-40">Ruangan</TableHeadCell>
                        <TableHeadCell className="w-24 text-center">Durasi</TableHeadCell>
                        <TableHeadCell className="w-32 text-center">Jam Soal</TableHeadCell>
                        <TableHeadCell className="w-56">Pengaturan</TableHeadCell>
                        <TableHeadCell className="w-24 text-center">Status</TableHeadCell>
                        {canManage && (
                            <TableHeadCell className="text-right">Aksi</TableHeadCell>
                        )}
                    </TableHead>
                    <TableBody>
                        {schedules.map((item, index) => (
                            <TableRow
                                key={item.id}
                                className="even:bg-surface-container-low/40 hover:bg-primary/5"
                            >
                                <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                                    {index + 1}
                                </TableCell>
                                <TableCell>
                                    <span className="font-body text-sm text-on-surface">
                                        {formatDate(item.exam_date)}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div className="text-sm text-on-surface">
                                        {item.session_name ?? '-'}
                                    </div>
                                    <div className="text-[11px] text-on-surface-variant font-mono">
                                        {item.session_start_time?.slice(0, 5)} -{' '}
                                        {item.session_end_time?.slice(0, 5)}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="text-sm font-medium text-on-surface">
                                        {item.class_name ?? '-'}
                                    </div>
                                    <div className="text-[10px] font-mono text-on-surface-variant">
                                        {item.class_code}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="text-sm text-on-surface">
                                        {item.subject_name ?? '-'}
                                    </div>
                                    <div className="text-[10px] font-mono text-on-surface-variant">
                                        {item.subject_code}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="text-sm text-on-surface">
                                        {item.room_name ?? '-'}
                                    </div>
                                    <div className="text-[10px] font-mono text-on-surface-variant">
                                        {item.room_code}
                                    </div>
                                </TableCell>
                                <TableCell className="text-center text-on-surface-variant">
                                    {item.duration_minutes} mnt
                                </TableCell>
                                <TableCell className="text-center">
                                    <span className="font-mono text-xs text-on-surface">
                                        {item.open_time?.slice(0, 5)} -{' '}
                                        {item.close_time?.slice(0, 5)}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div className="flex flex-wrap gap-1">
                                        <ScheduleSettingChip
                                            active={item.shuffle_questions}
                                        >
                                            Acak soal
                                        </ScheduleSettingChip>
                                        <ScheduleSettingChip
                                            active={item.shuffle_options}
                                        >
                                            Acak opsi
                                        </ScheduleSettingChip>
                                        <ScheduleSettingChip
                                            active={item.show_score_to_student}
                                        >
                                            Nilai tampil
                                        </ScheduleSettingChip>
                                    </div>
                                </TableCell>
                                <TableCell className="text-center">
                                    {canManage ? (
                                        <button
                                            type="button"
                                            onClick={() => onToggleStatus(item)}
                                            disabled={
                                                togglingId !== null ||
                                                examPackage?.status === 'selesai'
                                            }
                                            title={
                                                examPackage?.status === 'selesai'
                                                    ? 'Paket ujian sudah selesai.'
                                                    : item.status === 'aktif'
                                                      ? 'Klik untuk menonaktifkan jadwal ini'
                                                      : 'Klik untuk mengaktifkan jadwal ini'
                                            }
                                            className="group inline-flex items-center rounded-xl transition enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <Badge
                                                variant={
                                                    item.status === 'aktif'
                                                        ? 'success'
                                                        : 'neutral'
                                                }
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
                                                        : item.status === 'aktif'
                                                          ? 'check_circle'
                                                          : 'remove_circle'}
                                                </span>
                                                {item.status_label}
                                            </Badge>
                                        </button>
                                    ) : (
                                        <Badge
                                            variant={
                                                item.status === 'aktif'
                                                    ? 'success'
                                                    : 'neutral'
                                            }
                                        >
                                            <span className="material-symbols-outlined text-[13px]">
                                                {item.status === 'aktif'
                                                    ? 'check_circle'
                                                    : 'remove_circle'}
                                            </span>
                                            {item.status_label}
                                        </Badge>
                                    )}
                                </TableCell>
                                {canManage && (
                                    <TableCell className="text-right">
                                        <RowActions
                                            onEdit={
                                                examPackage?.status !== 'selesai'
                                                    ? () => onEdit(item)
                                                    : undefined
                                            }
                                            onDelete={
                                                item.status === 'nonaktif' &&
                                                examPackage?.status !== 'selesai'
                                                    ? () => onDelete(item)
                                                    : undefined
                                            }
                                        />
                                    </TableCell>
                                )}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </Card>
    );
}
