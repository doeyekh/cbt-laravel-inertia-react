import React from 'react';

export default function TopBar({
    breadcrumb,
    title,
    telemetry,
    actions = [],
    className = '',
}) {
    return (
        <div
            className={`
                flex flex-col lg:flex-row lg:items-center justify-between gap-4
                ${className}
            `}
        >
            <div className="space-y-1">
                {breadcrumb && (
                    <div className="flex items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant">
                        {breadcrumb}
                    </div>
                )}
                {title && (
                    <h1 className="font-headline text-2xl lg:text-3xl font-semibold tracking-tight text-on-surface">
                        {title}
                    </h1>
                )}
            </div>

            <div className="flex flex-wrap items-center gap-3">
                {telemetry && (
                    <div className="flex items-center gap-3 px-4 py-2 rounded-xl bg-surface-container-low shadow-sm">
                        <span className="relative flex h-2.5 w-2.5">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75" />
                            <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary" />
                        </span>
                        <div className="flex items-center gap-3 text-xs font-label divide-x-0">
                            <span className="font-semibold text-on-surface">{telemetry.cluster}</span>
                            <span className="text-on-surface-variant font-mono text-[11px]">
                                Lat: <strong className="text-on-surface">{telemetry.lat}</strong>
                            </span>
                            <span className="text-on-surface-variant font-mono text-[11px]">
                                CPU: <strong className="text-on-surface">{telemetry.cpu}</strong>
                            </span>
                            <span className="text-on-surface-variant font-mono text-[11px]">
                                RAM: <strong className="text-on-surface">{telemetry.ram}</strong>
                            </span>
                        </div>
                    </div>
                )}
                {actions.length > 0 && (
                    <div className="flex items-center gap-2">
                        {actions.map((action, idx) => (
                            <button
                                key={idx}
                                onClick={action.onClick}
                                className={`
                                    inline-flex items-center gap-2 px-3.5 py-2 rounded-xl
                                    font-label text-xs font-medium transition-all shadow-sm
                                    ${action.variant === 'primary'
                                        ? 'bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-md hover:brightness-110'
                                        : 'bg-surface-container hover:bg-surface-container-high text-on-surface'
                                }
                                `}
                            >
                                <span className={`material-symbols-outlined text-[18px]`}>
                                    {action.icon}
                                </span>
                                <span>{action.label}</span>
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
