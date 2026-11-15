/**
 * Helpers for reading student exam schedules.
 *
 * Exam date and times are stored as local wall-clock values, so status is
 * calculated with the browser clock instead of the server timezone.
 */

export function parseExamDate(date) {
    if (!date) {
        return null;
    }

    const parsed = new Date(`${date}T00:00:00`);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

export function getExamWindow(exam) {
    if (!exam?.date || !exam.open_time || !exam.close_time) {
        return null;
    }

    const start = new Date(`${exam.date}T${exam.open_time}:00`);
    const end = new Date(`${exam.date}T${exam.close_time}:00`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return null;
    }

    return { start, end };
}

export function getExamStatus(exam, now = new Date()) {
    const window = getExamWindow(exam);

    if (!window) {
        return 'unknown';
    }

    if (now < window.start) {
        return 'upcoming';
    }

    if (now > window.end) {
        return 'finished';
    }

    return 'ongoing';
}

export function getExamSummary(exams = [], now = new Date()) {
    return exams.reduce(
        (summary, exam) => {
            const status = getExamStatus(exam, now);

            return {
                total: summary.total + 1,
                ongoing: summary.ongoing + (status === 'ongoing' ? 1 : 0),
                upcoming: summary.upcoming + (status === 'upcoming' ? 1 : 0),
                finished: summary.finished + (status === 'finished' ? 1 : 0),
            };
        },
        { total: 0, ongoing: 0, upcoming: 0, finished: 0 },
    );
}

export function isExamToday(exam, now = new Date()) {
    const date = parseExamDate(exam?.date);

    return Boolean(date) && date.toDateString() === now.toDateString();
}

/**
 * Countdown toward the next relevant exam moment.
 *
 * @returns {{ target: 'start'|'end'|'finished', milliseconds: number }|null}
 */
export function getExamCountdown(exam, now = new Date()) {
    const window = getExamWindow(exam);

    if (!window) {
        return null;
    }

    if (now < window.start) {
        return { target: 'start', milliseconds: window.start - now };
    }

    if (now <= window.end) {
        return { target: 'end', milliseconds: window.end - now };
    }

    return { target: 'finished', milliseconds: 0 };
}

export function formatCountdown(milliseconds) {
    if (!Number.isFinite(milliseconds) || milliseconds <= 0) {
        return '00:00:00';
    }

    const totalSeconds = Math.floor(milliseconds / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (value) => String(value).padStart(2, '0');

    if (days > 0) {
        return `${days} hari ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
    }

    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
}

export function formatExamDate(date) {
    const parsed = parseExamDate(date);

    if (!parsed) {
        return '-';
    }

    return parsed.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export function getExamDateParts(date) {
    const parsed = parseExamDate(date);

    if (!parsed) {
        return { day: '--', month: '---' };
    }

    return {
        day: parsed.toLocaleDateString('id-ID', { day: '2-digit' }),
        month: parsed.toLocaleDateString('id-ID', { month: 'short' }),
    };
}

/**
 * Sort exams chronologically without mutating the Inertia payload.
 */
export function sortExams(exams = []) {
    return [...exams].sort((first, second) => {
        const firstWindow = getExamWindow(first);
        const secondWindow = getExamWindow(second);

        if (!firstWindow || !secondWindow) {
            return (first?.date ?? '').localeCompare(second?.date ?? '');
        }

        return firstWindow.start - secondWindow.start;
    });
}
