import React, { useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const sanitize = (value) =>
    String(value ?? '')
        .toUpperCase()
        .replace(/[^A-Z0-9-]+/g, '')
        .replace(/^-+|-+$/g, '');

const renderTemplate = (template, sequence, width, replacements) => {
    const fallback = String(sequence).padStart(width, '0');

    const rendered = String(template ?? '').replace(
        /\{([a-z]+)\}/g,
        (match, key) => {
            const value = replacements[key] ?? '';

            return value !== '' ? value : fallback;
        }
    );

    return sanitize(rendered) || fallback;
};

export default function CredentialTemplateModal({
    isOpen,
    onClose,
    schools = [],
    participants = [],
    academicYear = null,
    defaultSchoolId = '',
}) {
    const { data, setData, post, processing, errors, clearErrors, setError } =
        useForm({
            school_id: '',
            academic_year_id: '',
            overwrite: false,
        });

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();

        setData({
            school_id: defaultSchoolId ? String(defaultSchoolId) : '',
            academic_year_id: academicYear?.id
                ? String(academicYear.id)
                : '',
            overwrite: false,
        });
    }, [isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    const school = useMemo(
        () =>
            schools.find(
                (item) => String(item.id) === String(data.school_id)
            ) ?? null,
        [schools, data.school_id]
    );

    const preview = useMemo(() => {
        const width = Math.max(1, Math.min(6, Number(school?.number_width ?? 3)));

        return participants
            .filter(
                (participant) =>
                    String(participant.school_id) === String(data.school_id) &&
                    String(participant.academic_year_id) ===
                        String(data.academic_year_id)
            )
            .slice(0, 3)
            .map((participant, index) => {
                const sequence = index + 1;
                const replacements = {
                    no: String(sequence).padStart(width, '0'),
                    nis: participant.student_nis ?? '',
                    nisn: participant.student_nisn ?? '',
                    kelas: participant.class_name ?? '',
                    ruang: participant.room_code ?? '',
                    sesi: participant.session_code ?? '',
                    tahun: participant.academic_year_code ?? '',
                    tanggal: '',
                };

                return {
                    id: participant.id,
                    name: participant.student_name ?? '-',
                    username: renderTemplate(
                        school?.username_template,
                        sequence,
                        width,
                        replacements
                    ),
                    participant_number: renderTemplate(
                        school?.participant_number_template,
                        sequence,
                        width,
                        replacements
                    ),
                };
            });
    }, [
        participants,
        data.school_id,
        data.academic_year_id,
        school?.username_template,
        school?.participant_number_template,
        school?.number_width,
    ]);

    const handleSubmit = (event) => {
        event.preventDefault();

        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!data.academic_year_id) {
            nextErrors.academic_year_id = 'Tahun pelajaran wajib dipilih.';
        }

        if (Object.keys(nextErrors).length > 0) {
            Object.entries(nextErrors).forEach(([field, message]) =>
                setError(field, message)
            );

            return;
        }

        post(route('admin.exam-participants.generate'), {
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
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-2xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        key
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Generate Kredensial
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Username, nomor peserta, dan password dibangkitkan untuk
                        seluruh peserta sekolah pada tahun pelajaran aktif.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        id="credential-school"
                        label="Sekolah"
                        value={data.school_id}
                        onChange={(event) =>
                            setData('school_id', event.target.value)
                        }
                        error={errors.school_id}
                    >
                        <option value="" disabled>
                            Pilih sekolah
                        </option>
                        {schools.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </Select>

                    <div className="self-end">
                        <div className="rounded-xl bg-surface-container-low px-4 py-2.5">
                            <p className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                Tahun Pelajaran Aktif
                            </p>
                            <p className="text-sm font-body font-semibold text-on-surface mt-0.5">
                                {academicYear?.name ?? 'Belum ada tahun aktif'}
                            </p>
                        </div>

                        {errors.academic_year_id && (
                            <p className="text-[11px] font-body text-error mt-1.5">
                                {errors.academic_year_id}
                            </p>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="rounded-xl bg-surface-container-low px-4 py-3">
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Template Username
                        </p>
                        <p className="font-mono text-sm text-on-surface mt-1 break-all">
                            {school?.username_template ?? '-'}
                        </p>
                    </div>

                    <div className="rounded-xl bg-surface-container-low px-4 py-3">
                        <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                            Template Nomor Peserta
                        </p>
                        <p className="font-mono text-sm text-on-surface mt-1 break-all">
                            {school?.participant_number_template ?? '-'}
                        </p>
                    </div>
                </div>

                <label className="flex items-start gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        className="h-4 w-4 mt-0.5 rounded border-outline text-primary focus:ring-primary/30"
                        checked={Boolean(data.overwrite)}
                        onChange={(event) =>
                            setData('overwrite', event.target.checked)
                        }
                    />
                    <span className="text-xs font-body text-on-surface">
                        Timpa kredensial yang sudah ada
                        <span className="block text-[10px] text-on-surface-variant">
                            Username, nomor peserta, dan password peserta yang
                            sudah lengkap akan dibuat ulang.
                        </span>
                    </span>
                </label>

                <div className="rounded-xl bg-surface-container-low px-4 py-3">
                    <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                        Contoh Hasil
                    </p>

                    {preview.length === 0 ? (
                        <p className="text-xs text-on-surface-variant font-body mt-2">
                            Peserta pada sekolah ini belum dimuat di halaman.
                            Contoh mengikuti nomor urut pertama.
                        </p>
                    ) : (
                        <div className="mt-2 space-y-1.5">
                            {preview.map((row) => (
                                <div
                                    key={row.id}
                                    className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-body"
                                >
                                    <span className="font-semibold text-on-surface min-w-[140px]">
                                        {row.name}
                                    </span>
                                    <span className="font-mono text-on-surface-variant">
                                        {row.participant_number}
                                    </span>
                                    <span className="font-mono text-primary font-semibold">
                                        {row.username}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
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
                        Generate
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
