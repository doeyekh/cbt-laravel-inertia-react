import React, { useState, useEffect, useMemo } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { route, hasRoute } from '@/Utils/route';

const navRoute = (name, fallback = '#') =>
    hasRoute(name) ? route(name) : fallback;

const buildNavItems = () => [
    { name: 'Dashboard', href: navRoute('admin.dashboard'), icon: 'dashboard' },
    {
        name: 'Master Data',
        icon: 'database',
        children: [
            { name: 'Tahun Pelajaran', href: navRoute('admin.academic-years.index') },
            { name: 'Kelas', href: navRoute('admin.classes.index') },
            { name: 'Sekolah', href: navRoute('admin.schools.index') },
            { name: 'Mata Pelajaran', href: navRoute('admin.subjects.index') },
        ],
    },
    {
        name: 'Pengelolaan Ujian',
        icon: 'quiz',
        children: [
            { name: 'Jenis Ujian', href: navRoute('admin.exam-types.index') },
            { name: 'Bank Soal', href: navRoute('admin.question-banks.index') },
            { name: 'Sesi', href: navRoute('admin.sessions.index') },
            { name: 'Ruangan', href: navRoute('admin.rooms.index') },
            { name: 'Paket Ujian', href: navRoute('admin.exam-packages.index') },
            {
                name: 'Peserta Ujian',
                href: navRoute('admin.exam-participants.index'),
            },
            { name: 'Jenis Penilaian', href: navRoute('admin.assessment-types.index') },
        ],
    },
    { name: 'Monitoring Ujian', href: navRoute('admin.monitoring.index'), icon: 'monitoring' },
    {
        name: 'Siswa',
        icon: 'people',
        activePath: navRoute('admin.students.index'),
        children: [
            { name: 'Siswa Aktif', href: navRoute('admin.students.active') },
            { name: 'Alumni / Non Aktif', href: navRoute('admin.students.alumni') },
        ],
    },
    { name: 'Users', href: navRoute('admin.users.index'), icon: 'person' },
    { name: 'Laporan', href: navRoute('admin.reports.index', '/admin/reports'), icon: 'assessment' },
    { name: 'Pengaturan', href: navRoute('admin.settings.index'), icon: 'settings' },
];

