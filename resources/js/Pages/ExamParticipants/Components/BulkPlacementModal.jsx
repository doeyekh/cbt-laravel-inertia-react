import React, { useEffect, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    school_id: '',
    academic_year_id: '',
    participant_ids: [],
    room_id: '',
    session_id: '',
};

export default function BulkPlacementModal({
    isOpen,
    onClose,
    schoolId = '',
    academicYearId = '',
    participantIds = [],
    sessions = [],
    rooms = [],
}) {
    const { data, setData, patch, processing, errors, clearErrors, setError } =
        useForm(emptyForm);

    const [count, setCount] = useState(0);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setCount(participantIds.length);
        setData({
            ...emptyForm,
            school_id: schoolId ? String(schoolId) : '',
            academic_year_id: academicYearId ? String(academicYearId) : '',
            participant_ids: participantIds,
        });
    }, [isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    const filteredSessions = useMemo(
        () =>
            sessions.filter(
                (item) =>
                    !data.school_id ||
                    String(item.school_id) === String(data.school_id)
            ),
        [sessions, data.school_id]
    );

    const filteredRooms = useMemo(
        () =>
            rooms.filter(
                (item) =>
                    !data.school_id ||
                    String(item.school_id) === String(data.school_id)
            ),
        [rooms, data.school_id]
    );

    const setField = (field, value) => {
        setData(field, value);

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const nextErrors = {};

        if (!data.participant_ids.length) {
            nextErrors.participant_ids = 'Pilih minimal satu peserta.';
        }

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah peserta tidak ditemukan.';
        }

        if (!data.room_id) {
            nextErrors.room_id = 'Ruangan wajib dipilih.';
        }

        if (!data.session_id) {
            nextErrors.session_id = 'Sesi wajib dipilih.';
        }

        if (Object.keys(nextErrors).length > 0) {
            Object.entries(nextErrors).forEach(([field, message]) =>
                setError(field, message)
            );

            return;
        }

        patch(
            route('admin.exam-participants.bulk-placement'),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setData(emptyForm);
                    onClose();
                },
            }
        );
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        swap_horiz
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Atur Ruangan dan Sesi
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Berlaku untuk{' '}
                        <span className="font-semibold text-on-surface">
                            {count} peserta
                        </span>{' '}
                        yang dipilih.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <Select
                    id="bulk-placement-room"
                    label="Ruangan"
                    value={data.room_id}
                    onChange={(event) => setField('room_id', event.target.value)}
                    error={errors.room_id}
                >
                    <option value="" disabled>
                        Pilih ruangan
                    </option>
                    {filteredRooms.map((room) => (
                        <option key={room.id} value={room.id}>
                            {room.name} ({room.code})
                            {room.is_active ? '' : ' - tidak aktif'}
                        </option>
                    ))}
                </Select>

                <Select
                    id="bulk-placement-session"
                    label="Sesi"
                    value={data.session_id}
                    onChange={(event) =>
                        setField('session_id', event.target.value)
                    }
                    error={errors.session_id}
                >
                    <option value="" disabled>
                        Pilih sesi
                    </option>
                    {filteredSessions.map((session) => (
                        <option key={session.id} value={session.id}>
                            {session.name} ({session.code})
                            {session.is_active ? '' : ' - tidak aktif'}
                        </option>
                    ))}
                </Select>

                {errors.participant_ids && (
                    <div className="rounded-xl bg-error-container/30 px-3 py-2">
                        <p className="text-[11px] font-body text-error">
                            {errors.participant_ids}
                        </p>
                    </div>
                )}

                <p className="text-[10px] font-body text-on-surface-variant">
                    Ruangan dan sesi harus berasal dari sekolah yang sama dengan
                    peserta terpilih.
                </p>

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
                        Simpan Penempatan
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
