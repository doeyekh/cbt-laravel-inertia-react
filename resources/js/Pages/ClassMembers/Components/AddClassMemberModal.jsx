import React, { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';
import StudentAvatar from '@/Pages/Students/Components/StudentAvatar';

export default function AddClassMemberModal({ isOpen, onClose, schoolClassId }) {
    const { data, setData, post, processing, errors, clearErrors, reset } = useForm({
        student_id: '',
    });
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [localError, setLocalError] = useState('');
    const [searched, setSearched] = useState(false);

    const abortRef = useRef(null);
    const skipInitialSearch = useRef(true);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        reset();
        setQuery('');
        setResults([]);
        setLocalError('');
        setSearched(false);
        skipInitialSearch.current = true;
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

        if (trimmed === '') {
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
                    route('admin.class-members.available', {
                        schoolClass: schoolClassId,
                    }) +
                        `?search=${encodeURIComponent(trimmed)}`,
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
    }, [query, isOpen, schoolClassId]);

    useEffect(() => {
        return () => abortRef.current?.abort();
    }, []);

    const handleSelect = (student) => {
        setData('student_id', String(student.id));
        setLocalError('');
        clearErrors('student_id');
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (!data.student_id) {
            setLocalError('Pilih siswa terlebih dahulu.');

            return;
        }

        post(route('admin.class-members.store', { schoolClass: schoolClassId }), {
            preserveScroll: true,
            onSuccess: () => {
                setQuery('');
                setResults([]);
                setSearched(false);
                onClose();
            },
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
                        person_add
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Tambah Anggota Kelas
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Cari siswa berdasarkan nama, NISN, atau NIS. Hanya siswa dengan registrasi
                        aktif di sekolah ini yang dapat ditambahkan.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <Input
                    id="class-member-search"
                    label="Cari Siswa"
                    type="search"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Ketik nama, NISN, atau NIS..."
                    autoFocus
                />

                <div className="max-h-72 overflow-y-auto rounded-xl bg-surface-container-low p-2 space-y-1">
                    {loading && (
                        <p className="px-3 py-6 text-center text-xs text-on-surface-variant font-body">
                            Mencari siswa...
                        </p>
                    )}

                    {!loading && results.length === 0 && (
                        <p className="px-3 py-6 text-center text-xs text-on-surface-variant font-body">
                            {searched
                                ? 'Tidak ada siswa yang dapat ditambahkan.'
                                : 'Ketik minimal satu karakter untuk mencari siswa.'}
                        </p>
                    )}

                    {!loading &&
                        results.map((student) => {
                            const selected = data.student_id === String(student.id);

                            return (
                                <button
                                    key={student.id}
                                    type="button"
                                    onClick={() => handleSelect(student)}
                                    className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-colors ${
                                        selected
                                            ? 'bg-primary/10 ring-1 ring-primary/30'
                                            : 'hover:bg-surface-container'
                                    }`}
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
                                        <span className="block font-mono text-[11px] text-on-surface-variant">
                                            {student.nis ?? '—'} · {student.nisn ?? 'Tanpa NISN'} ·{' '}
                                            {student.gender_label}
                                        </span>
                                    </span>
                                    {selected && (
                                        <span className="material-symbols-outlined text-primary text-[18px]">
                                            check_circle
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                </div>

                {(localError || errors.student_id) && (
                    <p className="text-[11px] font-label text-error">
                        {localError || errors.student_id}
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
                        Tambahkan
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
