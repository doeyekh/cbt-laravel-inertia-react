import React from 'react';

export default function Avatar({
    label,
    size = 'w-8 h-8',
    textSize = 'text-xs',
    variant = 'primary',
    className = '',
}) {
    const initials = label
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    const variants = {
        primary: 'bg-surface-container-high text-primary',
        secondary: 'bg-surface-container-high text-secondary',
        tertiary: 'bg-tertiary-container/40 text-tertiary',
        error: 'bg-error-container text-on-error-container',
    };

    return (
        <div
            className={`
                ${size} rounded-full flex items-center justify-center
                font-label font-bold ${textSize}
                ${variants[variant]}
                ${className}
            `}
        >
            {initials}
        </div>
    );
}
