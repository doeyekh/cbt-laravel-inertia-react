import { route } from '@/Utils/route';

export function useStudentNavItems() {
    return [
        {
            label: 'Beranda & Jadwal Ujian',
            icon: 'dashboard',
            href: route('student.dashboard'),
            active: true,
        },
        {
            label: 'Ruang Ujian Aktif',
            icon: 'assignment',
            badge: 'Segera',
            disabled: true,
        },
        {
            label: 'Riwayat & Nilai Ujian',
            icon: 'history',
            disabled: true,
        },
        {
            label: 'Uji Kesiapan Perangkat',
            icon: 'laptop_mac',
            href: '#kesiapan',
        },
        {
            label: 'Panduan & Tata Tertib',
            icon: 'menu_book',
            href: '#panduan',
        },
        {
            label: 'Bantuan & Pengawas',
            icon: 'support_agent',
            href: '#bantuan',
        },
    ];
}
