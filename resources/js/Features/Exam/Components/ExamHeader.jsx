import React from 'react';

const ExamHeader = ({
    packageName,
    subjectName,
    packageCode,
    formattedTime,
    sessionName,
    examType,
}) => {
    return (
        <header className="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs">
            <div className="w-full px-5 py-2.5 flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3 pr-5 border-r border-slate-200">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 to-blue-500 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                        <span className="text-white font-bold text-xs">CBT</span>
                    </div>
                    <div>
                        <div className="flex items-center gap-1.5 leading-none">
                            <span className="text-lg font-black tracking-tight text-blue-700">EduTest</span>
                            <span className="text-lg font-black tracking-tight text-slate-800">Pro</span>
                        </div>
                        <p className="text-[10px] uppercase font-bold tracking-wider text-slate-400 mt-0.5">Computer-Based Test</p>
                    </div>
                </div>

                <div className="flex flex-col gap-1">
                    <div className="flex items-center gap-2.5">
                        <h1 className="text-sm font-bold text-slate-900 leading-tight">{packageName}</h1>
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            Sesi Ujian Aktif
                        </span>
                    </div>
                    <p className="text-xs text-slate-500 font-medium mt-0.5">
                        {subjectName} • Kode: {packageCode}
                    </p>
                </div>

                <div className="flex items-center bg-blue-50/80 border border-blue-200 px-4 py-1.5 rounded-xl shadow-xs">
                    <div className="mr-3 text-blue-600 flex items-center justify-center">
                        <span className="material-symbols-outlined text-[20px]">timer</span>
                    </div>
                    <div>
                        <span className="text-[10px] font-bold uppercase tracking-wider text-blue-600 block leading-tight">Sisa Waktu Ujian</span>
                        <span className="font-mono font-extrabold text-xl tracking-tight text-blue-900">{formattedTime}</span>
                    </div>
                </div>

                <div className="flex items-center pl-3 border-l border-slate-200 gap-3">
                    <div className="relative">
                        <div className="w-9 h-9 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center border-2 border-white shadow-xs">
                            {subjectName.charAt(0)}
                        </div>
                        <span className="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </div>
                    <div className="text-right hidden sm:block">
                        <p className="text-xs font-bold text-slate-800 leading-tight">Peserta Ujian</p>
                        <div className="flex items-center justify-end gap-2 text-[11px] text-slate-500 font-medium">
                            <span>{sessionName}</span>
                            <span>•</span>
                            <span className="text-blue-700 font-semibold">{examType}</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>
    );
};

export default ExamHeader;
