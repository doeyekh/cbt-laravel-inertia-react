/**
 * Helpers for reading the participant's exam answers on the client.
 */

/**
 * Determine whether an answer payload contains a usable value.
 *
 * A matching (menjodohkan) answer is stored as a list of right-pair ids, so a
 * single filled pair already counts as answered. This mirrors the server-side
 * answer check and keeps the question grid, progress counters, and the final
 * confirmation modal in sync.
 */
export function hasAnswerContent(answer) {
    if (answer === null || answer === undefined || answer === 'flagged') {
        return false;
    }

    if (Array.isArray(answer)) {
        return answer.some((value) => value !== null && value !== undefined && value !== '');
    }

    return answer !== '';
}
