import React from 'react';

export default function AuthLayout({ children }) {
    return (
        <div className="min-h-screen flex items-center justify-center bg-surface-container-low px-4 sm:px-6 lg:px-8">
            <div className="max-w-md w-full">
                {children}
            </div>
        </div>
    );
}
