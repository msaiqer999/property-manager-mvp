<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'country_id', 'currency_code', 'locale', 'timezone'];

    public function country() { return $this->belongsTo(Country::class); }
    public function currency() { return $this->belongsTo(Currency::class, 'currency_code', 'code'); }
    public function users() { return $this->hasMany(User::class); }
    public function buildings() { return $this->hasMany(Building::class); }
    public function tenants() { return $this->hasMany(Tenant::class); }
    public function contracts() { return $this->hasMany(Contract::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }
    public function betaFeedback() { return $this->hasMany(BetaFeedback::class); }

    public function effectiveCurrencyCode(): string
    {
        return $this->currency_code
            ?: $this->country?->default_currency_code
            ?: 'AED';
    }

    public function effectiveLocale(): string
    {
        return $this->locale
            ?: $this->country?->default_locale
            ?: config('app.locale', 'en');
    }

    public function effectiveTimezone(): string
    {
        return $this->timezone
            ?: $this->country?->default_timezone
            ?: config('app.timezone', 'UTC');
    }
}
