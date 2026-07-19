<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'default_currency_code',
        'default_locale',
        'default_timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function defaultCurrency()
    {
        return $this->belongsTo(Currency::class, 'default_currency_code', 'code');
    }

    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }

    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    public function propertyTypes()
    {
        return $this->hasMany(PropertyType::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function contractTemplates()
    {
        return $this->hasMany(ContractTemplate::class);
    }

    public function taxSettings()
    {
        return $this->hasMany(TaxSetting::class);
    }
}
