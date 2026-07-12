<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:mark-overdue')
    ->name('payments:mark-overdue:daily')
    ->dailyAt('01:00')
    ->withoutOverlapping(60)
    ->onFailure(function (): void {
        Log::error('Scheduled command failed.', [
            'command' => 'payments:mark-overdue',
            'schedule' => 'payments:mark-overdue:daily',
        ]);
    });

Schedule::command('contracts:expire')
    ->name('contracts:expire:daily')
    ->dailyAt('00:30')
    ->withoutOverlapping(60)
    ->onFailure(function (): void {
        Log::error('Scheduled command failed.', [
            'command' => 'contracts:expire',
            'schedule' => 'contracts:expire:daily',
        ]);
    });
