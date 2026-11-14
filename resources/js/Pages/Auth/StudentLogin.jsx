import React, { useState, useEffect, useRef } from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import { route, hasRoute } from '@/Utils/route';
import ToastContainer from '@/Components/Layout/ToastContainer';
import { useToast } from '@/Hooks/useToast';

export default function StudentLogin({ systemInfo = {} }) {
    const { flash, errors: pageErrors } = usePage().props;
    const { toasts, toast, dismiss } = useToast();
    const handledToast = useRef(null);

    const [showPassword, setShowPassword] = useState(false);
    const [currentTime, setCurrentTime] = useState('');
    const [currentDate, setCurrentDate] = useState('');
    const [rulesModalOpen, setRulesModalOpen] = useState(false);

    const { data, setData, post, processing, errors, clearErrors, reset } = useForm({
        username: '',
        password: '',
        token: '',
        remember: true,
    });

    // Format jam & tanggal real-time ala CBT
    useEffect(() => {
        const updateClock = () => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            }) + ' WIB';

            const dateStr = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });

            setCurrentTime(timeStr);
            setCurrentDate(dateStr);
        };

        updateClock();
        const timer = setInterval(updateClock, 1000);
        return () => clearInterval(timer);
    }, []);

    // Tangani flash toast
    useEffect(() => {
        if (!flash?.toast || handledToast.current === flash.toast) {
            return;
        }

        handledToast.current = flash.toast;
        toast(flash.toast);
    }, [flash, toast]);

    const handleSubmit = (e) => {
        e.preventDefault();
        clearErrors();

        const submitRoute = hasRoute('student.login.store')
            ? route('student.login.store')
            : '/login';

        post(submitRoute, {
            preserveScroll: true,
            onError: () => {
                // reset password field on error
                setData('password', '');
            },
        });
    };

    const adminLoginUrl = hasRoute('admin.login') ? route('admin.login') : '/admin/login';

    return (
        <div className="min-h-screen bg-surface font-body text-on-surface flex flex-col relative selection:bg-primary/20 selection:text-primary overflow-x-hidden">
            {/* Ambient Tonal Gradient Background (No-Line rule) */}
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-gradient-to-b from-primary/[0.04] via-transparent to-transparent pointer-events-none blur-3xl -z-10" />
            <div className="absolute -top-24 right-0 w-96 h-96 bg-tertiary/[0.03] rounded-full blur-3xl pointer-events-none -z-10" />
            <div className="absolute top-1/2 left-0 w-80 h-80 bg-primary/[0.03] rounded-full blur-3xl pointer-events-none -z-10" />

            {/* Top Bar / Header Telemetri Sistem CBT */}
            <header className="w-full bg-surface-container-lowest/80 backdrop-blur-xl border-b border-outline-variant/15 sticky top-0 z-40 transition-all">
                <div className="max-w-[1720px] mx-auto px-4 sm:px-8 h-16 flex items-center justify-between gap-4">
                    {/* Brand & Sistem */}
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-on-primary shadow-sm">
                            <span className="material-symbols-outlined text-[20px]">school</span>
                        </div>
                        <div className="flex flex-col">
                            <span className="font-headline font-bold text-base text-primary tracking-tight leading-none">
                                {systemInfo.appName || 'EduTest CBT'}
                            </span>
                            <span className="font-label text-[10px] font-semibold tracking-wider uppercase text-on-surface-variant mt-1">
                                {systemInfo.systemName || 'Sistem Evaluasi Terpadu'}
                            </span>
                        </div>
                    </div>

                    {/* Status & Jam Server Real-time (Desktop & Tablet) */}
                    <div className="hidden md:flex items-center gap-6 text-xs font-label">
                        {/* Server Status Live Indicator */}
                        <div className="flex items-center gap-2.5 px-3 py-1.5 bg-surface-container-low rounded-xl">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75" />
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-primary" />
                            </span>
                            <span className="text-on-surface-variant font-medium">Status Server:</span>
                            <span className="font-semibold text-on-surface">
                                {systemInfo.serverStatus || 'Online / Siap'}
                            </span>
                        </div>

                        {/* Jam Server Live */}
                        <div className="flex items-center gap-2 text-on-surface-variant">
                            <span className="material-symbols-outlined text-sm text-secondary">schedule</span>
                            <span className="font-semibold font-mono text-on-surface">{currentTime || '08:00:00 WIB'}</span>
                            <span className="text-outline-variant">•</span>
                            <span>{currentDate || 'Hari Ini'}</span>
                        </div>

                        {/* Tahun Pelajaran Aktif Chip */}
                        {systemInfo.academicYear && (
                            <div className="flex items-center gap-1.5 px-2.5 py-1 bg-tertiary-container/15 rounded-lg text-tertiary font-label text-xs font-semibold">
                                <span className="material-symbols-outlined text-xs">calendar_month</span>
                                <span>{systemInfo.academicYear}</span>
                            </div>
                        )}
                    </div>

                    {/* Shortcut Portal Pengawas / Proktor */}
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setRulesModalOpen(true)}
                            className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-label font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-all"
                        >
                            <span className="material-symbols-outlined text-[16px] text-tertiary">gavel</span>
                            <span>Tata Tertib</span>
                        </button>

                        <a
                            href={adminLoginUrl}
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label text-xs font-medium transition-all shadow-sm"
                            title="Masuk ke Panel Proktor / Administrator"
                        >
                            <span className="material-symbols-outlined text-[16px] text-primary">admin_panel_settings</span>
                            <span>Portal Proktor</span>
                        </a>
                    </div>
                </div>
            </header>

            {/* Main Content: High-End Editorial Split Layout */}
            <main className="flex-1 flex items-center justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
                <div className="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    {/* Kolom Kiri: Panduan & Editorial Asesmen Siswa (5 cols) */}
                    <div className="lg:col-span-5 space-y-6 order-2 lg:order-1">
                        <div className="space-y-3">
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary font-label text-xs font-semibold tracking-wider uppercase">
                                <span className="material-symbols-outlined text-[14px]">verified</span>
                                <span>Portal Asesmen Digital Siswa</span>
                            </div>
                            
                            <h1 className="font-headline text-3xl sm:text-4xl font-bold text-on-surface tracking-tight leading-tight">
                                Selamat Datang di Ruang Ujian Online
                            </h1>
                            
                            <p className="text-sm text-on-surface-variant font-body leading-relaxed">
                                Silakan masuk menggunakan kredensial resmi yang tercantum pada <strong>Kartu Peserta Ujian</strong> Anda. Pastikan sesi dan ruangan telah sesuai jadwal.
                            </p>
                        </div>

                        {/* 3 Academic Guidance Cards (No-Line Rule: surface shifts) */}
                        <div className="space-y-3 pt-2">
                            {/* Card 1 */}
                            <div className="bg-surface-container-low p-4 rounded-2xl flex items-start gap-3.5 transition-all hover:bg-surface-container">
                                <div className="p-2.5 rounded-xl bg-primary/10 text-primary shrink-0">
                                    <span className="material-symbols-outlined text-[20px]">badge</span>
                                </div>
                                <div>
                                    <h4 className="font-headline font-semibold text-sm text-on-surface">
                                        Kredensial Peserta
                                    </h4>
                                    <p className="text-xs text-on-surface-variant font-body mt-0.5 leading-relaxed">
                                        Gunakan Nomor Peserta atau Username beserta Kata Sandi / PIN yang diberikan oleh panitia.
                                    </p>
                                </div>
                            </div>

                            {/* Card 2 */}
                            <div className="bg-surface-container-low p-4 rounded-2xl flex items-start gap-3.5 transition-all hover:bg-surface-container">
                                <div className="p-2.5 rounded-xl bg-tertiary-container/30 text-tertiary shrink-0">
                                    <span className="material-symbols-outlined text-[20px]">shield</span>
                                </div>
                                <div>
                                    <h4 className="font-headline font-semibold text-sm text-on-surface">
                                        Integritas & Fokus Ujian
                                    </h4>
                                    <p className="text-xs text-on-surface-variant font-body mt-0.5 leading-relaxed">
                                        Sistem mencatat aktivitas browser. Dilarang berpindah jendela atau membuka aplikasi lain selama ujian.
                                    </p>
                                </div>
                            </div>

                            {/* Card 3 */}
                            <div className="bg-surface-container-low p-4 rounded-2xl flex items-start gap-3.5 transition-all hover:bg-surface-container">
                                <div className="p-2.5 rounded-xl bg-surface-container-highest text-secondary shrink-0">
                                    <span className="material-symbols-outlined text-[20px]">support_agent</span>
                                </div>
                                <div>
                                    <h4 className="font-headline font-semibold text-sm text-on-surface">
                                        Bantuan Pengawas
                                    </h4>
                                    <p className="text-xs text-on-surface-variant font-body mt-0.5 leading-relaxed">
                                        Jika mengalami kendala teknis atau lupa kredensial, segera angkat tangan dan hubungi pengawas ruangan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Footer Editorial Metadata */}
                        <div className="pt-2 flex flex-wrap items-center gap-4 text-xs font-label text-on-surface-variant/80">
                            <span className="flex items-center gap-1.5">
                                <span className="material-symbols-outlined text-[15px] text-primary">lock</span>
                                Enkripsi SSL 256-bit
                            </span>
                            <span>•</span>
                            <span className="flex items-center gap-1.5">
                                <span className="material-symbols-outlined text-[15px] text-tertiary">hub</span>
                                {systemInfo.clusterNode || 'Cluster Node 01'}
                            </span>
                            <span>•</span>
                            <span>{systemInfo.version || 'v4.6.2'}</span>
                        </div>
                    </div>

                    {/* Kolom Kanan: Kartu Form Login Siswa (7 cols) */}
                    <div className="lg:col-span-7 order-1 lg:order-2 flex justify-center">
                        <div className="w-full max-w-lg bg-surface-container-lowest rounded-3xl p-6 sm:p-10 shadow-xl border border-outline-variant/15 relative overflow-hidden transition-all">
                            {/* Decorative Corner Ambient Glow */}
                            <div className="absolute -right-12 -top-12 w-40 h-40 bg-primary/5 rounded-full blur-2xl pointer-events-none" />
                            <div className="absolute -left-12 -bottom-12 w-40 h-40 bg-tertiary/5 rounded-full blur-2xl pointer-events-none" />

                            {/* Card Header */}
                            <div className="mb-8">
                                <div className="flex items-center justify-between mb-3">
                                    <div className="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shadow-sm">
                                        <span className="material-symbols-outlined text-[26px]">how_to_reg</span>
                                    </div>
                                    <span className="px-3 py-1 rounded-full bg-surface-container font-label text-[11px] font-semibold text-on-surface-variant">
                                        Sesi Peserta
                                    </span>
                                </div>

                                <h2 className="font-headline font-bold text-2xl text-on-surface tracking-tight">
                                    Masuk Sesi Asesmen
                                </h2>
                                <p className="text-xs sm:text-sm text-on-surface-variant font-body mt-1 leading-relaxed">
                                    Lengkapi nomor peserta dan kata sandi untuk mengakses naskah ujian.
                                </p>
                            </div>

                            {/* Form Login */}
                            <form onSubmit={handleSubmit} className="space-y-5">
                                {/* Input Username / Nomor Peserta */}
                                <div>
                                    <label
                                        htmlFor="student-username"
                                        className="block text-[11px] font-label font-semibold text-on-surface mb-1.5 uppercase tracking-wider flex items-center justify-between"
                                    >
                                        <span>Nomor Peserta / Username</span>
                                        <span className="text-[10px] text-primary lowercase tracking-normal font-normal">
                                            tertera di kartu
                                        </span>
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                                            <span className="material-symbols-outlined text-[18px]">account_circle</span>
                                        </div>
                                        <input
                                            id="student-username"
                                            type="text"
                                            value={data.username}
                                            onChange={(e) => setData('username', e.target.value)}
                                            placeholder="Contoh: 01-001-025 atau NISN"
                                            autoComplete="username"
                                            autoFocus
                                            disabled={processing}
                                            aria-invalid={errors.username ? 'true' : undefined}
                                            className={`
                                                w-full bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/60 font-body text-sm
                                                pl-10 pr-4 py-3 rounded-xl focus:outline-none focus:bg-surface-container-lowest
                                                focus:ring-2 focus:ring-primary focus:shadow-sm
                                                transition-all disabled:opacity-50
                                                border border-transparent
                                                ${errors.username ? 'ring-2 ring-error bg-error-container/10 text-error' : ''}
                                            `}
                                        />
                                    </div>
                                    {errors.username && (
                                        <div className="mt-1.5 flex items-center gap-1.5 text-xs text-error font-body">
                                            <span className="material-symbols-outlined text-[14px]">error</span>
                                            <span>{errors.username}</span>
                                        </div>
                                    )}
                                </div>

                                {/* Input Password / PIN Ujian */}
                                <div>
                                    <label
                                        htmlFor="student-password"
                                        className="block text-[11px] font-label font-semibold text-on-surface mb-1.5 uppercase tracking-wider flex items-center justify-between"
                                    >
                                        <span>Kata Sandi / PIN Ujian</span>
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="text-[10px] font-label font-medium text-primary hover:underline lowercase tracking-normal"
                                        >
                                            {showPassword ? 'Sembunyikan' : 'Tampilkan'}
                                        </button>
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                                            <span className="material-symbols-outlined text-[18px]">lock</span>
                                        </div>
                                        <input
                                            id="student-password"
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="Masukkan kata sandi / PIN"
                                            autoComplete="current-password"
                                            disabled={processing}
                                            aria-invalid={errors.password ? 'true' : undefined}
                                            className={`
                                                w-full bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/60 font-body text-sm
                                                pl-10 pr-11 py-3 rounded-xl focus:outline-none focus:bg-surface-container-lowest
                                                focus:ring-2 focus:ring-primary focus:shadow-sm
                                                transition-all disabled:opacity-50
                                                border border-transparent
                                                ${errors.password ? 'ring-2 ring-error bg-error-container/10 text-error' : ''}
                                            `}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-on-surface-variant hover:text-on-surface transition-colors"
                                            aria-label={showPassword ? 'Sembunyikan password' : 'Lihat password'}
                                        >
                                            <span className="material-symbols-outlined text-[18px]">
                                                {showPassword ? 'visibility_off' : 'visibility'}
                                            </span>
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <div className="mt-1.5 flex items-center gap-1.5 text-xs text-error font-body">
                                            <span className="material-symbols-outlined text-[14px]">error</span>
                                            <span>{errors.password}</span>
                                        </div>
                                    )}
                                </div>

                                {/* Token Ujian (Opsional / CBT Token) */}
                                <div className="p-3.5 rounded-2xl bg-surface-container-low/80 space-y-2">
                                    <div className="flex items-center justify-between">
                                        <label
                                            htmlFor="student-token"
                                            className="text-[11px] font-label font-semibold text-on-surface flex items-center gap-1.5"
                                        >
                                            <span className="material-symbols-outlined text-[14px] text-tertiary">key</span>
                                            <span>Token Ujian</span>
                                            <span className="text-[10px] text-on-surface-variant font-normal">(opsional)</span>
                                        </label>
                                        <span className="text-[10px] font-label text-tertiary font-semibold bg-tertiary-container/20 px-2 py-0.5 rounded-md">
                                            Diberikan Pengawas
                                        </span>
                                    </div>
                                    <input
                                        id="student-token"
                                        type="text"
                                        value={data.token}
                                        onChange={(e) => setData('token', e.target.value.toUpperCase())}
                                        placeholder="Contoh: WXYZ99"
                                        maxLength={10}
                                        disabled={processing}
                                        className="w-full bg-surface-container-lowest text-on-surface placeholder:text-on-surface-variant/40 font-mono text-sm tracking-widest text-center uppercase py-2 px-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-tertiary border border-transparent transition-all"
                                    />
                                    <p className="text-[11px] text-on-surface-variant/80 font-body">
                                        Kosongkan jika pengawas belum membagikan token di ruangan Anda.
                                    </p>
                                </div>

                                {/* Remember me checkbox & help */}
                                <div className="flex items-center justify-between text-xs font-body">
                                    <label className="flex items-center gap-2 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                            className="w-4 h-4 rounded text-primary bg-surface-container-low border-outline-variant focus:ring-primary accent-primary"
                                        />
                                        <span className="text-on-surface-variant text-xs">Simpan kredensial di browser ini</span>
                                    </label>

                                    <button
                                        type="button"
                                        onClick={() => setRulesModalOpen(true)}
                                        className="text-primary hover:underline font-label font-medium text-xs"
                                    >
                                        Petunjuk & Aturan
                                    </button>
                                </div>

                                {/* Submit Button: High-End Gradient CTA */}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-on-primary font-label text-sm font-semibold shadow-md hover:brightness-110 active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:pointer-events-none"
                                >
                                    {processing ? (
                                        <>
                                            <span className="material-symbols-outlined text-[18px] animate-spin">sync</span>
                                            <span>Memverifikasi Peserta...</span>
                                        </>
                                    ) : (
                                        <>
                                            <span className="material-symbols-outlined text-[18px]">login</span>
                                            <span>Masuk ke Ruang Ujian</span>
                                        </>
                                    )}
                                </button>
                            </form>

                            {/* Divider & Switch Role / Contact Proctor */}
                            <div className="mt-8 pt-6 border-t border-outline-variant/15 text-center space-y-3">
                                <p className="text-xs text-on-surface-variant font-body">
                                    Bukan peserta ujian?{' '}
                                    <a
                                        href={adminLoginUrl}
                                        className="font-semibold text-primary hover:underline inline-flex items-center gap-1"
                                    >
                                        Masuk sebagai Pengawas / Proktor
                                        <span className="material-symbols-outlined text-[14px]">arrow_forward</span>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {/* Footer Sistem */}
            <footer className="w-full py-6 px-4 sm:px-8 border-t border-outline-variant/15 text-xs text-on-surface-variant font-label bg-surface-container-low/40">
                <div className="max-w-[1720px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                    <div className="flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-emerald-500" />
                        <span>Sistem Ujian Terkoneksi ke Server Pusat</span>
                        <span className="text-outline-variant hidden sm:inline">•</span>
                        <span className="hidden sm:inline">Latensi: {systemInfo.serverLatency || '12ms'}</span>
                    </div>
                    <div>
                        © {new Date().getFullYear()} {systemInfo.appName || 'EduTest CBT'} — Platform Evaluasi Multi-Sekolah
                    </div>
                </div>
            </footer>

            {/* Modal Tata Tertib Peserta Ujian */}
            {rulesModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="bg-surface-container-lowest max-w-lg w-full rounded-3xl p-6 sm:p-8 shadow-2xl border border-outline-variant/20 relative space-y-5">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2.5">
                                <div className="p-2 rounded-xl bg-tertiary-container/30 text-tertiary">
                                    <span className="material-symbols-outlined text-[20px]">assignment</span>
                                </div>
                                <h3 className="font-headline font-bold text-lg text-on-surface">
                                    Tata Tertib Peserta Ujian
                                </h3>
                            </div>
                            <button
                                type="button"
                                onClick={() => setRulesModalOpen(false)}
                                className="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors"
                            >
                                <span className="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <div className="space-y-3.5 text-xs text-on-surface-variant font-body leading-relaxed max-h-[60vh] overflow-y-auto pr-1">
                            <div className="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low">
                                <span className="font-mono font-bold text-primary text-sm mt-0.5">1.</span>
                                <p>Pastikan Anda memasuki ruang ujian 15 menit sebelum sesi dimulai dengan membawa Kartu Peserta resmi.</p>
                            </div>
                            <div className="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low">
                                <span className="font-mono font-bold text-primary text-sm mt-0.5">2.</span>
                                <p>Dilarang membawa alat komunikasi, catatan, atau perangkat digital tambahan selain komputer yang ditentukan.</p>
                            </div>
                            <div className="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low">
                                <span className="font-mono font-bold text-primary text-sm mt-0.5">3.</span>
                                <p>Dilarang menekan tombol pintas (shortcut), berganti tab, atau meminimalkan layar browser. Setiap pelanggaran tercatat otomatis di layar proktor.</p>
                            </div>
                            <div className="flex items-start gap-3 p-3 rounded-xl bg-surface-container-low">
                                <span className="font-mono font-bold text-primary text-sm mt-0.5">4.</span>
                                <p>Jika koneksi terputus atau komputer mengalami masalah, jawaban tersimpan otomatis. Segera beritahu pengawas ruangan.</p>
                            </div>
                        </div>

                        <div className="pt-2 flex justify-end">
                            <button
                                type="button"
                                onClick={() => setRulesModalOpen(false)}
                                className="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-label text-xs font-semibold hover:brightness-110 transition-all cursor-pointer shadow-sm"
                            >
                                Saya Mengerti
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Toast Container */}
            <ToastContainer toasts={toasts} dismiss={dismiss} />
        </div>
    );
}
