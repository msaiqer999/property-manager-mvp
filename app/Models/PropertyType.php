<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeAvailableForCountry(Builder $query, Country|int|null $country): Builder
    {
        $countryId = $country instanceof Country ? $country->getKey() : $country;

        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) use ($countryId): void {
                $query->whereNull('country_id');

                if ($countryId !== null) {
                    $query->orWhere('country_id', $countryId);
                }
            });
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
