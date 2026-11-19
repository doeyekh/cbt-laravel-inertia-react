import React from "react";
import { router } from "@inertiajs/react";
import Card from "@/Components/UI/Card";
import Table, {
    TableHead,
    TableBody,
    TableHeadCell,
    TableRow,
    TableCell,
} from "@/Components/UI/Table";
import { route } from "@/Utils/route";
import { formatPercentage } from "@/Utils/formatPercentage";
import Button from "@/Components/UI/Button";


function ReportScheaculeTable({ reports }) {
    return (
        <Card padding="p-0">
            <div className="overflow-x-auto">
                <Table className="w-full min-w-[980px] text-left">
                    <TableHead className="bg-surface-container-low text-xs font-label uppercase tracking-wider text-on-surface-variant">
                        <TableHeadCell>Ujian</TableHeadCell>
                        <TableHeadCell>Kelas / Sesi</TableHeadCell>
                        <TableHeadCell>Tanggal</TableHeadCell>
                        <TableHeadCell className="text-right">
                            Peserta
                        </TableHeadCell>
                        <TableHeadCell className="text-right">
                            Selesai
                        </TableHeadCell>
                        <TableHeadCell className="text-right">
                            Rata-rata
                        </TableHeadCell>
                        <TableHeadCell className="text-right">
                            Tuntas
                        </TableHeadCell>
                        <TableHeadCell className="text-right">
                            Aksi
                        </TableHeadCell>
                    </TableHead>

                    <TableBody>
                        {reports.map((report) => (
                            <TableRow key={report.id}>
                                {/* Ujian */}
                                <TableCell>
                                    <div>
                                        <p className="font-semibold text-on-surface">
                                            {report.name}
                                        </p>
                                        <p className="mt-1 text-xs text-on-surface-variant">
                                            {report.code} · {report.subject}
                                        </p>
                                    </div>
                                </TableCell>

                                {/* Kelas / Sesi */}
                                <TableCell>
                                    <div>
                                        <p className="text-on-surface">
                                            {report.class}
                                        </p>
                                        <p className="mt-1 text-xs text-on-surface-variant">
                                            {report.session ?? "-"} ·{" "}
                                            {report.room ?? "-"}
                                        </p>
                                    </div>
                                </TableCell>

                                {/* Tanggal */}
                                <TableCell>
                                    <div>
                                        <p className="text-on-surface">
                                            {report.exam_date}
                                        </p>
                                        <p className="mt-1 text-xs text-on-surface-variant">
                                            {report.open_time?.slice(0, 5) ??
                                                "--:--"}
                                            {" – "}
                                            {report.close_time?.slice(0, 5) ??
                                                "--:--"}
                                        </p>
                                    </div>
                                </TableCell>

                                {/* Peserta */}
                                <TableCell className="text-right font-semibold">
                                    {report.participant_count}
                                </TableCell>

                                {/* Selesai */}
                                <TableCell className="text-right">
                                    {report.completed_count}
                                </TableCell>

                                {/* Rata-rata */}
                                <TableCell className="text-right font-semibold">
                                    {formatPercentage(
                                        report.average_percentage,
                                    )}
                                </TableCell>

                                {/* Tuntas */}
                                <TableCell className="text-right">
                                    {report.passed_count}
                                </TableCell>

                                {/* Aksi */}
                                <TableCell className="text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.visit(
                                                route(
                                                    "admin.reports.show",
                                                    report.id,
                                                ),
                                            )
                                        }
                                    >
                                        Lihat
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}

                        {/* Data kosong */}
                        {reports.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={8}
                                    className="px-5 py-12 text-center text-sm text-on-surface-variant"
                                >
                                    Belum ada jadwal ujian yang cocok dengan
                                    filter.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </Card>
    );
}

export default ReportScheaculeTable;
