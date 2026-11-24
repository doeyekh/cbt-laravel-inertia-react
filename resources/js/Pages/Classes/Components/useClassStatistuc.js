
export default function useClassStatistic({
    total = 0,
    activeCount = 0,
    inactiveCount = 0,
}) {
    const statistics = [
        {
            title: "Total Kelas",
            value: total,
            icon: "groups",
            color: "primary",
        },
        {
            title: "Aktif",
            value: activeCount,
            icon: "check_circle",
            color: "success",
        },
        {
            title: "Tidak Aktif",
            value: inactiveCount,
            icon: "history",
            color: "tertiary",
            iconBackground: "tertiary-container/30",
        },
    ];

    return statistics;
}
