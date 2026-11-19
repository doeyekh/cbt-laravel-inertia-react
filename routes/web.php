<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AssessmentTypeController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\ClassMemberController;
use App\Http\Controllers\Admin\ExamAttemptResetController;
use App\Http\Controllers\Admin\ExamEssayGradeController;
use App\Http\Controllers\Admin\ExamPackageController;
use App\Http\Controllers\Admin\ExamParticipantController;
use App\Http\Controllers\Admin\ExamScheduleController;
use App\Http\Controllers\Admin\ExamTypeController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionImportController;
use App\Http\Controllers\Admin\ExamReportController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentRegistrationController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SubjectTeacherController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\Student\ExamAccessController;
use App\Http\Controllers\Student\ExamAnswerController;
use App\Http\Controllers\Student\ExamController;
use App\Http\Controllers\Student\ExamResultController;
use App\Http\Controllers\Student\ExamSubmitController;
use App\Http\Controllers\Student\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StudentLoginController::class, 'create'])->name('student.login');
Route::get('/login', [StudentLoginController::class, 'create'])->name('login');
Route::post('/login', [StudentLoginController::class, 'store'])->name('student.login.store');
Route::post('/logout', [StudentLoginController::class, 'destroy'])->name('student.logout');

Route::middleware('student.auth')
    ->prefix('siswa')
    ->name('student.')
    ->group(function () {
        Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('exams/{examSchedule}', [ExamController::class, 'show'])->name('exam');
        Route::post('exams/{examSchedule}/answers', [ExamAnswerController::class, 'store'])
            ->name('exam-answers.store');
        Route::post('exams/{examSchedule}/submit', [ExamSubmitController::class, 'store'])
            ->name('exam-submit.store');
        Route::get('exams/{examSchedule}/result', [ExamResultController::class, 'show'])
            ->name('exam-result.show');
        Route::get('exams/{examSchedule}/access-token', [ExamAccessController::class, 'create'])
            ->name('exam-access.create');
        Route::post('exams/{examSchedule}/access-token', [ExamAccessController::class, 'verify'])
            ->name('exam-access.verify');
    });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');

    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::post('/logout', [LogoutController::class, 'destroy'])
        ->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])
            ->name('profile.show');

        Route::put('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('/password/edit', [PasswordUpdateController::class, 'edit'])
            ->name('password.edit');

        Route::put('/password/update', [PasswordUpdateController::class, 'update'])
            ->name('password.update');

        Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');

        Route::get('/monitoring', [PageController::class, 'monitoring'])
            ->middleware('school-admin')->name('monitoring.index');
        Route::middleware('school-admin')->group(function () {
            Route::get('/reports', [ExamReportController::class, 'index'])
                ->name('reports.index');
            Route::get('/reports/{examSchedule}', [ExamReportController::class, 'show'])
                ->name('reports.show');
            Route::get('/reports/{examSchedule}/export', [ExamReportController::class, 'export'])
                ->name('reports.export');
        });
        Route::middleware('school-admin')->group(function () {
            Route::delete('exam-attempts/{examAttempt}/reset', [ExamAttemptResetController::class, 'destroy'])
                ->name('exam-attempts.reset');
            Route::get('exam-attempts/{examAttempt}/essays', [ExamEssayGradeController::class, 'index'])
                ->name('exam-attempts.essays.index');
            Route::patch('exam-attempts/{examAttempt}/essays/{examAnswer}', [ExamEssayGradeController::class, 'update'])
                ->name('exam-attempts.essays.update');
        });

        Route::patch('schools/{school}/credential-template', [ExamParticipantController::class, 'updateCredentialTemplate'])
            ->name('schools.credential-template');

        Route::get('exam-participants/cards', [ExamParticipantController::class, 'cards'])
            ->name('exam-participants.cards');

        Route::post('exam-participants/sync', [ExamParticipantController::class, 'sync'])
            ->name('exam-participants.sync');

        Route::post('exam-participants/generate', [ExamParticipantController::class, 'generate'])
            ->name('exam-participants.generate');

        Route::patch('exam-participants/placement', [ExamParticipantController::class, 'bulkPlacement'])
            ->name('exam-participants.bulk-placement');

        Route::patch('exam-participants/{examParticipant}/password', [ExamParticipantController::class, 'resetPassword'])
            ->name('exam-participants.reset-password');

        Route::resource('exam-participants', ExamParticipantController::class)
            ->only(['index', 'update', 'destroy'])
            ->parameters(['exam-participants' => 'examParticipant']);

        Route::patch('exam-types/{examTypeId}/status', [ExamTypeController::class, 'toggleStatus'])
            ->name('exam-types.toggle-status');

        Route::resource('exam-types', ExamTypeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('exam-packages/{examPackage}/schedules', [ExamScheduleController::class, 'store'])
            ->name('exam-schedules.store');

        Route::put('exam-packages/{examPackage}/schedules/{examSchedule}', [ExamScheduleController::class, 'update'])
            ->name('exam-schedules.update');

        Route::delete('exam-packages/{examPackage}/schedules/{examSchedule}', [ExamScheduleController::class, 'destroy'])
            ->name('exam-schedules.destroy');

        Route::patch('exam-packages/{examPackage}/schedules/{examSchedule}/status', [ExamScheduleController::class, 'toggleStatus'])
            ->name('exam-schedules.toggle-status');

        Route::patch('exam-packages/{examPackage}/schedules/bulk', [ExamScheduleController::class, 'bulkUpdate'])
            ->name('exam-schedules.bulk-update');

        Route::get('question-banks/{questionBank}/questions', [QuestionController::class, 'index'])
            ->name('question-banks.questions.index');

        Route::get('question-banks/{questionBank}/questions/preview', [QuestionController::class, 'preview'])
            ->name('question-banks.questions.preview');

        Route::get('question-banks/{questionBank}/questions/print', [QuestionController::class, 'print'])
            ->name('question-banks.questions.print');

        Route::get('question-banks/{questionBank}/questions/import', [QuestionImportController::class, 'index'])
            ->name('question-banks.questions.import');

        Route::post('question-banks/{questionBank}/questions/import', [QuestionImportController::class, 'store'])
            ->name('question-banks.questions.import.store');

        Route::get('question-banks/{questionBank}/questions/import/template', [QuestionImportController::class, 'template'])
            ->name('question-banks.questions.import.template');

        Route::post('question-banks/{questionBank}/questions', [QuestionController::class, 'store'])
            ->name('question-banks.questions.store');

        Route::put('question-banks/{questionBank}/questions/{question}', [QuestionController::class, 'update'])
            ->name('question-banks.questions.update');

        Route::delete('question-banks/{questionBank}/questions/{question}', [QuestionController::class, 'destroy'])
            ->name('question-banks.questions.destroy');

        Route::delete('question-banks/{questionBank}/questions', [QuestionController::class, 'destroyAll'])
            ->name('question-banks.questions.destroy-all');

        Route::resource('question-banks', QuestionBankController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('exam-packages', ExamPackageController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::post('exam-packages/{examPackage}/access-token', [ExamPackageController::class, 'generateAccessToken'])
            ->name('exam-packages.access-token.store');

        Route::delete('exam-packages/{examPackage}/access-token', [ExamPackageController::class, 'revokeAccessToken'])
            ->name('exam-packages.access-token.destroy');

        Route::patch('sessions/{sessionId}/status', [SessionController::class, 'toggleStatus'])
            ->name('sessions.toggle-status');

        Route::resource('sessions', SessionController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::patch('rooms/{roomId}/status', [RoomController::class, 'toggleStatus'])
            ->name('rooms.toggle-status');

        Route::resource('rooms', RoomController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::patch('classes/{schoolClass}/status', [ClassController::class, 'toggleStatus'])
            ->name('classes.toggle-status');

        Route::resource('classes', ClassController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['classes' => 'schoolClass']);

        Route::get('classes/{schoolClass}/subject-learning', [SubjectTeacherController::class, 'index'])
            ->name('subject-teachers.index');

        Route::post('classes/{schoolClass}/subject-learning', [SubjectTeacherController::class, 'store'])
            ->name('subject-teachers.store');

        Route::put('classes/{schoolClass}/subject-learning/{subjectTeacher}', [SubjectTeacherController::class, 'update'])
            ->name('subject-teachers.update');

        Route::patch('classes/{schoolClass}/subject-learning/{subjectTeacher}/status', [SubjectTeacherController::class, 'toggleStatus'])
            ->name('subject-teachers.toggle-status');

        Route::delete('classes/{schoolClass}/subject-learning/{subjectTeacher}', [SubjectTeacherController::class, 'destroy'])
            ->name('subject-teachers.destroy');

        Route::get('classes/{schoolClass}/students', [ClassMemberController::class, 'index'])
            ->name('class-members.index');

        Route::get('classes/{schoolClass}/students/available', [ClassMemberController::class, 'available'])
            ->name('class-members.available');

        Route::post('classes/{schoolClass}/students', [ClassMemberController::class, 'store'])
            ->name('class-members.store');

        Route::delete('classes/{schoolClass}/students/{classMember}', [ClassMemberController::class, 'destroy'])
            ->name('class-members.destroy');

        Route::post('students/import', [StudentController::class, 'import'])
            ->name('students.import');

        Route::get('students/aktif', [StudentController::class, 'active'])
            ->name('students.active');

        Route::get('students/alumni', [StudentController::class, 'alumni'])
            ->name('students.alumni');

        Route::patch('students/bulk-graduate', [StudentController::class, 'bulkGraduate'])
            ->name('students.bulk-graduate');

        Route::get('students/pull/available', [StudentRegistrationController::class, 'available'])
            ->name('student-registrations.available');

        Route::resource('students', StudentController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('students/{student}/registrations', [StudentRegistrationController::class, 'index'])
            ->name('student-registrations.index');

        Route::post('students/{student}/registrations', [StudentRegistrationController::class, 'store'])
            ->name('student-registrations.store');

        Route::put('students/{student}/registrations/{registration}', [StudentRegistrationController::class, 'update'])
            ->name('student-registrations.update');

        Route::delete('students/{student}/registrations/{registration}', [StudentRegistrationController::class, 'destroy'])
            ->name('student-registrations.destroy');

        Route::patch('schools/{school}/status', [SchoolController::class, 'toggleStatus'])
            ->name('schools.toggle-status');

        Route::resource('schools', SchoolController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('subjects/import', [SubjectController::class, 'import'])
            ->name('subjects.import');

        Route::patch('subjects/{subject}/status', [SubjectController::class, 'toggleStatus'])
            ->name('subjects.toggle-status');

        Route::resource('subjects', SubjectController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::patch('assessment-types/{assessmentType}/status', [AssessmentTypeController::class, 'toggleStatus'])
            ->name('assessment-types.toggle-status');

        Route::resource('assessment-types', AssessmentTypeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Users management hanya untuk administrator
        Route::middleware('administrator')->group(function () {
            Route::post('academic-years/generate', [AcademicYearController::class, 'generate'])
                ->name('academic-years.generate');

            Route::patch('academic-years/{academic_year}/status', [AcademicYearController::class, 'toggleStatus'])
                ->name('academic-years.toggle-status');

            Route::resource('academic-years', AcademicYearController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            Route::post('users/import', [UserController::class, 'import'])
                ->name('users.import');

            Route::patch('users/{userId}/status', [UserController::class, 'toggleStatus'])
                ->name('users.toggle-status');

            Route::resource('users', UserController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });
    });
});
