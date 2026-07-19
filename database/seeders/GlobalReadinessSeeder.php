<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\PropertyType;
use App\Models\TaxSetting;
use Illuminate\Database\Seeder;

class GlobalReadinessSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->currencies() as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        $countries = [];

        foreach ($this->countries() as $country) {
            $countries[$country['code']] = Country::updateOrCreate(
                ['code' => $country['code']],
                $country
            );
        }

        $this->seedPropertyTypes($countries);
        $this->seedPaymentMethods($countries);
        $this->seedContractTemplates($countries);
        $this->seedTaxSettings($countries);
    }

    private function seedPropertyTypes(array $countries): void
    {
        foreach ([
            null => ['apartment', 'shop', 'office', 'warehouse', 'house', 'villa', 'chalet', 'other'],
            'AE' => ['apartment', 'villa', 'shop', 'office', 'warehouse'],
            'ID' => ['kos', 'kamar', 'kontrakan', 'ruko', 'apartment', 'shop', 'warehouse'],
            'KE' => ['apartment', 'shop', 'house', 'office', 'warehouse'],
            'TZ' => ['apartment', 'shop', 'house', 'office', 'warehouse'],
        ] as $countryCode => $codes) {
            foreach ($codes as $code) {
                PropertyType::updateOrCreate(
                    [
                        'country_id' => $countryCode ? $countries[$countryCode]->id : null,
                        'code' => $code,
                    ],
                    [
                        'name' => str($code)->replace('_', ' ')->title()->toString(),
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedPaymentMethods(array $countries): void
    {
        foreach ([
            null => ['cash', 'bank_transfer'],
            'AE' => ['cash', 'bank_transfer', 'cheque'],
            'ID' => ['cash', 'bank_transfer', 'qris'],
            'SA' => ['cash', 'bank_transfer', 'stc_pay'],
            'KE' => ['cash', 'bank_transfer', 'mpesa'],
            'TZ' => ['cash', 'bank_transfer', 'mpesa'],
            'MA' => ['cash', 'bank_transfer', 'cheque'],
        ] as $countryCode => $codes) {
            foreach ($codes as $code) {
                PaymentMethod::updateOrCreate(
                    [
                        'country_id' => $countryCode ? $countries[$countryCode]->id : null,
                        'code' => $code,
                    ],
                    [
                        'name' => str($code)->replace('_', ' ')->title()->toString(),
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedContractTemplates(array $countries): void
    {
        foreach ([
            'AE' => ['en', 'ar'],
            'ID' => ['id', 'en'],
            'SA' => ['ar', 'en'],
            'KE' => ['en', 'sw'],
            'TZ' => ['sw', 'en'],
            'MA' => ['ar', 'fr', 'en'],
        ] as $countryCode => $languages) {
            foreach ($languages as $index => $language) {
                ContractTemplate::updateOrCreate(
                    [
                        'country_id' => $countries[$countryCode]->id,
                        'language' => $language,
                        'name' => 'Basic lease template',
                    ],
                    [
                        'content' => 'Basic lease template placeholder for '.$countries[$countryCode]->name.' ('.$language.'). Operator review required before use.',
                        'is_default' => $index === 0,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedTaxSettings(array $countries): void
    {
        foreach ($countries as $country) {
            TaxSetting::updateOrCreate(
                [
                    'country_id' => $country->id,
                    'type' => 'manual_review',
                    'name' => 'Tax configuration placeholder',
                ],
                [
                    'rate' => 0,
                    'notes' => 'Configure current local tax treatment before enabling tax-sensitive reports or invoices.',
                    'is_active' => false,
                ]
            );
        }
    }

    private function currencies(): array
    {
        return [
            ['code' => 'AED', 'name' => 'United Arab Emirates Dirham', 'symbol' => 'AED', 'decimal_places' => 2, 'is_active' => true],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'IDR', 'decimal_places' => 0, 'is_active' => true],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR', 'decimal_places' => 2, 'is_active' => true],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KES', 'decimal_places' => 2, 'is_active' => true],
            ['code' => 'TZS', 'name' => 'Tanzanian Shilling', 'symbol' => 'TZS', 'decimal_places' => 2, 'is_active' => true],
            ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'MAD', 'decimal_places' => 2, 'is_active' => true],
        ];
    }

    private function countries(): array
    {
        return [
            ['name' => 'United Arab Emirates', 'code' => 'AE', 'default_currency_code' => 'AED', 'default_locale' => 'en', 'default_timezone' => 'Asia/Dubai', 'is_active' => true],
            ['name' => 'Indonesia', 'code' => 'ID', 'default_currency_code' => 'IDR', 'default_locale' => 'id', 'default_timezone' => 'Asia/Jakarta', 'is_active' => true],
            ['name' => 'Saudi Arabia', 'code' => 'SA', 'default_currency_code' => 'SAR', 'default_locale' => 'ar', 'default_timezone' => 'Asia/Riyadh', 'is_active' => true],
            ['name' => 'Kenya', 'code' => 'KE', 'default_currency_code' => 'KES', 'default_locale' => 'en', 'default_timezone' => 'Africa/Nairobi', 'is_active' => true],
            ['name' => 'Tanzania', 'code' => 'TZ', 'default_currency_code' => 'TZS', 'default_locale' => 'sw', 'default_timezone' => 'Africa/Dar_es_Salaam', 'is_active' => true],
            ['name' => 'Morocco', 'code' => 'MA', 'default_currency_code' => 'MAD', 'default_locale' => 'ar', 'default_timezone' => 'Africa/Casablanca', 'is_active' => true],
        ];
    }
}
