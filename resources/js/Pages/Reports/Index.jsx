import React from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/UI/Card';
import Button from '@/Components/UI/Button';
import { route } from '@/Utils/route';

function formatPercentage(value) {
    return value === null || value === undefined ? '-' : `${Number(value).toFixed(1)}%`;
}

export default function ReportsIndex({
    filters = {},
    academicYears = [],
    packages = [],
    subjects = [],
    classes = [],
    reports = [],
    summary = {},
}) {
    const updateFilter = (name, value) => {
        router.get(route('admin.reports.index'), {
            ...filters,
            [name]: value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const cards = [
        ['Jadwal Ujian', summary.schedule_count ?? 0, 'calendar_month', 'primary'],
        ['Peserta Unik', summary.participant_count ?? 0, 'groups', 'tertiary'],
        ['Sudah Dikumpulkan', summary.completed_count ?? 0, 'assignment_turned_in', 'secondary'],
        ['Rata-rata Nilai', formatPercentage(summary.average_percentage), 'monitoring', 'primary'],
        ['Ketuntasan', formatPercentage(summary.pass_percentage), 'verified', 'tertiary'],
    ];

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1720px] space-y-6">
                <section className="rounded-[28px] bg-primary px-6 py-7 text-on-primary shadow-sm sm:px-8 sm:py-9">
                    <p className="text-xs font-label font-semibold uppercase tracking-[0.18em] text-on-primary/70">
                        Sistem Evaluasi Terpadu
                    </p>
                    <h1 className="mt-3 font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                        Laporan & Analitik Ujian
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm leading-relaxed text-on-primary/75 sm:text-base">
                        Tinjau hasil peserta, tingkat ketuntasan, dan performa setiap jadwal ujian.
                    </p>
                </section>

                <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan laporan">
                    {cards.map(([label, value, icon, variant]) => (
                        <Card key={label} padding="p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-label text-on-surface-variant">{label}</p>
                                    <p className="mt-2 text-2xl font-bold text-on-surface">{value}</p>
                                </div>
                                <span className={`material-symbols-outlined rounded-xl p-2 text-[20px] ${
                                    variant === 'primary'
                                        ? 'bg-primary/10 text-primary'
                                        : variant === 'tertiary'
                                            ? 'bg-tertiary-container/30 text-tertiary'
                                            : 'bg-secondary-container/50 text-secondary'
                                }`} aria-hidden="true">
                                    {icon}
                                </span>
                            </div>
                        </Card>
                    ))}
                </section>

                <Card padding="p-5">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label className="space-y-1.5 text-xs font-label font-semibold text-on-surface-variant">
                            Tahun Pelajaran
                            <select
                                value={filters.academic_year_id ?? ''}
                                onChange={(event) => updateFilter('academic_year_id', event.target.value)}
                                className="w-full rounded-xl bg-surface-container-low px-3.5 py-3 text-sm text-on-surface outline-none"
                            >
                                <option value="">Semua tahun</option>
                                {academicYears.map((year) => (
                                    <option key={year.id} value={year.id}>{year.name}</option>
                                ))}
                            </select>
                        </label>
                        <label className="space-y-1.5 text-xs font-label font-semibold text-on-surface-variant">
                            Paket Ujian
                            <select
                                value={filters.package_id ?? ''}
                                onChange={(event) => updateFilter('package_id', event.target.value)}
                                className="w-full rounded-xl bg-surface-container-low px-3.5 py-3 text-sm text-on-surface outline-none"
                            >
                                <option value="">Semua paket</option>
                                {packages.map((examPackage) => (
                                    <option key={examPackage.id} value={examPackage.id}>
                                        {examPackage.code} - {examPackage.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="space-y-1.5 text-xs font-label font-semibold text-on-surface-variant">
                            Mata Pelajaran
                            <select
                                value={filters.subject_id ?? ''}
                                onChange={(event) => updateFilter('subject_id', event.target.value)}
                                className="w-full rounded-xl bg-surface-container-low px-3.5 py-3 text-sm text-on-surface outline-none"
                            >
                                <option value="">Semua mata pelajaran</option>
                                {subjects.map((subject) => (
                                    <option key={subject.id} value={subject.id}>{subject.name}</option>
                                ))}
                            </select>
                        </label>
                        <label className="space-y-1.5 text-xs font-label font-semibold text-on-surface-variant">
                            Kelas
                            <select
                                value={filters.class_id ?? ''}
                                onChange={(event) => updateFilter('class_id', event.target.value)}
                                className="w-full rounded-xl bg-surface-container-low px-3.5 py-3 text-sm text-on-surface outline-none"
                            >
                                <option value="">Semua kelas</option>
                                {classes.map((schoolClass) => (
                                    <option key={schoolClass.id} value={schoolClass.id}>{schoolClass.name}</option>
                                ))}
                            </select>
                        </label>
                    </div>
                </Card>

                <Card padding="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[980px] text-left">
                            <thead className="bg-surface-container-low text-xs font-label uppercase tracking-wider text-on-surface-variant">
                                <tr>
                                    <th className="px-5 py-4">Ujian</th>
                                    <th className="px-5 py-4">Kelas / Sesi</th>
                                    <th className="px-5 py-4">Tanggal</th>
                                    <th className="px-5 py-4 text-right">Peserta</th>
                                    <th className="px-5 py-4 text-right">Selesai</th>
                                    <th className="px-5 py-4 text-right">Rata-rata</th>
                                    <th className="px-5 py-4 text-right">Tuntas</th>
                                    <th className="px-5 py-4" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-surface-container-high">
                                {reports.map((report) => (
                                    <tr key={report.id} className="text-sm text-on-surface">
                                        <td className="px-5 py-4">
                                            <p className="font-semibold">{report.name}</p>
                                            <p className="mt-1 text-xs text-on-surface-variant">
                                                {report.code} · {report.subject}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <p>{report.class}</p>
                                            <p className="mt-1 text-xs text-on-surface-variant">
                                                {report.session ?? '-'} · {report.room ?? '-'}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {report.exam_date}
                                            <p className="mt-1 text-xs text-on-surface-variant">
                                                {report.open_time?.slice(0, 5)}–{report.close_time?.slice(0, 5)}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4 text-right font-semibold">{report.participant_count}</td>
                                        <td className="px-5 py-4 text-right">{report.completed_count}</td>
                                        <td className="px-5 py-4 text-right font-semibold">
                                            {formatPercentage(report.average_percentage)}
                                        </td>
                                        <td className="px-5 py-4 text-right">{report.passed_count}</td>
                                        <td className="px-5 py-4 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.visit(route('admin.reports.show', report.id))}
                                            >
                                                Lihat
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {reports.length === 0 && (
                                    <tr>
                                        <td colSpan="8" className="px-5 py-12 text-center text-sm text-on-surface-variant">
                                            Belum ada jadwal ujian yang cocok dengan filter.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
