import React from 'react';

export default function Checkbox({
    id,
    label,
    checked = false,
    onChange,
    disabled = false,
    className = '',
}) {
    return (
        <div
            className={`flex items-start gap-2 ${className}`}
        >
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={onChange}
                disabled={disabled}
                className="mt-0.5 h-4 w-4 shrink-0 rounded border-outline text-primary focus:ring-primary/30"
            />
            {label && (
                <label
                    htmlFor={id}
                    className="text-xs font-body text-on-surface leading-tight cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {label}
                </label>
            )}
        </div>
    );
}
