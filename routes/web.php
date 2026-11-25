<?php

use App\Domain\Academics\Http\Controllers\AcademicYearController;
use App\Domain\Academics\Http\Controllers\ClassGroupController;
use App\Domain\Academics\Http\Controllers\CourseController;
use App\Domain\Academics\Http\Controllers\TeacherTimetableController;
use App\Domain\Attendance\Http\Controllers\AttendanceController;
use App\Domain\Auth\Http\Controllers\AuthenticatedSessionController;
use App\Domain\Auth\Http\Controllers\PasswordResetController;
use App\Domain\Auth\Http\Controllers\UserInvitationController;
use App\Domain\Finance\Http\Controllers\FinanceController;
use App\Domain\Hifz\Http\Controllers\HifzController;
use App\Domain\Institution\Http\Controllers\DashboardController;
use App\Domain\Institution\Http\Controllers\InstitutionRegistrationController;
use App\Domain\Institution\Http\Controllers\LandingPageController;
use App\Domain\Institution\Http\Controllers\StaffController;
use App\Domain\Platform\Http\Controllers\PlatformAnnouncementController;
use App\Domain\Platform\Http\Controllers\PlatformAuditLogController;
use App\Domain\Platform\Http\Controllers\PlatformDemoRequestController;
use App\Domain\Platform\Http\Controllers\PlatformImpersonationController;
use App\Domain\Platform\Http\Controllers\PlatformInstitutionController;
use App\Domain\Platform\Http\Controllers\PlatformOverviewController;
use App\Domain\Platform\Http\Controllers\PlatformPaymentController;
use App\Domain\Platform\Http\Controllers\PlatformPlanController;
use App\Domain\Platform\Http\Controllers\PlatformSettingsController;
use App\Domain\Platform\Http\Controllers\PlatformStaffController;
use App\Domain\Platform\Http\Controllers\PlatformSubscriptionController;
use App\Domain\Platform\Http\Controllers\PlatformSystemHealthController;
use App\Domain\Reports\Http\Controllers\ReportController;
use App\Domain\Students\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public Landing & Marketing Routes
Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::get('/locale/{locale}', [LandingPageController::class, 'setLocale'])->name('locale.set');
Route::post('/demo-request', [LandingPageController::class, 'submitDemo'])->middleware('throttle:5,10')->name('demo.request');
Route::get('/sitemap.xml', [LandingPageController::class, 'sitemap'])->name('sitemap');
Route::get('/privacy', [LandingPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LandingPageController::class, 'terms'])->name('terms');

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

