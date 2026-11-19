import React from "react";
import AppLayout from "@/Layouts/AppLayout";
import Card from "@/Components/UI/Card";
import { formatPercentage } from "@/Utils/formatPercentage";
import CardHeaderPage from "@/Components/Layout/CardHeaderPage";
import ReportScheaculeTable from "@/Features/Reports/Components/ReportScheaculeTable";
import ReportFilter from "@/Features/Reports/Components/ReportFilter";

export default function ReportsIndex({
    filters = {},
    academicYears = [],
    packages = [],
    subjects = [],
    classes = [],
    reports = [],
    summary = {},
}) {
    

    const cards = [
        [
            "Jadwal Ujian",
            summary.schedule_count ?? 0,
            "calendar_month",
            "primary",
        ],
        ["Peserta Unik", summary.participant_count ?? 0, "groups", "tertiary"],
        [
            "Sudah Dikumpulkan",
            summary.completed_count ?? 0,
            "assignment_turned_in",
            "secondary",
        ],
        [
            "Rata-rata Nilai",
            formatPercentage(summary.average_percentage),
            "monitoring",
            "primary",
        ],
        [
            "Ketuntasan",
            formatPercentage(summary.pass_percentage),
            "verified",
            "tertiary",
        ],
    ];

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1720px] space-y-6">
                <CardHeaderPage
                    subTitle=""
                    title="Laporan & Analitik Ujian"
                    description="Tinjau hasil peserta, tingkat ketuntasan, dan performa setiap jadwal ujian."
                />

                <section
                    className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5"
                    aria-label="Ringkasan laporan"
                >
                    {cards.map(([label, value, icon, variant]) => (
                        <Card key={label} padding="p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-label text-on-surface-variant">
                                        {label}
                                    </p>
                                    <p className="mt-2 text-2xl font-bold text-on-surface">
                                        {value}
                                    </p>
                                </div>
                                <span
                                    className={`material-symbols-outlined rounded-xl p-2 text-[20px] ${
                                        variant === "primary"
                                            ? "bg-primary/10 text-primary"
                                            : variant === "tertiary"
                                              ? "bg-tertiary-container/30 text-tertiary"
                                              : "bg-secondary-container/50 text-secondary"
                                    }`}
                                    aria-hidden="true"
                                >
                                    {icon}
                                </span>
                            </div>
                        </Card>
                    ))}
                </section>
                <ReportFilter academicYears={academicYears} packages={packages} subjects={subjects} classes={classes} filters={filters} />
                <ReportScheaculeTable reports={reports} />
            </div>
        </AppLayout>
    );
}
