import { Link } from '@inertiajs/react';

export default function ExamPage({ exam, exam_answered_count = 0 }) {
    return (
        <div className="min-h-screen bg-slate-100">
            <header className="flex items-center gap-2 p-4">
                <Link href="/" aria-label="Kembali ke beranda">
                    <svg className="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l2-2m-2 2l2 2m2-2l2 2M7 7H5m3 3H5m3 3H5m3 3H5m3 3H5M7 7h2m0 4h2m0 4h2m2-6v2m0-4v2m-6 2v2m0-4v2m6-2v2m0-4v2m2 4h2m0-4h2m0-4h2m2-6v2m0-4v2m6 2v2m0-4v2m-6 2v2m0 4h2m0 4h2m0 4h2m-6-2v2m0 4v2m-6-2v2m6 2v2m0 4h2m0 4h2m0 4h2"></path>
                    </svg>
                </Link>
                <span className="text-xl font-black tracking-tight text-blue-700">EduTest</span>
                <span className="text-xl font-black tracking-tight text-slate-800">Pro</span>
            </header>

            <main class="flex-1 w-full max-w-[1720px] mx-auto p-4 sm:p-5 flex flex-col gap-5 items-start">
            <section class="flex-1 w-full bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden min-h-187.5">
                {/* Panel Kontrol Soal (Header Kartu Soal) */}
                <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-sm tracking-wide shadow-xs">
                            Soal No. {exam.current_question_no} dari {exam.total_questions} Soal
                        </span>
                        <span class="text-xs text-slate-400 font-medium">dari {exam.total_questions} Soal</span>
                        <span class="px-2.5 py-1 rounded bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-semibold">
                            {exam.current_question_type} / Pasangan
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        {/* Bobot Nilai */}
                        <div class="text-xs font-medium text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                            Bobot Soal: <span class="font-bold text-blue-700">{exam.current_question_points} Poin</span>
                        </div>
                        {/* Ukuran Font Soal Switcher */}
                        <div class="flex items-center bg-white rounded-lg border border-slate-200 p-0.5 shadow-2xs">
                            <span class="text-[11px] font-medium text-slate-400 px-2">Ukuran:</span>
                            <button class="w-7 h-7 rounded text-xs font-bold text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" title="Perkecil Font" type="button">A-</button>
                            <button class="w-7 h-7 rounded text-xs font-bold bg-blue-50 text-blue-700 flex items-center justify-center border border-blue-200" title="Font Standar" type="button">A</button>
                            <button class="w-7 h-7 rounded text-xs font-bold text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" title="Perbesar Font" type="button">A+</button>
                        </div>
                    </div>
                </div>

                {/* Area Teks Soal & Stimulus Soal */}
                <div class="p-6 md:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="text-slate-800 text-base leading-relaxed space-y-4">
                        {/* Instruksi Soal */}
                        <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 text-sm text-slate-700">
                            <p class="font-semibold text-indigo-950 flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Petunjuk Pengerjaan:
                            </p>
                            <p class="font-normal text-slate-700 leading-relaxed">
                                Pasangkan konsep/rumus fungsi operasional di <span class="font-semibold text-slate-900">Kolom A (Premis)</span> dengan definisi dan penerapan nilai optimal yang sesuai di <span class="font-semibold text-slate-900">Kolom B (Target)</span>. Klik premis lalu pilih opsi pasangan yang sesuai.
                            </p>
                        </div>

                        {/* Komponen Interaktif Mencocokkan Pasangan */}
                        <div class="relative bg-slate-50/70 border border-slate-200 rounded-2xl p-5 md:p-6" data-purpose="matching-container">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 items-start relative">
                                
                                {/* KOLOM A: Premis / Pernyataan */}
                                <div class="space-y-3.5">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-md bg-slate-800 text-white flex items-center justify-center text-[11px] font-mono">A</span>
                                            Kolom A (Premis / Konsep)
                                        </h3>
                                        <span class="text-[11px] text-slate-500 font-medium">3/4 Terhubung</span>
                                    </div>

                                    {/* A1 - Terpasang dengan B3 (Warna Biru / Primary ) */}
                                    <div class="relative p-4 rounded-xl bg-blue-50/80 border-2 border-blue-500 shadow-xs transition-all flex items-center justify-between gap-3 group">
                                        <div class="flex items-start gap-3">
                                            <span class="w-7 h-7 rounded-lg bg-blue-600 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                                A1
                                            </span>
                                            <div>
                                                <p class="text-xs font-bold text-blue-950">Pendapatan Marjinal (MR = 0)</p>
                                                <p class="text-xs font-mono text-blue-800 mt-0.5">R'(x) = 280 - 4x = 0</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-white px-2 py-0.5 rounded-full border border-blue-200 shadow-2xs">
                                                ➔ Target <span class="font-bold font-mono">B3</span>
                                            </span>
                                            <div class="w-3.5 h-3.5 rounded-full bg-blue-600 ring-4 ring-blue-100 shrink-0"></div>
                                        </div>
                                    </div>

                                    {/* A2 - Terpasang dengan B1 (Warna Ungu/Indigo) */}
                                    <div class="relative p-4 rounded-xl bg-purple-50/80 border-2 border-purple-500 shadow-xs transition-all flex items-center justify-between gap-3 group">
                                        <div class="flex items-start gap-3">
                                            <span class="w-7 h-7 rounded-lg bg-purple-600 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                                A2
                                            </span>
                                            <div>
                                                <p class="text-xs font-bold text-purple-950">Keuntungan Maksimum (π'(x) = 0)</p>
                                                <p class="text-xs font-mono text-purple-800 mt-0.5">π(x) = R(x) - C(x) ; x = 30</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-700 bg-white px-2 py-0.5 rounded-full border border-purple-200 shadow-2xs">
                                                ➔ Target <span class="font-bold font-mono">B1</span>
                                            </span>
                                            <div class="w-3.5 h-3.5 rounded-full bg-purple-600 ring-4 ring-purple-100 shrink-0"></div>
                                        </div>
                                    </div>

                                    {/* A3 - Terpasang dengan B4 (Warna Teal) */}
                                    <div class="relative p-4 rounded-xl bg-teal-50/80 border-2 border-teal-500 shadow-xs transition-all flex items-center justify-between gap-3 group">
                                        <div class="flex items-start gap-3">
                                            <span class="w-7 h-7 rounded-lg bg-teal-600 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                                A3
                                            </span>
                                            <div>
                                                <p class="text-xs font-bold text-teal-950">Biaya Tetap Awal (Fixed Cost)</p>
                                                <p class="text-xs font-mono text-teal-800 mt-0.5">C(0) saat produksi = 0 unit</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-teal-700 bg-white px-2 py-0.5 rounded-full border border-teal-200 shadow-2xs">
                                                ➔ Target <span class="font-bold font-mono">B4</span>
                                            </span>
                                            <div class="w-3.5 h-3.5 rounded-full bg-teal-600 ring-4 ring-teal-100 shrink-0"></div>
                                        </div>
                                    </div>

                                    {/* A4 - Sedang Dipilih / Menunggu Dipasangkan */}
                                    <div class="relative p-4 rounded-xl bg-amber-50/70 border-2 border-dashed border-amber-500 shadow-xs transition-all flex items-center justify-between gap-3 ring-2 ring-amber-200 animate-pulse">
                                        <div class="flex items-start gap-3">
                                            <span class="w-7 h-7 rounded-lg bg-amber-500 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                                A4
                                            </span>
                                            <div>
                                                <p class="text-xs font-bold text-amber-950">Biaya Rata-Rata Minimum (AC'(x) = 0)</p>
                                                <p class="text-xs font-mono text-amber-800 mt-0.5">AC(x) = C(x) / x = 2x + 40 + 800/x</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-300">
                                                Pilih di Kolom B
                                            </span>
                                            <div class="w-3.5 h-3.5 rounded-full border-2 border-amber-600 bg-white shrink-0"></div>
                                        </div>
                                    </div>
                                </div>

                                {/* KOLOM B: Opsi Pasangan Target */}
                                <div class="space-y-3.5">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-md bg-blue-700 text-white flex items-center justify-center text-[11px] font-mono">B</span>
                                            Kolom B (Target / Definisi & Nilai)
                                        </h3>
                                        <span class="text-[11px] text-slate-500 font-medium">4 Pilihan Target</span>
                                    </div>

                                    {/* B1 - Terpasang ke A2 (Ungu) */}
                                    <div class="relative p-4 rounded-xl bg-purple-50/80 border-2 border-purple-500 shadow-xs flex items-center justify-between gap-3 group">
                                        <div class="flex items-center gap-2 shrink-0">
                                            <div class="w-3.5 h-3.5 rounded-full bg-purple-600 ring-4 ring-purple-100 shrink-0"></div>
                                            <span class="w-7 h-7 rounded-lg bg-purple-600 text-white font-mono font-bold text-xs flex items-center justify-center shadow-2xs">
                                                B1
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-bold text-purple-950">Rp 2.800.000,00 pada volume 30 unit</p>
                                            <p class="text-[11px] text-purple-800 mt-0.5">Titik puncak laba diferensiasi turunan pertama nol</p>
                                        </div>
                                        <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-purple-200 text-purple-900 border border-purple-300">
                                            Pasangan: A2
                                        </span>
                                    </div>

                                    {/* B2 - Target yang Belum Terpasang (Hoverable untuk A4) */}
                                    <button class="w-full text-left relative p-4 rounded-xl bg-white border-2 border-slate-300 hover:border-amber-500 hover:bg-amber-50/30 transition-all flex items-center justify-between gap-3 group shadow-2xs focus:outline-hidden" type="button">
                                        <div class="flex items-center gap-2 shrink-0">
                                            <div class="w-3.5 h-3.5 rounded-full border-2 border-slate-400 group-hover:border-amber-500 group-hover:bg-amber-100 bg-white shrink-0"></div>
                                            <span class="w-7 h-7 rounded-lg bg-slate-200 group-hover:bg-amber-500 group-hover:text-white text-slate-700 font-mono font-bold text-xs flex items-center justify-center transition">
                                                B2
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-semibold text-slate-800 group-hover:text-amber-950 transition">Produksi 20 unit dengan biaya minimum Rp 120.000 / unit</p>
                                            <p class="text-[11px] text-slate-500 group-hover:text-amber-800 mt-0.5">Diperoleh saat turunan d(AC)/dx = 0</p>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-400 group-hover:text-amber-700 group-hover:bg-amber-100 px-2 py-0.5 rounded border border-transparent group-hover:border-amber-200 transition">
                                            Klik untuk Cocokkan
                                        </span>
                                    </button>

                                    {/* B3 - Terpasang ke A1 (Biru) */}
                                    <div class="relative p-4 rounded-xl bg-blue-50/80 border-2 border-blue-500 shadow-xs flex items-center justify-between gap-3 group">
                                        <div class="flex items-center gap-2 shrink-0">
                                            <div class="w-3.5 h-3.5 rounded-full bg-blue-600 ring-4 ring-blue-100 shrink-0"></div>
                                            <span class="w-7 h-7 rounded-lg bg-blue-600 text-white font-mono font-bold text-xs flex items-center justify-center shadow-2xs">
                                                B3
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-bold text-blue-950">Volume 70 unit untuk memaksimumkan omzet kotor</p>
                                            <p class="text-[11px] text-blue-800 mt-0.5">Kondisi penerimaan total mencapai nilai optimum stasioner</p>
                                        </div>
                                        <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-blue-200 text-blue-900 border border-blue-300">
                                            Pasangan: A1
                                        </span>
                                    </div>

                                    {/* B4 - Terpasang ke A3 (Teal) */}
                                    <div class="relative p-4 rounded-xl bg-teal-50/80 border-2 border-teal-500 shadow-xs flex items-center justify-between gap-3 group">
                                        <div class="flex items-center gap-2 shrink-0">
                                            <div class="w-3.5 h-3.5 rounded-full bg-teal-600 ring-4 ring-teal-100 shrink-0"></div>
                                            <span class="w-7 h-7 rounded-lg bg-teal-600 text-white font-mono font-bold text-xs flex items-center justify-center shadow-2xs">
                                                B4
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-bold text-teal-950">Rp 8.000.000,00 (overhead mesin dan sewa fasilitas)</p>
                                            <p class="text-[11px] text-teal-800 mt-0.5">Konstanta nominal pembiayaan sebelum operasional dimulai</p>
                                        </div>
                                        <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-teal-200 text-teal-900 border border-teal-300">
                                            Pasangan: A3
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Rangkuman Pasangan Jawaban Siswa */}
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Rangkuman Pasangan Terpilih:</p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <div class="bg-white p-2.5 rounded-lg border border-blue-200 flex items-center justify-between shadow-2xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded bg-blue-600 text-white font-mono font-bold text-[10px] flex items-center justify-center">A1</span>
                                            <span class="text-xs font-bold text-slate-700">➔</span>
                                            <span class="w-5 h-5 rounded bg-blue-100 text-blue-800 font-mono font-bold text-[10px] flex items-center justify-center border border-blue-200">B3</span>
                                        </div>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500" title="Terpasang"></span>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-lg border border-purple-200 flex items-center justify-between shadow-2xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded bg-purple-600 text-white font-mono font-bold text-[10px] flex items-center justify-center">A2</span>
                                            <span class="text-xs font-bold text-slate-700">➔</span>
                                            <span class="w-5 h-5 rounded bg-purple-100 text-purple-800 font-mono font-bold text-[10px] flex items-center justify-center border border-purple-200">B1</span>
                                        </div>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500" title="Terpasang"></span>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-lg border border-teal-200 flex items-center justify-between shadow-2xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded bg-teal-600 text-white font-mono font-bold text-[10px] flex items-center justify-center">A3</span>
                                            <span class="text-xs font-bold text-slate-700">➔</span>
                                            <span class="w-5 h-5 rounded bg-teal-100 text-teal-800 font-mono font-bold text-[10px] flex items-center justify-center border border-teal-200">B4</span>
                                        </div>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500" title="Terpasang"></span>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-lg border border-amber-300 border-dashed flex items-center justify-between shadow-2xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded bg-amber-500 text-white font-mono font-bold text-[10px] flex items-center justify-center">A4</span>
                                            <span class="text-xs font-bold text-slate-400">➔</span>
                                            <span class="w-5 h-5 rounded bg-slate-100 text-slate-400 font-mono font-bold text-[10px] flex items-center justify-center border border-slate-300">--</span>
                                        </div>
                                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping" title="Belum Dipilih"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Footer Tombol Navigasi Soal */}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    {/* Tombol Sebelumnya */}
                    <button class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 hover:border-slate-400 font-semibold text-sm transition shadow-2xs" type="button">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Soal Sebelumnya
                    </button>

                    {/* Toggle Ragu-Ragu (Tengah) */}
                    <label class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 cursor-pointer hover:bg-amber-100/80 transition select-none">
                        <input class="w-4 h-4 rounded text-amber-600 border-amber-400 focus:ring-amber-500" type="checkbox"/>
                        <span class="text-xs font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Tandai Ragu-Ragu
                        </span>
                    </label>

                    {/* Tombol Berikutnya */}
                    <div class="flex items-center gap-2">
                        <button class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-sm transition shadow-2xs" type="button">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                            </svg>
                            <span>Daftar Soal</span>
                        </button>
                        <button class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition shadow-md shadow-blue-500/20 active:scale-98" type="button">
                            Soal Berikutnya
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

            {/* RightColumn NavigationSidebar */}
            <aside class="w-full flex flex-col gap-5">
                {/* Kartu Ringkasan Progres & Pemicu Modal Daftar Soal */}
                <div>
                    <h2 class="font-bold text-slate-900">Progres Ujian</h2>
                    <p class="text-slate-500">Soal {exam_answered_count ?? 0} dari {exam.total_questions} sudah dijawab</p>
                </div>
            </aside>
            </main>
        </div>
    );
}