<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarkOverduePayments extends Command
{
    protected $signature = 'payments:mark-overdue';
    protected $description = 'Mark unpaid past-due payment schedule rows as overdue.';

    public function handle(): int
    {
        $startedAt = now();

        Log::info('Payment overdue command started.', [
            'command' => 'payments:mark-overdue',
            'started_at' => $startedAt->toIso8601String(),
        ]);

        try {
            $count = Payment::query()
                ->where('due_date', '<', now()->toDateString())
                ->where('status', 'pending')
                ->where('amount_paid', 0)
                ->whereNull('payment_date')
                ->update(['status' => 'overdue']);

            $completedAt = now();

            Log::info('Payment overdue command completed.', [
                'command' => 'payments:mark-overdue',
                'started_at' => $startedAt->toIso8601String(),
                'completed_at' => $completedAt->toIso8601String(),
                'processed_count' => $count,
                'changed_count' => $count,
                'failed_count' => 0,
            ]);

            $this->info("Payments overdue check complete. Affected: {$count}; status: complete.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Payment overdue command failed.', [
                'command' => 'payments:mark-overdue',
                'started_at' => $startedAt->toIso8601String(),
                'failed_at' => now()->toIso8601String(),
                'exception_class' => get_class($exception),
            ]);

            $this->error('Payments overdue check failed. Affected: 0; status: failed.');

            return self::FAILURE;
        }
    }
}
