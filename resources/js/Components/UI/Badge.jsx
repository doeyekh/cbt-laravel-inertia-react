import React from 'react';

export default function Badge({
    children,
    variant = 'neutral',
    className = '',
}) {
    const variants = {
        primary: 'bg-primary text-on-primary',
        secondary: 'bg-secondary-container text-on-secondary-container',
        tertiary: 'bg-tertiary-container text-on-tertiary-container',
        error: 'bg-error-container text-on-error-container',
        success: 'bg-primary/10 text-primary',
        warning: 'bg-tertiary-container/30 text-tertiary',
        neutral: 'bg-surface-container text-on-surface-variant',
    };

    return (
        <span
            className={`
                inline-flex items-center gap-1.5
                px-2.5 py-0.5 rounded-xl text-[11px] font-label font-semibold
                ${variants[variant]}
                ${className}
            `}
        >
            {children}
        </span>
    );
}
