import React, { useState } from 'react';
import Avatar from '@/Components/UI/Avatar';
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

const PAGE_SIZE = 5;

const STUDENTS = [
    {
        id: 1,
        no: '01',
        name: 'Muhammad Rizky Pratama',
        nisn: '0064928172',
        class: 'XII MIPA 1',
        room: 'Lab Komputer 01',
        subject: 'Matematika XII (A-04)',
        progress: { current: 38, total: 40, percent: 95 },
        connection: 'stable',
        integrity: 'clean',
        status: 'active',
    },
    {
        id: 2,
        no: '02',
        name: 'Dimas Wahyu Wicaksono',
        nisn: '0058392100',
        class: 'XII MIPA 1',
        room: 'Lab Komputer 01',
        subject: 'Matematika XII (A-19)',
        progress: { current: 19, total: 40, percent: 47 },
        connection: 'fluctuating',
        integrity: 'tabSwitch',
        status: 'violation',
    },
    {
        id: 3,
        no: '03',
        name: 'Siti Aisyah Nuraini',
        nisn: '0061129944',
        class: 'XII MIPA 2',
        room: 'Lab Multimedia 02',
        subject: 'Matematika XII (B-02)',
        progress: { current: 31, total: 40, percent: 77 },
        connection: 'disconnected',
        integrity: 'clean',
        status: 'disconnected',
    },
    {
        id: 4,
        no: '04',
        name: 'Amanda Putri Kusuma',
        nisn: '0067741289',
        class: 'XII MIPA 2',
        room: 'Lab Multimedia 02',
        subject: 'Matematika XII (B-14)',
        progress: { current: 26, total: 40, percent: 65 },
        connection: 'stable',
        integrity: 'clean',
        status: 'active',
    },
    {
        id: 5,
        no: '05',
        name: 'Bagas Aditya Wardana',
        nisn: '0069921105',
        class: 'XI IPS 3',
        room: 'Ruang Kelas XII A',
        subject: 'Bahasa Indonesia (C-09)',
        progress: { current: 15, total: 50, percent: 30 },
        connection: 'stable',
        integrity: 'multipleFace',
        status: 'warning',
    },
    {
        id: 6,
        no: '06',
        name: 'Nabila Zahra Ramadhani',
        nisn: '0065532148',
        class: 'XII MIPA 1',
        room: 'Lab Komputer 01',
        subject: 'Matematika XII (A-27)',
        progress: { current: 40, total: 40, percent: 100 },
        connection: 'stable',
        integrity: 'clean',
        status: 'active',
    },
    {
        id: 7,
        no: '07',
        name: 'Fajar Nugroho Saputra',
        nisn: '0062219874',
        class: 'XII IPS 1',
        room: 'Lab Komputer 02',
        subject: 'Bahasa Inggris (D-11)',
        progress: { current: 22, total: 40, percent: 55 },
        connection: 'fluctuating',
        integrity: 'clean',
        status: 'active',
    },
    {
        id: 8,
        no: '08',
        name: 'Zahra Amelia Putri',
        nisn: '0068874512',
        class: 'XI MIPA 3',
        room: 'Ruang Kelas XI B',
        subject: 'IPA Terpadu (E-03)',
        progress: { current: 9, total: 50, percent: 18 },
        connection: 'stable',
        integrity: 'tabSwitch',
        status: 'warning',
    },
];

const getRowClassName = (status) => {
    if (status === 'violation') {
        return 'bg-error-container/20 hover:bg-error-container/30';
    }

    if (status === 'warning') {
        return 'bg-tertiary-container/15 hover:bg-tertiary-container/25';
    }

    return '';
};

export default function StudentTable({ className = '' }) {
    const [page, setPage] = useState(1);

    const lastPage = Math.max(1, Math.ceil(STUDENTS.length / PAGE_SIZE));
    const currentPage = Math.min(page, lastPage);
    const startIndex = (currentPage - 1) * PAGE_SIZE;
    const visibleStudents = STUDENTS.slice(startIndex, startIndex + PAGE_SIZE);
    const showingFrom = STUDENTS.length === 0 ? 0 : startIndex + 1;
    const showingTo = Math.min(startIndex + PAGE_SIZE, STUDENTS.length);

    return (
        <div className={`bg-surface-container-lowest rounded-2xl shadow-sm overflow-hidden ${className}`}>
            <Table id="studentsTable">
                <TableHead>
                    <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                    <TableHeadCell>Siswa &amp; Identitas</TableHeadCell>
                    <TableHeadCell>Ruang &amp; Subjek</TableHeadCell>
                    <TableHeadCell className="w-44">Progres Soal</TableHeadCell>
                    <TableHeadCell className="text-center">Koneksi</TableHeadCell>
                    <TableHeadCell>Integritas CBT</TableHeadCell>
                    <TableHeadCell className="text-right">Tindakan Pengawas</TableHeadCell>
                </TableHead>
                <TableBody>
                    {visibleStudents.map((student) => (
                        <TableRow key={student.id} className={getRowClassName(student.status)}>
                            <TableCell className="text-center font-mono text-on-surface-variant font-medium">
                                {student.no}
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center gap-3">
                                    <Avatar
                                        label={student.name}
                                        variant={
                                            student.status === 'violation'
                                                ? 'error'
                                                : student.status === 'warning'
                                                ? 'tertiary'
                                                : 'primary'
                                        }
                                    />
                                    <div>
                                        <div className="font-headline font-semibold text-on-surface text-sm">
                                            {student.name}
                                        </div>
                                        <div className="font-mono text-[11px] text-on-surface-variant">
                                            NISN: {student.nisn} • {student.class}
                                        </div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div className="text-on-surface font-medium text-xs">
                                    {student.room}
                                </div>
                                <div className="text-[11px] text-on-surface-variant">
                                    {student.subject}
                                </div>
                            </TableCell>
                            <TableCell>
                                <div className="flex justify-between items-center text-[11px] font-label mb-1">
                                    <span className="font-semibold text-on-surface">
                                        {student.progress.current} / {student.progress.total} Soal
                                    </span>
                                    <span
                                        className={
                                            student.integrity === 'tabSwitch'
                                                ? 'text-error font-bold'
                                                : 'text-primary font-bold'
                                        }
                                    >
                                        {student.progress.percent}%
                                    </span>
                                </div>
                                <ProgressBar
                                    value={student.progress.percent}
                                    color={student.integrity === 'tabSwitch' ? 'error' : 'primary'}
                                />
                            </TableCell>
                            <TableCell className="text-center">
                                <ConnectionStatus status={student.connection} />
                            </TableCell>
                            <TableCell>
                                <IntegrityStatus status={student.integrity} />
                            </TableCell>
                            <TableCell className="text-right">
                                <RowActions student={student} />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>

            <Pagination
                currentPage={currentPage}
                lastPage={lastPage}
                onPageChange={setPage}
                from={showingFrom}
                to={showingTo}
                total={STUDENTS.length}
                itemLabel="Siswa Aktif"
                className="p-4 bg-surface-container-low"
            />
        </div>
    );
}
