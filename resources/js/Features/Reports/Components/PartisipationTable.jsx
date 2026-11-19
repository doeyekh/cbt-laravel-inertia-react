import React, { useState } from "react";
import Card from "@/Components/UI/Card";
import Button from "@/Components/UI/Button";
import { formatPercentage } from "@/Utils/formatPercentage";
import Table, {
    TableHeadCell,
    TableHead,
    TableBody,
    TableRow,
    TableCell,
} from "@/Components/UI/Table";
import { router } from "@inertiajs/react";
import EssayGradeModal from "@/Features/Monitoring/Components/EssayGradeModal";

function PartisipationTable({ participants,schedule }) {
    const [gradeTarget, setGradeTarget] = useState(null);

    const closeGradeModal = () => {
            setGradeTarget(null);
            router.reload({
                only: [
                    "participants",
                    "summary",
                    "distribution",
                    "questionAnalysis",
                ],
            });
        };
    return (
        <Card padding="p-0">
            <div className="overflow-x-auto">
                <Table>
                    <TableHead className="bg-surface-container-low text-center">
                            <TableHeadCell>Peserta</TableHeadCell>
                            <TableHeadCell>Status</TableHeadCell>
                            <TableHeadCell>Skor</TableHeadCell>
                            <TableHeadCell>Nilai</TableHeadCell>   
                            <TableHeadCell>Waktu</TableHeadCell>
                            <TableHeadCell>Aksi</TableHeadCell>
                    </TableHead>
                    <TableBody>
                        {participants.map((participant) => (
                            <TableRow key={participant.attempt_id}>
                                <TableCell className="px-5 py-4">
                                    <p className="font-semibold">{participant.name}</p>
                                    <p className="mt-1 text-xs text-on-surface-variant">
                                        {participant.participant_number} · {participant.nisn}
                                    </p>
                                </TableCell>
                                <TableCell className="px-5 py-4">{participant.status}</TableCell>
                                <TableCell className="px-5 py-4 text-right">
                                    {participant.score ?? "-"} / {participant.max_score ?? "-"}
                                </TableCell>
                                <TableCell className="px-5 py-4 text-right font-semibold">
                                    {formatPercentage(participant.percentage)}
                                </TableCell>
                                <TableCell className="px-5 py-4 text-right">
                                    {Math.floor((participant.time_spent_seconds ?? 0) / 60)} menit
                                </TableCell>
                                <TableCell className="px-5 py-4">
                                    <div className="flex flex-wrap items-center justify-end gap-2">
                                        <Button variant="outline" size="sm" onClick={() => router.visit(participant.answers_url)}>
                                            <span className="material-symbols-outlined text-[16px]" aria-hidden="true">
                                                visibility  
                                            </span>
                                            Lihat Jawaban
                                        </Button>
                                        {participant.grade_url && (
                                            <Button variant="secondary" size="sm" onClick={() => setGradeTarget(participant)}>
                                                <span className="material-symbols-outlined text-[16px]" aria-hidden="true">
                                                    grading
                                                </span>
                                                Koreksi Esai
                                                {participant.pending_essay_count > 0 && (
                                                    <span className="rounded-full bg-error px-1.5 py-0.5 text-[10px] font-bold text-on-error">
                                                        {participant.pending_essay_count}
                                                    </span>
                                                )}
                                            </Button>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                        {participants.length === 0 && (
                            <TableRow>
                                <TableCell colSpan="6" className="px-5 py-10 text-center text-on-surface-variant">
                                    Belum ada peserta yang memulai ujian.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {gradeTarget && (
                    <EssayGradeModal
                        isOpen
                        onClose={closeGradeModal}
                        student={{ ...gradeTarget, subject: schedule.subject }}
                    />
                )}
        </Card>
    );
}


export default PartisipationTable;
