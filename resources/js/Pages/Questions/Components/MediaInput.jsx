import React, { useEffect, useState } from 'react';
import Modal from '@/Components/UI/Modal';
import Button from '@/Components/UI/Button';

const formatSize = (bytes) => {
    if (!bytes) {
        return '';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1
    );
    const value = bytes / 1024 ** exponent;

    return `${value.toFixed(exponent === 0 ? 0 : 1)} ${units[exponent]}`;
};

export default function MediaInput({
    id,
    label = 'Gambar / Audio',
    kind = 'image',
    accept,
    file,
    onChange,
    onRemove,
    existingUrl = null,
    existingName = null,
    removed = false,
    error = null,
    className = '',
}) {
    const [objectUrl, setObjectUrl] = useState(null);
    const [previewOpen, setPreviewOpen] = useState(false);

    const isImage = kind === 'image';

    useEffect(() => {
        if (!file) {
            setObjectUrl(null);

            return undefined;
        }

        const url = URL.createObjectURL(file);

        setObjectUrl(url);

        return () => URL.revokeObjectURL(url);
    }, [file]);

    const previewUrl = objectUrl ?? existingUrl;
    const hasPreview = Boolean(previewUrl);
    const isPendingReplacement = Boolean(file && existingUrl);

    return (
        <div className={className}>
            <label
                htmlFor={id}
                className="block text-[11px] font-label font-semibold text-on-surface-variant mb-1.5 uppercase tracking-wider"
            >
                {label}
            </label>

            <div className="rounded-xl border border-outline-variant/40 bg-surface-container-low p-3 space-y-3">
                {hasPreview && isImage && (
                    <button
                        type="button"
                        onClick={() => setPreviewOpen(true)}
                        title="Perbesar pratinjau gambar"
                        className="block w-full rounded-lg overflow-hidden border border-outline-variant/30 bg-white group"
                    >
                        <span className="relative block">
                            <img
                                src={previewUrl}
                                alt={label}
                                className={`w-full h-36 object-contain bg-surface-container-lowest transition-opacity ${
                                    removed ? 'opacity-40' : ''
                                }`}
                            />

                            {removed && (
                                <span className="absolute inset-0 flex items-center justify-center">
                                    <span className="inline-flex items-center gap-1.5 rounded-lg bg-error px-2.5 py-1 text-[10px] font-label font-semibold uppercase tracking-wider text-on-error">
                                        <span className="material-symbols-outlined text-[14px]">
                                            delete
                                        </span>
                                        Akan dihapus
                                    </span>
                                </span>
                            )}
                        </span>

                        <span className="flex items-center justify-center gap-1.5 border-t border-outline-variant/30 py-1.5 text-[10px] font-label font-semibold uppercase tracking-wider text-on-surface-variant group-hover:text-primary transition-colors">
                            <span className="material-symbols-outlined text-[14px]">
                                zoom_out_map
                            </span>
                            Perbesar
                        </span>
                    </button>
                )}

                {hasPreview && !isImage && (
                    <audio
                        src={previewUrl}
                        controls
                        className="w-full"
                        aria-label={label}
                    />
                )}

                <input
                    id={id}
                    type="file"
                    accept={accept}
                    onChange={(event) =>
                        onChange?.(event.target.files?.[0] ?? null)
                    }
                    className="block w-full text-[11px] font-body text-on-surface file:mr-3 file:rounded-lg file:border-0 file:bg-surface-container-high file:px-3 file:py-1.5 file:text-[11px] file:font-label file:font-semibold file:text-on-surface hover:file:bg-surface-container"
                />

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="min-w-0 flex-1 text-[10px] font-body text-on-surface-variant truncate">
                        {file ? (
                            <span className="text-primary">
                                File baru: {file.name}
                                {formatSize(file.size) && ` (${formatSize(file.size)})`}
                            </span>
                        ) : existingName ? (
                            <span className="truncate">
                                File tersimpan: {existingName}
                            </span>
                        ) : (
                            'Belum ada file.'
                        )}
                    </p>

                    {hasPreview && onRemove && (
                        <button
                            type="button"
                            onClick={onRemove}
                            title={
                                removed
                                    ? 'Batalkan penghapusan file'
                                    : 'Hapus file'
                            }
                            aria-label={
                                removed
                                    ? 'Batalkan penghapusan file'
                                    : 'Hapus file'
                            }
                            className="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/20 hover:text-error transition-colors"
                        >
                            <span className="material-symbols-outlined text-[18px]">
                                {removed ? 'undo' : 'delete'}
                            </span>
                        </button>
                    )}
                </div>

                {removed && (
                    <p className="text-[10px] font-body text-error">
                        File ini akan dihapus dari penyimpanan saat soal
                        disimpan.
                    </p>
                )}

                {isPendingReplacement && (
                    <p className="text-[10px] font-body text-on-surface-variant">
                        File baru akan menggantikan file tersimpan saat soal
                        disimpan.
                    </p>
                )}
            </div>

            {error && (
                <p className="mt-1 text-[10px] font-label text-error">{error}</p>
            )}

            <Modal
                isOpen={previewOpen}
                onClose={() => setPreviewOpen(false)}
                maxWidth="max-w-3xl"
            >
                <img
                    src={previewUrl ?? ''}
                    alt={label}
                    className="w-full max-h-[70vh] object-contain rounded-xl bg-surface-container-lowest"
                />

                <div className="flex items-center justify-between gap-3 mt-4">
                    <p className="min-w-0 flex-1 text-[11px] font-body text-on-surface-variant truncate">
                        {file?.name ?? existingName ?? label}
                    </p>

                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        onClick={() => setPreviewOpen(false)}
                    >
                        Tutup
                    </Button>
                </div>
            </Modal>
        </div>
    );
}
