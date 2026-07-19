<?php

namespace App\Support;

use App\Models\Building;
use App\Models\PropertyType;
use App\Models\User;

class UnitTypeCatalog
{
    private const FALLBACK_CODES = [
        'apartment',
        'shop',
        'office',
        'warehouse',
        'villa',
        'chalet',
        'other',
    ];

    public static function forUser(?User $user): array
    {
        return self::forCountry($user?->organization?->country_id);
    }

    public static function forBuilding(?Building $building): array
    {
        return self::forCountry($building?->effectiveCountryId());
    }

    public static function forCountry(?int $countryId): array
    {
        $codes = PropertyType::availableForCountry($countryId)
            ->orderBy('country_id')
            ->orderBy('name')
            ->pluck('code')
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique([...$codes, ...self::FALLBACK_CODES]));
    }
}
