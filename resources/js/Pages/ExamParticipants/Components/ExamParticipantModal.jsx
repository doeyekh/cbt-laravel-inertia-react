import React, { useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    room_id: '',
    session_id: '',
    username: '',
    participant_number: '',
};

export default function ExamParticipantModal({
    isOpen,
    onClose,
    initialData = null,
    sessions = [],
    rooms = [],
}) {
    const { data, setData, put, processing, errors, clearErrors, setError } =
        useForm(emptyForm);

    const initialDataId = initialData?.id ?? null;

    useEffect(() => {
        if (!isOpen || !initialData) {
            return;
        }

        clearErrors();

        setData({
            room_id: initialData.room_id ? String(initialData.room_id) : '',
            session_id: initialData.session_id
                ? String(initialData.session_id)
                : '',
            username: (initialData.username ?? '').trim(),
            participant_number: (initialData.participant_number ?? '').trim(),
        });
    }, [isOpen, initialDataId]); // eslint-disable-line react-hooks/exhaustive-deps

    const filteredSessions = useMemo(
        () =>
            sessions.filter(
                (item) =>
                    !initialData?.school_id ||
                    String(item.school_id) === String(initialData.school_id)
            ),
        [sessions, initialData?.school_id]
    );

    const filteredRooms = useMemo(
        () =>
            rooms.filter(
                (item) =>
                    !initialData?.school_id ||
                    String(item.school_id) === String(initialData.school_id)
            ),
        [rooms, initialData?.school_id]
    );

    const setField = (field, value) => {
        setData(field, value);

        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (!initialData) {
            return;
        }

        put(route('admin.exam-participants.update', initialData.id), {
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
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        badge
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Edit Peserta
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        {initialData?.student_name ?? 'Peserta'} ·{' '}
                        {initialData?.class_name ?? 'Tanpa kelas'}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        id="participant-room"
                        label="Ruangan"
                        value={data.room_id}
                        onChange={(event) =>
                            setField('room_id', event.target.value)
                        }
                        error={errors.room_id}
                    >
                        <option value="">Tanpa ruangan</option>
                        {filteredRooms.map((room) => (
                            <option key={room.id} value={room.id}>
                                {room.name} ({room.code})
                            </option>
                        ))}
                    </Select>

                    <Select
                        id="participant-session"
                        label="Sesi"
                        value={data.session_id}
                        onChange={(event) =>
                            setField('session_id', event.target.value)
                        }
                        error={errors.session_id}
                    >
                        <option value="">Tanpa sesi</option>
                        {filteredSessions.map((session) => (
                            <option key={session.id} value={session.id}>
                                {session.name} ({session.code})
                            </option>
                        ))}
                    </Select>
                </div>

                <Input
                    id="participant-username"
                    label="Username"
                    type="text"
                    value={data.username}
                    onChange={(event) =>
                        setField('username', event.target.value)
                    }
                    placeholder="Kosongkan untuk dibangkitkan ulang"
                    error={errors.username}
                    maxLength={50}
                />

                <Input
                    id="participant-number"
                    label="Nomor Peserta"
                    type="text"
                    value={data.participant_number}
                    onChange={(event) =>
                        setField('participant_number', event.target.value)
                    }
                    placeholder="Kosongkan untuk dibangkitkan ulang"
                    error={errors.participant_number}
                    maxLength={30}
                />

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
                        Perbarui
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
