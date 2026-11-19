import React from "react";
import Table, {
    TableHeadCell,
    TableHead,
    TableBody,
    TableRow,
    TableCell,
} from "@/Components/UI/Table";
import Card from "@/Components/UI/Card";
import { formatPercentage } from "@/Utils/formatPercentage";

function TableShowReport({ questionAnalysis }) {
    return (
        <Card padding="p-5" className="xl:col-span-2">
            <h2 className="font-headline text-lg font-bold text-on-surface">
                Analisis Butir Soal
            </h2>
            <div className="mt-4 overflow-x-auto">
                <Table className="w-full min-w-[620px] text-left text-xl">
                    <TableHead>
                        <TableHeadCell className="w-12 text-center">
                            No
                        </TableHeadCell>
                        <TableHeadCell className="w-30">Tipe</TableHeadCell>
                        <TableHeadCell>Soal</TableHeadCell>
                        <TableHeadCell className="w-10">Di jawab</TableHeadCell>
                        <TableHeadCell className="w-10">Benar</TableHeadCell>
                        <TableHeadCell className="w-10">Akurasi</TableHeadCell>
                    </TableHead>
                    <TableBody>
                        {questionAnalysis.map((question) => (
                            <TableRow
                                key={question.id}
                                className="even:bg-surface-container-low/40 hover:bg-primary/5 "
                            >
                                <TableCell className="text-center">
                                    {question.number}
                                </TableCell>
                                <TableCell>{question.type}</TableCell>
                                <TableCell>{question.body || "-"}</TableCell>
                                <TableCell className="text-right">
                                    {question.answered_count}
                                </TableCell>
                                <TableCell className="text-right">
                                    {question.correct_count}
                                </TableCell>
                                <TableCell className="text-right font-semibold">
                                    {formatPercentage(
                                        question.accuracy_percentage,
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                        {questionAnalysis.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center">
                                    Belum ada butir soal pada bank soal jadwal
                                    ini.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </Card>
    );
}

export default TableShowReport;
