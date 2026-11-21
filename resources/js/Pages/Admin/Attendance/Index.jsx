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

const STATUS_LABELS = {
    hadir: 'Hadir',
    tidak_hadir: 'Tidak Hadir',
    ijin: 'Ijin',
    sakit: 'Sakit',
};

const STATUS_VARIANTS = {
    hadir: 'success',
    tidak_hadir: 'neutral',
    ijin: 'warning',
    sakit: 'error',
};

export default function AttendanceIndex({
    schedules = [],
    selectedSchedule = null,
    participants = [],
    attendanceCount = 0,
    presentCount = 0,
    statuses = [],
    filters = {},
    schools = [],
    classes = [],
    sessions = [],
    rooms = [],
    activeAcademicYear = null,
}) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(
        auth?.user?.is_administrator || auth?.user?.is_school_admin,
    );

    const [search, setSearch] = useState(filters?.search ?? '');
    const [schoolFilter, setSchoolFilter] = useState(
        filters?.school_id ? String(filters.school_id) : '',
    );
    const [academicYearFilter, setAcademicYearFilter] = useState(
        filters?.academic_year_id ? String(filters.academic_year_id) : '',
    );
    const [classFilter, setClassFilter] = useState(
        filters?.class_id ? String(filters.class_id) : '',
    );
    const [sessionFilter, setSessionFilter] = useState(
        filters?.session_id ? String(filters.session_id) : '',
    );
    const [roomFilter, setRoomFilter] = useState(
        filters?.room_id ? String(filters.room_id) : '',
    );
    const [scheduleFilter, setScheduleFilter] = useState(
        filters?.exam_schedule_id ? String(filters.exam_schedule_id) : '',
    );
    const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');

    const [attendanceForm, setAttendanceForm] = useState({});
    const [saving, setSaving] = useState(false);
    const skipInitialSearch = useRef(true);
    const handledToast = useRef(null);

    const rows = participants ?? [];
    const schedule = selectedSchedule ?? null;

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

    const filteredSchedules = useMemo(
        () =>
            schedules.filter((item) => {
                if (academicYearFilter && item.academic_year_id !== academicYearFilter) {
                    return false;
                }
                if (classFilter && item.class_id !== classFilter) {
                    return false;
                }
                if (sessionFilter && item.session_id !== sessionFilter) {
                    return false;
                }
                if (roomFilter && item.room_id !== roomFilter) {
                    return false;
                }
                return true;
            }),
        [schedules, academicYearFilter, classFilter, sessionFilter, roomFilter],
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
        ...(sessionFilter ? { session_id: sessionFilter } : {}),
        ...(roomFilter ? { room_id: roomFilter } : {}),
        ...(scheduleFilter ? { exam_schedule_id: scheduleFilter } : {}),
        ...(statusFilter ? { status: statusFilter } : {}),
        ...extra,
    });

    useEffect(() => {
        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(route('admin.attendances.index'), buildQuery(), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['schedules', 'selectedSchedule', 'participants', 'attendanceCount', 'presentCount', 'filters'],
            });
        }, 300);

        return () => clearTimeout(timeout);
    }, [
        schoolFilter,
        academicYearFilter,
        classFilter,
        sessionFilter,
        roomFilter,
        scheduleFilter,
        statusFilter,
    ]);

    const handleStatusChange = (participantId, status) => {
        setAttendanceForm((previous) => ({
            ...previous,
            [participantId]: status,
        }));
    };

    const attendanceRecords = useMemo(() => {
        return rows.map((row) => ({
            exam_participant_id: row.participant_id,
            status: attendanceForm[row.participant_id] ?? row.status,
        }));
    }, [rows, attendanceForm]);

    const handleSaveAttendance = () => {
        if (!schedule) {
            return;
        }

        setSaving(true);

        router.post(
            route('admin.attendances.store'),
            {
                exam_schedule_id: schedule.id,
                attendances: attendanceRecords,
            },
            {
                onSuccess: () => {
                    setAttendanceForm({});
                    setSaving(false);
                    router.get(route('admin.attendances.index'), buildQuery(), {
                        preserveState: true,
                        preserveScroll: true,
                    });
                },
                onFinish: () => setSaving(false),
            },
        );
    };

    const defaultSchoolId = schoolFilter || schools[0]?.id || '';
    const hasActiveYear = Boolean(activeAcademicYear?.id);
    const hasSchedule = Boolean(schedule);

    const presentCountValue = presentCount ?? rows.filter((r) => r.status === 'hadir').length;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1720px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span>Pengelolaan Ujian</span>
                            <span className="text-primary font-semibold">
                                Daftar Hadir
                            </span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Daftar Hadir Ujian
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Tandai kehadiran peserta pada jadwal ujian yang
                            dipilih.
                        </p>
                    </div>

                    {canManage && hasSchedule && (
                        <Button
                            onClick={handleSaveAttendance}
                            disabled={!hasSchedule || saving}
                            loading={saving}
                        >
                            <span className="material-symbols-outlined text-[18px]">
                                save
                            </span>
                            Simpan Kehadiran
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
                                Aktifkan salah satu tahun pelajaran sebelum
                                memilih jadwal ujian.
                            </p>
                        </div>
                    </div>
                )}

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div>
                            <h3 className="font-headline font-semibold text-on-surface text-lg">
                                Filter Jadwal Ujian
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Pilih sekolah, tahun pelajaran, dan jadwal untuk
                                melihat daftar peserta.
                            </p>
                        </div>

                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                            <Select
                                id="attendance-school-filter"
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
                                id="attendance-year-filter"
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
                                id="attendance-class-filter"
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
                                id="attendance-session-filter"
                                label="Sesi"
                                value={sessionFilter}
                                onChange={(event) => setSessionFilter(event.target.value)}
                                placeholder="Semua Sesi"
                                className="w-full sm:w-40"
                            >
                                <option value="">Semua Sesi</option>
                                {filteredSessions.map((session) => (
                                    <option key={session.id} value={session.id}>
                                        {session.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="attendance-room-filter"
                                label="Ruangan"
                                value={roomFilter}
                                onChange={(event) => setRoomFilter(event.target.value)}
                                placeholder="Semua Ruangan"
                                className="w-full sm:w-40"
                            >
                                <option value="">Semua Ruangan</option>
                                {filteredRooms.map((room) => (
                                    <option key={room.id} value={room.id}>
                                        {room.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                id="attendance-schedule-filter"
                                label="Jadwal Ujian"
                                value={scheduleFilter}
                                onChange={(event) => setScheduleFilter(event.target.value)}
                                placeholder="Pilih Jadwal"
                                className="w-full sm:w-56"
                                disabled={!hasActiveYear}
                            >
                                <option value="">Pilih Jadwal Ujian</option>
                                {filteredSchedules.map((scheduleItem) => (
                                    <option
                                        key={scheduleItem.id}
                                        value={scheduleItem.id}
                                    >
                                        {scheduleItem.subject} • {scheduleItem.class} • {scheduleItem.exam_date}
                                    </option>
                                ))}
                            </Select>
                        </div>
                    </div>

                    {hasSchedule && (
                        <>
                        <div className="px-6 py-3 bg-surface-container-low/40 border-b border-surface-container-low flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <span className="material-symbols-outlined text-[16px] text-on-surface-variant">
                                    schedule
                                </span>
                                <span className="font-semibold text-on-surface">
                                    {schedule?.subject} • {schedule?.class}
                                </span>
                                <span className="text-xs text-on-surface-variant">
                                    {schedule?.exam_date} • {schedule?.open_time} - {schedule?.close_time}
                                </span>
                                <span className="text-xs text-on-surface-variant">
                                    • {schedule?.room} / {schedule?.session}
                                </span>
                            </div>
                            <div className="flex items-center gap-3 text-xs">
                                <span className="font-semibold text-on-surface-variant">
                                    Total Peserta:
                                </span>
                                <span className="text-on-surface font-mono">
                                    {attendanceCount}
                                </span>
                                <Badge variant="success">
                                    Hadir: {presentCountValue}
                                </Badge>
                            </div>
                        </div>

                        <div className="px-6 py-4">
                            {rows.length === 0 ? (
                                <div className="flex items-center justify-center py-12 text-xs text-on-surface-variant">
                                    Belum ada peserta terdaftar pada jadwal
                                    ini.
                                </div>
                            ) : (
                                
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-surface-container-low text-on-surface-variant font-label text-[11px] font-semibold uppercase tracking-wider">
                                            <th className="px-4 py-3.5">No.</th>
                                            <th className="px-4 py-3.5">Nama Peserta</th>
                                            <th className="px-4 py-3.5 text-center">NISN</th>
                                            <th className="px-4 py-3.5 text-center">No. Peserta</th>
                                            <th className="px-4 py-3.5 text-center">Kelas</th>
                                            <th className="px-4 py-3.5 text-center">Kredensial</th>
                                            <th className="px-4 py-3.5 text-center">Status Kehadiran</th>
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
                                                    <div className="flex items-center justify-center gap-2">
                                                        {statuses.map((status) => (
                                                            <label
                                                                key={status.value}
                                                                className="inline-flex items-center gap-1.5 cursor-pointer"
                                                                onClick={() =>
                                                                    handleStatusChange(
                                                                        row.participant_id,
                                                                        status.value,
                                                                    )
                                                                }
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    name={`attendance-${row.participant_id}`}
                                                                    value={status.value}
                                                                    checked={
                                                                        (attendanceForm[row.participant_id] ??
                                                                            row.status) ===
                                                                        status.value
                                                                    }
                                                                    onChange={(event) =>
                                                                        handleStatusChange(
                                                                            row.participant_id,
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                    className="h-4 w-4 shrink-0 rounded border-outline text-primary focus:ring-primary/30"
                                                                />
                                                                <span
                                                                    className={`text-[10px] font-label font-semibold ${
                                                                        STATUS_VARIANTS[status.value]
                                                                    }`}
                                                                >
                                                                    {STATUS_LABELS[status.value]}
                                                                </span>
                                                            </label>
                                                        ))}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                        </>
                    )}
                </Card>

                <ToastContainer toasts={toasts} dismiss={dismiss} />
            </div>
        </AppLayout>
    );
}
