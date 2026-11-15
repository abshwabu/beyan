<?php

use App\Domain\Academics\Http\Controllers\AcademicYearController;
use App\Domain\Academics\Http\Controllers\TeacherTimetableController;
use App\Domain\Attendance\Http\Controllers\AttendanceController;
use App\Domain\Auth\Http\Controllers\AuthenticatedSessionController;
use App\Domain\Auth\Http\Controllers\PasswordResetController;
use App\Domain\Auth\Http\Controllers\UserInvitationController;
use App\Domain\Finance\Http\Controllers\FinanceController;
use App\Domain\Hifz\Http\Controllers\HifzController;
use App\Domain\Institution\Http\Controllers\DashboardController;
use App\Domain\Institution\Http\Controllers\InstitutionRegistrationController;
use App\Domain\Institution\Http\Controllers\PlatformInstitutionController;
use App\Domain\Reports\Http\Controllers\ReportController;
use App\Domain\Students\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public Root Redirect
Route::get('/', function () {
    if (Auth::check()) {
        if (Auth::user()->isPlatformSuperAdmin()) {
            return redirect()->route('platform.institutions.index');
        }

        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

    Route::get('/register-institution', [InstitutionRegistrationController::class, 'create'])->name('institution.register');
    Route::post('/register-institution', [InstitutionRegistrationController::class, 'store'])->name('institution.register.store');

    // Public invitation acceptance
    Route::get('/invitations/{token}/accept', [UserInvitationController::class, 'showAccept'])->name('invitations.accept');
    Route::post('/invitations/{token}/accept', [UserInvitationController::class, 'accept'])->name('invitations.process');
});

// Authenticated Logout
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Platform Super Admin Routes (Outside any institution)
Route::middleware(['auth', 'platform.admin'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/institutions', [PlatformInstitutionController::class, 'index'])->name('institutions.index');
    Route::post('/institutions/{id}/suspend', [PlatformInstitutionController::class, 'suspend'])->name('institutions.suspend');
    Route::post('/institutions/{id}/activate', [PlatformInstitutionController::class, 'activate'])->name('institutions.activate');
    Route::put('/institutions/{id}/plan', [PlatformInstitutionController::class, 'updatePlan'])->name('institutions.plan');
});

// Tenant Workspace Routes (Protected by auth and tenant middleware)
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // User Invitations
    Route::post('/invitations', [UserInvitationController::class, 'store'])->name('invitations.store');

    // Academic Years
    Route::prefix('academic-years')->name('academic-years.')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::post('/', [AcademicYearController::class, 'store'])->name('store');
        Route::get('/{id}', [AcademicYearController::class, 'show'])->name('show');
        Route::put('/{id}', [AcademicYearController::class, 'update'])->name('update');
        Route::delete('/{id}', [AcademicYearController::class, 'destroy'])->name('destroy');
    });

    // Students
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index');
        Route::get('/create', [StudentController::class, 'create'])->name('create');
        Route::post('/', [StudentController::class, 'store'])->name('store');
        Route::get('/{id}', [StudentController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [StudentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [StudentController::class, 'update'])->name('update');
        Route::delete('/{id}', [StudentController::class, 'delete'])->name('delete');
        Route::post('/{id}/status', [StudentController::class, 'changeStatus'])->name('status');
        Route::post('/{id}/transfer', [StudentController::class, 'transferClass'])->name('transfer');
        Route::post('/{id}/documents', [StudentController::class, 'uploadDocument'])->name('documents.upload');
        Route::get('/{id}/documents/{documentId}/download', [StudentController::class, 'downloadDocument'])->name('documents.download');
        Route::delete('/{id}/documents/{documentId}', [StudentController::class, 'deleteDocument'])->name('documents.delete');
    });

    // Timetable
    Route::get('/timetable/today', [TeacherTimetableController::class, 'today'])->name('timetable.today');

    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/analytics', [AttendanceController::class, 'analytics'])->name('analytics');
        Route::get('/flagged-absences', [AttendanceController::class, 'flaggedAbsences'])->name('flagged');
        Route::post('/sessions/{sessionId}', [AttendanceController::class, 'saveSessionAttendance'])->name('sessions.save');
        Route::get('/{id}', [AttendanceController::class, 'show'])->name('show');
    });

    // Finance
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/invoices', [FinanceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices', [FinanceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{id}', [FinanceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{id}/payments', [FinanceController::class, 'recordPayment'])->name('invoices.payments.store');
        Route::post('/invoices/{id}/remind', [FinanceController::class, 'sendReminder'])->name('invoices.remind');

        Route::post('/payments/{id}/reversal', [FinanceController::class, 'recordReversal'])->name('payments.reversal');
        Route::get('/payments/{id}/receipt', [FinanceController::class, 'receipt'])->name('payments.receipt');

        Route::get('/fee-plans', [FinanceController::class, 'feePlans'])->name('fee-plans.index');
        Route::post('/fee-plans', [FinanceController::class, 'storeFeePlan'])->name('fee-plans.store');
        Route::post('/students/{student}/discounts', [FinanceController::class, 'storeStudentDiscount'])->name('students.discounts.store');

        Route::get('/expenses', [FinanceController::class, 'expenses'])->name('expenses.index');
        Route::post('/expenses', [FinanceController::class, 'storeExpense'])->name('expenses.store');

        Route::get('/reports/income-expense', [FinanceController::class, 'incomeExpenseReport'])->name('reports.income-expense');
        Route::get('/reminders', [FinanceController::class, 'overdueReminders'])->name('reminders.index');
    });

    // Qur'an & Hifz Progress
    Route::prefix('hifz')->name('hifz.')->group(function () {
        Route::get('/', [HifzController::class, 'index'])->name('index');
        Route::get('/fast-entry', [HifzController::class, 'fastEntry'])->name('fast-entry');
        Route::post('/entries', [HifzController::class, 'store'])->name('entries.store');
        Route::get('/students/{student}', [HifzController::class, 'studentProfile'])->name('student.profile');
        Route::post('/students/{student}/targets', [HifzController::class, 'storeTarget'])->name('student.targets.store');
        Route::post('/students/{student}/certifications', [HifzController::class, 'storeCertification'])->name('student.certifications.store');
        Route::get('/students/{student}/compare', [HifzController::class, 'compareProgress'])->name('student.compare');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{type}', [ReportController::class, 'show'])->name('show');
        Route::get('/{type}/export', [ReportController::class, 'export'])->name('export');
    });
});
