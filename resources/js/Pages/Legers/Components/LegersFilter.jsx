
import { useEffect, useState } from "react";
import { router } from "@inertiajs/react";

import Card from "@/Components/UI/Card";
import Select from "@/Components/UI/Select";
import Input from "@/Components/UI/Input";
import Button from "@/Components/UI/Button";

import { route } from "@/Utils/route";
import { buildLegerQuery } from "./legerFilterUtils";

function LegersFilter({
    filters = {},
    schools = [],
    academicYears = [],
    classes = [],
    subjects = [],
    examTypes = [],
    schoolIsLocked = false,
}) {
    const [search, setSearch] = useState(filters.search ?? "");

    useEffect(() => {
        setSearch(filters.search ?? "");
    }, [filters.search]);

    const updateFilter = (name, value) => {
        router.get(
            route("admin.leger-grades.index"),
            {
                ...buildLegerQuery(filters),
                [name]: value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const handleSearch = () => {
        updateFilter("search", search.trim());
    };

    const handleSearchKeyDown = (event) => {
        if (event.key === "Enter") {
            handleSearch();
        }
    };

    return (
        <Card className="flex flex-wrap items-end gap-4 p-4">
            <Select
                id="leger-school"
                label="Sekolah"
                value={filters.school_id ?? ""}
                onChange={(event) =>
                    updateFilter("school_id", event.target.value)
                }
                className="min-w-[200px]"
                disabled={schoolIsLocked}
            >
                {!schoolIsLocked && <option value="">Semua sekolah</option>}

                {schools.map((school) => (
                    <option key={school.id} value={school.id}>
                        {school.name}
                    </option>
                ))}
            </Select>

            <Select
                id="leger-year"
                label="Tahun Ajaran"
                value={filters.academic_year_id ?? ""}
                onChange={(event) =>
                    updateFilter("academic_year_id", event.target.value)
                }
                className="min-w-[180px]"
                placeholder="Pilih tahun pelajaran..."
                required
            >
                {academicYears.map((year) => (
                    <option key={year.id} value={year.id}>
                        {year.semester_label
                            ? `${year.name} · ${year.semester_label}`
                            : year.name}
                    </option>
                ))}
            </Select>

            <Select
                id="leger-class"
                label="Kelas"
                value={filters.class_id ?? ""}
                onChange={(event) =>
                    updateFilter("class_id", event.target.value)
                }
                className="min-w-[160px]"
            >
                <option value="">Semua kelas</option>

                {classes.map((classItem) => (
                    <option key={classItem.id} value={classItem.id}>
                        {classItem.name}
                    </option>
                ))}
            </Select>

            <Select
                id="leger-subject"
                label="Mata Pelajaran"
                value={filters.subject_id ?? ""}
                onChange={(event) =>
                    updateFilter("subject_id", event.target.value)
                }
                className="min-w-[200px]"
            >
                <option value="">Semua mapel</option>

                {subjects.map((subject) => (
                    <option key={subject.id} value={subject.id}>
                        {subject.name}
                    </option>
                ))}
            </Select>

            <Select
                id="leger-exam-type"
                label="Jenis Ujian"
                value={filters.exam_type_id ?? ""}
                onChange={(event) =>
                    updateFilter("exam_type_id", event.target.value)
                }
                className="min-w-[180px]"
                placeholder="Pilih jenis ujian..."
                required
            >
                {examTypes.map((examType) => (
                    <option key={examType.id} value={examType.id}>
                        {examType.name}
                    </option>
                ))}
            </Select>

            <Input
                id="leger-search"
                label="Cari Siswa"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                onKeyDown={handleSearchKeyDown}
                placeholder="Nama / NISN / no. peserta"
                className="w-64"
            />

            <Button
                variant="secondary"
                onClick={handleSearch}
            >
                Cari
            </Button>
        </Card>
    );
}

export default LegersFilter;
