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

        $periodStart = $contract->start_date->copy();
        $contractEnd = $contract->end_date->copy()->addDay();

        while ($periodStart->lt($contractEnd)) {
            $periodEnd = $periodStart->copy()->addMonthsNoOverflow($months);
            $actualEnd = $periodEnd->lt($contractEnd) ? $periodEnd : $contractEnd;
            $actualDays = $periodStart->diffInDays($actualEnd);
            $periodDays = $periodStart->diffInDays($periodEnd);

            if ($actualDays <= 0 || $periodDays <= 0) {
                break;
            }

            $amountDue = $actualDays === $periodDays
                ? $contract->rent_amount
                : round($contract->rent_amount * ($actualDays / $periodDays), 2);

            $contract->payments()->create([
                'organization_id' => $contract->organization_id,
                'due_date' => $periodStart,
                'amount_due' => $amountDue,
                'amount_paid' => 0,
                'status' => 'pending',
            ]);

            $periodStart = $periodEnd;
        }
    }
}
