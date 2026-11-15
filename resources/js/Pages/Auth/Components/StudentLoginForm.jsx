import React, { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { route } from '@/Utils/route';

function Field({ id, label, error, hint, action, children }) {
    return (
        <div>
            <div className="mb-2 flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="font-label text-[11px] font-semibold uppercase tracking-[0.14em] text-on-surface"
                >
                    {label}
                </label>
                {action}
            </div>

            {children}

            {error && (
                <p role="alert" className="mt-2 font-label text-[11px] font-medium text-error">
                    {error}
                </p>
            )}

            {!error && hint && (
                <p className="mt-2 text-[11px] leading-relaxed text-on-surface-variant">
                    {hint}
                </p>
            )}
        </div>
    );
}

const inputClass = `
    w-full rounded-xl bg-surface-container-lowest px-4 py-3 font-body text-sm text-on-surface
    border border-outline-variant/15 placeholder:text-on-surface-variant/50
    transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15
    disabled:cursor-not-allowed disabled:opacity-60
`;

const inputErrorClass = 'border-error focus:border-error focus:ring-error/15';

export default function StudentLoginForm({ academicYear = null, onShowRules }) {
    const { data, setData, post, processing, errors, clearErrors } = useForm({
        username: '',
        password: '',
    });

    const [showPassword, setShowPassword] = useState(false);

    const handleSubmit = (event) => {
        event.preventDefault();
        clearErrors();

        post(route('student.login.store'), {
            preserveScroll: true,
            onError: () => setData('password', ''),
        });
    };

    return (
        <div className="rounded-3xl bg-surface-container-lowest p-7 sm:p-10">
            <header className="space-y-3">
                <div className="flex items-center justify-between gap-3">
                    <p className="font-label text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">
                        Sesi Peserta
                    </p>

                    {academicYear && (
                        <span className="rounded-full bg-tertiary-fixed/40 px-3 py-1 font-label text-[10px] font-semibold text-tertiary">
                            {academicYear}
                        </span>
                    )}
                </div>

                <h2
                    id="student-login-heading"
                    className="font-headline text-3xl font-bold tracking-tight text-on-surface"
                >
                    Masuk Sesi Asesmen
                </h2>

                <p className="text-[13px] leading-relaxed text-on-surface-variant">
                    Lengkapi kredensial resmi Anda untuk membuka naskah ujian.
                </p>
            </header>

            <form
                onSubmit={handleSubmit}
                aria-labelledby="student-login-heading"
                className="mt-8 space-y-6"
            >
                <Field
                    id="student-username"
                    label="Username"
                    error={errors.username}
                    hint="Tercantum pada Kartu Peserta Ujian."
                >
                    <input
                        id="student-username"
                        type="text"
                        value={data.username}
                        onChange={(event) => setData('username', event.target.value)}
                        placeholder="Masukkan username"
                        autoComplete="username"
                        autoFocus
                        disabled={processing}
                        aria-invalid={errors.username ? 'true' : undefined}
                        className={`${inputClass} ${errors.username ? inputErrorClass : ''}`}
                    />
                </Field>

                <Field
                    id="student-password"
                    label="Kata Sandi / PIN"
                    error={errors.password}
                    hint="Gunakan PIN dari panitia bila Anda tidak menerima kata sandi."
                    action={
                        <button
                            type="button"
                            onClick={() => setShowPassword((previous) => !previous)}
                            aria-pressed={showPassword}
                            className="font-label text-[10px] font-semibold uppercase tracking-[0.12em] text-primary transition-colors hover:underline"
                        >
                            {showPassword ? 'Sembunyikan' : 'Tampilkan'}
                        </button>
                    }
                >
                    <input
                        id="student-password"
                        type={showPassword ? 'text' : 'password'}
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        placeholder="Masukkan kata sandi atau PIN"
                        autoComplete="current-password"
                        disabled={processing}
                        aria-invalid={errors.password ? 'true' : undefined}
                        className={`${inputClass} ${errors.password ? inputErrorClass : ''}`}
                    />
                </Field>

                
                <button
                    type="submit"
                    disabled={processing}
                    className="
                        flex w-full items-center justify-center gap-2 rounded-xl
                        bg-gradient-to-r from-primary to-primary-container px-6 py-3.5
                        font-label text-sm font-semibold text-on-primary
                        transition-all hover:brightness-110 active:scale-[0.995]
                        disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:brightness-100
                    "
                >
                    {processing ? (
                        <>
                            <span className="material-symbols-outlined animate-spin text-[18px]">
                                sync
                            </span>
                            Memverifikasi Peserta...
                        </>
                    ) : (
                        <>
                            <span className="material-symbols-outlined text-[18px]">
                                arrow_forward
                            </span>
                            Masuk ke Ruang Ujian
                        </>
                    )}
                </button>
            </form>

            
        </div>
    );
}
