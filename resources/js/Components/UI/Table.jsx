import React from 'react';

export default function Table({ children, className = '', id, ...rest }) {
    return (
        <div className="overflow-x-auto">
            <table
                id={id}
                className={`w-full text-left border-collapse ${className}`}
                {...rest}
            >
                {children}
            </table>
        </div>
    );
}

export function TableHead({ children, className = '' }) {
    return (
        <thead>
            <tr
                className={`
                    bg-surface-container-low text-on-surface-variant
                    font-label text-[11px] font-semibold uppercase tracking-wider
                    ${className}
                `}
            >
                {children}
            </tr>
        </thead>
    );
}

export function TableHeadCell({ children, className = '', ...rest }) {
    return (
        <th scope="col" className={`px-4 py-3.5 ${className}`} {...rest}>
            {children}
        </th>
    );
}

export function TableBody({ children, className = 'text-xs font-body' }) {
    return <tbody className={className}>{children}</tbody>;
}

export function TableRow({ children, className = '', ...rest }) {
    return (
        <tr
            className={`hover:bg-surface-container-low/60 transition-colors ${className}`}
            {...rest}
        >
            {children}
        </tr>
    );
}

export function TableCell({ children, className = '', ...rest }) {
    return (
        <td className={`px-4 py-4 ${className}`} {...rest}>
            {children}
        </td>
    );
}
