<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BetaFeedbackController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BulkUnitController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentFollowUpController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitDocumentController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureAbility;
use App\Http\Middleware\EnsureRegistrationIsEnabled;
use App\Support\DashboardAuthorization;
use App\Support\SupportedLocales;
use Illuminate\Support\Facades\Route;

Route::post('/locale/{locale}', LocaleController::class)
    ->whereIn('locale', SupportedLocales::codes())
    ->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:password-reset-email')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:password-reset-submit')->name('password.store');
});

Route::middleware(['guest', EnsureRegistrationIsEnabled::class])->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/', function () {
    if (auth()->check()) {
        return app(DashboardController::class)(app(DashboardAuthorization::class));
    }

    return view('landing');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard.show');
    Route::post('beta-feedback', [BetaFeedbackController::class, 'store'])->name('beta-feedback.store');

    Route::middleware(EnsureAbility::class.':manage-properties')->group(function () {
        Route::get('buildings/{building}/units/bulk-create', [BulkUnitController::class, 'create'])->name('buildings.units.bulk.create');
        Route::get('buildings/{building}/units/bulk-preview', [BulkUnitController::class, 'expiredPreview'])->name('buildings.units.bulk.preview.expired');
        Route::post('buildings/{building}/units/bulk-preview', [BulkUnitController::class, 'preview'])->name('buildings.units.bulk.preview');
        Route::post('buildings/{building}/units/bulk-store', [BulkUnitController::class, 'store'])->name('buildings.units.bulk.store');
        Route::post('units/{unit}/documents', [UnitDocumentController::class, 'store'])->name('unit-documents.store');
        Route::get('unit-documents/{unitDocument}/download', [UnitDocumentController::class, 'download'])->name('unit-documents.download');
        Route::delete('unit-documents/{unitDocument}', [UnitDocumentController::class, 'destroy'])->name('unit-documents.destroy');
        Route::resource('buildings', BuildingController::class);
        Route::resource('units', UnitController::class);
    });

    Route::middleware(EnsureAbility::class.':manage-tenants')->group(function () {
        Route::patch('tenants/{tenant}/archive', [TenantController::class, 'archiveTenant'])->name('tenants.archive');
        Route::resource('tenants', TenantController::class);
    });
    Route::middleware(EnsureAbility::class.':manage-contracts')->group(function () {
        Route::patch('contracts/{contract}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');
        Route::resource('contracts', ContractController::class);
        Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
    });

    Route::middleware(EnsureAbility::class.':view-payments')->group(function () {
        Route::resource('payments', PaymentController::class)->only(['index', 'show', 'edit', 'update']);
        Route::post('payments/{payment}/follow-ups', [PaymentFollowUpController::class, 'store'])->name('payment-follow-ups.store');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('payments/{payment}/proof', [PaymentController::class, 'downloadProof'])->name('payments.proof.download');
    });

    Route::middleware(EnsureAbility::class.':view-expenses')->group(function () {
        Route::patch('expenses/{expense}/void', [ExpenseController::class, 'voidExpense'])->name('expenses.void');
        Route::get('expenses/{expense}/invoice', [ExpenseController::class, 'downloadInvoice'])->name('expenses.invoice');
        Route::get('expenses/{expense}/invoice/download', [ExpenseController::class, 'downloadInvoice'])->name('expenses.invoice.download');
        Route::resource('expenses', ExpenseController::class);
    });

    Route::middleware(EnsureAbility::class.':view-reports')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    });

    Route::middleware(EnsureAbility::class.':manage-users')->group(function () {
        Route::get('beta-feedback', [BetaFeedbackController::class, 'index'])->name('beta-feedback.index');
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::patch('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
        Route::get('activity-logs', ActivityLogController::class)->name('activity-logs.index');
    });
});
