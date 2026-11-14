
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

import Button from '@/Components/UI/Button';
import { route } from '@/Utils/route';

export default function Header({
    onMenuToggle,
    onRefresh,
    onEmergency,
}) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const userMenuRef = useRef(null);

    // Toggle dropdown profil
    const handleToggleUserMenu = useCallback(() => {
        setUserMenuOpen((prev) => !prev);
    }, []);

    // Tutup dropdown profil
    const handleCloseUserMenu = useCallback(() => {
        setUserMenuOpen(false);
    }, []);

    // Deteksi klik di luar dropdown
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                userMenuRef.current &&
                !userMenuRef.current.contains(event.target)
            ) {
                handleCloseUserMenu();
            }
        };

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                handleCloseUserMenu();
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [handleCloseUserMenu]);

    // Navigasi profil
    const handleNavigate = useCallback((url) => {
        handleCloseUserMenu();
        router.visit(route(url));
    }, [handleCloseUserMenu]);

    // Logout
    const handleLogout = useCallback(() => {
        router.post(
            route('admin.logout'),
            {},
            {
                preserveScroll: true,
                onSuccess: handleCloseUserMenu,
            }
        );
    }, [handleCloseUserMenu]);

    return (
        <header className="sticky top-0 z-40 bg-surface-bright/80 backdrop-blur-md border-b border-outline/10">
            <div className="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16 gap-4">

                {/* SISI KIRI */}
                <div className="flex items-center gap-4 min-w-0">

                    <button
                        type="button"
                        onClick={onMenuToggle}
                        className="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-colors shrink-0"
                        aria-label="Buka menu navigasi"
                    >
                        <span className="material-symbols-outlined text-[24px]">
                            menu
                        </span>
                    </button>

                    <div className="hidden lg:flex items-center gap-2">
                        <span className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                            Sistem Evaluasi Terpadu
                        </span>

                        <span className="w-px h-3 bg-surface-container-high" />

                        <span className="text-primary font-semibold font-headline text-sm truncate">
                            Monitoring Real-time
                        </span>
                    </div>
                </div>

                {/* SISI KANAN */}
                <div className="flex items-center gap-3 shrink-0">

                    {/* STATUS SERVER */}
                    <ServerStatus />

                    {/* TOMBOL AKSI */}
                    <div className="flex items-center gap-2">
                        <Button
                            variant="secondary"
                            onClick={onRefresh}
                            title="Segarkan Data"
                        >
                            <span className="material-symbols-outlined text-[16px]">
                                sync
                            </span>
                            <span className="hidden sm:inline">
                                Refresh Live
                            </span>
                        </Button>

                        <Button
                            variant="danger"
                            onClick={onEmergency}
                            title="Sesi Darurat"
                        >
                            <span className="material-symbols-outlined text-[18px]">
                                warning
                            </span>
                            <span className="hidden md:inline">
                                Mulai Sesi Darurat
                            </span>
                        </Button>
                    </div>

                    <span className="w-px h-6 bg-surface-container-high hidden sm:block" />

                    {/* PROFIL PENGGUNA */}
                    <div
                        ref={userMenuRef}
                        className="relative"
                    >
                        <button
                            type="button"
                            onClick={handleToggleUserMenu}
                            aria-expanded={userMenuOpen}
                            aria-haspopup="true"
                            aria-label="Menu profil pengguna"
                            className="flex items-center gap-3 rounded-xl hover:bg-surface-container-low p-1.5 transition-colors"
                        >
                            {/* AVATAR */}
                            <div className="w-8 h-8 rounded-full bg-surface-container-high flex items-center justify-center overflow-hidden border border-outline/20 shrink-0">
                                {user?.name ? (
                                    <span className="text-xs font-label font-medium text-on-surface">
                                        {user.name.charAt(0).toUpperCase()}
                                    </span>
                                ) : (
                                    <span className="material-symbols-outlined text-[18px] text-on-surface-variant">
                                        person
                                    </span>
                                )}
                            </div>

                            {/* IDENTITAS */}
                            <div className="hidden md:flex flex-col items-end text-right">
                                <span className="text-xs font-medium text-on-surface max-w-[120px] truncate">
                                    {user?.name ?? 'Pengguna'}
                                </span>

                                <span className="text-[10px] font-label text-on-surface-variant capitalize">
                                    {user?.role ?? 'administrator'}
                                </span>
                            </div>

                            <span className={`material-symbols-outlined text-[16px] text-on-surface-variant transition-transform ${
                                userMenuOpen ? 'rotate-180' : ''
                            }`}>
                                keyboard_arrow_down
                            </span>
                        </button>

                        {/* DROPDOWN */}
                        {userMenuOpen && (
                            <div className="absolute right-0 top-full mt-2 w-56 bg-surface-container-lowest rounded-2xl shadow-2xl border border-outline/20 z-50 overflow-hidden">

                                <div className="py-2">

                                    <ProfileMenuItem
                                        icon="person"
                                        label="Profil Saya"
                                        onClick={() => handleNavigate('admin.profile.show')}
                                    />

                                    <div className="my-1.5 border-t border-outline/10" />

                                    <ProfileMenuItem
                                        icon="lock"
                                        label="Ganti Kata Sandi"
                                        onClick={() => handleNavigate('admin.password.edit')}
                                    />

                                    <div className="my-1.5 border-t border-outline/10" />

                                    <ProfileMenuItem
                                        icon="logout"
                                        label="Keluar"
                                        danger
                                        onClick={handleLogout}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}


// ========================================
// COMPONENT: SERVER STATUS
// ========================================

function ServerStatus() {
    return (
        <div className="hidden xl:flex items-center gap-3 px-4 py-2 rounded-xl bg-surface-container-low shadow-sm">

            <span className="relative flex h-2.5 w-2.5 shrink-0">
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75" />

                <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary" />
            </span>

            <div className="flex flex-col items-start gap-0.5">
                <span className="font-semibold text-on-surface text-xs">
                    Cluster CBT-Node 01
                </span>

                <div className="flex items-center gap-3 text-xs font-mono text-on-surface-variant">
                    <span>
                        Lat: <strong className="text-on-surface">14ms</strong>
                    </span>

                    <span>
                        CPU: <strong className="text-on-surface">28%</strong>
                    </span>

                    <span>
                        RAM: <strong className="text-on-surface">4.2 / 16 GB</strong>
                    </span>
                </div>
            </div>
        </div>
    );
}


// ========================================
// COMPONENT: PROFILE MENU ITEM
// ========================================

function ProfileMenuItem({
    icon,
    label,
    onClick,
    danger = false,
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            onClick={onClick}
            className={`w-full text-left px-4 py-2.5 text-xs font-label flex items-center gap-3 ${
                danger
                    ? 'text-error hover:bg-error-container/10'
                    : 'text-on-surface-variant hover:bg-surface-container-low'
            }`}
        >
            <span className="material-symbols-outlined text-[18px]">
                {icon}
            </span>

            {label}
        </Button>
    );
}
