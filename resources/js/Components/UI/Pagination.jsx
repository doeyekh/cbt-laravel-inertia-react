import React from 'react';
import Button from '@/Components/UI/Button';

const buildPages = (currentPage, lastPage) => {
    if (lastPage <= 7) {
        return Array.from({ length: lastPage }, (_, index) => index + 1);
    }

    if (currentPage <= 4) {
        return [1, 2, 3, 4, 5, 'end-ellipsis', lastPage];
    }

    if (currentPage >= lastPage - 3) {
        return [
            1,
            'start-ellipsis',
            lastPage - 4,
            lastPage - 3,
            lastPage - 2,
            lastPage - 1,
            lastPage,
        ];
    }

    return [
        1,
        'start-ellipsis',
        currentPage - 1,
        currentPage,
        currentPage + 1,
        'end-ellipsis',
        lastPage,
    ];
};

export default function Pagination({
    currentPage = 1,
    lastPage = 1,
    onPageChange = () => {},
    from = 0,
    to = 0,
    total = 0,
    itemLabel = 'data',
    className = '',
}) {
    const safeLastPage = Math.max(1, lastPage);
    const pages = buildPages(Math.min(currentPage, safeLastPage), safeLastPage);
    const canGoPrevious = currentPage > 1;
    const canGoNext = currentPage < safeLastPage;

    return (
        <div
            className={`
                flex flex-col sm:flex-row items-center justify-between gap-3
                text-xs font-label text-on-surface-variant
                ${className}
            `}
        >
            <p>
                Menampilkan{' '}
                <span className="font-semibold text-on-surface">{from}</span> -{' '}
                <span className="font-semibold text-on-surface">{to}</span> dari{' '}
                <span className="font-semibold text-on-surface">{total}</span> {itemLabel}
            </p>

            {safeLastPage > 1 && (
                <nav className="flex items-center gap-1" aria-label="Navigasi halaman">
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => onPageChange(currentPage - 1)}
                        disabled={!canGoPrevious}
                        aria-label="Halaman sebelumnya"
                    >
                        <span className="material-symbols-outlined text-[16px]">
                            chevron_left
                        </span>
                    </Button>

                    {pages.map((page) => {
                        if (typeof page === 'string') {
                            return (
                                <span
                                    key={page}
                                    aria-hidden="true"
                                    className="px-1 text-on-surface-variant"
                                >
                                    &hellip;
                                </span>
                            );
                        }

                        const isActive = page === currentPage;

                        return (
                            <Button
                                key={page}
                                variant={isActive ? 'primary' : 'ghost'}
                                size="sm"
                                onClick={() => onPageChange(page)}
                                aria-label={`Halaman ${page}`}
                                aria-current={isActive ? 'page' : undefined}
                                className={`min-w-8 ${isActive ? '' : 'hover:bg-surface-container'}`}
                            >
                                {page}
                            </Button>
                        );
                    })}

                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => onPageChange(currentPage + 1)}
                        disabled={!canGoNext}
                        aria-label="Halaman berikutnya"
                    >
                        <span className="material-symbols-outlined text-[16px]">
                            chevron_right
                        </span>
                    </Button>
                </nav>
            )}
        </div>
    );
}
