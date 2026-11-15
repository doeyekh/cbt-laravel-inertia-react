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

export function getExamStatus(exam, now = new Date()) {
    if (!exam?.date || !exam.open_time || !exam.close_time) {
        return 'unknown';
    }

    const start = new Date(`${exam.date}T${exam.open_time}:00`);
    const end = new Date(`${exam.date}T${exam.close_time}:00`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return 'unknown';
    }

    if (now < start) {
        return 'upcoming';
    }

    if (now > end) {
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
