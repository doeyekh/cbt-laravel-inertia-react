import StatCard from "./StatCard";



export default function StatCardGroup({ items = [], columns = 3 }) {
    const columnClasses = {
        1: "md:grid-cols-1",
        2: "md:grid-cols-2",
        3: "md:grid-cols-3",
        4: "md:grid-cols-4",
    };

    return (
        <div
            className={`grid grid-cols-1 ${columnClasses[columns] || columnClasses[3]} gap-5`}
        >
            {items.map((item) => (
                <StatCard key={item.title} {...item} />
            ))}
        </div>
    );
}
