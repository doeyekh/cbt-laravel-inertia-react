import { useEffect, useMemo, useRef, useState } from 'react';
import { useHttp } from '@inertiajs/react';
import { useToast } from '@/Hooks/useToast';
import { hasAnswerContent } from '@/Utils/examAnswer';

/**
 * Hitung sisa waktu dari tenggat di server.
 *
 * Nilai selalu dihitung ulang dari `deadline` absolut, bukan dari sisa detik
 * sebelumnya. Dengan begitu tampilan tetap benar setelah tab di-background,
 * jam perangkat berubah, atau props halaman sudah usang.
 */
function remainingSecondsUntil(deadlineTimestamp, clockSkewMs, durationSeconds) {
    const seconds = Math.ceil((deadlineTimestamp - (Date.now() + clockSkewMs)) / 1000);

    return Math.max(0, Math.min(durationSeconds, seconds));
}

function resolveDeadlineTimestamp(deadlineAt, fallbackRemainingSeconds, clockSkewMs) {
    const deadlineTimestamp = deadlineAt ? Date.parse(deadlineAt) : NaN;

    if (Number.isFinite(deadlineTimestamp)) {
        return deadlineTimestamp;
    }

    return Date.now() + clockSkewMs + fallbackRemainingSeconds * 1000;
}

/**
 * Ambil `status` dari respons error HTTP Inertia.
 */
function responseStatus(error) {
    const rawBody = error?.response?.data;

    if (typeof rawBody === 'string') {
        try {
            return JSON.parse(rawBody)?.status;
        } catch {
            return undefined;
        }
    }

    return rawBody?.status;
}

