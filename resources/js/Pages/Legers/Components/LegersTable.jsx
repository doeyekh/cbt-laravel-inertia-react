import React from "react";
import Card from "@/Components/UI/Card";
import Table, {
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
} from "@/Components/UI/Table";
import { formatPercentage } from '@/Utils/formatPercentage';

function LegersTable({ columns,rows }) {
    return (
        <Card padding="0">
            <div className="px-6 py-4 bg-surface-container-low/60">
                        <h3 className="font-headline font-semibold text-on-surface text-lg">
                            Rekap Nilai per Siswa
                        </h3>
                        <p className="text-[11px] text-on-surface-variant mt-0.5">
                            Nilai adalah rata-rata persentase attempt yang sudah
                            dinilai. Kolom mengikuti mata pelajaran yang tersedia.
                        </p>
                    </div>
            <Table className="w-full min-w-[1200px] text-left">
                <TableHead className="bg-surface-container-low text-xs font-label uppercase tracking-wider text-on-surface-variant">
                    <TableHeadCell>No</TableHeadCell>
                    <TableHeadCell>No. Peserta</TableHeadCell>
                    <TableHeadCell>NISN</TableHeadCell>
                    <TableHeadCell>Nama Siswa</TableHeadCell>
                    <TableHeadCell>Kelas</TableHeadCell>
                    <TableHeadCell>Sekolah</TableHeadCell>

                    {columns.map((column) => (
                        <TableHeadCell key={column.id} className="text-right">
                            {column.name}
                        </TableHeadCell>
                    ))}

                    <TableHeadCell className="text-right">
                        Rata-rata
                    </TableHeadCell>
                    <TableHeadCell className="text-right">Ujian</TableHeadCell>
                    <TableHeadCell className="text-right">Tuntas</TableHeadCell>
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
                                const value = row.scores?.[String(column.id)];

                                return (
                                    <TableCell
                                        key={column.id}
                                        className="text-right"
                                    >
                                        {value === null || value === undefined
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
                                Belum ada nilai yang cocok dengan filter.
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </Card>
    );
}

export default LegersTable;
