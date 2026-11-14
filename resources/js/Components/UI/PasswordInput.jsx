
import React, { useState } from 'react';
import Input from '@/Components/UI/Input';

export default function PasswordInput({
    label,
    value,
    onChange,
    error,
    placeholder = '',
    helperText,
    id,
    ...props
}) {
    const [visible, setVisible] = useState(false);

    return (
        <div>
            <div className="relative">
                <Input
                    {...props}
                    id={id}
                    label={label}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={onChange}
                    placeholder={placeholder}
                    error={error}
                />

                <button
                    type="button"
                    onClick={() => setVisible((prev) => !prev)}
                    title={visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                    aria-label={visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                    aria-pressed={visible}
                    className="absolute right-3 top-[38px] flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors"
                >
                    <span className="material-symbols-outlined text-[20px]">
                        {visible ? 'visibility_off' : 'visibility'}
                    </span>
                </button>
            </div>

            {helperText && (
                <p className="mt-1 text-[10px] font-body text-on-surface-variant">
                    {helperText}
                </p>
            )}
        </div>
    );
}
