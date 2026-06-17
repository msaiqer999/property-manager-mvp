<?php

namespace App\Support;

use App\Models\Unit;

class UnitOccupancy
{
    public static function sync(Unit $unit): void
    {
        if ($unit->status === 'maintenance') {
            return;
        }

        $hasCurrentActiveContract = $unit->contracts()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        $unit->update(['status' => $hasCurrentActiveContract ? 'rented' : 'vacant']);
    }
}
