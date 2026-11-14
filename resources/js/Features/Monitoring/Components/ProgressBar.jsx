import React from 'react';

export default function ProgressBar({
    value,
    max = 100,
    color = 'primary',
    className = '',
}) {
    const percentage = Math.min((value / max) * 100, 100);

    const colorClasses = {
        primary: 'bg-primary',
        error: 'bg-error',
        tertiary: 'bg-tertiary-container',
        surface: 'bg-surface-container-high',
    };

    return (
        <div className={`w-full bg-surface-container h-2 rounded-full overflow-hidden ${className}`}>
            <div
                className={`h-full rounded-full transition-all duration-500 ${colorClasses[color]}`}
                style={{ width: `${percentage}%` }}
            />
        </div>
    );
}