// Impersonation Stop Route (Authenticated tenant user returning to platform admin)
Route::post('/platform/impersonate/stop', [PlatformImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('platform.impersonate.stop');

// Platform Super Admin & Staff Routes (Outside any institution)
Route::middleware(['auth', 'platform.admin'])->prefix('platform')->name('platform.')->group(function () {
    // 1. Overview Dashboard
    Route::get('/', [PlatformOverviewController::class, 'index'])->name('overview');
    Route::get('/dashboard', [PlatformOverviewController::class, 'index'])->name('dashboard');

    // 2. Institutions Management
    Route::get('/institutions', [PlatformInstitutionController::class, 'index'])->name('institutions.index');
    Route::get('/institutions/create', [PlatformInstitutionController::class, 'create'])->name('institutions.create');
    Route::post('/institutions', [PlatformInstitutionController::class, 'store'])->name('institutions.store');
    Route::get('/institutions/{id}', [PlatformInstitutionController::class, 'show'])->name('institutions.show');
    Route::get('/institutions/{id}/edit', [PlatformInstitutionController::class, 'edit'])->name('institutions.edit');
    Route::put('/institutions/{id}', [PlatformInstitutionController::class, 'update'])->name('institutions.update');
    Route::delete('/institutions/{id}', [PlatformInstitutionController::class, 'destroy'])->name('institutions.destroy');
    Route::post('/institutions/{id}/suspend', [PlatformInstitutionController::class, 'suspend'])->name('institutions.suspend');
    Route::post('/institutions/{id}/activate', [PlatformInstitutionController::class, 'activate'])->name('institutions.activate');
    Route::post('/institutions/{id}/extend-trial', [PlatformInstitutionController::class, 'extendTrial'])->name('institutions.extend-trial');
    Route::put('/institutions/{id}/plan', [PlatformInstitutionController::class, 'updatePlan'])->name('institutions.plan');

    // Impersonate owner (Super Admin only)
    Route::post('/institutions/{id}/impersonate', [PlatformImpersonationController::class, 'start'])
        ->middleware('platform.super')
        ->name('institutions.impersonate');

    // 3. Plans CRUD
    Route::resource('plans', PlatformPlanController::class);

    // Subscriptions
    Route::get('/subscriptions', [PlatformSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/{id}/extend', [PlatformSubscriptionController::class, 'extend'])->name('subscriptions.extend');

    // 4. Manual Subscription Payments
    Route::get('/payments', [PlatformPaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PlatformPaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/{id}/reverse', [PlatformPaymentController::class, 'reverse'])->name('payments.reverse');
    Route::get('/payments/{id}/receipt', [PlatformPaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/payments/export', [PlatformPaymentController::class, 'export'])->name('payments.export');

    // 5. Demo Requests
    Route::get('/demo-requests', [PlatformDemoRequestController::class, 'index'])->name('demo-requests.index');
    Route::put('/demo-requests/{id}', [PlatformDemoRequestController::class, 'updateStatus'])->name('demo-requests.update');
    Route::get('/demo-requests/{id}/convert', [PlatformDemoRequestController::class, 'convert'])->name('demo-requests.convert');

    // 6. Platform Announcements
    Route::get('/announcements', [PlatformAnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [PlatformAnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{id}', [PlatformAnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{id}', [PlatformAnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // 7. System Health & Settings
    Route::get('/health', [PlatformSystemHealthController::class, 'index'])->name('health.index');
    Route::post('/health/backup', [PlatformSystemHealthController::class, 'triggerBackup'])->name('health.backup');
    Route::get('/settings', [PlatformSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');

    // 8. Platform Staff Management (Super Admin only)
    Route::get('/staff', [PlatformStaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [PlatformStaffController::class, 'store'])->middleware('platform.super')->name('staff.store');
    Route::put('/staff/{id}', [PlatformStaffController::class, 'update'])->middleware('platform.super')->name('staff.update');
    Route::delete('/staff/{id}', [PlatformStaffController::class, 'destroy'])->middleware('platform.super')->name('staff.destroy');

    // Platform Audit Logs
    Route::get('/audit-logs', [PlatformAuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export', [PlatformAuditLogController::class, 'export'])->name('audit-logs.export');
});

// Tenant Workspace Routes (Protected by auth and tenant middleware)
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Platform Announcement Dismissal
    Route::post('/announcements/{id}/dismiss', [PlatformAnnouncementController::class, 'dismiss'])->name('announcements.dismiss');

    // User Invitations
    Route::post('/invitations', [UserInvitationController::class, 'store'])->name('invitations.store');

    // Staff & User Management (for Institution Owners & Admins)
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::delete('/{id}', [StaffController::class, 'destroy'])->name('destroy');
    });

    // Fast Qur'an Entry direct shortcuts (supports /fast-entry, /fast-entery, /fast_entry, /fastentry)
    Route::get('/fast-entry', [HifzController::class, 'fastEntry'])->name('fast-entry');
    Route::get('/fast-entery', [HifzController::class, 'fastEntry']);
    Route::get('/fast_entry', [HifzController::class, 'fastEntry']);
    Route::get('/fastentry', [HifzController::class, 'fastEntry']);

    // Classes & Class Groups (for Institution Owners & Admins)
    Route::prefix('classes')->name('classes.')->group(function () {
        Route::get('/', [ClassGroupController::class, 'index'])->name('index');
        Route::post('/', [ClassGroupController::class, 'store'])->name('store');
        Route::put('/{id}', [ClassGroupController::class, 'update'])->name('update');
        Route::delete('/{id}', [ClassGroupController::class, 'destroy'])->name('destroy');
    });

    // Courses & Curriculum (for Institution Owners & Admins)
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('index');
        Route::post('/', [CourseController::class, 'store'])->name('store');
        Route::put('/{id}', [CourseController::class, 'update'])->name('update');
        Route::delete('/{id}', [CourseController::class, 'destroy'])->name('destroy');
    });

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
        Route::get('/fast-entery', [HifzController::class, 'fastEntry']);
        Route::get('/fast_entry', [HifzController::class, 'fastEntry']);
        Route::get('/fastentry', [HifzController::class, 'fastEntry']);
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
