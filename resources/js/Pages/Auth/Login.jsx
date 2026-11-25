import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { route } from '@/Utils/route';
import AuthLayout from '@/Layouts/AuthLayout';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';

export default function Login() {
    const { data, setData, post, processing, errors, clearErrors, reset } =
        useForm({
            username: '',
            password: '',
            remember: false,
        });

    const [showPassword, setShowPassword] = useState(false);

    const handleSubmit = (event) => {
        event.preventDefault();
        post(route('admin.login.store'));
    };

    const handleRemember = (event) => {
        setData('remember', event.target.checked);
    };

    return (
        <AuthLayout>
            <Card className="p-8 sm:p-10 shadow-2xl">
                <div className="text-center mb-8">
                    <div className="w-12 h-12 rounded-xl bg-primary flex items-center justify-center mx-auto mb-4">
                        <span className="material-symbols-outlined text-on-primary text-[24px]">
                            school
                        </span>
                    </div>
                    <h2 className="font-headline font-bold text-2xl text-on-surface tracking-tight">
                        Masuk ke CBT Panel
                    </h2>
                    <p className="text-sm text-on-surface-variant font-body mt-1">
                        Masukkan username atau email dan kata sandi Anda.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <Input
                        id="login-username"
                        label="Username atau Email"
                        type="text"
                        value={data.username}
                        onChange={(event) => setData('username', event.target.value)}
                        placeholder="Masukkan username atau email"
                        error={errors.username}
                        autoFocus
                    />
                    <Input
                        id="login-password"
                        label="Kata Sandi"
                        type={showPassword ? 'text' : 'password'}
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        placeholder="Masukkan kata sandi"
                        error={errors.password}
                    />
                    <div className="flex items-center gap-2">
                        <input
                            id="login-remember"
                            type="checkbox"
                            checked={data.remember}
                            onChange={handleRemember}
                            className="w-4 h-4 rounded text-primary bg-surface-container-low border-outline focus:ring-primary"
                        />
                        <label htmlFor="login-remember" className="text-xs text-on-surface-variant font-body">
                            Ingatkan saya
                        </label>
                    </div>
                    <Button type="submit" variant="primary" className="w-full" loading={processing}>
                        Masuk
                    </Button>
                </form>

                <div className="mt-6 text-center">
                    <p className="text-xs text-on-surface-variant font-body">
                        Tidak memiliki akun? Hubungi administrator yayasan.
                    </p>
                </div>

                <div className="mt-4 flex items-center justify-between text-xs">
                    <Badge variant="neutral">
                        <span className="material-symbols-outlined text-[13px]">info</span>
                        {'Versi 1.0'}
                    </Badge>
                    <Badge variant="primary">
                        <span className="material-symbols-outlined text-[13px]">lock</span>
                        {'Aman & Terenkripsi'}
                    </Badge>
                </div>
            </Card>
        </AuthLayout>
    );
}
