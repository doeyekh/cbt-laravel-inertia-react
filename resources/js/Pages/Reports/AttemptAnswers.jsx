import React from "react";
import { router } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import Badge from "@/Components/UI/Badge";
import Button from "@/Components/UI/Button";
import Card from "@/Components/UI/Card";
import { route } from "@/Utils/route";
import { formatPercentage } from "@/Utils/formatPercentage";
import CardHeaderPage from "@/Components/Layout/CardHeaderPage";

function formatScore(value) {
    if (value === null || value === undefined) {
        return "-";
    }

    return Number(value).toLocaleString("id-ID", { maximumFractionDigits: 2 });
}

function formatDateTime(value) {
    if (!value) {
        return "-";
    }

    return new Date(value).toLocaleString("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    });
}

function AnswerStatus({ question }) {
    if (!question.is_answered) {
        return <Badge variant="neutral">Tidak dijawab</Badge>;
    }

    if (question.is_correct === true) {
        return <Badge variant="success">Benar</Badge>;
    }

    if (question.is_correct === false) {
        return <Badge variant="error">Salah</Badge>;
    }

    return <Badge variant="warning">Belum dinilai</Badge>;
}

function OptionsReview({ options }) {
    return (
        <ul className="space-y-2">
            {options.map((option, index) => (
                <li
                    key={`${option.label}-${index}`}
                    className={[
                        "flex items-start gap-3 rounded-xl border px-3 py-2 text-sm",
                        option.is_correct
                            ? "border-primary/40 bg-primary/5"
                            : option.is_selected
                              ? "border-error/40 bg-error-container/40"
                              : "border-outline-variant/40 bg-surface-container-lowest",
                    ].join(" ")}
                >
                    <span className="font-bold text-on-surface">
                        {option.label}.
                    </span>
                    <span className="flex-1 whitespace-pre-wrap text-on-surface">
                        {option.text}
                    </span>
                    <span className="flex shrink-0 flex-wrap justify-end gap-1">
                        {option.is_selected && (
                            <Badge variant="secondary">Dipilih</Badge>
                        )}
                        {option.is_correct && (
                            <Badge variant="success">Kunci</Badge>
                        )}
                    </span>
                </li>
            ))}
        </ul>
    );
}

function PairsReview({ pairs }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[560px] text-left text-sm">
                <thead className="text-xs uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th className="pb-2 pr-3">Pernyataan</th>
                        <th className="pb-2 pr-3">Jawaban Peserta</th>
                        <th className="pb-2">Kunci</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-surface-container-high">
                    {pairs.map((pair, index) => (
                        <tr key={`${pair.left}-${index}`}>
                            <td className="py-2 pr-3 text-on-surface">
                                {pair.left}
                            </td>
                            <td
                                className={`py-2 pr-3 font-medium ${pair.is_correct ? "text-primary" : "text-error"}`}
                            >
                                {pair.selected_right || "—"}
                            </td>
                            <td className="py-2 text-on-surface-variant">
                                {pair.correct_right}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function BlanksReview({ blanks }) {
    return (
        <ol className="space-y-2">
            {blanks.map((blank) => (
                <li
                    key={blank.order}
                    className="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm"
                >
                    <p className="font-semibold text-on-surface">
                        Isian {blank.order}
                    </p>
                    <p
                        className={`mt-1 ${blank.is_correct ? "text-primary" : "text-error"}`}
                    >
                        Jawaban peserta: {blank.student_answer || "—"}
                    </p>
                    <p className="mt-1 text-xs text-on-surface-variant">
                        Kunci:{" "}
                        {blank.accepted_answers.length > 0
                            ? blank.accepted_answers.join(" / ")
                            : "—"}
                    </p>
                </li>
            ))}
        </ol>
    );
}

function QuestionReview({ question }) {
    return (
        <Card padding="p-5" className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-label font-semibold uppercase tracking-[0.16em] text-on-surface-variant">
                        Soal {question.number} · {question.type_label}
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <AnswerStatus question={question} />
                    <span className="text-xs font-semibold text-on-surface">
                        {formatScore(question.score_awarded)} /{" "}
                        {formatScore(question.score)} poin
                    </span>
                </div>
            </div>

            <div
                className="text-sm leading-relaxed text-on-surface"
                dangerouslySetInnerHTML={{ __html: question.body || "" }}
            />

            {question.options.length > 0 && (
                <OptionsReview options={question.options} />
            )}
            {question.pairs.length > 0 && (
                <PairsReview pairs={question.pairs} />
            )}
            {question.blanks.length > 0 && (
                <BlanksReview blanks={question.blanks} />
            )}

            {question.type === "esai" && (
                <div className="rounded-xl bg-surface-container-low p-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
                        Jawaban peserta
                    </p>
                    <p className="mt-2 whitespace-pre-wrap text-sm text-on-surface">
                        {question.text_answer || "Tidak dijawab."}
                    </p>
                </div>
            )}
        </Card>
    );
}

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
