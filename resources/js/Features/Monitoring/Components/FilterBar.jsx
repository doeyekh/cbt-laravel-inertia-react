import React, { useState } from 'react';

export default function FilterBar({ onSearchChange, className = '' }) {
    const [search, setSearch] = useState('');

    const rooms = [
        { value: 'all', label: 'Semua Ruangan (3 Lab)' },
        { value: 'lab1', label: 'Lab Komputer 01 (Utama)' },
        { value: 'lab2', label: 'Lab Komputer 02 (Multimedia)' },
        { value: 'kelasA', label: 'Ruang Kelas XII A' },
    ];

    const statuses = [
        { value: 'all', label: 'Semua Status Pengerjaan' },
        { value: 'active', label: 'Sedang Mengerjakan (Active)' },
        { value: 'offline', label: 'Koneksi Putus (Disconnect)' },
        { value: 'locked', label: 'Terkunci (Lockscreen)' },
        { value: 'done', label: 'Selesai (Submitted)' },
    ];

    return (
        <div
            className={`
                bg-surface-container-lowest p-4 rounded-2xl shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4
                ${className}
            `}
        >
            <div className="flex flex-wrap items-center gap-2.5">
                <div className="relative min-w-[200px]">
                    <select
                        className="appearance-none w-full bg-surface-container-low text-on-surface font-label text-xs font-medium pl-3 pr-10 py-2 rounded-xl focus:outline-none focus:bg-surface-container-high transition-colors cursor-pointer"
                    >
                        {rooms.map((r) => (
                            <option key={r.value} value={r.value}>
                                {r.label}
                            </option>
                        ))}
                    </select>
                    <span className="material-symbols-outlined absolute right-2.5 top-2 text-[16px] pointer-events-none text-on-surface-variant">
                        expand_more
                    </span>
                </div>
                <div className="relative min-w-[200px]">
                    <select
                        className="appearance-none w-full bg-surface-container-low text-on-surface font-label text-xs font-medium pl-3 pr-10 py-2 rounded-xl focus:outline-none focus:bg-surface-container-high transition-colors cursor-pointer"
                    >
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                    <span className="material-symbols-outlined absolute right-2.5 top-2 text-[16px] pointer-events-none text-on-surface-variant">
                        expand_more
                    </span>
                </div>
                <span className="hidden sm:inline-block px-3 py-1.5 rounded-xl bg-surface-container text-xs font-label font-medium text-on-surface-variant">
                    Matematika Terapan XII
                </span>
            </div>

            <div className="relative min-w-[240px]">
                <input
                    id="searchInput"
                    type="text"
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        onSearchChange?.(e.target.value);
                    }}
                    placeholder="Cari NISN atau Nama siswa..."
                    className="w-full bg-surface-container-low text-on-surface placeholder:text-on-surface-variant font-body text-xs pl-9 pr-3.5 py-2 rounded-xl focus:outline-none focus:bg-surface-container-highest transition-colors"
                />
                <span className="material-symbols-outlined absolute left-2.5 top-2 text-[18px] text-on-surface-variant">
                    search
                </span>
            </div>
        </div>
    );
}
