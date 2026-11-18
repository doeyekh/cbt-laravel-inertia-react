import { useEffect, useMemo, useRef, useState } from 'react';
import { useHttp } from '@inertiajs/react';
import { useToast } from '@/Hooks/useToast';

export default function useExamSession({
    exam,
    questions = [],
    initialAnswers = {},
    initialTimeRemainingSeconds,
}) {
    const { toast } = useToast();
    const {
        setData: setSaveData,
        post: postAnswers,
        processing: isAutoSaving,
    } = useHttp({
        answers: {},
        time_spent_seconds: 0,
    });

    const subjectName = exam.subject?.name ?? exam.subject ?? 'Mata Pelajaran Tidak Ditemukan';
    const packageName = exam.examPackage?.name ?? exam.package ?? 'Paket Ujian';
    const packageCode = exam.examPackage?.code ?? exam.package_code ?? '-';
    const examType = exam.examPackage?.examType?.name ?? exam.exam_type ?? 'Ujian';
    const sessionName = exam.session?.name ?? exam.session ?? '-';
    const totalQuestions = questions.length > 0 ? questions.length : (exam.total_questions ?? 40);
    const durationMinutes = exam.duration_minutes ?? 0;
    const durationSeconds = durationMinutes * 60;
    const countdownStartSeconds = Number.isFinite(initialTimeRemainingSeconds)
        ? Math.max(0, Math.min(durationSeconds, initialTimeRemainingSeconds))
        : durationSeconds;

    const [timeRemaining, setTimeRemaining] = useState(countdownStartSeconds);
    const [isRunning, setIsRunning] = useState(true);
    const [answers, setAnswers] = useState(() => initialAnswers);
    const [currentQuestionNo, setCurrentQuestionNo] = useState(1);
    const [showSubmitModal, setShowSubmitModal] = useState(false);
    const [showQuestionModal, setShowQuestionModal] = useState(false);
    const [fontSize, setFontSize] = useState('normal');
    const timerRef = useRef(null);

    useEffect(() => {
        const deadline = Date.now() + countdownStartSeconds * 1000;
        const updateTimeRemaining = () => {
            const remainingSeconds = Math.max(
                0,
                Math.ceil((deadline - Date.now()) / 1000),
            );
            setTimeRemaining(remainingSeconds);

            if (remainingSeconds === 0) {
                clearInterval(timerRef.current);
                setIsRunning(false);
            }
        };

        if (countdownStartSeconds === 0) {
            setIsRunning(false);
        } else {
            timerRef.current = setInterval(updateTimeRemaining, 1000);
        }

        return () => {
            clearInterval(timerRef.current);
        };
    }, [countdownStartSeconds]);

    const answerStats = useMemo(() => questions.reduce(
        (stats, question) => {
            const answer = answers[question.id];

            if (answer === 'flagged') {
                stats.flagged += 1;
            } else if (question.type === 'menjodohkan') {
                const pairCount = question.pairs?.length ?? 0;
                const allPairsAnswered = Array.isArray(answer)
                    && pairCount > 0
                    && answer.length >= pairCount
                    && answer.slice(0, pairCount).every(
                        (pairAnswer) => typeof pairAnswer === 'string' && pairAnswer !== ''
                    );

                if (allPairsAnswered) {
                    stats.answered += 1;
                }
            } else if (
                Array.isArray(answer)
                    ? answer.some((value) => value !== '')
                    : answer !== null && answer !== undefined && answer !== ''
            ) {
                stats.answered += 1;
            }
            return stats;
        },
        { answered: 0, flagged: 0 },
    ), [answers, questions]);

    const formattedTime = useMemo(() => {
        const hours = Math.floor(timeRemaining / 3600);
        const minutes = Math.floor((timeRemaining % 3600) / 60);
        const seconds = timeRemaining % 60;
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }, [timeRemaining]);

    const handleExitExam = () => {
        setShowSubmitModal(false);
        window.location.href = route('student.dashboard');
    };

    const saveAnswersToServer = async (nextAnswers, exitAfterSave = false) => {
        if (Object.keys(nextAnswers).length === 0) {
            return;
        }

        setSaveData({
            answers: nextAnswers,
            time_spent_seconds: durationSeconds - timeRemaining,
        });

        try {
            await postAnswers(route('student.exam-answers.store', exam.id), {
                onSuccess: () => {
                    if (exitAfterSave) {
                        handleExitExam();
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
        } catch (error) {
            const status = error?.response?.status;
            toast({
                title: 'Gagal Menyimpan',
                description: status === 419
                    ? 'Sesi keamanan sudah kedaluwarsa. Muat ulang halaman lalu coba kembali.'
                    : status
                        ? `Server menolak penyimpanan jawaban (HTTP ${status}).`
                        : 'Terjadi kesalahan jaringan saat menyimpan jawaban.',
                type: 'error',
            });
        }
    };

    const handleAnswerChange = async (questionNo, answer) => {
        const nextAnswers = {
            ...answers,
            [questionNo]: answer,
        };
        setAnswers(nextAnswers);
        await saveAnswersToServer(nextAnswers, false);
    };

    const handleSubmitAnswer = async (event) => {
        if (event) {
            event.preventDefault();
        }

        const answersToSubmit = questions.reduce((submission, question) => {
            submission[question.id] = answers[question.id] ?? null;
            return submission;
        }, {});

        await saveAnswersToServer(answersToSubmit, true);
    };

    const goToPreviousQuestion = () => {
        setCurrentQuestionNo((previous) => Math.max(1, previous - 1));
    };

    const goToNextQuestion = () => {
        setCurrentQuestionNo((previous) => Math.min(totalQuestions, previous + 1));
    };

    const selectQuestion = (questionNo) => {
        setCurrentQuestionNo(questionNo);
        setShowQuestionModal(false);
    };

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
        isAutoSaving,
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
        handleSubmitAnswer,
        handleExitExam,
        goToPreviousQuestion,
        goToNextQuestion,
        selectQuestion,
        setCurrentQuestionNo,
    };
}
