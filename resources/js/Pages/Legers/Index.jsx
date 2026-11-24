import React, { useState } from "react";
import { router } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import Button from "@/Components/UI/Button";
import Card from "@/Components/UI/Card";
import CardHeaderPage from "@/Components/Layout/CardHeaderPage";
import Input from "@/Components/UI/Input";
import Select from "@/Components/UI/Select";
import Table, {
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
} from "@/Components/UI/Table";
import { route } from "@/Utils/route";
import { formatPercentage } from "@/Utils/formatPercentage";
import StatCardGroup from "@/Components/UI/stat-card-group";
import useLegersStatistics from "./Components/useLegersStatistics";

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
    const [search, setSearch] = useState(filters.search ?? "");

    const query = {
        school_id: filters.school_id || undefined,
        academic_year_id: filters.academic_year_id || undefined,
        class_id: filters.class_id || undefined,
        subject_id: filters.subject_id || undefined,
        exam_type_id: filters.exam_type_id || undefined,
        search: filters.search || undefined,
    };

    const updateFilter = (name, value) => {
        router.get(
            route("admin.leger-grades.index"),
            {
                ...query,
                [name]: value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const openExport = (routeName) => {
        window.open(
            route(routeName,query),
            '_blank',
            'noopener,noreferrer'
        )
    };

    const statistic = useLegersStatistics({
        summary
    })

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
                        onClick={() => openExport("admin.leger-grades.pdf")}
                    >
                        <span className="material-symbols-outlined text-[18px]">
                            picture_as_pdf
                        </span>
                        PDF
                    </Button>
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => openExport("admin.leger-grades.export")}
                    >
                        <span className="material-symbols-outlined text-[18px]">
                            table_view
                        </span>
                        Excel
                    </Button>
                </CardHeaderPage>
                <div>

                    <StatCardGroup items={statistic} columns={5}/>
                </div>

                <Card className="flex flex-wrap items-end gap-4 p-4">
                    <Select
                        id="leger-school"
                        label="Sekolah"
                        value={filters.school_id ?? ""}
                        onChange={(event) =>
                            updateFilter("school_id", event.target.value)
                        }
                        className="min-w-[200px]"
                    >
                        <option value="">Semua sekolah</option>
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
                    >
                        <option value="">Semua tahun</option>
                        {academicYears.map((year) => (
                            <option key={year.id} value={year.id}>
                                {year.name}
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
                    >
                        <option value="">Semua jenis ujian</option>
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
                        onKeyDown={(event) => {
                            if (event.key === "Enter") {
                                updateFilter("search", search);
                            }
                        }}
                        placeholder="Nama / NISN / no. peserta"
                        className="w-64"
                    />

                    <Button
                        variant="secondary"
                        onClick={() => updateFilter("search", search)}
                    >
                        Cari
                    </Button>
                </Card>

                <Card padding="p-0">
                    <div className="px-6 py-4 bg-surface-container-low/60">
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            Rekap Nilai per Siswa
                        </h3>
                        <p className="text-[11px] text-on-surface-variant mt-0.5">
                            Nilai adalah rata-rata persentase attempt yang sudah
                            dinilai. Kolom mengikuti mata pelajaran yang tersedia.
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <Table className="w-full min-w-[1200px] text-left">
                            <TableHead className="bg-surface-container-low text-xs font-label uppercase tracking-wider text-on-surface-variant">
                                <TableHeadCell>No</TableHeadCell>
                                <TableHeadCell>No. Peserta</TableHeadCell>
                                <TableHeadCell>NISN</TableHeadCell>
                                <TableHeadCell>Nama Siswa</TableHeadCell>
                                <TableHeadCell>Kelas</TableHeadCell>
                                <TableHeadCell>Sekolah</TableHeadCell>

                                {columns.map((column) => (
                                    <TableHeadCell
                                        key={column.id}
                                        className="text-right"
                                    >
                                        {column.name}
                                    </TableHeadCell>
                                ))}

                                <TableHeadCell className="text-right">
                                    Rata-rata
                                </TableHeadCell>
                                <TableHeadCell className="text-right">
                                    Ujian
                                </TableHeadCell>
                                <TableHeadCell className="text-right">
                                    Tuntas
                                </TableHeadCell>
                            </TableHead>

                            <TableBody>
                                {rows.map((row, index) => (
                                    <TableRow key={row.participant_id}>
                                        <TableCell>{index + 1}</TableCell>
                                        <TableCell>
                                            {row.participant_number ?? "-"}
                                        </TableCell>
                                        <TableCell>{row.nisn ?? "-"}</TableCell>
                                        <TableCell className="font-semibold">
                                            {row.name ?? "-"}
                                        </TableCell>
                                        <TableCell>{row.class ?? "-"}</TableCell>
                                        <TableCell>{row.school ?? "-"}</TableCell>

                                        {columns.map((column) => {
                                            const value =
                                                row.scores?.[String(column.id)];

                                            return (
                                                <TableCell
                                                    key={column.id}
                                                    className="text-right"
                                                >
                                                    {value === null ||
                                                    value === undefined
                                                        ? "-"
                                                        : formatPercentage(value)}
                                                </TableCell>
                                            );
                                        })}

                                        <TableCell className="text-right font-semibold">
                                            {formatPercentage(row.average)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {row.completed_count ?? 0}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {row.passed_count ?? 0}
                                        </TableCell>
                                    </TableRow>
                                ))}

                                {rows.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6 + columns.length + 3}
                                            className="px-5 py-12 text-center text-sm text-on-surface-variant"
                                        >
                                            Belum ada nilai yang cocok dengan
                                            filter.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}