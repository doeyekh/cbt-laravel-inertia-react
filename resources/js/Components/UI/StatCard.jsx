
import Card from "@/components/ui/card";

export default function StatCard({
    title,
    value,
    icon,
    color = "primary",
    iconBackground,
}) {
    const background = iconBackground || `${color}/10`;

    return (
        <Card padding="p-6">
            <div className="flex items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-[11px] font-label uppercase tracking-wider text-on-surface-variant font-semibold">
                        {title}
                    </p>

                    <h3 className="font-headline text-2xl font-bold text-on-surface mt-1 truncate">
                        {value}
                    </h3>
                </div>

                <div
                    className={`w-12 h-12 rounded-xl ${background} flex items-center justify-center shrink-0`}
                >
                    <span
                        className={`material-symbols-outlined text-${color} text-[24px]`}
                    >
                        {icon}
                    </span>
                </div>
            </div>
        </Card>
    );
}
