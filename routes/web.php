<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureAbility;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware(EnsureAbility::class.':manage-properties')->group(function () {
        Route::resource('buildings', BuildingController::class);
        Route::resource('units', UnitController::class);
    });

    Route::middleware(EnsureAbility::class.':manage-tenants')->resource('tenants', TenantController::class);
    Route::middleware(EnsureAbility::class.':manage-contracts')->resource('contracts', ContractController::class);
    Route::middleware(EnsureAbility::class.':manage-contracts')->get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');

    Route::middleware(EnsureAbility::class.':view-payments')->group(function () {
        Route::resource('payments', PaymentController::class)->only(['index', 'show', 'edit', 'update']);
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    });

    Route::middleware(EnsureAbility::class.':view-expenses')->resource('expenses', ExpenseController::class);

    Route::middleware(EnsureAbility::class.':view-reports')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    });

    Route::middleware(EnsureAbility::class.':manage-users')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::get('activity-logs', ActivityLogController::class)->name('activity-logs.index');
    });
});
