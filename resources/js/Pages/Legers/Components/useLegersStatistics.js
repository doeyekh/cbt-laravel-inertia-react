import React from 'react'
import { formatPercentage } from '@/Utils/formatPercentage';

function useLegersStatistics({summary}) {
    const statistics = [
        {
            title: "Jumlah Siswa",
            value: summary.student_count ?? 0,
            icon: "groups",
            color: "primary",
        },
        {
            title: "Mata Pelajaran",
            value: summary.subject_count ?? 0,
            icon: "book",
            color: "primary",
        },
        {
            title: "Rata-rata Nilai",
            value: formatPercentage(summary.average_percentage),
            icon: "monitoring",
            color: "success",
        },
        {
            title: "Ketuntasan",
            value: formatPercentage(summary.passed_percentage),
            icon: "verified",
            color: "primary",
        },
        {
            title: "Nilai Tertinggi",
            value: formatPercentage(summary.highest_percentage),
            icon: "trending_up",
            color: "primary",
        },
    ]
  return statistics;
}

export default useLegersStatistics