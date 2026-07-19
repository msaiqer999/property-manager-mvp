<?php

namespace App\Support;

use App\Models\Building;
use App\Models\Currency;
use App\Models\Organization;

class MoneyFormatter
{
    public static function format(mixed $value, ?string $currencyCode): string
    {
        $currencyCode = self::normalizeCurrencyCode($currencyCode);
        $decimals = self::decimalPlaces($currencyCode);
        $amount = number_format((float) $value, $decimals);

        return trim(($currencyCode ? $currencyCode.' ' : '').$amount);
    }

    public static function forOrganization(?Organization $organization, mixed $value): string
    {
        return self::format($value, $organization?->effectiveCurrencyCode());
    }

    public static function forBuilding(?Building $building, mixed $value): string
    {
        return self::format($value, $building?->effectiveCurrencyCode());
    }

    private static function decimalPlaces(?string $currencyCode): int
    {
        if ($currencyCode === null) {
            return 2;
        }

        return (int) (Currency::query()
            ->where('code', $currencyCode)
            ->where('is_active', true)
            ->value('decimal_places') ?? 2);
    }

    private static function normalizeCurrencyCode(?string $currencyCode): ?string
    {
        $currencyCode = strtoupper(trim((string) $currencyCode));

        return $currencyCode !== '' ? $currencyCode : null;
    }
}
