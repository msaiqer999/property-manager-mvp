<?php

namespace App\Support;

class SupportedLocales
{
    private const LOCALES = [
        'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
        'en' => ['label' => 'English', 'dir' => 'ltr'],
        'fil' => ['label' => 'Filipino (Tagalog)', 'dir' => 'ltr'],
        'ur' => ['label' => 'اردو', 'dir' => 'rtl'],
        'hi' => ['label' => 'हिन्दी', 'dir' => 'ltr'],
        'bn' => ['label' => 'বাংলা', 'dir' => 'ltr'],
    ];

    public static function all(): array
    {
        return self::LOCALES;
    }

    public static function codes(): array
    {
        return array_keys(self::LOCALES);
    }

    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::LOCALES);
    }

    public static function direction(string $locale): string
    {
        return self::LOCALES[$locale]['dir'] ?? 'ltr';
    }
}
