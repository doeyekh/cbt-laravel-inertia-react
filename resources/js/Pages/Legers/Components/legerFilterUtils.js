
const FILTER_KEYS = [
    "school_id",
    "academic_year_id",
    "class_id",
    "subject_id",
    "exam_type_id",
    "search",
];

export const buildLegerQuery = (filters = {}) =>
    Object.fromEntries(
        FILTER_KEYS
            .map((key) => [key, filters[key] || undefined])
            .filter(([, value]) => value !== undefined)
    );
