import React, { useEffect, useRef, useState } from 'react';
import Badge from '@/Components/UI/Badge';
import Table, {
    TableHead,
    TableHeadCell,
    TableBody,
    TableRow,
    TableCell,
} from '@/Components/UI/Table';

export default function ExamParticipantTable({
    participants,
    onEdit,
    onDelete,
    onResetPassword,
    selectedIds = [],
    onToggleSelect,
    onToggleAll,
    searchQuery = '',
    startIndex = 0,
    canManage = true,
}) {
    const [revealed, setRevealed] = useState({});
    const selectAllRef = useRef(null);

    const selectable = canManage && Boolean(onToggleSelect) && Boolean(onToggleAll);
    const selectedCount = participants
        ? participants.filter((item) => selectedIds.includes(item.id)).length
        : 0;
    const allSelected =
        Boolean(participants?.length) && selectedCount === participants.length;
    const someSelected = selectedCount > 0 && !allSelected;

    useEffect(() => {
        if (selectAllRef.current) {
            selectAllRef.current.indeterminate = someSelected;
        }
    }, [someSelected]);

    if (!participants || participants.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4">
                    <span className="material-symbols-outlined text-on-surface-variant text-[32px]">
                        person_search
                    </span>
                </div>
                <h3 className="font-headline font-semibold text-on-surface text-lg mb-2">
                    {searchQuery
                        ? 'Data Tidak Ditemukan'
                        : 'Belum Ada Peserta'}
                </h3>
                <p className="text-sm text-on-surface-variant font-body">
                    {searchQuery
                        ? `Tidak ada peserta yang cocok dengan "${searchQuery}".`
                        : 'Gunakan "Tarik Peserta dari Kelas" untuk mengisi anggota kelas.'}
                </p>
            </div>
        );
    }

    return (
        <Table>
            <TableHead>
                {selectable && (
                    <TableHeadCell className="w-12 text-center">
                        <input
                            ref={selectAllRef}
                            type="checkbox"
                            checked={allSelected}
                            onChange={(event) =>
                                onToggleAll?.(event.target.checked)
                            }
                            aria-label="Pilih semua peserta"
                            className="h-4 w-4 rounded border-outline text-primary focus:ring-primary"
                        />
                    </TableHeadCell>
                )}
                <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                <TableHeadCell>Nama Peserta</TableHeadCell>
                <TableHeadCell className="w-40">Nomor Peserta</TableHeadCell>
                <TableHeadCell className="w-40">Username</TableHeadCell>
                <TableHeadCell className="w-40">Password</TableHeadCell>
                <TableHeadCell className="w-48">Ruangan / Sesi</TableHeadCell>
                <TableHeadCell className="w-32 text-center">Status</TableHeadCell>
                <TableHeadCell className="text-right">Aksi</TableHeadCell>
            </TableHead>
            <TableBody>
                {participants.map((item, index) => {
                    const isRevealed = Boolean(revealed[item.id]);
                    const hasPassword = Boolean(item.password);

                    return (
                        <TableRow
                            key={item.id}
                            className="even:bg-surface-container-low/40 hover:bg-primary/5"
                        >
                            {selectable && (
                                <TableCell className="text-center">
                                    <input
                                        type="checkbox"
                                        checked={selectedIds.includes(item.id)}
                                        onChange={() =>
                                            onToggleSelect?.(item.id)
                                        }
                                        className="h-4 w-4 rounded border-outline text-primary focus:ring-primary"
                                        aria-label={`Pilih ${item.student_name ?? 'peserta'}`}
                                    />
                                </TableCell>
                            )}
                            <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                                {startIndex + index + 1}
                            </TableCell>
                            <TableCell>
                                <div className="font-semibold text-on-surface text-sm">
                                    {item.student_name ?? '-'}
                                </div>
                                <div className="text-[10px] font-mono text-on-surface-variant">
                                    NIS: {item.student_nis ?? '-'} · NISN:{' '}
                                    {item.student_nisn ?? '-'}
                                </div>
                                <div className="text-[10px] text-on-surface-variant">
                                    {item.class_name ?? 'Tanpa kelas'}
                                </div>
                            </TableCell>
                            <TableCell>
                                {item.participant_number ? (
                                    <span className="inline-flex rounded-lg bg-primary/10 px-2 py-1 font-mono text-xs font-bold text-primary">
                                        {item.participant_number}
                                    </span>
                                ) : (
                                    <span className="text-xs text-on-surface-variant">
                                        Belum diisi
                                    </span>
                                )}
                            </TableCell>
                            <TableCell>
                                {item.username ? (
                                    <span className="font-mono text-xs font-semibold text-on-surface">
                                        {item.username}
                                    </span>
                                ) : (
                                    <span className="text-xs text-on-surface-variant">
                                        Belum diisi
                                    </span>
                                )}
                            </TableCell>
                            <TableCell>
                                {hasPassword ? (
                                    <div className="flex items-center gap-1.5">
                                        <span className="font-mono text-xs font-semibold text-on-surface">
                                            {isRevealed
                                                ? item.password
                                                : '••••••••'}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setRevealed((current) => ({
                                                    ...current,
                                                    [item.id]:
                                                        !current[item.id],
                                                }))
                                            }
                                            title={
                                                isRevealed
                                                    ? 'Sembunyikan password'
                                                    : 'Tampilkan password'
                                            }
                                            className="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors"
                                        >
                                            <span className="material-symbols-outlined text-[16px]">
                                                {isRevealed
                                                    ? 'visibility_off'
                                                    : 'visibility'}
                                            </span>
                                        </button>
                                    </div>
                                ) : (
                                    <span className="text-xs text-on-surface-variant">
                                        Belum dibuat
                                    </span>
                                )}
                            </TableCell>
                            <TableCell>
                                {item.has_placement ? (
                                    <>
                                        <div className="text-sm font-medium text-on-surface">
                                            {item.room_name ?? '-'}
                                        </div>
                                        <div className="text-[10px] text-on-surface-variant">
                                            {item.session_name ?? '-'}
                                        </div>
                                    </>
                                ) : (
                                    <Badge variant="warning">
                                        <span className="material-symbols-outlined text-[13px]">
                                            pending
                                        </span>
                                        Belum
                                    </Badge>
                                )}
                            </TableCell>
                            <TableCell className="text-center">
                                <Badge
                                    variant={
                                        item.has_credential
                                            ? 'success'
                                            : 'warning'
                                    }
                                >
                                    <span className="material-symbols-outlined text-[13px]">
                                        {item.has_credential
                                            ? 'task_alt'
                                            : 'pending'}
                                    </span>
                                    {item.has_credential
                                        ? 'Lengkap'
                                        : 'Belum'}
                                </Badge>
                            </TableCell>
                            <TableCell className="text-right">
                                {canManage ? (
                                    <div className="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                onResetPassword?.(item)
                                            }
                                            title="Acak ulang password"
                                            className="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors"
                                        >
                                            <span className="material-symbols-outlined text-[18px]">
                                                restart_alt
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => onEdit?.(item)}
                                            title="Edit kredensial"
                                            className="p-2 rounded-lg text-primary hover:bg-primary/10 transition-colors"
                                        >
                                            <span className="material-symbols-outlined text-[18px]">
                                                edit
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => onDelete?.(item)}
                                            title="Keluarkan peserta"
                                            className="p-2 rounded-lg text-error hover:bg-error-container/20 transition-colors"
                                        >
                                            <span className="material-symbols-outlined text-[18px]">
                                                delete
                                            </span>
                                        </button>
                                    </div>
                                ) : (
                                    <span className="text-xs text-on-surface-variant">
                                        -
                                    </span>
                                )}
                            </TableCell>
                        </TableRow>
                    );
                })}
            </TableBody>
        </Table>
    );
}
