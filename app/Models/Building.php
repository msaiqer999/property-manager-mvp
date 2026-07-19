<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use SoftDeletes;

    protected $fillable = ['organization_id', 'country_id', 'currency_code', 'timezone', 'name', 'location', 'description'];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function country() { return $this->belongsTo(Country::class); }
    public function currency() { return $this->belongsTo(Currency::class, 'currency_code', 'code'); }
    public function units() { return $this->hasMany(Unit::class); }
    public function expenses() { return $this->hasMany(Expense::class); }

    public function effectiveCountryId(): ?int
    {
        return $this->country_id ?: $this->organization?->country_id;
    }

    public function effectiveCurrencyCode(): ?string
    {
        return $this->currency_code
            ?: $this->country?->default_currency_code
            ?: $this->organization?->effectiveCurrencyCode()
            ?: config('app.fallback_currency_code');
    }

    public function effectiveTimezone(): string
    {
        return $this->timezone ?: $this->organization?->effectiveTimezone() ?: config('app.timezone', 'UTC');
    }
}
