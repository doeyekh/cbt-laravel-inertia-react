import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';
import { route } from '@/Utils/route';

export default function Profile({ user }) {
    const { data, setData, put, processing, errors, clearErrors } =
        useForm({
            name: user?.name ?? '',
            username: user?.username ?? '',
            email: user?.email ?? '',
        });

    const [showToast, setShowToast] = useState(false);

    const handleSubmit = (event) => {
        event.preventDefault();
        put(route('admin.profile.update'), {
            onSuccess: () => {
                setShowToast(true);
                setTimeout(() => setShowToast(false), 4000);
            },
        });
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[600px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Pengaturan Akun</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">Profil Saya</span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            Profil Saya
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Kelola informasi profil akun Anda.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        as="a"
                        href={route('admin.dashboard')}
                        className="hidden sm:flex"
                    >
                        <span className="material-symbols-outlined text-[18px]">arrow_back</span>
                        Kembali
                    </Button>
                </div>

                {showToast && (
                    <div
                        className="flex items-start gap-3 p-4 rounded-xl shadow-lg border bg-primary/10 border-primary/20 animate-in slide-in-from-top-2 duration-200"
                        role="alert"
                    >
                        <div className="flex-shrink-0 mt-0.5">
                            <span className="material-symbols-outlined text-[20px] text-primary">
                                check_circle
                            </span>
                        </div>
                        <div className="flex-1">
                            <h4 className="font-semibold text-on-surface text-sm">
                                Profil Diperbarui
                            </h4>
                            <p className="mt-0.5 text-xs text-on-surface-variant font-body">
                                Informasi profil Anda telah berhasil diperbarui.
                            </p>
                        </div>
                        <button
                            onClick={() => setShowToast(false)}
                            className="ml-2 flex-shrink-0 p-1 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low rounded-lg transition-colors"
                            aria-label="Dismiss"
                        >
                            <span className="material-symbols-outlined text-[16px]">close</span>
                        </button>
                    </div>
                )}

                <Card padding="p-6">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="flex items-center gap-3">
                            <div className="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center shrink-0 border border-outline/20">
                                <span className="text-2xl font-label font-medium text-on-surface">
                                    {data?.name?.charAt(0).toUpperCase() ?? '?'}
                                </span>
                            </div>
                            <div>
                                <p className="font-headline font-semibold text-on-surface">
                                    {data?.name ?? '-'}
                                </p>
                                <p className="text-xs text-on-surface-variant font-body">
                                    {data?.username ?? '-'}
                                </p>
                                <Badge
                                    variant={
                                        user?.role === 'administrator' ? 'success' : 'neutral'
                                    }
                                    className="flex items-center gap-1.5"
                                >
                                    <span className="material-symbols-outlined text-[14px]">
                                        {user?.role === 'administrator' ? 'admin_panel' : 'person'}
                                    </span>
                                    {user?.role?.charAt(0).toUpperCase() + user?.role?.slice(1) ??
                                        '-'}
                                </Badge>
                            </div>
                        </div>

                        <div>
                            <Input
                                id="profile-name"
                                label="Nama Lengkap"
                                type="text"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="Masukkan nama lengkap"
                                error={errors.name}
                                autoFocus
                            />
                        </div>

                        <div>
                            <Input
                                id="profile-username"
                                label="Username"
                                type="text"
                                value={data.username}
                                onChange={(event) => setData('username', event.target.value)}
                                placeholder="Masukkan username"
                                error={errors.username}
                            />
                        </div>

                        <div>
                            <Input
                                id="profile-email"
                                label="Email"
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                placeholder="Masukkan alamat email"
                                error={errors.email}
                            />
                        </div>

                        <div className="flex items-center justify-end pt-4">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => router.back()}
                                className="flex-1 md:flex-1"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant="primary"
                                loading={processing}
                                className="flex-1 md:flex-1"
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
