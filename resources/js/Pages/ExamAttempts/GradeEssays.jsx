import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { route } from '@/Utils/route';

function EssayAnswer({ attemptId, essay }) {
    const { data, setData, patch, processing, errors } = useForm({
        score_awarded: essay.score_awarded ?? '',
    });

    const submit = (event) => {
        event.preventDefault();
        patch(route('admin.exam-attempts.essays.update', {
            examAttempt: attemptId,
            examAnswer: essay.id,
        }), { preserveScroll: true });
    };

    return (
        <article className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 className="font-semibold text-slate-900">Soal {essay.question_no}</h2>
                <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">{essay.question}</p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Jawaban peserta
                </p>
                <p className="mt-2 whitespace-pre-wrap text-sm text-slate-800">{essay.answer}</p>
            </div>
            <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                <label className="block">
                    <span className="mb-1 block text-xs font-medium text-slate-700">
                        Nilai (maks. {essay.max_score})
                    </span>
                    <input
                        type="number"
                        min="0"
                        max={essay.max_score}
                        step="0.01"
                        value={data.score_awarded}
                        onChange={(event) => setData('score_awarded', event.target.value)}
                        className="w-36 rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                    {errors.score_awarded && (
                        <span className="mt-1 block text-xs text-red-600">{errors.score_awarded}</span>
                    )}
                </label>
                <button
                    type="submit"
                    disabled={processing || data.score_awarded === ''}
                    className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                >
                    {processing ? 'Menyimpan...' : 'Simpan Nilai'}
                </button>
                {essay.score_awarded !== null && (
                    <span className="text-xs font-medium text-emerald-700">Sudah dinilai</span>
                )}
            </form>
        </article>
    );
}

export default function GradeEssays({ attempt, essays = [] }) {
    return (
        <AppLayout>
            <main className="mx-auto max-w-5xl space-y-6 p-4 sm:p-8">
                <Link
                    href={route('admin.monitoring.index')}
                    className="text-sm font-semibold text-blue-700 hover:underline"
                >
                    Kembali ke Monitoring
                </Link>
                <header className="rounded-2xl bg-white p-6 shadow-sm">
                    <h1 className="text-2xl font-bold text-slate-900">Penilaian Esai</h1>
                    <p className="mt-2 text-sm text-slate-600">
                        {attempt.student_name} · {attempt.subject}
                    </p>
                    <p className="mt-1 text-xs text-slate-500">
                        Nilai otomatis saat ini: {attempt.score ?? 0} / {attempt.max_score ?? 0}
                    </p>
                </header>
                {essays.length > 0 ? essays.map((essay) => (
                    <EssayAnswer key={essay.id} attemptId={attempt.id} essay={essay} />
                )) : (
                    <p className="rounded-xl bg-white p-6 text-sm text-slate-600">
                        Tidak ada jawaban esai yang menunggu penilaian.
                    </p>
                )}
            </main>
        </AppLayout>
    );
}
