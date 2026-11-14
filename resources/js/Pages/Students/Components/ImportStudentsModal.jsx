import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

export default function ImportStudentsModal({
    isOpen,
    onClose,
    schools = [],
    academicYears = [],
    isAdministrator = false,
}) {
    const { data, setData, post, processing, errors, clearErrors, reset } = useForm({
        file: null,
        school_id: '',
    });
    const [localError, setLocalError] = useState('');

    const activeAcademicYear =
        academicYears.find((academicYear) => academicYear.is_active) ?? null;

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        reset();
        setLocalError('');
        setData({
            file: null,
            school_id: schools[0]?.id ? String(schools[0].id) : '',
        });
    }, [isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    const handleFileChange = (event) => {
        const file = event.target.files?.[0] ?? null;

        setData('file', file);
        setLocalError('');
        clearErrors('file');
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (!data.file) {
            setLocalError('Berkas import wajib dipilih.');

            return;
        }

        if (!data.school_id) {
            setLocalError('Sekolah wajib dipilih.');

            return;
        }

        if (!activeAcademicYear) {
            setLocalError(
                'Tahun pelajaran aktif belum diatur. Aktifkan tahun pelajaran terlebih dahulu.'
            );

            return;
        }

        post(route('admin.students.import'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
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
                        upload_file
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Import Siswa
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Unggah berkas berisi identitas siswa dan registrasinya.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="rounded-xl bg-surface-container-low p-4 space-y-3">
                    <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                        Format Berkas
                    </p>
                    <div className="space-y-1.5 text-xs text-on-surface-variant font-body">
                        <p>
                            <span className="font-semibold text-on-surface">Wajib:</span>{' '}
                            <code className="font-mono text-[11px]">nis</code>,{' '}
                            <code className="font-mono text-[11px]">nama</code>,{' '}
                            <code className="font-mono text-[11px]">jenis_kelamin</code>{' '}
                            (L/P)
                        </p>
                        <p>
                            <span className="font-semibold text-on-surface">Opsional:</span>{' '}
                            <code className="font-mono text-[11px]">nisn</code>,{' '}
                            <code className="font-mono text-[11px]">tempat_lahir</code>,{' '}
                            <code className="font-mono text-[11px]">tanggal_lahir</code>
                        </p>
                        <p>
                            <span className="font-semibold text-on-surface">
                                Data orang tua (opsional):
                            </span>{' '}
                            <code className="font-mono text-[11px]">nama_ayah</code>,{' '}
                            <code className="font-mono text-[11px]">pekerjaan_ayah</code>,{' '}
                            <code className="font-mono text-[11px]">no_hp_ayah</code>,{' '}
                            <code className="font-mono text-[11px]">nama_ibu</code>,{' '}
                            <code className="font-mono text-[11px]">pekerjaan_ibu</code>,{' '}
                            <code className="font-mono text-[11px]">no_hp_ibu</code>,{' '}
                            <code className="font-mono text-[11px]">alamat_orang_tua</code>,{' '}
                            <code className="font-mono text-[11px]">biografi_orang_tua</code>
                        </p>
                        <p>
                            Semua siswa akan didaftarkan dengan status{' '}
                            <span className="font-semibold">Aktif</span> pada sekolah terpilih
                            dan otomatis memakai tahun pelajaran aktif. Baris duplikat atau tidak
                            valid dilewati.
                        </p>
                    </div>
                </div>

                <Select
                    id="import-school"
                    label="Sekolah"
                    value={data.school_id}
                    onChange={(event) => setData('school_id', event.target.value)}
                    error={errors.school_id}
                    disabled={!isAdministrator && schools.length <= 1}
                    placeholder="Pilih sekolah..."
                >
                    {schools.map((school) => (
                        <option key={school.id} value={String(school.id)}>
                            {school.name}
                        </option>
                    ))}
                </Select>

                <div className="flex items-start gap-2.5 rounded-xl bg-primary/5 px-3 py-3">
                    <span className="material-symbols-outlined text-[18px] text-primary shrink-0">
                        calendar_month
                    </span>
                    <div className="min-w-0">
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Tahun Masuk
                        </p>
                        <p className="text-xs font-body text-on-surface mt-0.5">
                            {activeAcademicYear
                                ? `${activeAcademicYear.name} · ${activeAcademicYear.semester_label} (otomatis mengikuti tahun pelajaran aktif)`
                                : 'Tahun pelajaran aktif belum diatur.'}
                        </p>
                    </div>
                </div>

                <div>
                    <Input
                        id="import-students-file"
                        label="Berkas Import"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        onChange={handleFileChange}
                        error={localError || errors.file}
                        disabled={processing}
                    />
                    <p className="mt-1.5 text-[10px] text-on-surface-variant font-body">
                        Format XLSX, XLS, atau CSV. Ukuran maksimal 5 MB.
                    </p>
                </div>

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
                        Import
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
