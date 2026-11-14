import React from 'react';

const typeConfig = {
    success: {
        icon: 'check_circle',
        color: 'text-primary',
        bg: 'bg-primary/10',
        border: 'border-primary/20',
    },
    error: {
        icon: 'error',
        color: 'text-error',
        bg: 'bg-error-container/20',
        border: 'border-error-container/40',
    },
    warning: {
        icon: 'warning',
        color: 'text-tertiary',
        bg: 'bg-tertiary-container/20',
        border: 'border-tertiary-container/40',
    },
    info: {
        icon: 'info',
        color: 'text-secondary',
        bg: 'bg-surface-container-low',
        border: 'border-outline/30',
    },
};

export function Toast({ toast, onDismiss }) {
    const config = typeConfig[toast.type] || typeConfig.info;

    return (
        <div
            className={`flex items-start gap-3 p-4 rounded-xl shadow-lg border ${config.bg} ${config.border} animate-in slide-in-from-top-2 duration-200`}
            role="alert"
        >
            <div className="flex-shrink-0 mt-0.5">
                <span className={`material-symbols-outlined text-[20px] ${config.color}`}>
                    {config.icon}
                </span>
            </div>
            <div className="flex-1">
                <h4 className="font-semibold text-on-surface text-sm">{toast.title}</h4>
                {toast.description && (
                    <p className="mt-0.5 text-xs text-on-surface-variant font-body">
                        {toast.description}
                    </p>
                )}
            </div>
            <button
                onClick={() => onDismiss(toast.id)}
                className="ml-2 flex-shrink-0 p-1 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low rounded-lg transition-colors"
                aria-label="Dismiss"
            >
                <span className="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    );
}

export default function ToastContainer({ toasts, dismiss }) {
    return (
        <div className="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
            {toasts.map((t) => (
                <Toast
                    key={t.id}
                    toast={t}
                    onDismiss={(id) => {
                        dismiss(id);
                    }}
                />
            ))}
        </div>
    );
}