export default function useExamSession({
    exam,
    attemptId,
    questions = [],
    initialAnswers = {},
    initialFlags = [],
    initialTimeRemainingSeconds,
    initialDeadlineAt = null,
    initialServerNow = null,
    initialDurationSeconds = null,
}) {
    const { toast } = useToast();
    const {
        setData: setSaveData,
        post: postAnswers,
        processing: isAutoSaving,
    } = useHttp({
        attempt_id: attemptId,
        answers: {},
        flags: [],
    });
    const {
        setData: setSubmitData,
        post: postSubmit,
        processing: isSubmitting,
    } = useHttp({
        attempt_id: attemptId,
        answers: {},
    });

    const subjectName = exam.subject?.name ?? exam.subject ?? 'Mata Pelajaran Tidak Ditemukan';
    const packageName = exam.examPackage?.name ?? exam.package ?? 'Paket Ujian';
    const packageCode = exam.examPackage?.code ?? exam.package_code ?? '-';
    const examType = exam.examPackage?.examType?.name ?? exam.exam_type ?? 'Ujian';
    const sessionName = exam.session?.name ?? exam.session ?? '-';
    const totalQuestions = questions.length > 0 ? questions.length : (exam.total_questions ?? 40);
    const durationMinutes = exam.duration_minutes ?? 0;
    const scheduleDurationSeconds = durationMinutes * 60;
    const durationSeconds = Number.isFinite(initialDurationSeconds) && initialDurationSeconds > 0
        ? initialDurationSeconds
        : scheduleDurationSeconds;

    const initialServerTimestamp = initialServerNow ? Date.parse(initialServerNow) : NaN;
    const clockSkewRef = useRef(
        Number.isFinite(initialServerTimestamp) ? initialServerTimestamp - Date.now() : 0,
    );

    const initialRemainingSeconds = Number.isFinite(initialTimeRemainingSeconds)
        ? Math.max(0, Math.min(durationSeconds, initialTimeRemainingSeconds))
        : durationSeconds;

    const deadlineRef = useRef(
        resolveDeadlineTimestamp(initialDeadlineAt, initialRemainingSeconds, clockSkewRef.current),
    );

    const [timeRemaining, setTimeRemaining] = useState(
        () => remainingSecondsUntil(deadlineRef.current, clockSkewRef.current, durationSeconds),
    );
    const [deadlineVersion, setDeadlineVersion] = useState(0);
    const [isRunning, setIsRunning] = useState(true);
    const [answers, setAnswers] = useState(() => initialAnswers);
    const [flagged, setFlagged] = useState(() => new Set(initialFlags.map(Number)));
    const [currentQuestionNo, setCurrentQuestionNo] = useState(1);
    const [showSubmitModal, setShowSubmitModal] = useState(false);
    const [showQuestionModal, setShowQuestionModal] = useState(false);
    const [fontSize, setFontSize] = useState('normal');
    const timerRef = useRef(null);
    const pendingAnswersRef = useRef({});
    const flaggedRef = useRef(flagged);
    const saveQueueRef = useRef(Promise.resolve(true));
    const debounceTimerRef = useRef(null);
    const submissionInProgressRef = useRef(false);
    const submitAttemptRef = useRef(null);
    const [hasPendingSave, setHasPendingSave] = useState(false);

    flaggedRef.current = flagged;

    useEffect(() => {
        const updateTimeRemaining = () => {
            const remainingSeconds = remainingSecondsUntil(
                deadlineRef.current,
                clockSkewRef.current,
                durationSeconds,
            );
            setTimeRemaining(remainingSeconds);

            if (remainingSeconds === 0) {
                clearInterval(timerRef.current);
                timerRef.current = null;
                setIsRunning(false);
            }
        };

        updateTimeRemaining();

        if (remainingSecondsUntil(deadlineRef.current, clockSkewRef.current, durationSeconds) === 0) {
            return;
        }

        setIsRunning(true);
        timerRef.current = setInterval(updateTimeRemaining, 1000);

        return () => {
            clearInterval(timerRef.current);
            timerRef.current = null;
        };
    }, [durationSeconds, deadlineVersion]);

    const flaggedQuestionIds = useMemo(() => Array.from(flagged), [flagged]);

    const answerStats = useMemo(() => questions.reduce(
        (stats, question) => {
            // Flags are tracked separately from answers, so a marked question
            // still counts as answered once it has content.
            if (hasAnswerContent(answers[question.id])) {
                stats.answered += 1;
            }

            if (flagged.has(question.id)) {
                stats.flagged += 1;
            }

            return stats;
        },
        { answered: 0, flagged: 0 },
    ), [answers, flagged, questions]);

    const formattedTime = useMemo(() => {
        const hours = Math.floor(timeRemaining / 3600);
        const minutes = Math.floor((timeRemaining % 3600) / 60);
        const seconds = timeRemaining % 60;
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }, [timeRemaining]);

    const handleExitExam = () => {
        setShowSubmitModal(false);
        window.location.href = route('student.exam-result.show', exam.id);
    };

    /**
     * Sinkronkan timer dengan jawaban server.
     *
     * Server mengirim `deadline_at` dan `server_now`, sehingga selisih jam
     * perangkat peserta bisa dikoreksi dan tenggat yang dipakai selalu versi
     * server, bukan snapshot saat halaman pertama kali dibuka.
     */
    const syncTimerWithServer = (payload) => {
        const serverTimestamp = payload?.server_now ? Date.parse(payload.server_now) : NaN;

        if (Number.isFinite(serverTimestamp)) {
            clockSkewRef.current = serverTimestamp - Date.now();
        }

        const serverDeadlineTimestamp = payload?.deadline_at ? Date.parse(payload.deadline_at) : NaN;

        if (Number.isFinite(serverDeadlineTimestamp)) {
            deadlineRef.current = serverDeadlineTimestamp;
        } else if (Number.isFinite(payload?.remaining_seconds)) {
            deadlineRef.current = Date.now() + clockSkewRef.current + payload.remaining_seconds * 1000;
        } else {
            return;
        }

        setTimeRemaining(
            remainingSecondsUntil(deadlineRef.current, clockSkewRef.current, durationSeconds),
        );
        setDeadlineVersion((version) => version + 1);
    };

    const sendAnswerDiffToServer = async (answerDiff, nextFlaggedIds) => {
        setSaveData({
            attempt_id: attemptId,
            answers: answerDiff,
            flags: nextFlaggedIds,
        });

        try {
            await postAnswers(route('student.exam-answers.store', exam.id), {
                onSuccess: (response) => {
                    syncTimerWithServer(response);

                    if (Array.isArray(response?.flags)) {
                        setFlagged(new Set(response.flags.map(Number)));
                    }
                },
                onError: () => {
                    toast({
                        title: 'Gagal Menyimpan',
                        description: 'Data jawaban ditolak server. Periksa jawaban lalu coba kembali.',
                        type: 'error',
                    });
                },
            });

            return true;
        } catch (error) {
            const status = error?.response?.status;
            const serverStatus = responseStatus(error);

            if (status === 409 && serverStatus === 'reset') {
                window.location.href = route('student.exam', exam.id);

                return false;
            }

            if (status === 409 && serverStatus === 'submitted') {
                toast({
                    title: 'Ujian Sudah Dikumpulkan',
                    description: 'Jawaban sudah final di server. Anda akan diarahkan ke portal siswa.',
                    type: 'info',
                });

                window.location.href = route('student.dashboard');

                return false;
            }

            if (status === 409 && serverStatus === 'expired') {
                window.location.href = route('student.exam-result.show', exam.id);

                return false;
            }

            if (status === 409) {
                clearInterval(timerRef.current);
                timerRef.current = null;
                setIsRunning(false);
                setTimeRemaining(0);
                toast({
                    title: 'Waktu Ujian Habis',
                    description: 'Waktu pengerjaan telah berakhir di server. Jawaban tidak dapat diubah lagi.',
                    type: 'error',
                });

                return false;
            }

            toast({
                title: 'Gagal Menyimpan',
                description: status === 419
                    ? 'Sesi keamanan sudah kedaluwarsa. Muat ulang halaman lalu coba kembali.'
                    : status
                        ? `Server menolak penyimpanan jawaban (HTTP ${status}).`
                        : 'Terjadi kesalahan jaringan saat menyimpan jawaban.',
                type: 'error',
            });

            return false;
        }
    };

    const enqueueAnswerDiff = (answerDiff, nextFlaggedIds) => {
        const request = () => sendAnswerDiffToServer(answerDiff, nextFlaggedIds);
        const queuedRequest = saveQueueRef.current.then(request, request);
        saveQueueRef.current = queuedRequest;

        return queuedRequest;
    };

    const flushPendingAnswers = async (nextFlaggedIds = null) => {
        clearTimeout(debounceTimerRef.current);
        debounceTimerRef.current = null;

        const answerDiff = pendingAnswersRef.current;
        pendingAnswersRef.current = {};

        if (Object.keys(answerDiff).length === 0 && nextFlaggedIds === null) {
            return saveQueueRef.current;
        }

        setHasPendingSave(false);

        const result = await enqueueAnswerDiff(
            answerDiff,
            nextFlaggedIds ?? Array.from(flaggedRef.current),
        );

        if (Object.keys(pendingAnswersRef.current).length === 0) {
            setHasPendingSave(false);
        }

        return result;
    };

    const scheduleAnswerSave = (questionId, answer) => {
        pendingAnswersRef.current = {
            ...pendingAnswersRef.current,
            [questionId]: answer,
        };
        setHasPendingSave(true);
        clearTimeout(debounceTimerRef.current);
        debounceTimerRef.current = setTimeout(() => {
            void flushPendingAnswers();
        }, 1000);
    };

    const handleAnswerChange = (questionId, answer) => {
        setAnswers((currentAnswers) => ({
            ...currentAnswers,
            [questionId]: answer,
        }));
        scheduleAnswerSave(questionId, answer);
    };

    /**
     * Tandai atau lepas tanda ragu-ragu tanpa menyentuh jawaban tersimpan.
     */
    const handleFlagToggle = (questionId) => {
        const nextFlagged = new Set(flaggedRef.current);

        if (nextFlagged.has(questionId)) {
            nextFlagged.delete(questionId);
        } else {
            nextFlagged.add(questionId);
        }

        flaggedRef.current = nextFlagged;
        setFlagged(nextFlagged);
        void flushPendingAnswers(Array.from(nextFlagged));
    };

    const handleSubmitAnswer = async (event) => {
        if (event) {
            event.preventDefault();
        }

        if (submissionInProgressRef.current) {
            return;
        }

        submissionInProgressRef.current = true;

        const answersToSubmit = questions.reduce((submission, question) => {
            submission[question.id] = answers[question.id] ?? null;
            return submission;
        }, {});

        try {
            clearTimeout(debounceTimerRef.current);
            debounceTimerRef.current = null;
            pendingAnswersRef.current = {};
            setHasPendingSave(false);
            await saveQueueRef.current;

            setSubmitData({
                attempt_id: attemptId,
                answers: answersToSubmit,
            });

            await postSubmit(route('student.exam-submit.store', exam.id), {
                onSuccess: () => {
                    window.location.href = route('student.exam-result.show', exam.id);
                },
                onError: () => {
                    submissionInProgressRef.current = false;
                    toast({
                        title: 'Gagal Mengumpulkan',
                        description: 'Server menolak pengumpulan ujian. Periksa koneksi lalu coba kembali.',
                        type: 'error',
                    });
                },
            });
        } catch (error) {
            submissionInProgressRef.current = false;
            if (error?.response?.status === 409 && responseStatus(error) === 'reset') {
                window.location.href = route('student.exam', exam.id);

                return;
            }

            toast({
                title: 'Gagal Mengumpulkan',
                description: error?.response?.status
                    ? `Server menolak pengumpulan ujian (HTTP ${error.response.status}).`
                    : 'Terjadi kesalahan jaringan saat mengumpulkan ujian.',
                type: 'error',
            });
        }
    };

    submitAttemptRef.current = handleSubmitAnswer;

    useEffect(() => {
        if (timeRemaining === 0) {
            void flushPendingAnswers(flaggedQuestionIds);
        }
    }, [timeRemaining, flaggedQuestionIds]);

    const goToPreviousQuestion = () => {
        void flushPendingAnswers();
        setCurrentQuestionNo((previous) => Math.max(1, previous - 1));
    };

    const goToNextQuestion = () => {
        void flushPendingAnswers();
        setCurrentQuestionNo((previous) => Math.min(totalQuestions, previous + 1));
    };

    const selectQuestion = (questionNo) => {
        void flushPendingAnswers();
        setCurrentQuestionNo(questionNo);
        setShowQuestionModal(false);
    };

    useEffect(() => {
        const flushOnVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                void flushPendingAnswers();
            }
        };

        const flushBeforeUnload = () => {
            const answerDiff = pendingAnswersRef.current;

            if (Object.keys(answerDiff).length === 0) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const body = JSON.stringify({
                _token: csrfToken,
                attempt_id: attemptId,
                answers: answerDiff,
                flags: Array.from(flaggedRef.current),
            });
            const answersUrl = route('student.exam-answers.store', exam.id);
            const beaconQueued = typeof navigator.sendBeacon === 'function'
                && navigator.sendBeacon(
                    answersUrl,
                    new Blob([body], { type: 'application/json' }),
                );

            if (!beaconQueued) {
                void fetch(answersUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body,
                    credentials: 'same-origin',
                    keepalive: true,
                })
                    .then((response) => {
                        if (!response.ok) {
                            toast({
                                title: 'Gagal Menyimpan',
                                description: `Penyimpanan sebelum keluar gagal (HTTP ${response.status}).`,
                                type: 'error',
                            });
                        }
                    })
                    .catch(() => {
                        toast({
                            title: 'Gagal Menyimpan',
                            description: 'Jawaban belum sempat disimpan sebelum halaman ditutup.',
                            type: 'error',
                        });
                    });
            }
        };

        document.addEventListener('visibilitychange', flushOnVisibilityChange);
        window.addEventListener('beforeunload', flushBeforeUnload);

        return () => {
            document.removeEventListener('visibilitychange', flushOnVisibilityChange);
            window.removeEventListener('beforeunload', flushBeforeUnload);
            clearTimeout(debounceTimerRef.current);
        };
    }, [exam.id]);

    return {
        subjectName,
        packageName,
        packageCode,
        examType,
        sessionName,
        totalQuestions,
        durationMinutes,
        timeRemaining,
        isRunning,
        answers,
        flaggedQuestionIds,
        isAutoSaving: isAutoSaving || hasPendingSave,
        isSubmitting,
        currentQuestionNo,
        answeredCount: answerStats.answered,
        flaggedCount: answerStats.flagged,
        showSubmitModal,
        showQuestionModal,
        setShowSubmitModal,
        setShowQuestionModal,
        fontSize,
        setFontSize,
        formattedTime,
        handleAnswerChange,
        handleFlagToggle,
        handleSubmitAnswer,
        handleExitExam,
        goToPreviousQuestion,
        goToNextQuestion,
        selectQuestion,
        setCurrentQuestionNo,
    };
}
