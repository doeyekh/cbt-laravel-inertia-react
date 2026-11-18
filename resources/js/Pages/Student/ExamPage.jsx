import React from 'react';
import ExamHeader from '@/Features/Exam/Components/ExamHeader';
import ExamWorkspace from '@/Features/Exam/Components/ExamWorkspace';
import QuestionListModal from '@/Features/Exam/Components/QuestionListModal';
import SubmitConfirmationModal from '@/Features/Exam/Components/SubmitConfirmationModal';
import useExamSession from '@/Features/Exam/Hooks/useExamSession';

export default function ExamPage({ exam, questions = [], answers = {} }) {
    const examSession = useExamSession({ exam, questions, initialAnswers: answers });

    return (
        <div className="min-h-screen bg-slate-100 font-sans">
            <ExamHeader
                packageName={examSession.packageName}
                subjectName={examSession.subjectName}
                packageCode={examSession.packageCode}
                formattedTime={examSession.formattedTime}
                sessionName={examSession.sessionName}
                examType={examSession.examType}
            />

            <main className="flex-1 w-full max-w-[1720px] mx-auto p-4 sm:p-5 flex flex-col gap-3 items-start">
                <ExamWorkspace
                    exam={exam}
                    questions={questions}
                    examType={examSession.examType}
                    durationMinutes={examSession.durationMinutes}
                    totalQuestions={examSession.totalQuestions}
                    currentQuestionNo={examSession.currentQuestionNo}
                    fontSize={examSession.fontSize}
                    setFontSize={examSession.setFontSize}
                    answers={examSession.answers}
                    isAutoSaving={examSession.isAutoSaving}
                    handleAnswerChange={examSession.handleAnswerChange}
                    goToPreviousQuestion={examSession.goToPreviousQuestion}
                    goToNextQuestion={examSession.goToNextQuestion}
                    setShowQuestionModal={examSession.setShowQuestionModal}
                />

                <SubmitConfirmationModal
                    isOpen={examSession.showSubmitModal}
                    onClose={() => examSession.setShowSubmitModal(false)}
                    onSubmit={examSession.handleSubmitAnswer}
                    subjectName={examSession.subjectName}
                    packageName={examSession.packageName}
                    formattedTime={examSession.formattedTime}
                    answeredCount={examSession.answeredCount}
                    flaggedCount={examSession.flaggedCount}
                    totalQuestions={examSession.totalQuestions}
                    isAutoSaving={examSession.isAutoSaving}
                />

                <QuestionListModal
                    isOpen={examSession.showQuestionModal}
                    onClose={() => examSession.setShowQuestionModal(false)}
                    onSelectQuestion={examSession.selectQuestion}
                    questions={questions}
                    subjectName={examSession.subjectName}
                    packageCode={examSession.packageCode}
                    totalQuestions={examSession.totalQuestions}
                    answeredCount={examSession.answeredCount}
                    flaggedCount={examSession.flaggedCount}
                    currentQuestionNo={examSession.currentQuestionNo}
                    answers={examSession.answers}
                />
            </main>
        </div>
    );
}
