import { useEffect, useState } from 'react';

/**
 * Keep a live clock for portal headers, countdowns, and schedule grouping.
 */
export function useClock(intervalMs = 1000) {
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), intervalMs);

        return () => clearInterval(timer);
    }, [intervalMs]);

    return {
        now,
        time: now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }),
        date: now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }),
        shortDate: now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }),
    };
}
