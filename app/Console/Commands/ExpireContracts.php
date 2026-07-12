<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Unit;
use App\Support\UnitOccupancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpireContracts extends Command
{
    protected $signature = 'contracts:expire';
    protected $description = 'Expire active contracts that ended before today and sync affected unit occupancy.';

    public function handle(): int
    {
        $startedAt = now();

        Log::info('Contract expiry command started.', [
            'command' => 'contracts:expire',
            'started_at' => $startedAt->toIso8601String(),
        ]);

        $contractIds = Contract::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', now()->toDateString())
            ->orderBy('id')
            ->pluck('id');

        $inspected = $contractIds->count();
        $expired = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($contractIds as $contractId) {
            try {
                $result = DB::transaction(fn () => $this->expireContract((int) $contractId), 3);

                if ($result === 'expired') {
                    $expired++;

                    continue;
                }

                $skipped++;
            } catch (Throwable $exception) {
                $failed++;

                Log::warning('Contract expiry record failed.', [
                    'command' => 'contracts:expire',
                    'contract_id' => $contractId,
                    'exception_class' => get_class($exception),
                ]);
            }
        }

        $completedAt = now();

        Log::info('Contract expiry command completed.', [
            'command' => 'contracts:expire',
            'started_at' => $startedAt->toIso8601String(),
            'completed_at' => $completedAt->toIso8601String(),
            'processed_count' => $inspected,
            'changed_count' => $expired,
            'skipped_count' => $skipped,
            'failed_count' => $failed,
        ]);

        $this->info(
            "Contracts expiry complete. Inspected: {$inspected}; expired: {$expired}; skipped: {$skipped}; failed: {$failed}."
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function expireContract(int $contractId): string
    {
        $contract = Contract::query()
            ->whereKey($contractId)
            ->lockForUpdate()
            ->first();

        if (! $contract || ! $this->isEligible($contract)) {
            return 'skipped';
        }

        $unit = Unit::query()
            ->whereKey($contract->unit_id)
            ->lockForUpdate()
            ->first();

        $contract->update(['status' => 'expired']);

        if ($unit) {
            $this->syncUnitOccupancy($unit);
        }

        return 'expired';
    }

    private function isEligible(Contract $contract): bool
    {
        return $contract->status === 'active'
            && $contract->end_date->lt(now()->startOfDay());
    }

    protected function syncUnitOccupancy(Unit $unit): void
    {
        UnitOccupancy::sync($unit);
    }
}
