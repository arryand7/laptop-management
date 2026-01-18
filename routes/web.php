<?php

use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\LaptopController as AdminLaptopController;
use App\Http\Controllers\Admin\LaptopUpdateRequestController;
use App\Http\Controllers\Admin\MobileTransactionController as AdminMobileTransactionController;
use App\Http\Controllers\Admin\SanctionController as AdminSanctionController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ViolationController as AdminViolationController;
use App\Http\Controllers\Admin\SsoSyncController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\Staff\BorrowController as StaffBorrowController;
use App\Http\Controllers\Staff\ChecklistController as StaffChecklistController;
use App\Http\Controllers\Staff\LaptopTransactionController as StaffLaptopTransactionController;
use App\Http\Controllers\Staff\LookupController as StaffLookupController;
use App\Http\Controllers\Staff\ReturnController as StaffReturnController;
use App\Http\Controllers\Student\HistoryController as StudentHistoryController;
use App\Http\Controllers\Student\LaptopController as StudentLaptopController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/sso/login', [SsoController::class, 'redirect'])->name('sso.login');
    Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard')->middleware('module:dashboard');

    Route::middleware('role:admin,staff')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('dashboard'));

        Route::middleware('module:admin.students')->group(function () {
            Route::get('students/template', [AdminStudentController::class, 'downloadTemplate'])->name('students.template');
            Route::post('students/import', [AdminStudentController::class, 'import'])->name('students.import');
            Route::post('students/bulk', [AdminStudentController::class, 'bulkUpdate'])->name('students.bulk');
            Route::get('students/{student}/qr', [AdminStudentController::class, 'qr'])->name('students.qr');
            Route::resource('students', AdminStudentController::class);
        });

        Route::middleware('module:admin.laptops')->group(function () {
            Route::get('laptops/template', [AdminLaptopController::class, 'downloadTemplate'])->name('laptops.template');
            Route::post('laptops/import', [AdminLaptopController::class, 'import'])->name('laptops.import');
            Route::post('laptops/bulk', [AdminLaptopController::class, 'bulkUpdate'])->name('laptops.bulk');
            Route::get('laptops/{laptop}/qr', [AdminLaptopController::class, 'qr'])->name('laptops.qr');
            Route::resource('laptops', AdminLaptopController::class);
        });

        Route::middleware('module:admin.laptop-requests')->group(function () {
            Route::get('laptop-requests', [LaptopUpdateRequestController::class, 'index'])->name('laptop-requests.index');
            Route::get('laptop-requests/{laptopUpdateRequest}', [LaptopUpdateRequestController::class, 'show'])->name('laptop-requests.show');
            Route::patch('laptop-requests/{laptopUpdateRequest}/approve', [LaptopUpdateRequestController::class, 'approve'])->name('laptop-requests.approve');
            Route::patch('laptop-requests/{laptopUpdateRequest}/reject', [LaptopUpdateRequestController::class, 'reject'])->name('laptop-requests.reject');
        });

        Route::middleware('module:admin.transactions.mobile')->prefix('transactions/mobile')->name('transactions.mobile.')->group(function () {
            Route::get('/', [AdminMobileTransactionController::class, 'index'])->name('index');
            Route::post('preview', [AdminMobileTransactionController::class, 'preview'])->name('preview');
            Route::post('confirm', [AdminMobileTransactionController::class, 'confirm'])->name('confirm');
        });

        Route::prefix('settings')->middleware('module:admin.settings')->name('settings.')->group(function () {
            Route::get('application', [AppSettingController::class, 'application'])->name('application');
            Route::put('application', [AppSettingController::class, 'updateApplication'])->name('application.update');

            Route::get('lending', [AppSettingController::class, 'lending'])->name('lending');
            Route::put('lending', [AppSettingController::class, 'updateLending'])->name('lending.update');

            Route::get('mail', [AppSettingController::class, 'mail'])->name('mail');
            Route::put('mail', [AppSettingController::class, 'updateMail'])->name('mail.update');

            Route::get('ai', [AppSettingController::class, 'ai'])->name('ai');
            Route::put('ai', [AppSettingController::class, 'updateAi'])->name('ai.update');
            Route::get('safe-exam-browser', [AppSettingController::class, 'safeExamBrowser'])->name('safe-exam-browser');
            Route::put('safe-exam-browser', [AppSettingController::class, 'updateSafeExamBrowser'])->name('safe-exam-browser.update');

            Route::get('sso-sync', [SsoSyncController::class, 'index'])->name('sso-sync');
            Route::post('sso-sync', [SsoSyncController::class, 'sync'])->name('sso-sync.run');

            Route::get('/', function () {
                return redirect()->route('admin.settings.application');
            })->name('index');
        });

        Route::resource('users', AdminUserController::class)
            ->except(['show'])
            ->middleware('module:admin.users');

        Route::middleware('module:admin.violations')->group(function () {
            Route::get('violations', [AdminViolationController::class, 'index'])->name('violations.index');
            Route::get('violations/create', [AdminViolationController::class, 'create'])->name('violations.create');
            Route::post('violations', [AdminViolationController::class, 'store'])->name('violations.store');
            Route::patch('violations/{violation}', [AdminViolationController::class, 'update'])->name('violations.update');
        });

        Route::middleware('module:admin.sanctions')->group(function () {
            Route::get('sanctions', [AdminSanctionController::class, 'index'])->name('sanctions.index');
            Route::get('sanctions/create', [AdminSanctionController::class, 'create'])->name('sanctions.create');
            Route::post('sanctions', [AdminSanctionController::class, 'store'])->name('sanctions.store');
            Route::patch('sanctions/{sanction}', [AdminSanctionController::class, 'update'])->name('sanctions.update');
        });

        Route::middleware('module:admin.reports')->group(function () {
            Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
            Route::post('reports/export/excel', [AdminReportController::class, 'exportExcel'])->name('reports.export.excel');
            Route::post('reports/export/pdf', [AdminReportController::class, 'exportPdf'])->name('reports.export.pdf');
        });
    });

    Route::middleware('role:staff,admin')->prefix('staff')->name('staff.')->group(function () {
        Route::get('lookup/students', [StaffLookupController::class, 'students'])->name('lookup.students');
        Route::get('lookup/laptops', [StaffLookupController::class, 'laptops'])->name('lookup.laptops');

        Route::middleware('module:staff.transactions')->prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [StaffLaptopTransactionController::class, 'index'])->name('index');
            Route::post('preview', [StaffLaptopTransactionController::class, 'preview'])->name('preview');
            Route::post('confirm', [StaffLaptopTransactionController::class, 'confirm'])->name('confirm');
        });

        Route::middleware('module:staff.borrow')->group(function () {
            Route::get('borrow', [StaffBorrowController::class, 'create'])->name('borrow.create');
            Route::post('borrow', [StaffBorrowController::class, 'store'])->name('borrow.store');
        });

        Route::middleware('module:staff.return')->group(function () {
            Route::get('return', [StaffReturnController::class, 'create'])->name('return.create');
            Route::post('return', [StaffReturnController::class, 'store'])->name('return.store');
            Route::post('return/{transaction}/quick', [StaffReturnController::class, 'quickReturn'])->name('return.quick');
        });

        Route::middleware('module:staff.checklist')->prefix('checklist')->name('checklist.')->group(function () {
            Route::get('/', [StaffChecklistController::class, 'create'])->name('create');
            Route::post('/', [StaffChecklistController::class, 'store'])->name('store');
            Route::get('history', [StaffChecklistController::class, 'history'])->name('history');
            Route::get('{session}', [StaffChecklistController::class, 'show'])->name('show');
            Route::get('{session}/edit', [StaffChecklistController::class, 'edit'])->name('edit');
            Route::put('{session}', [StaffChecklistController::class, 'update'])->name('update');
            Route::delete('{session}', [StaffChecklistController::class, 'destroy'])->name('destroy');
        });
    });

    Route::middleware('role:staff,admin')->group(function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('history', StudentHistoryController::class)->name('history')->middleware('module:student.history');
        Route::middleware('module:student.laptops')->group(function () {
            Route::get('laptops', [StudentLaptopController::class, 'index'])->name('laptops.index');
            Route::get('laptops/create', [StudentLaptopController::class, 'create'])->name('laptops.create');
            Route::post('laptops', [StudentLaptopController::class, 'store'])->name('laptops.store');
            Route::get('laptops/{laptop}/edit', [StudentLaptopController::class, 'edit'])->name('laptops.edit');
            Route::post('laptops/{laptop}/requests', [StudentLaptopController::class, 'storeUpdateRequest'])->name('laptops.requests.store');
            Route::get('laptops/{laptop}/qr', [StudentLaptopController::class, 'qr'])->name('laptops.qr');
        });
    });

    Route::middleware('module:chatbot')->group(function () {
        Route::get('chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
        Route::post('chatbot/preview', [ChatbotController::class, 'preview'])->name('chatbot.preview');
        Route::post('chatbot/commit', [ChatbotController::class, 'commit'])->name('chatbot.commit');
    });
});
