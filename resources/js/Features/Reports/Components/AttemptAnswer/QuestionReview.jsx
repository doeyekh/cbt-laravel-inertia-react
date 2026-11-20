import React from 'react'
import Badge from "@/Components/UI/Badge";
import Card from "@/Components/UI/Card";
import OptionsReview from "@/Features/Reports/Components/AttemptAnswer/OptionsReview";
import PairsReview from "@/Features/Reports/Components/AttemptAnswer/PairsReview";
import BlanksReview from "@/Features/Reports/Components/AttemptAnswer/BlanksReview";
import AnswerStatus from "@/Features/Reports/Components/AttemptAnswer/AnswerStatus";

function QuestionReview({ question }) {

    function formatScore(value) {
    if (value === null || value === undefined) {
        return "-";
    }

    return Number(value).toLocaleString("id-ID", { maximumFractionDigits: 2 });
}
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
  )
}

export default QuestionReview