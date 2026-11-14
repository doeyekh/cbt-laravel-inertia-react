import React, { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import StudentAvatar from './StudentAvatar';

const emptyForm = {
    school_id: '',
    nis: '',
    status: 'aktif',
};

export default function PullStudentModal({
    isOpen,
    onClose,
    schools = [],
    academicYears = [],
    isAdministrator = false,
}) {
    const { data, setData, post, processing, errors, clearErrors, reset } = useForm(emptyForm);
    const [clientErrors, setClientErrors] = useState({});
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const [loading, setLoading] = useState(false);
    const [searched, setSearched] = useState(false);

    const abortRef = useRef(null);
    const skipInitialSearch = useRef(true);

    const activeAcademicYear =
        academicYears.find((academicYear) => academicYear.is_active) ?? null;

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        reset();
        setClientErrors({});
        setQuery('');
        setResults([]);
        setSelectedStudent(null);
        setSearched(false);
        skipInitialSearch.current = true;
        setData('school_id', schools[0]?.id ? String(schools[0].id) : '');
    }, [isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        if (skipInitialSearch.current) {
            skipInitialSearch.current = false;

            return;
        }

        const trimmed = query.trim();

        if (!data.school_id || trimmed === '') {
            setResults([]);
            setSearched(false);

            return;
        }

        const timeout = setTimeout(async () => {
            abortRef.current?.abort();
            const controller = new AbortController();
            abortRef.current = controller;
            setLoading(true);

            try {
                const response = await fetch(
                    route('admin.student-registrations.available', {
                        school_id: data.school_id,
                        search: trimmed,
                    }),
                    {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                        signal: controller.signal,
                    }
                );

                if (!response.ok) {
                    throw new Error('Gagal memuat data siswa.');
                }

                const payload = await response.json();
                setResults(payload.data ?? []);
                setSearched(true);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setResults([]);
                    setSearched(true);
                }
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(timeout);
    }, [query, data.school_id, isOpen]);

    useEffect(() => {
        return () => abortRef.current?.abort();
    }, []);

    const fieldError = (field) => clientErrors[field] ?? errors[field];

    const clearFieldError = (field) => {
        if (clientErrors[field]) {
            setClientErrors((previous) => {
                const next = { ...previous };
                delete next[field];

                return next;
            });
        }

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSchoolChange = (value) => {
        setData('school_id', value);
        setData('nis', '');
        setSelectedStudent(null);
        setResults([]);
        setSearched(false);
        clearFieldError('school_id');
        clearFieldError('nis');
    };

    const handleSelect = (student) => {
        if (selectedStudent?.id !== student.id) {
            setData('nis', '');
            clearFieldError('nis');
        }

        setSelectedStudent(student);
        clearFieldError('student_id');
    };

    const handleClearSelection = () => {
        setSelectedStudent(null);
        setData('nis', '');
        clearFieldError('nis');
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!selectedStudent) {
            nextErrors.student_id = 'Pilih siswa yang akan ditarik.';
        }

        if (!data.nis.trim()) {
            nextErrors.nis = 'NIS wajib diisi.';
        }

        if (!activeAcademicYear) {
            nextErrors.academic_year =
                'Tahun pelajaran aktif belum diatur. Aktifkan tahun pelajaran terlebih dahulu.';
        }

        if (Object.keys(nextErrors).length > 0) {
            setClientErrors(nextErrors);

            return;
        }

        post(
            route('admin.student-registrations.store', {
                student: selectedStudent.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => onClose(),
            }
        );
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        person_add
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Tarik Siswa
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Daftarkan siswa yang sudah ada di tabel siswa ke sekolah baru.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <Select
                    id="pull-school"
                    label="Sekolah Tujuan"
                    value={data.school_id}
                    onChange={(event) => handleSchoolChange(event.target.value)}
                    error={fieldError('school_id')}
                    disabled={!isAdministrator && schools.length <= 1}
                    placeholder="Pilih sekolah..."
                >
                    {schools.map((school) => (
                        <option key={school.id} value={String(school.id)}>
                            {school.is_active ? school.name : `${school.name} (Tidak Aktif)`}
                        </option>
                    ))}
                </Select>

                <Input
                    id="pull-student-search"
                    label="Cari Siswa"
                    type="search"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Ketik nama, NISN, atau NIS..."
                    disabled={!data.school_id}
                />

                {!selectedStudent && (
                    <div className="max-h-60 overflow-y-auto rounded-xl bg-surface-container-low p-2 space-y-1">
                        {loading && (
                            <p className="px-3 py-6 text-center text-xs text-on-surface-variant font-body">
                                Mencari siswa...
                            </p>
                        )}

                        {!loading && results.length === 0 && (
                            <p className="px-3 py-6 text-center text-xs text-on-surface-variant font-body">
                                {!data.school_id
                                    ? 'Pilih sekolah tujuan terlebih dahulu.'
                                    : searched
                                      ? 'Tidak ada siswa yang cocok atau siswa sudah terdaftar di sekolah tujuan.'
                                      : 'Ketik minimal satu karakter untuk mencari siswa.'}
                            </p>
                        )}

                        {!loading &&
                            results.map((student) => (
                                <button
                                    key={student.id}
                                    type="button"
                                    onClick={() => handleSelect(student)}
                                    className="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-colors hover:bg-surface-container"
                                >
                                    <StudentAvatar
                                        name={student.name}
                                        photoUrl={student.photo_url}
                                        size="w-8 h-8"
                                        textSize="text-[10px]"
                                    />
                                    <span className="min-w-0 flex-1">
                                        <span className="block font-semibold text-on-surface text-sm truncate">
                                            {student.name}
                                        </span>
                                        <span className="block font-mono text-[11px] text-on-surface-variant truncate">
                                            {student.nisn ?? 'Tanpa NISN'} ·{' '}
                                            {student.gender_label} ·{' '}
                                            {student.current_school_name ?? 'Belum terdaftar'}
                                        </span>
                                    </span>
                                    <span className="material-symbols-outlined text-primary text-[18px]">
                                        chevron_right
                                    </span>
                                </button>
                            ))}
                    </div>
                )}

                {(fieldError('student_id') || clientErrors.student_id) && (
                    <p className="text-[11px] font-label text-error">
                        {fieldError('student_id')}
                    </p>
                )}

                {selectedStudent && (
                    <div className="flex items-start gap-3 rounded-xl bg-primary/5 px-3 py-3 ring-1 ring-primary/20">
                        <StudentAvatar
                            name={selectedStudent.name}
                            photoUrl={selectedStudent.photo_url}
                            size="w-10 h-10"
                            textSize="text-xs"
                        />
                        <div className="min-w-0 flex-1">
                            <p className="font-semibold text-on-surface text-sm truncate">
                                {selectedStudent.name}
                            </p>
                            <p className="font-mono text-[11px] text-on-surface-variant">
                                {selectedStudent.nisn ?? 'Tanpa NISN'} ·{' '}
                                {selectedStudent.gender_label}
                            </p>
                            <p className="text-[11px] text-on-surface-variant font-body mt-1">
                                Saat ini:{' '}
                                {selectedStudent.current_school_name ?? 'Belum terdaftar'}
                                {selectedStudent.current_status_label
                                    ? ` · ${selectedStudent.current_status_label}`
                                    : ''}
                            </p>
                            {selectedStudent.has_active_registration && (
                                <p className="text-[11px] text-tertiary font-body mt-1">
                                    Registrasi aktif lama akan otomatis menjadi Pindah.
                                </p>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={handleClearSelection}
                            className="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container transition-colors"
                            aria-label="Batalkan pilihan siswa"
                        >
                            <span className="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                )}

                {selectedStudent && (
                    <Input
                        id="pull-nis"
                        label="NIS di Sekolah Tujuan"
                        type="text"
                        value={data.nis}
                        onChange={(event) => {
                            setData('nis', event.target.value);
                            clearFieldError('nis');
                        }}
                        placeholder="Nomor induk sekolah baru"
                        error={fieldError('nis')}
                        maxLength={30}
                    />
                )}

                <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                    <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                        calendar_month
                    </span>
                    <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                        Tahun masuk:{' '}
                        {activeAcademicYear
                            ? `${activeAcademicYear.name} · ${activeAcademicYear.semester_label} (otomatis mengikuti tahun pelajaran aktif)`
                            : 'tahun pelajaran aktif belum diatur.'}
                    </p>
                </div>

                {fieldError('academic_year') && (
                    <p className="text-[11px] font-label text-error">
                        {fieldError('academic_year')}
                    </p>
                )}

                <div className="flex items-center gap-3 justify-end pt-4">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={handleClose}
                        disabled={processing}
                        className="flex-1"
                    >
                        Batal
                    </Button>
                    <Button type="submit" loading={processing}>
                        Tarik &amp; Registrasikan
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