export default function Sidebar({ isOpen, onClose }) {
    const { url, props } = usePage();
    const [openDropdowns, setOpenDropdowns] = useState(new Set());

    const user = props.auth?.user;
    const isAdministrator = user?.is_administrator ?? (user?.role === 'administrator');
    const isSchoolAdmin = user?.is_school_admin ?? false;
    const canAccessSchool = user?.can_access_school ?? isAdministrator;

    // Route helper only works after Ziggy is initialized on the client,
    // so the navigation config is built during render, not at module scope.
    const navItems = useMemo(
        () =>
            buildNavItems()
                .filter((item) => {
                    if (item.name === 'Users') {
                        return isAdministrator;
                    }

                    if (item.name === 'Monitoring Ujian') {
                        return isSchoolAdmin;
                    }

                    if (item.name === 'Laporan') {
                        return isSchoolAdmin;
                    }

                    return true;
                })
                .map((item) => {
                    if (item.children) {
                        return {
                            ...item,
                            children: item.children.filter((child) =>{
                                // tahun pelajaran
                                if(child.name === 'Tahun Pelajaran' && !isAdministrator)
                                {
                                    return false;
                                }
                                if(child.name === 'Kelas' && !canAccessSchool)
                                {
                                    return false;
                                }
                                if(child.name === 'Sekolah' && !canAccessSchool)
                                {
                                    return false;
                                }
                                if (
                                    [
                                        'Jenis Ujian',
                                        'Bank Soal',
                                        'Paket Ujian',
                                        'Peserta Ujian',
                                        'Sesi',
                                        'Ruangan',
                                        'Jenis Penilaian',
                                    ].includes(
                                        child.name
                                    ) &&
                                    !canAccessSchool
                                ) {
                                    return false;
                                }

                                return true;
                            }),
                        };
                    }

                    return item;
                })
                .filter((item) => !item.children || item.children.length > 0),
        [isAdministrator, isSchoolAdmin, canAccessSchool]
    );

    // Deteksi apakah URL saat ini cocok dengan href.
    // Sub-halaman (misal /admin/classes/1/subject-learning) dianggap bagian dari menu induknya
    // (misal "Kelas" di /admin/classes), kecuali root admin/dashboard yang harus cocok persis.
    const isActive = (href) => {
        if (!href || href === '#') {
            return false;
        }

        try {
            const currentPath = new URL(url, window.location.origin).pathname;
            const targetPath = new URL(href, window.location.origin).pathname;

            if (currentPath === targetPath) {
                return true;
            }

            if (targetPath === '/' || targetPath === '/admin') {
                return false;
            }

            return currentPath.startsWith(`${targetPath}/`);
        } catch {
            return url === href;
        }
    };

    // Inisialisasi dropdown terbuka jika salah satu anaknya aktif
    useEffect(() => {
        const initialOpen = new Set();
        navItems.forEach((item) => {
            if (item.children) {
                const hasActiveChild =
                    item.children.some((child) => isActive(child.href)) ||
                    (item.activePath && isActive(item.activePath));
                if (hasActiveChild) {
                    initialOpen.add(item.name);
                }
            }
        });
        setOpenDropdowns(initialOpen);
    }, [url]);

    const toggleDropdown = (key) => {
        setOpenDropdowns((prev) => {
            const next = new Set(prev);
            if (next.has(key)) {
                next.delete(key);
            } else {
                next.add(key);
            }
            return next;
        });
    };

    const isDropdownOpen = (key) => openDropdowns.has(key);

    const handleLogout = (e) => {
        e.preventDefault();

        if (!hasRoute('admin.logout')) {
            return;
        }

        router.post(route('admin.logout'));
    };

    return (
        <aside
            className={`
                fixed inset-y-0 left-0 z-50 w-64 bg-surface-container-lowest flex flex-col
                transform transition-transform duration-300 ease-in-out
                ${isOpen ? 'translate-x-0' : '-translate-x-full'}
                lg:relative lg:translate-x-0
            `}
        >
            {/* Header Sidebar */}
            <div className="flex h-16 items-center justify-between px-6 bg-surface-container-low shrink-0">
                <div className="flex items-center gap-2">
                    <div className="w-8 h-8 rounded-xl bg-primary flex items-center justify-center shadow-sm">
                        <span className="material-symbols-outlined text-on-primary text-[18px]">school</span>
                    </div>
                    <span className="font-headline font-semibold text-on-surface text-sm tracking-tight">
                        CBT Panel
                    </span>
                </div>
                <button
                    onClick={onClose}
                    className="lg:hidden p-1.5 text-on-surface-variant hover:bg-surface-container-low rounded-lg transition-colors"
                    aria-label="Close Sidebar"
                >
                    <span className="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            {/* Navigasi Menu */}
            <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
                {navItems.map((item) => {
                    if (item.children) {
                        const active =
                            item.children.some((child) => isActive(child.href)) ||
                            (item.activePath && isActive(item.activePath));
                        const expanded = isDropdownOpen(item.name);
                        return (
                            <div key={item.name} className="space-y-0.5">
                                <button
                                    onClick={() => toggleDropdown(item.name)}
                                    className={`
                                        flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-label
                                        transition-colors w-full justify-between
                                        ${active
                                            ? 'bg-primary/10 text-primary font-medium'
                                            : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                                        }
                                    `}
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="material-symbols-outlined text-[18px]">
                                            {item.icon}
                                        </span>
                                        {item.name}
                                    </div>
                                    <span className={`material-symbols-outlined text-[16px] transition-transform duration-200 ${expanded ? 'rotate-180' : ''}`}>
                                        expand_more
                                    </span>
                                </button>

                                {expanded && (
                                    <div className="ml-4 space-y-0.5 pl-2 mt-1">
                                        {item.children.map((child) => {
                                            const childActive = isActive(child.href);
                                            const childDisabled = child.href === '#' || !child.href;

                                            if (childDisabled) {
                                                return (
                                                    <div
                                                        key={child.name}
                                                        title="Segera hadir"
                                                        className="flex items-center gap-2 px-3 py-2 rounded-xl text-[11px] font-label text-on-surface-variant/50 cursor-not-allowed"
                                                    >
                                                        <span className="material-symbols-outlined text-[14px] opacity-60">
                                                            arrow_right
                                                        </span>
                                                        {child.name}
                                                    </div>
                                                );
                                            }

                                            return (
                                                <Link
                                                    key={child.name}
                                                    href={child.href}
                                                    className={`
                                                        flex items-center gap-2 px-3 py-2 rounded-xl text-[11px] font-label
                                                        transition-colors w-full
                                                        ${childActive
                                                            ? 'bg-primary text-on-primary font-medium shadow-sm'
                                                            : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                                                        }
                                                    `}
                                                >
                                                    <span className="material-symbols-outlined text-[14px] opacity-70">
                                                        arrow_right
                                                    </span>
                                                    {child.name}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        );
                    }

                    const active = isActive(item.href);
                    const disabled = item.href === '#' || !item.href;

                    if (disabled) {
                        return (
                            <div
                                key={item.name}
                                title="Segera hadir"
                                className="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-label text-on-surface-variant/50 cursor-not-allowed"
                            >
                                <span className="material-symbols-outlined text-[18px]">
                                    {item.icon}
                                </span>
                                {item.name}
                            </div>
                        );
                    }

                    return (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={`
                                flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-label
                                transition-colors
                                ${active
                                    ? 'bg-primary text-on-primary font-medium shadow-sm'
                                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                                }
                            `}
                        >
                            <span className="material-symbols-outlined text-[18px]">
                                {item.icon}
                            </span>
                            {item.name}
                        </Link>
                    );
                })}
            </nav>

            {/* Tombol Logout di Bagian Bawah */}
            <div className="p-4 bg-surface-container-low/40 shrink-0">
                <button
                    onClick={handleLogout}
                    className="flex items-center gap-3 w-full px-3.5 py-2.5 rounded-xl text-xs font-label text-error hover:bg-error-container/20 transition-colors"
                >
                    <span className="material-symbols-outlined text-[18px]">logout</span>
                    Keluar
                </button>
            </div>
        </aside>
    );
}
