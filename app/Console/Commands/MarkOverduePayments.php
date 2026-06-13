<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class MarkOverduePayments extends Command
{
    protected $signature = 'payments:mark-overdue';
    protected $description = 'Mark unpaid past-due payment schedule rows as overdue.';

    public function handle(): int
    {
        $count = Payment::query()
            ->where('due_date', '<', now()->toDateString())
            ->where('status', 'pending')
            ->update(['status' => 'overdue']);

        $this->info("Marked {$count} payments overdue.");

        return self::SUCCESS;
    }
}
