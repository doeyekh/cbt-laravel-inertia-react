import React from 'react'

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
  )
}

export default BlanksReview