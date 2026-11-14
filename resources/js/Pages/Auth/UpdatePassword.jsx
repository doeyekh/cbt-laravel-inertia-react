
import React from 'react';
import { router, useForm } from '@inertiajs/react';

import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import PasswordInput from '@/Components/UI/PasswordInput';
import Button from '@/Components/UI/Button';

import { route } from '@/Utils/route';

const passwordFields = [
    {
        name: 'current_password',
        label: 'Kata Sandi Saat Ini',
        placeholder: 'Masukkan kata sandi saat ini',
    },
    {
        name: 'password',
        label: 'Kata Sandi Baru',
        placeholder: 'Masukkan kata sandi baru',
        helperText: 'Minimal 8 karakter.',
        minLength: 8,
    },
    {
        name: 'password_confirmation',
        label: 'Konfirmasi Kata Sandi Baru',
        placeholder: 'Ketik ulang kata sandi baru',
    },
];

export default function UpdatePassword() {
    const {
        data,
        setData,
        put,
        processing,
        errors,
        clearErrors,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    // Handle input change
    const handleChange = (field, value) => {
        setData(field, value);

        if (errors[field]) {
            clearErrors(field);
        }
    };

    // Submit form
    const handleSubmit = (event) => {
        event.preventDefault();

        put(route('admin.password.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1720px] space-y-6">

                {/* Page Header */}
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label font-semibold uppercase tracking-widest text-on-surface-variant">
                            <span>Pengaturan Akun</span>
                            <span>/</span>
                            <span className="text-primary">
                                Ganti Kata Sandi
                            </span>
                        </div>

                        <h1 className="font-headline text-3xl font-bold tracking-tight text-on-surface">
                            Ganti Kata Sandi
                        </h1>

                        <p className="font-body text-sm text-on-surface-variant">
                            Perbarui kata sandi akun Anda untuk menjaga keamanan.
                        </p>
                    </div>

                    <Button
                        as="a"
                        href={route('admin.dashboard')}
                        variant="outline"
                        className="hidden sm:flex"
                    >
                        <span className="material-symbols-outlined text-[18px]">
                            arrow_back
                        </span>
                        Kembali
                    </Button>
                </div>

                {/* Password Form */}
                <Card padding="p-6">
                    <form onSubmit={handleSubmit} className="space-y-5">

                        {passwordFields.map((field, index) => (
                            <PasswordInput
                                key={field.name}
                                id={field.name}
                                label={field.label}
                                value={data[field.name]}
                                onChange={(e) =>
                                    handleChange(field.name, e.target.value)
                                }
                                placeholder={field.placeholder}
                                error={errors[field.name]}
                                helperText={field.helperText}
                                minLength={field.minLength}
                                autoFocus={index === 0}
                            />
                        ))}

                        {/* Form Actions */}
                        <div className="flex flex-col-reverse gap-3 border-t border-outline/10 pt-5 sm:flex-row sm:justify-end">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => router.back()}
                                disabled={processing}
                                className="w-full sm:w-auto"
                            >
                                Batal
                            </Button>

                            <Button
                                type="submit"
                                variant="primary"
                                loading={processing}
                                disabled={processing}
                                className="w-full sm:w-auto"
                            >
                                Simpan Perubahan
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
