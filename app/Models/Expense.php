<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Expense extends Model
{
    protected $fillable = [
        'organization_id', 'building_id', 'unit_id', 'category', 'amount',
        'expense_date', 'invoice_image', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeNotVoided(Builder $query): Builder
    {
        if (! self::hasVoidLifecycleColumn()) {
            return $query;
        }

        return $query->where(function (Builder $query) {
            $query->whereNull('voided_at')
                ->orWhere('voided_at', '')
                ->orWhere('voided_at', '0000-00-00 00:00:00');
        });
    }

    public function scopeOnlyVoided(Builder $query): Builder
    {
        if (! self::hasVoidLifecycleColumn()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereNotNull('voided_at')
            ->where('voided_at', '!=', '')
            ->where('voided_at', '!=', '0000-00-00 00:00:00');
    }

    private static function hasVoidLifecycleColumn(): bool
    {
        return Schema::hasColumn((new self)->getTable(), 'voided_at');
    }
}
