import React from "react";
import Card from "@/Components/UI/Card";
import Select from "@/Components/UI/Select";
import { router } from "@inertiajs/react";

function ReportFilter({
    filters = {},
    academicYears = [],
    packages = [],
    subjects = [],
    classes = [],
}) {
    const updateFilter = (name, value) => {
        router.get(
            route("admin.reports.index"),
            {
                ...filters,
                [name]: value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <Card className="flex flex-wrap gap-4 p-4">
            <Select
                label="Tahun Ajaran"
                value={filters.academic_year_id ?? ""}
                onChange={(event) =>
                    updateFilter("academic_year_id", event.target.value)
                }
            >
                <option value="">Semua tahun</option>
                {academicYears.map((year) => (
                    <option key={year.id} value={year.id}>
                        {year.name}
                    </option>
                ))}
            </Select>

            <Select
                label="Paket Ujian"
                value={filters.package_id ?? ""}
                onChange={(event) =>
                    updateFilter("package_id", event.target.value)
                }
            >
                <option value="">Semua paket</option>
                {packages.map((examPackage) => (
                    <option key={examPackage.id} value={examPackage.id}>
                        {examPackage.code} - {examPackage.name}
                    </option>
                ))}
            </Select>
            <Select
                label="Mata Pelajaran"
                value={filters.subject_id ?? ""}
                onChange={(event) =>
                    updateFilter("subject_id", event.target.value)
                }
            >
                <option value="">Semua mata pelajaran</option>
                {subjects.map((subject) => (
                    <option key={subject.id} value={subject.id}>
                        {subject.name}
                    </option>
                ))}
            </Select>
            <Select
                label="Kelas"
                value={filters.class_id ?? ""}
                onChange={(event) =>
                    updateFilter("class_id", event.target.value)
                }
            >
                <option value="">Semua kelas</option>
                {classes.map((classItem) => (
                    <option key={classItem.id} value={classItem.id}>
                        {classItem.name}
                    </option>
                ))}
            </Select>
        </Card>
    );
}

export default ReportFilter;
