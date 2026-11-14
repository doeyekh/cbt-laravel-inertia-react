import React, { useState } from 'react';
import Sidebar from '@/Components/Layout/Sidebar';
import Header from '@/Components/Layout/Header';

export default function AppLayout({ children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-background flex">
            {/* Sidebar Komponen */}
            <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

            {/* Backdrop untuk Mobile */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 bg-black/40 backdrop-blur-xs z-40 lg:hidden transition-opacity duration-300"
                    onClick={() => setSidebarOpen(false)}
                    aria-hidden="true"
                />
            )}

            {/* Area Konten Utama */}
            <div className="flex-1 flex flex-col min-w-0">
                <Header onMenuToggle={() => setSidebarOpen((prev) => !prev)} />

                <main className="flex-1 p-4 sm:p-6 lg:p-8 space-y-8 max-w-[1720px] mx-auto w-full">
                    {children}
                </main>
            </div>
        </div>
    );
}