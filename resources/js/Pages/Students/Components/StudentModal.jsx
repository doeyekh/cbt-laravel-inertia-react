import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import Button from '@/Components/UI/Button';
import StudentAvatar from './StudentAvatar';
import useSpoofedMethod from '@/Hooks/useSpoofedMethod';

const emptyForm = {
    nisn: '',
    name: '',
    gender: 'L',
    birth_place: '',
    birth_date: '',
    photo: null,
    school_id: '',
    nis: '',
    status: 'aktif',
    exit_academic_year_id: '',
    father_name: '',
    father_occupation: '',
    father_phone: '',
    mother_name: '',
    mother_occupation: '',
    mother_phone: '',
    parent_address: '',
    parent_bio: '',
};

const MAX_NAME_LENGTH = 255;
const MAX_NISN_LENGTH = 20;
const MAX_NIS_LENGTH = 30;

export default function StudentModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    schools = [],
    academicYears = [],
    genders = [],
    statuses = [],
    isAdministrator = false,
}) {
    const { data, setData, post, transform, processing, errors, clearErrors } =
        useForm(emptyForm);
    const [clientErrors, setClientErrors] = useState({});
    const [previewUrl, setPreviewUrl] = useState(null);

    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);

    useSpoofedMethod(transform, isEdit);

    const activeAcademicYear =
        academicYears.find((academicYear) => academicYear.is_active) ?? null;
    const hasActiveAcademicYear = Boolean(activeAcademicYear);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setClientErrors({});

        if (isEdit && initialData) {
            setData({
                ...emptyForm,
                nisn: initialData.nisn ?? '',
                name: initialData.name ?? '',
                gender: initialData.gender ?? 'L',
                birth_place: initialData.birth_place ?? '',
                birth_date: initialData.birth_date ?? '',
                father_name: initialData.father_name ?? '',
                father_occupation: initialData.father_occupation ?? '',
                father_phone: initialData.father_phone ?? '',
                mother_name: initialData.mother_name ?? '',
                mother_occupation: initialData.mother_occupation ?? '',
                mother_phone: initialData.mother_phone ?? '',
                parent_address: initialData.parent_address ?? '',
                parent_bio: initialData.parent_bio ?? '',
            });
            setPreviewUrl(initialData.photo_url ?? null);

            return;
        }

        setData({
            ...emptyForm,
            school_id: schools[0]?.id ? String(schools[0].id) : '',
        });
        setPreviewUrl(null);
    }, [isOpen, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        return () => {
            if (previewUrl?.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const fieldError = (field) => clientErrors[field] ?? errors[field];

    const setField = (field, value) => {
        setData(field, value);

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

    const handleStatusChange = (value) => {
        setField('status', value);

        if (value === 'aktif') {
            setField('exit_academic_year_id', '');
        }
    };

    const handlePhotoChange = (event) => {
        const file = event.target.files?.[0] ?? null;

        setField('photo', file);

        if (file) {
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const nextErrors = {};
        const name = data.name.trim();

        if (!name) {
            nextErrors.name = 'Nama lengkap wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (!data.gender) {
            nextErrors.gender = 'Jenis kelamin wajib dipilih.';
        }

        if (!isEdit) {
            if (!data.school_id) {
                nextErrors.school_id = 'Sekolah wajib dipilih.';
            }

            if (!data.nis.trim()) {
                nextErrors.nis = 'NIS wajib diisi.';
            } else if (data.nis.trim().length > MAX_NIS_LENGTH) {
                nextErrors.nis = `NIS maksimal ${MAX_NIS_LENGTH} karakter.`;
            }

            if (!hasActiveAcademicYear) {
                nextErrors.academic_year =
                    'Tahun pelajaran aktif belum diatur. Aktifkan tahun pelajaran terlebih dahulu.';
            }

            if (!data.status) {
                nextErrors.status = 'Status registrasi wajib dipilih.';
            }

            if (data.status !== 'aktif' && !data.exit_academic_year_id) {
                nextErrors.exit_academic_year_id = 'Tahun keluar wajib dipilih.';
            }
        }

        if (Object.keys(nextErrors).length > 0) {
            setClientErrors(nextErrors);

            return;
        }

        const options = {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            post(route('admin.students.update', initialData.id), options);

            return;
        }

        post(route('admin.students.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Edit Siswa' : 'Tambah Siswa';
    const subtitle = isEdit
        ? 'Perbarui identitas siswa. Registrasi sekolah dikelola terpisah.'
        : 'Tambahkan identitas siswa beserta registrasi sekolahnya.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-2xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isEdit ? 'edit_note' : 'person_add'}
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        {title}
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">{subtitle}</p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-5">
                <section className="space-y-4">
                    <h4 className="font-label text-[11px] font-semibold uppercase tracking-wider text-primary">
                        Identitas Siswa
                    </h4>

                    <div className="flex items-center gap-4">
                        <StudentAvatar
                            name={data.name || 'Siswa'}
                            photoUrl={previewUrl}
                            size="w-16 h-16"
                            textSize="text-lg"
                        />
                        <div className="flex-1">
                            <label
                                htmlFor="student-photo"
                                className="block text-[11px] font-label font-semibold text-on-surface-variant mb-1.5 uppercase tracking-wider"
                            >
                                Foto Siswa
                            </label>
                            <input
                                id="student-photo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={handlePhotoChange}
                                disabled={processing}
                                className="block w-full text-xs text-on-surface-variant file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20"
                            />
                            <p className="mt-1 text-[10px] text-on-surface-variant font-body">
                                JPG, PNG, atau WEBP. Maksimal 2 MB.
                            </p>
                            {fieldError('photo') && (
                                <p className="mt-1 text-[10px] font-label text-error">
                                    {fieldError('photo')}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Input
                            id="student-nisn"
                            label="NISN"
                            type="text"
                            value={data.nisn}
                            onChange={(event) => setField('nisn', event.target.value)}
                            placeholder="Contoh: 0012345678"
                            error={fieldError('nisn')}
                            maxLength={MAX_NISN_LENGTH}
                        />
                        <Select
                            id="student-gender"
                            label="Jenis Kelamin"
                            value={data.gender}
                            onChange={(event) => setField('gender', event.target.value)}
                            error={fieldError('gender')}
                        >
                            {(genders.length > 0
                                ? genders
                                : [
                                      { value: 'L', label: 'Laki-laki' },
                                      { value: 'P', label: 'Perempuan' },
                                  ]
                            ).map((gender) => (
                                <option key={gender.value} value={gender.value}>
                                    {gender.label}
                                </option>
                            ))}
                        </Select>
                    </div>

                    <Input
                        id="student-name"
                        label="Nama Lengkap"
                        type="text"
                        value={data.name}
                        onChange={(event) => setField('name', event.target.value)}
                        placeholder="Contoh: Budi Santoso"
                        error={fieldError('name')}
                        maxLength={MAX_NAME_LENGTH}
                        autoFocus
                    />

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Input
                            id="student-birth-place"
                            label="Tempat Lahir"
                            type="text"
                            value={data.birth_place}
                            onChange={(event) => setField('birth_place', event.target.value)}
                            placeholder="Contoh: Bandung"
                            error={fieldError('birth_place')}
                        />
                        <Input
                            id="student-birth-date"
                            label="Tanggal Lahir"
                            type="date"
                            value={data.birth_date}
                            onChange={(event) => setField('birth_date', event.target.value)}
                            error={fieldError('birth_date')}
                        />
                    </div>
                </section>

                <section className="space-y-4 border-t border-outline-variant/30 pt-5">
                    <h4 className="font-label text-[11px] font-semibold uppercase tracking-wider text-primary">
                        Data Orang Tua / Wali
                    </h4>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Input
                            id="student-father-name"
                            label="Nama Ayah"
                            type="text"
                            value={data.father_name}
                            onChange={(event) => setField('father_name', event.target.value)}
                            placeholder="Nama lengkap ayah"
                            error={fieldError('father_name')}
                            maxLength={255}
                        />
                        <Input
                            id="student-mother-name"
                            label="Nama Ibu"
                            type="text"
                            value={data.mother_name}
                            onChange={(event) => setField('mother_name', event.target.value)}
                            placeholder="Nama lengkap ibu"
                            error={fieldError('mother_name')}
                            maxLength={255}
                        />
                        <Input
                            id="student-father-occupation"
                            label="Pekerjaan Ayah"
                            type="text"
                            value={data.father_occupation}
                            onChange={(event) =>
                                setField('father_occupation', event.target.value)
                            }
                            placeholder="Contoh: Karyawan Swasta"
                            error={fieldError('father_occupation')}
                            maxLength={255}
                        />
                        <Input
                            id="student-mother-occupation"
                            label="Pekerjaan Ibu"
                            type="text"
                            value={data.mother_occupation}
                            onChange={(event) =>
                                setField('mother_occupation', event.target.value)
                            }
                            placeholder="Contoh: Ibu Rumah Tangga"
                            error={fieldError('mother_occupation')}
                            maxLength={255}
                        />
                        <Input
                            id="student-father-phone"
                            label="No. HP Ayah"
                            type="tel"
                            value={data.father_phone}
                            onChange={(event) => setField('father_phone', event.target.value)}
                            placeholder="Contoh: 0812xxxxxxx"
                            error={fieldError('father_phone')}
                            maxLength={30}
                        />
                        <Input
                            id="student-mother-phone"
                            label="No. HP Ibu"
                            type="tel"
                            value={data.mother_phone}
                            onChange={(event) => setField('mother_phone', event.target.value)}
                            placeholder="Contoh: 0812xxxxxxx"
                            error={fieldError('mother_phone')}
                            maxLength={30}
                        />
                    </div>

                    <Textarea
                        id="student-parent-address"
                        label="Alamat Orang Tua / Wali"
                        value={data.parent_address}
                        onChange={(event) => setField('parent_address', event.target.value)}
                        placeholder="Alamat lengkap tempat tinggal orang tua/wali"
                        error={fieldError('parent_address')}
                        rows={2}
                        maxLength={1000}
                    />

                    <Textarea
                        id="student-parent-bio"
                        label="Biografi / Catatan Orang Tua"
                        value={data.parent_bio}
                        onChange={(event) => setField('parent_bio', event.target.value)}
                        placeholder="Catatan tambahan tentang kondisi, pekerjaan, atau latar belakang orang tua/wali"
                        error={fieldError('parent_bio')}
                        rows={3}
                        maxLength={2000}
                    />
                </section>

                {!isEdit && (
                    <section className="space-y-4 border-t border-outline-variant/30 pt-5">
                        <h4 className="font-label text-[11px] font-semibold uppercase tracking-wider text-primary">
                            Registrasi Sekolah
                        </h4>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Select
                                id="student-school"
                                label="Sekolah"
                                value={data.school_id}
                                onChange={(event) => setField('school_id', event.target.value)}
                                error={fieldError('school_id')}
                                disabled={!isAdministrator && schools.length <= 1}
                                placeholder="Pilih sekolah..."
                            >
                                {schools.map((school) => (
                                    <option key={school.id} value={String(school.id)}>
                                        {school.is_active
                                            ? school.name
                                            : `${school.name} (Tidak Aktif)`}
                                    </option>
                                ))}
                            </Select>

                            <Input
                                id="student-nis"
                                label="NIS"
                                type="text"
                                value={data.nis}
                                onChange={(event) => setField('nis', event.target.value)}
                                placeholder="Nomor induk sekolah"
                                error={fieldError('nis')}
                                maxLength={MAX_NIS_LENGTH}
                            />

                            <Select
                                id="student-status"
                                label="Status Registrasi"
                                value={data.status}
                                onChange={(event) => handleStatusChange(event.target.value)}
                                error={fieldError('status')}
                            >
                                {(statuses.length > 0
                                    ? statuses
                                    : [
                                          { value: 'aktif', label: 'Aktif' },
                                          { value: 'lulus', label: 'Lulus' },
                                          { value: 'pindah', label: 'Pindah' },
                                          { value: 'keluar', label: 'Keluar' },
                                      ]
                                ).map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        {data.status !== 'aktif' && (
                            <Select
                                id="student-exit-year"
                                label="Tahun Keluar"
                                value={data.exit_academic_year_id}
                                onChange={(event) =>
                                    setField('exit_academic_year_id', event.target.value)
                                }
                                error={fieldError('exit_academic_year_id')}
                                placeholder="Pilih tahun pelajaran..."
                            >
                                {academicYears.map((academicYear) => (
                                    <option
                                        key={academicYear.id}
                                        value={String(academicYear.id)}
                                    >
                                        {academicYear.name} · {academicYear.semester_label}
                                        {academicYear.is_active ? ' (Aktif)' : ''}
                                    </option>
                                ))}
                            </Select>
                        )}

                        <div className="flex items-start gap-2.5 rounded-xl bg-primary/5 px-3 py-3">
                            <span className="material-symbols-outlined text-[18px] text-primary shrink-0">
                                calendar_month
                            </span>
                            <div className="min-w-0">
                                <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Tahun Masuk
                                </p>
                                <p className="text-xs font-body text-on-surface mt-0.5">
                                    {hasActiveAcademicYear
                                        ? `${activeAcademicYear.name} · ${activeAcademicYear.semester_label} (otomatis mengikuti tahun pelajaran aktif)`
                                        : 'Tahun pelajaran aktif belum diatur.'}
                                </p>
                            </div>
                        </div>

                        {fieldError('academic_year') && (
                            <p className="text-[11px] font-label text-error">
                                {fieldError('academic_year')}
                            </p>
                        )}

                        <div className="flex items-start gap-2.5 rounded-xl bg-surface-container-low px-3 py-3">
                            <span className="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0">
                                info
                            </span>
                            <p className="text-[11px] font-body text-on-surface-variant leading-relaxed">
                                Menyimpan siswa dengan status{' '}
                                <span className="font-semibold text-on-surface">Aktif</span> akan
                                otomatis mengubah registrasi aktif siswa lainnya menjadi{' '}
                                <span className="font-semibold text-on-surface">Pindah</span>.
                                Anggota kelas dikelola dari halaman Kelas.
                            </p>
                        </div>
                    </section>
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
                        {submitLabel}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
