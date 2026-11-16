import React, { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import StudentLoginForm from './Components/StudentLoginForm';

export default function StudentLogin({ systemInfo = {} }) {
    const { flash } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const handledToast = useRef(null);

    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    return (
        <main className="flex min-h-screen items-center justify-center bg-surface-container-low px-6 py-10 font-body text-on-surface selection:bg-primary/15 selection:text-primary">
            <div className="w-full max-w-md">
                <StudentLoginForm academicYear={systemInfo.academicYear} />
            </div>

            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </main>
    );
}
