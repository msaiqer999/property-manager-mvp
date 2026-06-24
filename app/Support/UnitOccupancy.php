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

        $hasActiveContract = $unit->contracts()
            ->where('status', 'active')
            ->exists();

        $unit->update(['status' => $hasActiveContract ? 'rented' : 'vacant']);
    }
}
