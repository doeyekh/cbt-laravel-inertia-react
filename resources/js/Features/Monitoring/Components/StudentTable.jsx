import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import Avatar from '@/Components/UI/Avatar';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import Table, {
    TableHead,
    TableHeadCell,
    TableBody,
    TableRow,
    TableCell,
} from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import ConnectionStatus from './ConnectionStatus';
import IntegrityStatus from './IntegrityStatus';
import ProgressBar from './ProgressBar';
import RowActions from './RowActions';
import EssayGradeModal from './EssayGradeModal';
import { route } from '@/Utils/route';

const PAGE_SIZE = 10;

const formatDuration = (seconds) => {
    const totalSeconds = Math.max(0, Number(seconds) || 0);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const remainingSeconds = totalSeconds % 60;

    return [hours, minutes, remainingSeconds]
        .map((part) => String(part).padStart(2, '0'))
        .join(':');
};

const getRowClassName = (status) => {
    if (status === 'disconnected') {
        return 'bg-error-container/20 hover:bg-error-container/30';
    }

    if (status === 'submitted') {
        return 'bg-emerald-50/50 hover:bg-emerald-50';
    }

    return '';
};

export default function StudentTable({
    students = [],
    searchQuery = '',
    className = '',
}) {
    const [page, setPage] = useState(1);
    const [gradeTarget, setGradeTarget] = useState(null);
    const [resetTarget, setResetTarget] = useState(null);
    const [isResetting, setIsResetting] = useState(false);

    useEffect(() => {
        setPage(1);
    }, [searchQuery]);

    const normalizedSearch = searchQuery.trim().toLocaleLowerCase();
    const filteredStudents = students.filter((student) =>
        [student.name, student.nisn, student.class, student.room, student.subject]
            .some((value) => String(value ?? '').toLocaleLowerCase().includes(normalizedSearch))
    );
    const lastPage = Math.max(1, Math.ceil(filteredStudents.length / PAGE_SIZE));
    const currentPage = Math.min(page, lastPage);
    const startIndex = (currentPage - 1) * PAGE_SIZE;
    const visibleStudents = filteredStudents.slice(startIndex, startIndex + PAGE_SIZE);
    const showingFrom = filteredStudents.length === 0 ? 0 : startIndex + 1;
    const showingTo = Math.min(startIndex + PAGE_SIZE, filteredStudents.length);

    const handleConfirmReset = () => {
        if (!resetTarget || isResetting) {
            return;
        }

        setIsResetting(true);
        router.delete(route('admin.exam-attempts.reset', resetTarget.attempt_id), {
            preserveScroll: true,
            onSuccess: () => setResetTarget(null),
            onFinish: () => setIsResetting(false),
        });
    };

    return (
        <div className={`overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm ${className}`}>
            <Table id="studentsTable">
                <TableHead>
                    <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                    <TableHeadCell>Siswa &amp; Identitas</TableHeadCell>
                    <TableHeadCell>Ruang &amp; Subjek</TableHeadCell>
                    <TableHeadCell className="w-44">Progres Soal</TableHeadCell>
                    <TableHeadCell className="text-center">Aktivitas</TableHeadCell>
                    <TableHeadCell>Integritas CBT</TableHeadCell>
                    <TableHeadCell className="text-right">Tindakan</TableHeadCell>
                </TableHead>
                <TableBody>
                    {visibleStudents.length > 0 ? (
                        visibleStudents.map((student, index) => (
                            <TableRow key={student.id} className={getRowClassName(student.status)}>
                                <TableCell className="text-center font-mono font-medium text-on-surface-variant">
                                    {startIndex + index + 1}
                                </TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-3">
                                        <Avatar
                                            label={student.name}
                                            variant={student.status === 'disconnected' ? 'error' : 'primary'}
                                        />
                                        <div>
                                            <div className="font-headline text-sm font-semibold text-on-surface">
                                                {student.name}
                                            </div>
                                            <div className="font-mono text-[11px] text-on-surface-variant">
                                                NISN: {student.nisn} • {student.class}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="text-xs font-medium text-on-surface">{student.room}</div>
                                    <div className="text-[11px] text-on-surface-variant">{student.subject}</div>
                                </TableCell>
                                <TableCell>
                                    <div className="mb-1 flex items-center justify-between text-[11px]">
                                        <span className="font-semibold text-on-surface">
                                            {student.progress.current} / {student.progress.total} soal
                                        </span>
                                        <span className="font-bold text-primary">
                                            {student.progress.percent}%
                                        </span>
                                    </div>
                                    <ProgressBar value={student.progress.percent} color="primary" />
                                </TableCell>
                                <TableCell className="text-center">
                                    <ConnectionStatus status={student.connection} />
                                    {student.last_activity && (
                                        <p className="mt-1 text-[10px] text-on-surface-variant">
                                            {new Date(student.last_activity).toLocaleTimeString()}
                                        </p>
                                    )}
                                    {student.status !== 'not_started' && (
                                        <p className="mt-1 text-[10px] text-on-surface-variant">
                                            Waktu: {formatDuration(student.time_spent_seconds)}
                                        </p>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <IntegrityStatus flaggedCount={student.flagged_count} />
                                </TableCell>
                                <TableCell className="text-right">
                                    <RowActions
                                        student={student}
                                        onGrade={setGradeTarget}
                                        onReset={setResetTarget}
                                    />
                                </TableCell>
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell colSpan={7} className="py-12 text-center text-on-surface-variant">
                                {students.length === 0
                                    ? 'Belum ada peserta terjadwal pada sesi ujian yang sedang berlangsung.'
                                    : 'Tidak ada siswa yang cocok dengan pencarian.'}
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>

            <Pagination
                currentPage={currentPage}
                lastPage={lastPage}
                onPageChange={setPage}
                from={showingFrom}
                to={showingTo}
                total={filteredStudents.length}
                itemLabel="Peserta Terpantau"
                className="bg-surface-container-low p-4"
            />

            <EssayGradeModal
                isOpen={Boolean(gradeTarget)}
                onClose={() => setGradeTarget(null)}
                student={gradeTarget}
            />

            <ConfirmDialog
                isOpen={Boolean(resetTarget)}
                onCancel={() => {
                    if (!isResetting) {
                        setResetTarget(null);
                    }
                }}
                onConfirm={handleConfirmReset}
                loading={isResetting}
                title="Reset ujian peserta?"
                description={resetTarget
                    ? `Semua jawaban, flag, dan nilai ${resetTarget.name} untuk ${resetTarget.subject} akan dihapus permanen. Peserta dapat memulai ulang ujian.`
                    : ''}
                confirmLabel="Reset Jawaban & Nilai"
            />
        </div>
    );
}
