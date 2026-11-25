import React, { useCallback, useEffect, useRef, useState } from 'react';
import Button from '@/Components/UI/Button';
import { typesetMath } from '@/Utils/mathjax';

const toolbarActions = [
    { command: 'bold', icon: 'format_bold', title: 'Tebal' },
    { command: 'italic', icon: 'format_italic', title: 'Miring' },
    { command: 'underline', icon: 'format_underlined', title: 'Garis bawah' },
    { command: 'insertUnorderedList', icon: 'format_list_bulleted', title: 'Daftar' },
    { command: 'insertOrderedList', icon: 'format_list_numbered', title: 'Daftar angka' },
    { command: 'justifyLeft', icon: 'format_align_left', title: 'Rata kiri' },
    { command: 'justifyCenter', icon: 'format_align_center', title: 'Rata tengah' },
    { command: 'justifyRight', icon: 'format_align_right', title: 'Rata kanan' },
    { command: 'removeFormat', icon: 'format_clear', title: 'Bersihkan format' },
];

const escapeHtml = (value) =>
    String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

export default function RichTextEditor({
    id,
    value = '',
    onChange,
    placeholder = 'Tuliskan teks...',
    minHeight = 160,
    className = '',
}) {
    const editorRef = useRef(null);
    const previewRef = useRef(null);
    const savedRangeRef = useRef(null);
    const savedCodeRangeRef = useRef(null);
    const isFocusedRef = useRef(false);

    const [formulaOpen, setFormulaOpen] = useState(false);
    const [formula, setFormula] = useState('');
    const [codeOpen, setCodeOpen] = useState(false);
    const [code, setCode] = useState('');

    const serialize = useCallback(() => {
        const editor = editorRef.current;

        if (!editor) {
            return '';
        }

        const clone = editor.cloneNode(true);

        clone.querySelectorAll('.math-node').forEach((node) => {
            const tex = node.getAttribute('data-latex') ?? '';

            node.textContent = `\\(${tex}\\)`;
        });

        return clone.innerHTML;
    }, []);

    const applyValue = useCallback((html) => {
            const editor = editorRef.current;

            if (!editor) {
                return;
            }

            editor.innerHTML = html ?? '';
            typesetMath(editor);
        },
        []
    );
    useEffect(() => {
        const editor = editorRef.current;

        if (!editor || isFocusedRef.current) {
            return;
        }

        if (serialize() !== (value ?? '')) {
            applyValue(value);
        }
    }, [value, serialize, applyValue]);

    useEffect(() => {
        if (!formulaOpen) {
            return;
        }

        const timeout = setTimeout(() => {
            const preview = previewRef.current;

            if (!preview) {
                return;
            }

            preview.innerHTML = formula.trim()
                ? `\\(${escapeHtml(formula.trim())}\\)`
                : '';
            typesetMath(preview);
        }, 300);

        return () => clearTimeout(timeout);
    }, [formula, formulaOpen]);

    const runCommand = (command) => {
        const editor = editorRef.current;

        editor?.focus();
        document.execCommand(command);
        onChange?.(serialize());
    };

    const handleInput = () => {
        onChange?.(serialize());
    };

    const openFormula = () => {
        const selection = window.getSelection();

        if (
            selection &&
            selection.rangeCount > 0 &&
            editorRef.current?.contains(selection.anchorNode)
        ) {
            savedRangeRef.current = selection.getRangeAt(0).cloneRange();
        }

        setFormula('');
        setFormulaOpen(true);
    };

    const closeFormula = () => {
        setFormulaOpen(false);
        setFormula('');
        editorRef.current?.focus();
    };

    const insertFormula = () => {
        const editor = editorRef.current;
        const tex = formula.trim();

        if (!editor || !tex) {
            return;
        }

        editor.focus();

        const selection = window.getSelection();

        if (selection && savedRangeRef.current) {
            selection.removeAllRanges();
            selection.addRange(savedRangeRef.current);
        }

        const node = document.createElement('span');
        node.className = 'math-node';
        node.setAttribute('data-latex', tex);
        node.textContent = `\\(${tex}\\)`;

        const activeSelection = window.getSelection();

        if (
            activeSelection &&
            activeSelection.rangeCount > 0 &&
            editor.contains(activeSelection.anchorNode)
        ) {
            const range = activeSelection.getRangeAt(0);

            range.deleteContents();
            range.insertNode(node);
            range.setStartAfter(node);
            range.collapse(true);
            activeSelection.removeAllRanges();
            activeSelection.addRange(range);
        } else {
            editor.appendChild(node);
        }

        typesetMath(node);
        setFormulaOpen(false);
        setFormula('');
        onChange?.(serialize());
    };

    const openCode = () => {
        const selection = window.getSelection();

        if (
            selection &&
            selection.rangeCount > 0 &&
            editorRef.current?.contains(selection.anchorNode)
        ) {
            savedCodeRangeRef.current = selection.getRangeAt(0).cloneRange();
        }

        setCode('');
        setCodeOpen(true);
    };

    const closeCode = () => {
        setCodeOpen(false);
        setCode('');
        editorRef.current?.focus();
    };

    const insertCode = () => {
        const editor = editorRef.current;
        const source = code.replace(/\r\n/g, '\n');

        if (!editor || !source.trim()) {
            return;
        }

        editor.focus();

        const selection = window.getSelection();

        if (selection && savedCodeRangeRef.current) {
            selection.removeAllRanges();
            selection.addRange(savedCodeRangeRef.current);
        }

        const pre = document.createElement('pre');
        const codeElement = document.createElement('code');

        codeElement.textContent = source;
        pre.appendChild(codeElement);

        const activeSelection = window.getSelection();

        if (
            activeSelection &&
            activeSelection.rangeCount > 0 &&
            editor.contains(activeSelection.anchorNode)
        ) {
            const range = activeSelection.getRangeAt(0);
            const container = range.startContainer.nodeType === Node.ELEMENT_NODE
                ? range.startContainer
                : range.startContainer.parentElement;
            const paragraph = container?.closest?.('p');

            if (!paragraph || !editor.contains(paragraph)) {
                range.deleteContents();
                range.insertNode(pre);
                range.setStartAfter(pre);
                range.collapse(true);
                activeSelection.removeAllRanges();
                activeSelection.addRange(range);
            } else {
                const beforeRange = document.createRange();

                beforeRange.selectNodeContents(paragraph);
                beforeRange.setEnd(range.startContainer, range.startOffset);

                const beforeFragment = beforeRange.extractContents();
                const afterFragment = document.createDocumentFragment();

                while (paragraph.firstChild) {
                    afterFragment.appendChild(paragraph.firstChild);
                }

                const blocks = [];

                if (beforeFragment.textContent.trim()) {
                    const beforeParagraph = document.createElement('p');

                    beforeParagraph.appendChild(beforeFragment);
                    blocks.push(beforeParagraph);
                }

                blocks.push(pre);

                if (afterFragment.textContent.trim()) {
                    const afterParagraph = document.createElement('p');

                    afterParagraph.appendChild(afterFragment);
                    blocks.push(afterParagraph);
                }

                const nextSibling = paragraph.nextSibling;

                paragraph.remove();
                blocks.forEach((block) => editor.insertBefore(block, nextSibling));

                const caretRange = document.createRange();

                caretRange.setStartAfter(pre);
                caretRange.collapse(true);
                activeSelection.removeAllRanges();
                activeSelection.addRange(caretRange);
            }
        } else {
            editor.appendChild(pre);
        }

        setCodeOpen(false);
        setCode('');
        onChange?.(serialize());
    };

    return (
        <div
            className={`rounded-xl border border-outline-variant/40 bg-white overflow-hidden ${className}`}
        >
            <div className="flex flex-wrap items-center gap-0.5 px-2 py-1.5 border-b border-outline-variant/30 bg-surface-container-low">
                {toolbarActions.map((action) => (
                    <button
                        key={action.command}
                        type="button"
                        title={action.title}
                        aria-label={action.title}
                        onMouseDown={(event) => {
                            event.preventDefault();
                            runCommand(action.command);
                        }}
                        className="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
                    >
                        <span className="material-symbols-outlined text-[18px]">
                            {action.icon}
                        </span>
                    </button>
                ))}

                <span className="w-px h-5 bg-outline-variant/40 mx-1" />

                <button
                    type="button"
                    title="Sisipkan rumus matematika"
                    aria-label="Sisipkan rumus matematika"
                    onMouseDown={(event) => {
                        event.preventDefault();
                        openFormula();
                    }}
                    className="px-2 py-1 rounded-lg font-label font-semibold text-xs text-primary hover:bg-primary/10 transition-colors"
                >
                    ∑ Rumus
                </button>

                <button
                    type="button"
                    title="Sisipkan blok kode"
                    aria-label="Sisipkan blok kode"
                    onMouseDown={(event) => {
                        event.preventDefault();
                        openCode();
                    }}
                    className="inline-flex items-center gap-1 px-2 py-1 rounded-lg font-label font-semibold text-xs text-primary hover:bg-primary/10 transition-colors"
                >
                    <span className="material-symbols-outlined text-[16px]">code</span>
                    Kode
                </button>
            </div>

            <div
                id={id}
                ref={editorRef}
                contentEditable
                suppressContentEditableWarning
                onInput={handleInput}
                onFocus={() => {
                    isFocusedRef.current = true;
                }}
                onBlur={() => {
                    isFocusedRef.current = false;

                    if (editorRef.current) {
                        onChange?.(serialize());
                    }
                }}
                data-placeholder={placeholder}
                style={{ minHeight: `${minHeight}px` }}
                className="px-3 py-2.5 text-sm font-body text-on-surface leading-relaxed focus:outline-none empty:before:content-[attr(data-placeholder)] empty:before:text-on-surface-variant empty:before:pointer-events-none [&_.math-node]:inline-block [&_.math-node]:mx-0.5 [&_pre]:my-2 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-slate-900 [&_pre]:p-3 [&_pre]:font-mono [&_pre]:text-xs [&_pre]:leading-relaxed [&_pre]:text-slate-100 [&_code]:font-mono"
            />

            {formulaOpen && (
                <div className="border-t border-outline-variant/30 bg-surface-container-low px-3 py-3 space-y-2">
                    <label className="block text-[11px] font-label font-semibold text-on-surface-variant uppercase tracking-wider">
                        Kode Rumus (LaTeX)
                    </label>

                    <textarea
                        value={formula}
                        onChange={(event) => setFormula(event.target.value)}
                        rows={3}
                        spellCheck={false}
                        placeholder="Contoh: \frac{x}{y} = x^2 + y^2"
                        className="w-full bg-white text-on-surface font-mono text-xs px-3 py-2 rounded-xl border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />

                    <div className="flex flex-wrap items-center gap-3">
                        <div className="flex-1 min-w-[160px] rounded-xl bg-white border border-outline-variant/40 px-3 py-2 min-h-[42px] overflow-x-auto">
                            <span ref={previewRef} />
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={closeFormula}
                        >
                            Batal
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            onClick={insertFormula}
                            disabled={!formula.trim()}
                        >
                            Sisipkan
                        </Button>
                    </div>

                    <p className="text-[10px] font-body text-on-surface-variant">
                        Rumus disimpan sebagai kode LaTeX di dalam teks soal dan
                        otomatis dirender saat dibuka kembali.
                    </p>
                </div>
            )}

            {codeOpen && (
                <div className="border-t border-outline-variant/30 bg-surface-container-low px-3 py-3 space-y-2">
                    <label className="block text-[11px] font-label font-semibold text-on-surface-variant uppercase tracking-wider">
                        Kode Program
                    </label>

                    <textarea
                        value={code}
                        onChange={(event) => setCode(event.target.value)}
                        rows={6}
                        spellCheck={false}
                        placeholder="Tempel kode HTML/CSS/JS di sini..."
                        className="w-full bg-white text-on-surface font-mono text-xs px-3 py-2 rounded-xl border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />

                    <div className="flex flex-wrap items-center justify-end gap-3">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={closeCode}
                        >
                            Batal
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            onClick={insertCode}
                            disabled={!code.trim()}
                        >
                            Sisipkan
                        </Button>
                    </div>

                    <p className="text-[10px] font-body text-on-surface-variant">
                        Kode akan disimpan sebagai blok kode dan ditampilkan apa
                        adanya kepada siswa.
                    </p>
                </div>
            )}
        </div>
    );
}
