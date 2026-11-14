import React from 'react';

export default function Button({
    children,
    type = 'button',
    variant = 'primary',
    size = 'md',
    disabled = false,
    loading = false,
    onClick,
    className = '',
    ...rest
}) {
    const variants = {
        primary: 'bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-md hover:brightness-110 transition-all',
        secondary:
            'bg-surface-container hover:bg-surface-container-high text-on-surface font-medium transition-all shadow-sm',
        outline:
            'border border-outline hover:bg-surface-container-low text-on-surface transition-all shadow-sm',
        ghost: 'text-on-surface-variant hover:text-on-surface transition-all',
        danger: 'bg-error text-on-error hover:brightness-110 transition-all shadow-sm',
    };

    const sizes = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-xs',
    };

    return (
        <button
            type={type}
            disabled={disabled || loading}
            onClick={onClick}
            aria-busy={loading ? 'true' : undefined}
            className={`
                inline-flex items-center justify-center gap-1.5 cursor-pointer
                rounded-xl font-label font-medium
                transition disabled:opacity-50 disabled:pointer-events-none
                ${sizes[size]}
                ${variants[variant]}
                ${className}
            `}
            {...rest}
        >
            {loading ? (
                <>
                    <span className="material-symbols-outlined text-[18px] animate-spin">sync</span>
                    <span>Memproses...</span>
                </>
            ) : (
                children
            )}
        </button>
    );
}
