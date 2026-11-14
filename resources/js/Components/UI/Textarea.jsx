import React from 'react';

export default function Textarea({
    id,
    label,
    value,
    onChange,
    placeholder,
    rows = 3,
    disabled = false,
    error,
    className = '',
    ...rest
}) {
    return (
        <div className={className}>
            {label && (
                <label
                    htmlFor={id}
                    className="block text-[11px] font-label font-semibold text-on-surface-variant mb-1.5 uppercase tracking-wider"
                >
                    {label}
                </label>
            )}
            <textarea
                id={id}
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                rows={rows}
                disabled={disabled}
                aria-invalid={error ? 'true' : undefined}
                className={`
                    w-full bg-surface-container-low text-on-surface
                    placeholder:text-on-surface-variant font-body text-sm
                    px-3 py-2 rounded-xl resize-none focus:outline-none focus:bg-surface-container-high
                    focus:ring-2 focus:ring-primary/20 transition-colors
                    disabled:opacity-50 disabled:cursor-not-allowed
                    ${error ? 'text-error' : ''}
                `}
                {...rest}
            />
            {error && (
                <p className="mt-1 text-[10px] font-label text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
