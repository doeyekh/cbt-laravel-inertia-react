import React from 'react'
import Table, {
    TableHeadCell,
    TableHead,
    TableBody,
    TableRow,
    TableCell,
} from "@/Components/UI/Table";


function PairsReview({ pairs }) {
    return (
        <div className="overflow-x-auto">
            <Table>
                <TableHead>
                    <TableHeadCell className="w-12 text-center">No</TableHeadCell>
                    <TableHeadCell>Pernyataan</TableHeadCell>
                    <TableHeadCell>Jawaban Peserta</TableHeadCell>
                    <TableHeadCell>Kunci</TableHeadCell>
                </TableHead>
                <TableBody>
                    {pairs.map((pair, index) => (
                        <TableRow
                            key={`${pair.left}-${index}`}
                            className="even:bg-surface-container-low/40 hover:bg-primary/5"
                        >
                            <TableCell className="text-center">
                                {index + 1}
                            </TableCell>
                            <TableCell>{pair.left}</TableCell>
                            <TableCell
                                className={`font-medium ${pair.is_correct ? "text-primary" : "text-error"}`}
                            >
                                {pair.selected_right || "—"}
                            </TableCell>
                            <TableCell>{pair.correct_right}</TableCell>
                        </TableRow>
                    ))}
                    {pairs.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={4} className="text-center">
                                Belum ada pasangan jawaban pada soal ini.
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </div>
    )
}

export default PairsReview