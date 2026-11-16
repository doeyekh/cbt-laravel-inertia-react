import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Button from '@/Components/UI/Button';
import Checkbox from '@/Components/UI/Checkbox';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';

export default function ExamScheduleBulkModal({
    isOpen,
    onClose,
    schedules = [],
    sessions = [],
    rooms = [],
    onSubmit,
    submitting,
}) {
    const { data, setData, errors, processing, setError } = useForm({
        schedule_ids: [],
        session_id: '',
        room_id: '',
    });

    const [selectedIds, setSelectedIds] = useState([]);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        setData({ schedule_ids: [], session_id: '', room_id: '' });
        setSelectedIds([]);
    }, [isOpen]);

    const toggleSchedule = (id) => {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id],
        );
        setData('schedule_ids', setSelectedIds);
    };

    const selectAll = (checked) => {
        const next = checked ? schedules.map((item) => item.id) : [];
        setSelectedIds(next);
        setData('schedule_ids', next);
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (selectedIds.length === 0) {
            setError('schedule_ids', 'Pilih minimal satu jadwal ujian.');
            return;
        }

        if (!data.session_id && !data.room_id) {
            setError('session_id', 'Pilih setidaknya sesi atau ruangan yang akan ditetapkan.');
            return;
        }

        onSubmit({
            schedule_ids: selectedIds,
            session_id: data.session_id || null,
            room_id: data.room_id || null,
        });
    };

    const handleClose = () => {
        if (!processing && !submitting) {
            onClose();
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-2xl">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        group_add
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        Atur Sesi dan Ruang Secara Bersama
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        Pilih jadwal ujian, lalu tentukan sesi dan ruangan yang akan
                        diterapkan pada seluruh jadwal yang dipilih.
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="rounded-xl bg-surface-container-low/60 border border-outline-variant/30 p-3">
                    <div className="flex items-center gap-2 mb-3">
                        <Checkbox
                            id="bulk-select-all"
                            checked={selectedIds.length > 0 && selectedIds.length === schedules.length}
                            onChange={(event) => selectAll(event.target.checked)}
                        />
                        <label htmlFor="bulk-select-all" className="text-xs font-label text-on-surface">
                            Pilih semua ({selectedIds.length} dari {schedules.length})
                        </label>
                    </div>

                    <div className="max-h-48 overflow-y-auto border border-outline-variant rounded-lg p-2 space-y-1">
                        {schedules.length === 0 ? (
                            <p className="text-[11px] text-on-surface-variant font-body text-center">
                                Tidak ada jadwal ujian yang tersedia.
                            </p>
                        ) : (
                            schedules.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`bulk-schedule-${item.id}`}
                                        checked={selectedIds.includes(item.id)}
                                        onChange={() => toggleSchedule(item.id)}
                                    />
                                    <label
                                        htmlFor={`bulk-schedule-${item.id}`}
                                        className="text-[11px] font-body text-on-surface leading-tight"
                                    >
                                        <span className="font-medium">
                                            {item.subject_name} — {item.class_name}
                                        </span>
                                        <br />
                                        <span className="text-[10px] text-on-surface-variant font-mono">
                                            {item.exam_date} • Sesi: {item.session_name ?? '-'} • Ruangan: {item.room_name ?? '-'}
                                        </span>
                                    </label>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        id="bulk-session"
                        label="Sesi Ujian"
                        value={data.session_id}
                        onChange={(event) => setData('session_id', event.target.value)}
                        error={errors.session_id}
                        placeholder="Pilih sesi..."
                    >
                        {sessions.map((session) => (
                            <option key={session.id} value={String(session.id)}>
                                {session.code} — {session.name}
                            </option>
                        ))}
                    </Select>

                    <Select
                        id="bulk-room"
                        label="Ruangan Ujian"
                        value={data.room_id}
                        onChange={(event) => setData('room_id', event.target.value)}
                        error={errors.room_id}
                        placeholder="Pilih ruangan..."
                    >
                        {rooms.map((room) => (
                            <option key={room.id} value={String(room.id)}>
                                {room.code} — {room.name}
                            </option>
                        ))}
                    </Select>
                </div>

                <p className="text-[11px] text-on-surface-variant font-body">
                    Sesi dan ruangan yang dipilih akan ditetapkan pada seluruh
                    jadwal yang terpilih di atas.
                </p>

                <div className="flex items-center gap-3 justify-end pt-4">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={handleClose}
                        disabled={processing || submitting}
                        className="flex-1"
                    >
                        Batal
                    </Button>
                    <Button type="submit" loading={processing || submitting}>
                        Terapkan
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
