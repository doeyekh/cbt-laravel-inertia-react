import React from 'react';
import { useForm } from '@inertiajs/react';

/**
 * QuestionNavigation - Tombol navigasi di bagian bawah halaman soal
 * Menampilkan tombol Previous, Mark for Review, dan Next
 */

const QuestionNavigation = ({ exam, onPrevious, onNext, onFlagToggle }) => {
    const {
        current_question_no,
        total_questions,
        is_flagged,
    } = exam;

    const { setData } = useForm();

    const handlePrevious = () => {
        onPrevious();
    };

    const handleNext = () => {
        onNext();
    };

    const handleFlagToggle = (e) => {
        e.stopPropagation();
        setData('flagged', !is_flagged);
        onFlagToggle(!is_flagged);
    };

    return (
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
            {/* Tombol Sebelumnya */}
            <button class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:border-slate-400 font-semibold text-sm transition shadow-2xs" type="button" onClick={handlePrevious}>
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                Soal Sebelumnya
            </button>

            {/* Toggle Ragu-Ragu (Tengah) */}
            <label class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 cursor-pointer hover:bg-amber-100/80 transition select-none">
                <input
                    class="w-4 h-4 rounded text-amber-600 border-amber-400 focus:ring-amber-500"
                    type="checkbox"
                    checked={is_flagged}
                    onChange={(e) => handleFlagToggle(e)}
                />
                <span class="text-xs font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Tandai Ragu-Ragu
                </span>
            </label>

            {/* Tombol Berikutnya */}
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-sm transition shadow-2xs" type="button" onClick={handlePrevious}>
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                    </svg>
                    <span>Daftar Soal</span>
                </button>
                <button class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition shadow-md shadow-blue-500/20 active:scale-98" type="button" onClick={handleNext}>
                    Soal Berikutnya
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                </button>
            </div>
        </div>
    );
};

export default QuestionNavigation;