<?php

namespace App\Console\Commands;

use App\Models\Unit;
use App\Support\UnitOccupancy;
use Illuminate\Console\Command;

class SyncUnitOccupancy extends Command
{
    protected $signature = 'units:sync-occupancy {unit? : Optional unit ID to synchronize.}';
    protected $description = 'Synchronize unit occupancy status from active contracts.';

    public function handle(): int
    {
        $unitId = $this->argument('unit');
        $synced = 0;

        Unit::query()
            ->when($unitId !== null, fn ($query) => $query->whereKey($unitId))
            ->chunkById(100, function ($units) use (&$synced): void {
                $units->each(function (Unit $unit) use (&$synced): void {
                    UnitOccupancy::sync($unit);
                    $synced++;
                });
            });

        if ($unitId !== null && $synced === 0) {
            $this->error("Unit {$unitId} was not found.");

            return self::FAILURE;
        }

        $this->info("Synchronized {$synced} units.");

        return self::SUCCESS;
    }
}
