import React from "react";
import Card from "@/Components/UI/Card";

function DistributionCard({ distribution, maxDistribution }) {
    return (
        <Card padding="p-5">
            <h2 className="font-headline text-lg font-bold text-on-surface">
                Distribusi Nilai
            </h2>
            <div className="mt-5 space-y-3">
                {distribution.map((item) => (
                    <div
                        key={item.range}
                        className="grid grid-cols-[64px_1fr_32px] items-center gap-3 text-xs"
                    >
                        <span className="font-label text-on-surface-variant">
                            {item.range}
                        </span>
                        <div className="h-2.5 overflow-hidden rounded-full bg-surface-container-high">
                            <div
                                className="h-full rounded-full bg-primary"
                                style={{
                                    width: `${(item.count / maxDistribution) * 100}%`,
                                }}
                            />
                        </div>
                        <span className="text-right font-semibold text-on-surface">
                            {item.count}
                        </span>
                    </div>
                ))}
            </div>
        </Card>
    );
}

export default DistributionCard;
