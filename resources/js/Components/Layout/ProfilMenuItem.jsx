import React from "react";
import Button from '@/Components/UI/Button';

function ProfilMenuItem({
    icon,
    label,
    onClick,
    danger = false,
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            onClick={onClick}
            className={`w-full text-left px-4 py-2.5 text-xs font-label flex items-center gap-3 ${
                danger
                    ? "text-error hover:bg-error-container/10"
                    : "text-on-surface-variant hover:bg-surface-container-low"
            }`}
        >
            <span className="material-symbols-outlined text-[18px]">
                {icon}
            </span>

            {label}
        </Button>
    );
}

export default ProfilMenuItem;
