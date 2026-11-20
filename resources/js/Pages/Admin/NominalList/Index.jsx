import React, { useEffect, useMemo, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Badge from '@/Components/UI/Badge';
import { route } from '@/Utils/route';
import { useToast } from '@/Hooks/useToast';
import ToastContainer from '@/Components/Layout/ToastContainer';

export default function NominalListIndex({
    participants = [],
    subjects = [],
    classes = [],
    schools = [],
    sessions = [],
    rooms = [],
    activeAcademicYear = null,
    filters = {},
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(
        auth?.user?.is_administrator || auth?.user?.is_school_admin,
    );

    const [schoolFilter, setSchoolFilter] = useState(
        filters?.school_id ? String(filters.school_id) : '',
    );
    const [academicYearFilter, setAcademicYearFilter] = useState(
        filters?.academic_year_id ? String(filters.academic_year_id) : '',
    );
    const [classFilter, setClassFilter] = useState(
        filters?.class_id ? String(filters.class_id) : '',
    );
    const [subjectFilter, setSubjectFilter] = useState(
        filters?.subject_id ? String(filters.subject_id) : '',
    );
    const [search, setSearch] = useState(filters?.search ?? '');
    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = participants?.data ?? participants ?? [];

    const filteredClasses = useMemo(
        () =>
            classes.filter(
                (item) =>
                    !schoolFilter ||
                    String(item.school_id) === String(schoolFilter),
            ),
        [classes, schoolFilter],
    );

    const filteredSessions = useMemo(
        () =>
            sessions.filter(
                (item) =>
                    !schoolFilter ||
                    String(item.school_id) === String(schoolFilter),
            ),
        [sessions, schoolFilter],
    );

    const filteredRooms = useMemo(
        () =>
            rooms.filter(
                (item) =>
                    !schoolFilter ||
                    String(item.school_id) === String(schoolFilter),
            ),
        [rooms, schoolFilter],
    );

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const buildQuery = (extra = {}) => ({
        ...(schoolFilter ? { school_id: schoolFilter } : {}),
        ...(academicYearFilter ? { academic_year_id: academicYearFilter } : {}),
        ...(classFilter ? { class_id: classFilter } : {}),
        ...(subjectFilter ? { subject_id: subjectFilter } : {}),
        ...(search ? { search } : {}),
        ...extra,
    });

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(route('admin.nominal-list.index'), buildQuery(), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['participants', 'filters'],
            });
        }, 300);

        return () => clearTimeout(timeout);
    }, [
        schoolFilter,
        academicYearFilter,
        classFilter,
        subjectFilter,
        search,
    ]);

    const handleExportPdf = () => {
        const params = buildQuery();
        window.open(
            route('admin.nominal-list.pdf', params),
            '_blank',
            'noopener',
        );
    };

    const defaultSchoolId = schoolFilter || schools[0]?.id || '';
    const hasActiveYear = Boolean(activeAcademicYear?.id);

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1720px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span>Administrasi Ujian</span>
                            <span/>
                            <span className="text-primary font-semibold">
                                Daftar Nominatif
                            </span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Daftar Nominatif Peserta
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Daftar lengkap peserta ujian per kelas dengan NISN,
                            nama, dan status kredensial.
                        </p>
                    </div>

                    {canManage && hasActiveYear && (
                        <Button
                            onClick={handleExportPdf}
                        >
                            <span className="material-symbols-outlined text-[18px]">
                                picture_as_pdf
                            </span>
                            Export PDF
                        </Button>
                    )}
                </div>

                {!hasActiveYear && (
                    <div className="flex items-start gap-3 rounded-2xl bg-error-container/30 border border-error-container/60 px-4 py-3">
                        <span className="material-symbols-outlined text-error text-[20px] shrink-0">
                            error
                        </span>
                        <div>
                            <p className="font-label font-semibold text-sm text-on-surface">
                                Belum ada tahun pelajaran aktif
                            </p>
                            <p className="text-xs text-on-surface-variant font-body mt-0.5">
                                Aktifkan salah satu tahun pelajaran untuk
                                menampilkan daftar peserta.
                            </p>
                        </div>
                    </div>
                )}

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Filter Daftar Peserta
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Filter berdasarkan sekolah, tahun pelajaran,
                                kelas, dan cari nama/NISN.
                            </p>
                        </div>

                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                            <Select
                                id="nominal-school-filter"
                                label="Sekolah"
                                value={schoolFilter}
                                onChange={(event) => setSchoolFilter(event.target.value)}
                                placeholder="Semua Sekolah"
                                className="w-full sm:w-48"
                            >
                                <option value="">Semua Sekolah</option>
                                {schools.map((school) => (
                                    <option key={school.id} value={school.id}>
                                        {school.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="nominal-year-filter"
                                label="Tahun Pelajaran"
                                value={academicYearFilter}
                                onChange={(event) =>
                                    setAcademicYearFilter(event.target.value)
                                }
                                placeholder="Semua Tahun"
                                className="w-full sm:w-44"
                            >
                                <option value="">Semua Tahun Pelajaran</option>
                                <option
                                    key={activeAcademicYear?.id}
                                    value={activeAcademicYear?.id ?? ''}
                                >
                                    {activeAcademicYear?.name ?? 'Aktif'}
                                </option>
                            </Select>

                            <Select
                                id="nominal-class-filter"
                                label="Kelas"
                                value={classFilter}
                                onChange={(event) => setClassFilter(event.target.value)}
                                placeholder="Semua Kelas"
                                className="w-full sm:w-40"
                            >
                                <option value="">Semua Kelas</option>
                                {filteredClasses.map((schoolClass) => (
                                    <option key={schoolClass.id} value={schoolClass.id}>
                                        {schoolClass.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="nominal-subject-filter"
                                label="Mata Pelajaran"
                                value={subjectFilter}
                                onChange={(event) =>
                                    setSubjectFilter(event.target.value)
                                }
                                placeholder="Semua Mapel"
                                className="w-full sm:w-44"
                            >
                                <option value="">Semua Mata Pelajaran</option>
                                {subjects.map((subject) => (
                                    <option key={subject.id} value={subject.id}>
                                        {subject.name}
                                    </option>
                                ))}
                            </Select>

                            <Input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Cari nama atau NISN..."
                                className="w-full sm:w-64"
                                aria-label="Cari peserta"
                            />
                        </div>
                    </div>

                    <div className="px-6 py-3 bg-surface-container-low/40 border-b border-surface-container-low">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-label text-on-surface-variant uppercase tracking-wider font-semibold">
                                Daftar Peserta ({rows.length})
                            </span>
                            <div className="flex items-center gap-3 text-xs">
                                <span className="font-semibold text-on-surface-variant">
                                    Total:
                                </span>
                                <span className="text-on-surface font-mono">
                                    {rows.length}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="px-6 py-4">
                        {rows.length === 0 ? (
                            <div className="flex items-center justify-center py-12 text-xs text-on-surface-variant">
                                Belum ada peserta yang sesuai dengan filter.
                            </div>
                        ) : (
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-surface-container-low text-on-surface-variant font-label text-[11px] font-semibold uppercase tracking-wider">
                                        <th className="px-4 py-3.5 text-center" style={{ width: '5mm' }}>No.</th>
                                        <th className="px-4 py-3.5">Nama Peserta</th>
                                        <th className="px-4 py-3.5 text-center" style={{ width: '19mm' }}>NISN</th>
                                        <th className="px-4 py-3.5 text-center" style={{ width: '20mm' }}>No. Peserta</th>
                                        <th className="px-4 py-3.5 text-center" style={{ width: '26mm' }}>Kelas</th>
                                        <th className="px-4 py-3.5 text-center" style={{ width: '22mm' }}>Username</th>
                                        <th className="px-4 py-3.5 text-center" style={{ width: '22mm' }}>Kredensial</th>
                                        <th className="px-4 py-3.5 text-center" style={{ width: '22mm' }}>Penempatan</th>
                                    </tr>
                                </thead>
                                <tbody className="text-xs font-body divide-y divide-surface-container-low">
                                    {rows.map((row, index) => (
                                        <tr
                                            key={row.student_id}
                                            className="hover:bg-surface-container-low/60 transition-colors"
                                        >
                                            <td className="px-4 py-3 text-center font-mono">
                                                {index + 1}
                                            </td>
                                            <td className="px-4 py-3 font-semibold text-on-surface">
                                                {row.name}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {row.nisn ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {row.participant_number}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {row.class_name ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {row.username}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Badge
                                                    variant={
                                                        row.has_credential
                                                            ? 'success'
                                                            : 'neutral'
                                                    }
                                                >
                                                    {row.has_credential
                                                        ? 'Lengkap'
                                                        : 'Belum'}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Badge
                                                    variant={
                                                        row.has_placement
                                                            ? 'primary'
                                                            : 'tertiary'
                                                    }
                                                >
                                                    {row.has_placement
                                                        ? 'Terpenuhi'
                                                        : 'Belum'}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </Card>

                <ToastContainer toasts={toasts} dismiss={dismiss} />
            </div>
        </AppLayout>
    );
}
