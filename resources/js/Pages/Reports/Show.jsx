import React, { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import Card from "@/Components/UI/Card";
import CardHeader from "@/Features/Reports/Components/CardHeader";
import TableShowReport from "@/Features/Reports/Components/TableShowReport";
import DistributionCard from "@/Features/Reports/Components/DistributionCard";
import PartisipationTable from "@/Features/Reports/Components/PartisipationTable";
import { formatPercentage } from "@/Utils/formatPercentage";
import CardHeaderPage from "@/Components/Layout/CardHeaderPage";


export default function ReportsShow({
    schedule = {},
    summary = {},
    distribution = [],
    questionAnalysis = [],
    participants = [],
}) {
    const maxDistribution = Math.max(
        ...distribution.map((item) => item.count),
        1,
    );

    

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1720px] space-y-6">
                <CardHeader schedule={schedule} />

                <section className="grid grid-cols-2 gap-4 lg:grid-cols-6">
                    {[
                        ["Peserta", summary.participant_count ?? 0],
                        ["Selesai", summary.completed_count ?? 0],
                        [
                            "Rata-rata",
                            formatPercentage(summary.average_percentage),
                        ],
                        [
                            "Ketuntasan",
                            formatPercentage(summary.pass_percentage),
                        ],
                        [
                            "Tertinggi",
                            formatPercentage(summary.highest_percentage),
                        ],
                        [
                            "Terendah",
                            formatPercentage(summary.lowest_percentage),
                        ],
                    ].map(([label, value]) => (
                        <Card key={label} padding="p-5">
                            <p className="text-xs font-label text-on-surface-variant">
                                {label}
                            </p>
                            <p className="mt-2 text-xl font-bold text-on-surface">
                                {value}
                            </p>
                        </Card>
                    ))}
                </section>

                <PartisipationTable participants={participants} schedule={schedule} />
                <section className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <DistributionCard distribution={distribution} maxDistribution={maxDistribution} />
                    <TableShowReport questionAnalysis={questionAnalysis} />
                </section>
            </div>
        </AppLayout>
    );
}
