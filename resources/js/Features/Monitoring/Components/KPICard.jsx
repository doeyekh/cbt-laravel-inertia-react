import React from 'react';

export default function KPICard({
    title,
    value,
    subtitle,
    icon,
    iconVariant = 'primary',
    trend,
    trendLabel,
    children,
    className = '',
}) {
    const iconVariants = {
        primary: 'bg-surface-container text-primary',
        tertiary: 'bg-tertiary-container/30 text-tertiary',
        error: 'bg-error-container text-on-error-container',
        surface: 'bg-surface-container text-primary',
    };

    return (
        <div
            className={`
                bg-surface-container-lowest p-6 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group
                ${className}
            `}
        >
            <div className="absolute -right-4 -top-4 w-24 h-24 rounded-full blur-xl group-hover:opacity-110 transition-colors duration-500">
                {iconVariant === 'primary' && 'bg-primary/5'}
                {iconVariant === 'tertiary' && 'bg-tertiary/5'}
                {iconVariant === 'error' && 'bg-error/5'}
                {iconVariant === 'surface' && 'bg-primary-container/10'}
            </div>

            <div className="relative z-10">
                <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                        {title}
                    </span>
                    <span className="p-2 rounded-xl">
                        <span className={`material-symbols-outlined text-[20px] ${iconVariants[iconVariant]}`}>
                            {icon}
                        </span>
                    </span>
                </div>

                <div className="flex items-baseline gap-2 mb-1">
                    <span className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                        {value}
                    </span>
                    {subtitle && (
                        <span className="text-xs font-label text-on-surface-variant">
                            {subtitle}
                        </span>
                    )}
                </div>

                {children && <div className="mt-2">{children}</div>}
            </div>

            {trend && (
                <div className="mt-4 pt-3 flex items-center justify-between text-[11px] font-label">
                    <span className={trend.className}>{trend.label}</span>
                    <span className="material-symbols-outlined text-sm">
                        {trend.icon}
                    </span>
                </div>
            )}
        </div>
    );
}
