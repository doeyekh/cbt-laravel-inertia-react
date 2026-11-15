import { Link } from '@inertiajs/react';
import { route } from '@/Utils/route';

export default function StudentBrand({ appName, systemName, compact = false }) {
    return (
        <Link href={route('student.dashboard')} className="flex items-center gap-3">
            <span
                className={`flex items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-container text-on-primary ${
                    compact ? 'h-9 w-9' : 'h-11 w-11'
                }`}
            >
                <span className={`material-symbols-outlined ${compact ? 'text-[19px]' : 'text-[23px]'}`}>
                    school
                </span>
            </span>
            <span className="flex flex-col">
                <span
                    className={`font-headline font-extrabold leading-none tracking-tight text-on-surface ${
                        compact ? 'text-[15px]' : 'text-lg'
                    }`}
                >
                    {appName}
                </span>
                <span className="mt-1 font-label text-[9px] font-medium uppercase tracking-[0.16em] text-on-surface-variant">
                    {systemName}
                </span>
            </span>
        </Link>
    );
}
