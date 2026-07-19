<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function countries()
    {
        return $this->hasMany(Country::class, 'default_currency_code', 'code');
    }

    public function organizations()
    {
        return $this->hasMany(Organization::class, 'currency_code', 'code');
    }

    public function buildings()
    {
        return $this->hasMany(Building::class, 'currency_code', 'code');
    }
}
