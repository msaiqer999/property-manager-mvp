<?php

namespace App\Support;

use App\Models\Contract;
use Carbon\CarbonPeriod;

class PaymentSchedule
{
    public static function createFor(Contract $contract): void
    {
        if ($contract->payments()->exists()) {
            return;
        }

        self::generate($contract);
    }

    public static function replaceFor(Contract $contract): void
    {
        $hasRecordedPayments = $contract->payments()
            ->where(fn ($query) => $query->where('amount_paid', '>', 0)->orWhereNotNull('payment_date'))
            ->exists();

        if ($hasRecordedPayments) {
            return;
        }

        $contract->payments()->delete();
        self::generate($contract);
    }

    private static function generate(Contract $contract): void
    {
        $months = match ($contract->payment_frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1,
        };

        $period = CarbonPeriod::create($contract->start_date, "{$months} months", $contract->end_date);

        foreach ($period as $dueDate) {
            $contract->payments()->create([
                'organization_id' => $contract->organization_id,
                'due_date' => $dueDate,
                'amount_due' => $contract->rent_amount * $months,
                'amount_paid' => 0,
                'status' => 'pending',
            ]);
        }
    }
}
