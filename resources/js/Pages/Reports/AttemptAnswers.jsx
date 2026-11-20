import React from "react";
import { router } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import Button from "@/Components/UI/Button";
import Card from "@/Components/UI/Card";
import { route } from "@/Utils/route";
import { formatPercentage } from "@/Utils/formatPercentage";
import CardHeaderPage from "@/Components/Layout/CardHeaderPage";
import QuestionReview from "@/Features/Reports/Components/AttemptAnswer/QuestionReview";
import { formatDateTime } from "@/Utils/formatDateTime";




export default function AttemptAnswers({
    schedule = {},
    attempt = {},
    questions = [],
}) {
    const summaryItems = [
        ["Nilai Akhir", formatPercentage(attempt.percentage)],
        ["Benar", attempt.correct_count ?? 0],
        ["Salah", attempt.wrong_count ?? 0],
        ["Esai Belum Dinilai", attempt.pending_essay_count ?? 0],
        ["Status", attempt.status ?? "-"],
    ];

    return (
        <AppLayout>
            <div className="mx-auto w-full max-w-[1200px] space-y-6">
                <CardHeaderPage
                    subTitle="Jawaban Peserta"
                    title={attempt.student_name || "Peserta"}
                    description={`${attempt.participant_number || "-"} · ${attempt.nisn || "-"} · ${schedule.subject || "-"} · ${schedule.class || "-"}`}
                >
                    <div className="flex flex-wrap gap-3">
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.visit(
                                    route("admin.reports.show", schedule.id),
                                )
                            }
                        >
                            Kembali ke Laporan
                        </Button>
                        {attempt.essay_url && (
                            <Button
                                variant="secondary"
                                onClick={() => router.visit(attempt.essay_url)}
                            >
                                <span
                                    className="material-symbols-outlined text-[18px]"
                                    aria-hidden="true"
                                >
                                    grading
                                </span>
                                Koreksi Esai
                                {attempt.pending_essay_count > 0
                                    ? ` (${attempt.pending_essay_count})`
                                    : ""}
                            </Button>
                        )}
                    </div>
                </CardHeaderPage>

                <section className="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    {summaryItems.map(([label, value]) => (
                        <Card key={label} padding="p-5">
                            <p className="text-xs font-label text-on-surface-variant">
                                {label}
                            </p>
                            <p className="mt-2 text-xl font-bold text-on-surface">
                                {value}
                            </p>
                        </Card>
                    ))}
                </section>

                <section className="space-y-4">
                    {questions.map((question, index) => (
                        <QuestionReview
                            key={`${question.number}-${question.id ?? index}`}
                            question={question}
                        />
                    ))}
                    {questions.length === 0 && (
                        <Card padding="p-8">
                            <p className="text-center text-sm text-on-surface-variant">
                                Belum ada jawaban yang dapat ditampilkan untuk
                                peserta ini.
                            </p>
                        </Card>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
