import React, { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import { route } from '@/Utils/route';

const typeShortLabels = {
    pilihan_ganda: 'PG',
    pilihan_ganda_kompleks: 'PK',
    menjodohkan: 'MJ',
    isian_singkat: 'IS',
    esai: 'ES',
};

const columnDescriptions = [
    { name: 'tipe', required: true, hint: 'Jenis soal: pilihan_ganda, pilihan_ganda_kompleks, menjodohkan, isian_singkat, atau esai. Bisa juga PG/PK/MJ/IS/ES.' },
    { name: 'nomor', required: true, hint: 'Nomor soal pada tipe tersebut, maksimal sesuai komposisi bank soal.' },
    { name: 'soal', required: true, hint: 'Teks soal. Teks polos otomatis dibungkus paragraf, HTML tetap dipakai apa adanya.' },
    { name: 'opsi_a … opsi_e', required: false, hint: 'Teks pilihan jawaban untuk PG dan PG Kompleks. Jumlah kolom yang terisi harus sesuai pengaturan bank soal.' },
    { name: 'kunci', required: false, hint: 'Huruf kunci jawaban, pisahkan dengan koma untuk PG Kompleks, misalnya A,C.' },
    { name: 'pasangan', required: false, hint: 'Pasangan untuk Menjodohkan, format "kiri=kanan;kiri=kanan".' },
    { name: 'isian', required: false, hint: 'Jawaban yang diterima untuk Isian, pisahkan dengan titik koma.' },
];

export default function QuestionsImport({ questionBank, types = [] }) {
    const { flash, auth } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const canManage = Boolean(
        auth?.user?.is_administrator || auth?.user?.is_school_admin
    );

    const { data, setData, post, processing, errors, clearErrors } = useForm({
        file: null,
    });

    const [localError, setLocalError] = useState('');

    const handledToast = useRef(null);

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    useEffect(() => {
        setData('file', null);
        setLocalError('');
        clearErrors('file');
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    const handleFileChange = (event) => {
        setData('file', event.target.files?.[0] ?? null);
        setLocalError('');

        if (errors.file) {
            clearErrors('file');
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (!data.file) {
            setLocalError('Berkas import wajib dipilih.');

            return;
        }

        post(route('admin.question-banks.questions.import.store', questionBank.id), {
            forceFormData: true,
            preserveScroll: true,
            onError: () => setLocalError(''),
        });
    };

    const handleBack = () => {
        router.get(route('admin.question-banks.questions.index', questionBank.id));
    };

    const handleTemplate = () => {
        window.open(
            route('admin.question-banks.questions.import.template', questionBank.id),
            '_blank',
            'noopener'
        );
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1200px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Pengelolaan Ujian</span>
                            <span>/</span>
                            <span>Bank Soal</span>
                            <span>/</span>
                            <span>Import Soal</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Import Soal
                        </h1>
                        <div className="flex flex-wrap items-center gap-2 text-xs text-on-surface-variant font-body">
                            <span className="inline-flex rounded-lg bg-surface-container-low px-2 py-0.5 font-mono text-[11px] font-semibold">
                                {questionBank?.code}
                            </span>
                            <span>{questionBank?.name}</span>
                            <span>/</span>
                            <span>{questionBank?.subject_name}</span>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button variant="secondary" onClick={handleBack}>
                            <span className="material-symbols-outlined text-[18px]">
                                arrow_back
                            </span>
                            Kembali
                        </Button>
                        <Button variant="outline" onClick={handleTemplate}>
                            <span className="material-symbols-outlined text-[18px]">
                                download
                            </span>
                            Unduh Template
                        </Button>
                    </div>
                </div>

                <div className="flex items-start gap-3 rounded-2xl bg-primary/5 border border-primary/20 px-4 py-3">
                    <span className="material-symbols-outlined text-primary text-[20px] shrink-0">
                        info
                    </span>
                    <div>
                        <p className="font-label font-semibold text-sm text-on-surface">
                            Impor soal dari berkas Excel
                        </p>
                        <p className="text-xs text-on-surface-variant font-body mt-0.5">
                            Gunakan template yang tersedia agar kolom sesuai.
                            Baris yang tidak valid akan dilewati dan tidak
                            menghambat soal lain yang berhasil diimpor.
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <Card padding="p-5" className="lg:col-span-2">
                        <h3 className="font-headline font-semibold text-on-surface text-lg mb-3">
                            Format Berkas
                        </h3>

                        <div className="rounded-xl border border-outline-variant/40 overflow-hidden">
                            <div className="grid grid-cols-[9rem_minmax(0,1fr)] gap-3 px-3 py-2 bg-surface-container-low border-b border-outline-variant/30">
                                <span className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Kolom
                                </span>
                                <span className="text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                    Keterangan
                                </span>
                            </div>

                            {columnDescriptions.map((column) => (
                                <div
                                    key={column.name}
                                    className="grid grid-cols-[9rem_minmax(0,1fr)] gap-3 px-3 py-2 border-b border-outline-variant/20 last:border-b-0"
                                >
                                    <span className="font-mono text-[11px] text-on-surface">
                                        {column.name}
                                        {column.required && (
                                            <span className="text-error"> *</span>
                                        )}
                                    </span>
                                    <span className="text-xs text-on-surface-variant font-body">
                                        {column.hint}
                                    </span>
                                </div>
                            ))}
                        </div>

                        <div className="mt-4 rounded-xl bg-surface-container-low p-4 space-y-2">
                            <p className="text-[11px] font-label font-semibold uppercase tracking-wider text-on-surface-variant">
                                Aturan Pemeriksaan
                            </p>
                            <ul className="space-y-1 text-xs text-on-surface-variant font-body">
                                <li>
                                    Nomor soal harus unik per tipe pada bank soal
                                    ini; nomor yang sudah dipakai akan dilewati.
                                </li>
                                <li>
                                    Jumlah pilihan jawaban harus sama dengan
                                    pengaturan bank soal (
                                    {questionBank?.option_counts?.pilihan_ganda ?? 4}{' '}
                                    untuk PG dan{' '}
                                    {
                                        questionBank?.option_counts
                                            ?.pilihan_ganda_kompleks ?? 4
                                    }{' '}
                                    untuk PG Kompleks).
                                </li>
                                <li>
                                    Bobot soal mengikuti pengaturan bank soal
                                    ({questionBank?.scores?.pilihan_ganda ?? 50}{' '}
                                    poin untuk PG,{' '}
                                    {questionBank?.scores?.pilihan_ganda_kompleks ?? 30}{' '}
                                    poin untuk PG Kompleks, dan seterusnya).
                                </li>
                                <li>
                                    Hanya tipe soal yang termasuk dalam komposisi
                                    bank soal yang dapat diimpor.
                                </li>
                            </ul>
                        </div>
                    </Card>

                    <div className="space-y-5">
                        <Card padding="p-5">
                            <h3 className="font-headline font-semibold text-on-surface text-lg mb-3">
                                Komposisi Bank Soal
                            </h3>

                            <div className="space-y-2">
                                {types.map((type) => {
                                    const amount =
                                        questionBank?.composition?.[type.value] ??
                                        0;

                                    return (
                                        <div
                                            key={type.value}
                                            className="flex items-center justify-between gap-3 rounded-lg bg-surface-container-low px-3 py-2"
                                        >
                                            <span className="flex items-center gap-2 min-w-0">
                                                <span className="inline-flex h-6 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-[10px] font-label font-bold text-primary">
                                                    {
                                                        typeShortLabels[
                                                            type.value
                                                        ] ?? ''
                                                    }
                                                </span>
                                                <span className="text-sm font-body text-on-surface truncate">
                                                    {type.label}
                                                </span>
                                            </span>
                                            <span className="font-mono text-sm font-semibold text-on-surface">
                                                {amount}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </Card>

                        <Card padding="p-5">
                            <h3 className="font-headline font-semibold text-on-surface text-lg mb-3">
                                Unggah Berkas
                            </h3>

                            <form onSubmit={handleSubmit} className="space-y-4">
                                <Input
                                    id="import-questions-file"
                                    label="Berkas Import"
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={handleFileChange}
                                    error={localError || errors.file}
                                    disabled={processing || !canManage}
                                />
                                <p className="text-[10px] font-body text-on-surface-variant">
                                    Format XLSX, XLS, atau CSV. Ukuran maksimal 5
                                    MB.
                                </p>

                                <Button
                                    type="submit"
                                    loading={processing}
                                    disabled={!canManage}
                                    className="w-full"
                                >
                                    <span className="material-symbols-outlined text-[18px]">
                                        upload_file
                                    </span>
                                    Import Soal
                                </Button>
                            </form>

                            {!canManage && (
                                <p className="mt-3 text-[10px] font-body text-on-surface-variant">
                                    Anda hanya dapat melihat halaman ini.
                                </p>
                            )}
                        </Card>
                    </div>
                </div>
            </div>

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </AppLayout>
    );
}
