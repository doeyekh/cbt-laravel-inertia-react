import React from 'react'
import Badge from "@/Components/UI/Badge";

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
  )
}

export default OptionsReview