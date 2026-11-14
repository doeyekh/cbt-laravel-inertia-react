import React from 'react';

function ActionButton({ icon, label, onClick, className = '' }) {
    return (
        <button
            type="button"
            onClick={(event) => {
                event.stopPropagation();
                onClick();
            }}
            title={label}
            aria-label={label}
            className={`p-2 rounded-lg transition-colors ${className}`}
        >
            <span className="material-symbols-outlined text-[18px]">{icon}</span>
        </button>
    );
}

export default function RowActions({
    onShow,
    onEdit,
    onDelete,
    showLabel = 'Lihat detail',
    editLabel = 'Edit',
    deleteLabel = 'Hapus',
    className = '',
}) {
    return (
        <div className={`inline-flex items-center gap-1 ${className}`}>
            {onShow && (
                <ActionButton
                    icon="visibility"
                    label={showLabel}
                    onClick={onShow}
                    className="text-on-surface-variant hover:bg-surface-container"
                />
            )}

            {onEdit && (
                <ActionButton
                    icon="edit"
                    label={editLabel}
                    onClick={onEdit}
                    className="text-primary hover:bg-primary/10"
                />
            )}

            {onDelete && (
                <ActionButton
                    icon="delete"
                    label={deleteLabel}
                    onClick={onDelete}
                    className="text-error hover:bg-error-container/20"
                />
            )}
        </div>
    );
}
