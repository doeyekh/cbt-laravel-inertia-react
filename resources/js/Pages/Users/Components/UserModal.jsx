import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';

const emptyForm = {
    name: '',
    username: '',
    email: '',
    role: 'guru',
    password: '',
    password_confirmation: '',
};

const MAX_NAME_LENGTH = 255;
const MAX_USERNAME_LENGTH = 50;
const MAX_EMAIL_LENGTH = 255;
const MAX_PASSWORD_LENGTH = 255;
const MIN_PASSWORD_LENGTH = 8;

export default function UserModal({
    isOpen,
    onClose,
    mode = 'create',
    initialData = null,
    roles = [],
}) {
    const { data, setData, post, put, processing, errors, clearErrors } =
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
                      name: initialData.name,
                      username: initialData.username,
                      email: initialData.email,
                      role: initialData.role,
                      password: '',
                      password_confirmation: '',
                  }
                : emptyForm
        );
    }, [isOpen, initialDataId]);

    const roleOptions =
        roles.length > 0
            ? roles
            : [
                  { value: 'administrator', label: 'Administrator' },
                  { value: 'guru', label: 'Guru' },
              ];

    const setField = (field, value) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        const name = data.name.trim();
        const username = data.username.trim();
        const email = data.email.trim();

        const nextErrors = {};

        if (!name) {
            nextErrors.name = 'Nama lengkap wajib diisi.';
        } else if (name.length > MAX_NAME_LENGTH) {
            nextErrors.name = `Nama lengkap maksimal ${MAX_NAME_LENGTH} karakter.`;
        }

        if (!username) {
            nextErrors.username = 'Username wajib diisi.';
        } else if (username.length > MAX_USERNAME_LENGTH) {
            nextErrors.username = `Username maksimal ${MAX_USERNAME_LENGTH} karakter.`;
        }

        if (!email) {
            nextErrors.email = 'Email wajib diisi.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            nextErrors.email = 'Format email tidak valid.';
        } else if (email.length > MAX_EMAIL_LENGTH) {
            nextErrors.email = `Email maksimal ${MAX_EMAIL_LENGTH} karakter.`;
        }

        if (!isEdit && !data.password) {
            nextErrors.password = 'Kata sandi wajib diisi.';
        } else if (data.password && data.password.length < MIN_PASSWORD_LENGTH) {
            nextErrors.password = `Kata sandi minimal ${MIN_PASSWORD_LENGTH} karakter.`;
        } else if (data.password && data.password.length > MAX_PASSWORD_LENGTH) {
            nextErrors.password = `Kata sandi maksimal ${MAX_PASSWORD_LENGTH} karakter.`;
        }

        if (data.password && data.password !== data.password_confirmation) {
            nextErrors.password_confirmation = 'Konfirmasi kata sandi tidak cocok.';
        }

        if (!data.role) {
            nextErrors.role = 'Role wajib dipilih.';
        }

        if (Object.keys(nextErrors).length > 0) {
            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (isEdit) {
            put(route('admin.users.update', initialData.id), options);

            return;
        }

        post(route('admin.users.store'), options);
    };

    const handleClose = () => {
        if (!processing) {
            onClose();
        }
    };

    const title = isEdit ? 'Edit Pengguna' : 'Tambah Pengguna';
    const subtitle = isEdit
        ? 'Perbarui informasi pengguna dan hak akses.'
        : 'Tambahkan pengguna baru ke sistem.';
    const submitLabel = isEdit ? 'Perbarui' : 'Simpan';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} maxWidth="max-w-lg">
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
                    <p className="text-xs text-on-surface-variant font-body mt-0.5">
                        {subtitle}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Input
                        id="user-name"
                        label="Nama Lengkap"
                        type="text"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        placeholder="Contoh: Budi Santoso"
                        error={errors.name}
                        maxLength={MAX_NAME_LENGTH}
                        autoFocus
                    />
                </div>

                <div>
                    <Input
                        id="user-username"
                        label="Username"
                        type="text"
                        value={data.username}
                        onChange={(event) => setData('username', event.target.value)}
                        placeholder="Contoh: budi.santoso"
                        error={errors.username}
                        maxLength={MAX_USERNAME_LENGTH}
                    />
                </div>

                <div>
                    <Input
                        id="user-email"
                        label="Email"
                        type="email"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        placeholder="Contoh: budi@sekolah.sch.id"
                        error={errors.email}
                        maxLength={MAX_EMAIL_LENGTH}
                    />
                </div>

                <div>
                    <Select
                        id="user-role"
                        label="Role"
                        value={data.role}
                        onChange={(event) => setData('role', event.target.value)}
                        error={errors.role}
                    >
                        {roleOptions.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </Select>
                </div>

                {!isEdit && (
                    <div>
                        <Input
                            id="user-password"
                            label="Kata Sandi"
                            type="password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            placeholder="Masukkan kata sandi"
                            error={errors.password}
                            minLength={MIN_PASSWORD_LENGTH}
                            maxLength={MAX_PASSWORD_LENGTH}
                            autoComplete="new-password"
                        />
                        <p className="mt-1.5 text-[10px] text-on-surface-variant font-body">
                            Minimal {MIN_PASSWORD_LENGTH} karakter.
                        </p>
                    </div>
                )}

                {!isEdit && (
                    <div>
                        <Input
                            id="user-password-confirmation"
                            label="Konfirmasi Kata Sandi"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(event) =>
                                setData('password_confirmation', event.target.value)
                            }
                            placeholder="Ketik ulang kata sandi"
                            error={errors.password_confirmation}
                            minLength={MIN_PASSWORD_LENGTH}
                            maxLength={MAX_PASSWORD_LENGTH}
                            autoComplete="new-password"
                        />
                    </div>
                )}

                {isEdit && (
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Input
                                id="user-password"
                                label="Kata Sandi Baru"
                                type="password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                placeholder="Kosongkan jika tidak ingin mengubah"
                                error={errors.password}
                                minLength={MIN_PASSWORD_LENGTH}
                                maxLength={MAX_PASSWORD_LENGTH}
                                autoComplete="new-password"
                            />
                            <p className="mt-1.5 text-[10px] text-on-surface-variant font-body">
                                Biarkan kosong jika tidak ingin mengubah kata sandi.
                            </p>
                        </div>
                        <div>
                            <Input
                                id="user-password-confirmation"
                                label="Konfirmasi Kata Sandi Baru"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(event) =>
                                    setData('password_confirmation', event.target.value)
                                }
                                placeholder="Ketik ulang kata sandi baru"
                                error={errors.password_confirmation}
                                minLength={MIN_PASSWORD_LENGTH}
                                maxLength={MAX_PASSWORD_LENGTH}
                                autoComplete="new-password"
                            />
                        </div>
                    </div>
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
