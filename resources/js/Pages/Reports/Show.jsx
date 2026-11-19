import React from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/UI/Card';
import Button from '@/Components/UI/Button';
import { route } from '@/Utils/route';

function formatPercentage(value) {
    return value === null || value === undefined ? '-' : `${Number(value).toFixed(1)}%`;
}

export default function ReportsShow({
    schedule = {},
    summary = {},
    distribution = [],
    questionAnalysis = [],
    participants = [],
}) {
    const maxDistribution = Math.max(...distribution.map((item) => item.count), 1);

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1720px] space-y-6">
                <section className="flex flex-col justify-between gap-5 rounded-[28px] bg-primary px-6 py-7 text-on-primary sm:flex-row sm:items-end sm:px-8">
                    <div>
                        <p className="text-xs font-label font-semibold uppercase tracking-[0.18em] text-on-primary/70">
                            Detail laporan ujian
                        </p>
                        <h1 className="mt-3 font-headline text-3xl font-bold">{schedule.name}</h1>
                        <p className="mt-2 text-sm text-on-primary/75">
                            {schedule.code} · {schedule.subject} · {schedule.class} · {schedule.exam_date}
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Button variant="outline" onClick={() => router.visit(route('admin.reports.index'))}>
                            Kembali
                        </Button>
                        <Button
                            variant="primary"
                            onClick={() => window.location.assign(route('admin.reports.export', schedule.id))}
                        >
                            <span className="material-symbols-outlined text-[18px]" aria-hidden="true">download</span>
                            Export Excel
                        </Button>
                    </div>
                </section>

                <section className="grid grid-cols-2 gap-4 lg:grid-cols-6">
                    {[
                        ['Peserta', summary.participant_count ?? 0],
                        ['Selesai', summary.completed_count ?? 0],
                        ['Rata-rata', formatPercentage(summary.average_percentage)],
                        ['Ketuntasan', formatPercentage(summary.pass_percentage)],
                        ['Tertinggi', formatPercentage(summary.highest_percentage)],
                        ['Terendah', formatPercentage(summary.lowest_percentage)],
                    ].map(([label, value]) => (
                        <Card key={label} padding="p-5">
                            <p className="text-xs font-label text-on-surface-variant">{label}</p>
                            <p className="mt-2 text-xl font-bold text-on-surface">{value}</p>
                        </Card>
                    ))}
                </section>

                <Card padding="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[820px] text-left text-sm">
                            <thead className="bg-surface-container-low text-xs font-label uppercase tracking-wider text-on-surface-variant">
                                <tr>
                                    <th className="px-5 py-4">Peserta</th>
                                    <th className="px-5 py-4">Status</th>
                                    <th className="px-5 py-4 text-right">Skor</th>
                                    <th className="px-5 py-4 text-right">Nilai</th>
                                    <th className="px-5 py-4 text-right">Waktu</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-surface-container-high">
                                {participants.map((participant) => (
                                    <tr key={participant.attempt_id}>
                                        <td className="px-5 py-4">
                                            <p className="font-semibold">{participant.name}</p>
                                            <p className="mt-1 text-xs text-on-surface-variant">
                                                {participant.participant_number} · {participant.nisn}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">{participant.status}</td>
                                        <td className="px-5 py-4 text-right">
                                            {participant.score ?? '-'} / {participant.max_score ?? '-'}
                                        </td>
                                        <td className="px-5 py-4 text-right font-semibold">
                                            {formatPercentage(participant.percentage)}
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            {Math.floor((participant.time_spent_seconds ?? 0) / 60)} menit
                                        </td>
                                    </tr>
                                ))}
                                {participants.length === 0 && (
                                    <tr>
                                        <td colSpan="5" className="px-5 py-10 text-center text-on-surface-variant">
                                            Belum ada peserta yang memulai ujian.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <section className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <Card padding="p-5">
                        <h2 className="font-headline text-lg font-bold text-on-surface">Distribusi Nilai</h2>
                        <div className="mt-5 space-y-3">
                            {distribution.map((item) => (
                                <div key={item.range} className="grid grid-cols-[64px_1fr_32px] items-center gap-3 text-xs">
                                    <span className="font-label text-on-surface-variant">{item.range}</span>
                                    <div className="h-2.5 overflow-hidden rounded-full bg-surface-container-high">
                                        <div
                                            className="h-full rounded-full bg-primary"
                                            style={{ width: `${(item.count / maxDistribution) * 100}%` }}
                                        />
                                    </div>
                                    <span className="text-right font-semibold text-on-surface">{item.count}</span>
                                </div>
                            ))}
                        </div>
                    </Card>

                    <Card padding="p-5" className="xl:col-span-2">
                        <h2 className="font-headline text-lg font-bold text-on-surface">Analisis Butir Soal</h2>
                        <div className="mt-4 overflow-x-auto">
                            <table className="w-full min-w-[620px] text-left text-sm">
                                <thead className="text-xs uppercase tracking-wider text-on-surface-variant">
                                    <tr>
                                        <th className="pb-3">No.</th>
                                        <th className="pb-3">Tipe</th>
                                        <th className="pb-3">Soal</th>
                                        <th className="pb-3 text-right">Dijawab</th>
                                        <th className="pb-3 text-right">Benar</th>
                                        <th className="pb-3 text-right">Akurasi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-surface-container-high">
                                    {questionAnalysis.map((question) => (
                                        <tr key={question.id}>
                                            <td className="py-3 pr-3 font-semibold">{question.number}</td>
                                            <td className="py-3 pr-3">{question.type}</td>
                                            <td className="max-w-[300px] truncate py-3 pr-3 text-on-surface-variant">
                                                {question.body || '-'}
                                            </td>
                                            <td className="py-3 pr-3 text-right">{question.answered_count}</td>
                                            <td className="py-3 pr-3 text-right">{question.correct_count}</td>
                                            <td className="py-3 text-right font-semibold">
                                                {formatPercentage(question.accuracy_percentage)}
                                            </td>
                                        </tr>
                                    ))}
                                    {questionAnalysis.length === 0 && (
                                        <tr>
                                            <td colSpan="6" className="py-8 text-center text-on-surface-variant">
                                                Belum ada butir soal pada bank soal jadwal ini.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </section>
                

            </div>
        </AppLayout>
    );
}
