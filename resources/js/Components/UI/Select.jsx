import React from 'react';

export default function Select({
    id,
    label,
    value,
    onChange,
    placeholder,
    disabled = false,
    error,
    multiple = false,
    className = '',
    children,
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
            <div className="relative">
                <select
                    id={id}
                    multiple={multiple}
                    value={value}
                    onChange={onChange}
                    disabled={disabled}
                    aria-invalid={error ? 'true' : undefined}
                    className={`
                        appearance-none w-full bg-surface-container-low text-on-surface
                        font-label text-sm font-medium pl-3 pr-10 py-2 rounded-xl
                        focus:outline-none focus:bg-surface-container-high
                        focus:ring-2 focus:ring-primary/20
                        transition-colors cursor-pointer
                        disabled:opacity-50 disabled:cursor-not-allowed
                        ${multiple ? 'pr-3 py-1.5 space-y-0.5' : ''}
                        ${error ? 'text-error' : ''}
                    `}
                    {...rest}
                >
                    {placeholder && !multiple && (
                        <option value="" disabled>
                            {placeholder}
                        </option>
                    )}
                    {children}
                </select>
                {!multiple && (
                    <span className="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[16px] pointer-events-none text-on-surface-variant">
                        expand_more
                    </span>
                )}
            </div>
            {error && (
                <p className="mt-1 text-[10px] font-label text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
