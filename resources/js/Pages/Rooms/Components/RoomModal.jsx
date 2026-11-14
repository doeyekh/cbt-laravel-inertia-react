import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    school_id: '',
    code: '',
    name: '',
    building: '',
    floor: '',
    capacity: '',
};

const MAX_CODE_LENGTH = 20;
const MAX_NAME_LENGTH = 100;
const MAX_BUILDING_LENGTH = 100;
const MAX_FLOOR_LENGTH = 20;
const CODE_PATTERN = /^[A-Z0-9-]+$/;

export default function RoomModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    schools = [],
}) {
    const { data, setData, post, put, processing, errors, clearErrors, setError } =
        useForm(emptyForm);
    const initialDataId = initialData?.id ?? null;
    const isEdit = mode === 'edit' && Boolean(initialData);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        clearErrors();
        setData(
            initialData
                ? {
                      school_id: initialData.school_id
                          ? String(initialData.school_id)
                          : '',
                      code: initialData.code ?? '',
                      name: initialData.name,
                      building: initialData.building,
                      floor: initialData.floor,
                      capacity: initialData.capacity?.toString() ?? '',
                  }
                : {
                      ...emptyForm,
                      school_id: schools[0]?.id ? String(schools[0].id) : '',
                  }
        );
    }, [isOpen, initialDataId]);

    const setField = (field, value) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const code = data.code.trim().toUpperCase();
        const name = data.name.trim();
        const building = data.building.trim();
        const floor = data.floor.trim();
        const capacity = parseInt(data.capacity, 10);

        const nextErrors = {};

        if (!data.school_id) {
            nextErrors.school_id = 'Sekolah wajib dipilih.';
        }

        if (!code) {
            nextErrors.code = 'Kode wajib diisi.';
        } else if (code.length > MAX_CODE_LENGTH) {
            nextErrors.code = `Kode maksimal ${MAX_CODE_LENGTH} karakter.`;
        } else if (!CODE_PATTERN.test(code)) {
            nextErrors.code =
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.';
        }

        if (!name) {
            nextErrors.name = 'Nama ruangan wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama ruangan maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (!building) {
            nextErrors.building = 'Gedung wajib diisi.';
        } else if (building.length > MAX_BUILDING_LENGTH) {
            nextErrors.building = `Gedung maksimal ${MAX_BUILDING_LENGTH} karakter.`;
        }

        if (!floor) {
            nextErrors.floor = 'Lantai wajib diisi.';
        } else if (floor.length > MAX_FLOOR_LENGTH) {
            nextErrors.floor = `Lantai maksimal ${MAX_FLOOR_LENGTH} karakter.`;
        }

        if (!data.capacity) {
            nextErrors.capacity = 'Kapasitas wajib diisi.';
        } else if (!Number.isInteger(capacity) || capacity < 1) {
            nextErrors.capacity = 'Kapasitas minimal 1.';
        }

        if (Object.keys(nextErrors).length > 0) {
            Object.entries(nextErrors).forEach(([field, message]) => {
                setError(field, message);
            });

            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.rooms.update', initialData.id), options);

            return;
        }

        post(route('admin.rooms.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Edit Ruangan' : 'Tambah Ruangan';
    const subtitle = isEdit
        ? 'Perbarui kode, nama, gedung, lantai, dan kapasitas ruangan.'
        : 'Tambahkan ruangan ujian baru.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
            <div className="flex items-start gap-3 mb-6">
                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <span className="material-symbols-outlined text-primary text-[22px]">
                        {isEdit ? 'edit_note' : 'meeting_room'}
                    </span>
                </div>
                <div className="min-w-0">
                    <h3 className="font-headline font-semibold text-on-surface text-lg leading-tight">
                        {title}
                    </h3>
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        {subtitle}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Select
                        id="room-school"
                        label="Sekolah"
                        value={data.school_id}
                        onChange={(event) =>
                            setField('school_id', event.target.value)
                        }
                        error={errors.school_id}
                        disabled={isEdit || schools.length === 0}
                    >
                        <option value="" disabled>
                            Pilih sekolah
                        </option>
                        {schools.map((school) => (
                            <option key={school.id} value={school.id}>
                                {school.name} ({school.code})
                            </option>
                        ))}
                    </Select>
                    <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                        {isEdit
                            ? 'Sekolah tidak dapat diubah setelah data dibuat.'
                            : 'Ruangan ujian hanya berlaku untuk sekolah yang dipilih.'}
                    </p>
                </div>

                <div>
                    <Input
                        id="room-code"
                        label="Kode"
                        type="text"
                        value={data.code}
                        onChange={(event) =>
                            setField('code', event.target.value.toUpperCase())
                        }
                        placeholder="Contoh: RM01"
                        error={errors.code}
                        maxLength={MAX_CODE_LENGTH}
                        autoFocus
                    />
                    <p className="mt-1.5 text-[10px] font-body text-on-surface-variant">
                        Gunakan huruf kapital, angka, atau tanda hubung. Contoh: RM01, UJI-A.
                    </p>
                </div>

                <div>
                    <Input
                        id="room-name"
                        label="Nama Ruangan"
                        type="text"
                        value={data.name}
                        onChange={(event) => setField('name', event.target.value)}
                        placeholder="Contoh: Ruangan Ujian 1"
                        error={errors.name}
                        maxLength={MAX_NAME_LENGTH}
                    />
                </div>

                <div>
                    <Input
                        id="room-building"
                        label="Gedung"
                        type="text"
                        value={data.building}
                        onChange={(event) =>
                            setField('building', event.target.value)
                        }
                        placeholder="Contoh: Gedung A"
                        error={errors.building}
                        maxLength={MAX_BUILDING_LENGTH}
                    />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Input
                            id="room-floor"
                            label="Lantai"
                            type="text"
                            value={data.floor}
                            onChange={(event) => setField('floor', event.target.value)}
                            placeholder="Contoh: 1"
                            error={errors.floor}
                            maxLength={MAX_FLOOR_LENGTH}
                        />
                    </div>
                    <div>
                        <Input
                            id="room-capacity"
                            label="Kapasitas"
                            type="number"
                            value={data.capacity}
                            onChange={(event) =>
                                setField('capacity', event.target.value)
                            }
                            error={errors.capacity}
                            min={1}
                        />
                    </div>
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
                        {submitLabel}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
