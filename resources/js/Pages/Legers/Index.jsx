
import AppLayout from "@/Layouts/AppLayout";
import Button from "@/Components/UI/Button";
import CardHeaderPage from "@/Components/Layout/CardHeaderPage";
import StatCardGroup from "@/Components/UI/stat-card-group";

import useLegersStatistics from "./Components/useLegersStatistics";
import LegersTable from "./Components/LegersTable";
import LegersFilter from "./Components/LegersFilter";
import { buildLegerQuery } from "./Components/legerFilterUtils";
import { route } from "@/Utils/route";

export default function LegersIndex({
    rows = [],
    columns = [],
    summary = {},
    filters = {},
    schools = [],
    academicYears = [],
    classes = [],
    subjects = [],
    examTypes = [],
}) {
    const statistics = useLegersStatistics({ summary });
    const hasRequiredFilters = Boolean(filters.required_filters_selected);

    const handleExport = (routeName) => {
        if (!hasRequiredFilters) {
            return;
        }

        const url = route(routeName, buildLegerQuery(filters));

        window.open(url, "_blank", "noopener,noreferrer");
    };

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1720px] space-y-6">
                <CardHeaderPage
                    subTitle="Laporan"
                    title="Leger Nilai"
                    description="Rekap nilai ujian siswa per mata pelajaran, lengkap dengan rata-rata dan ketuntasan."
                >
                    <Button
                        variant="secondary"
                        size="sm"
                        disabled={!hasRequiredFilters}
                        onClick={() =>
                            handleExport("admin.leger-grades.pdf")
                        }
                    >
                        <span className="material-symbols-outlined text-[18px]">
                            picture_as_pdf
                        </span>
                        PDF
                    </Button>

                    <Button
                        variant="secondary"
                        size="sm"
                        disabled={!hasRequiredFilters}
                        onClick={() =>
                            handleExport("admin.leger-grades.export")
                        }
                    >
                        <span className="material-symbols-outlined text-[18px]">
                            table_view
                        </span>
                        Excel
                    </Button>
                </CardHeaderPage>

                <StatCardGroup items={statistics} columns={5} />

                <LegersFilter
                    filters={filters}
                    schools={schools}
                    academicYears={academicYears}
                    classes={classes}
                    subjects={subjects}
                    examTypes={examTypes}
                    schoolIsLocked={Boolean(filters.school_is_locked)}
                />

                <LegersTable
                    columns={columns}
                    rows={rows}
                />
            </div>
        </AppLayout>
    );
}
