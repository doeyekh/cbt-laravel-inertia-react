import React from 'react';

export default function Card({ children, className = '', padding = 'p-6' }) {
    return (
        <div
            className={`
                bg-surface-container-lowest rounded-2xl shadow-sm overflow-hidden
                ${padding}
                ${className}
            `}
        >
            {children}
        </div>
    );
}

export function CardBody({ children, className = '' }) {
    return <div className={className}>{children}</div>;
}

export function CardHeader({ children, className = '' }) {
    return <div className={className}>{children}</div>;
}

export function CardFooter({ children, className = '' }) {
    return <div className={className}>{children}</div>;
}
