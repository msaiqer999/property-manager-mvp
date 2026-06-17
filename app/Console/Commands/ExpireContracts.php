<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Unit;
use App\Support\UnitOccupancy;
use Illuminate\Console\Command;

class ExpireContracts extends Command
{
    protected $signature = 'contracts:expire';
    protected $description = 'Expire active contracts that ended before today and sync affected unit occupancy.';

    public function handle(): int
    {
        $contracts = Contract::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', now()->toDateString())
            ->get();

        $unitIds = $contracts->pluck('unit_id')->unique()->values();

        $contracts->each->update(['status' => 'expired']);

        Unit::whereIn('id', $unitIds)->get()->each(fn (Unit $unit) => UnitOccupancy::sync($unit));

        $this->info('Expired '.$contracts->count().' contracts; synchronized '.$unitIds->count().' units.');

        return self::SUCCESS;
    }
}
