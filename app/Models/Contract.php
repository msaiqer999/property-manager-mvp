<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'organization_id', 'unit_id', 'tenant_id', 'contract_number',
        'start_date', 'end_date', 'rent_amount', 'payment_frequency',
        'deposit_amount', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function expiryWarningGroup(): ?string
    {
        if ($this->status !== 'active') {
            return null;
        }

        $days = $this->daysUntilExpiry();

        if ($days === null || $days > 90) {
            return null;
        }

        return match (true) {
            $days <= 30 => '30',
            $days <= 60 => '60',
            default => '90',
        };
    }

    public function daysUntilExpiry(): ?int
    {
        if ($this->status !== 'active') {
            return null;
        }

        $today = now()->startOfDay();
        $endDate = $this->end_date->copy()->startOfDay();

        if ($endDate->lt($today)) {
            return null;
        }

        return (int) $today->diffInDays($endDate, false);
    }

    public function expiryWarningText(): ?string
    {
        $days = $this->daysUntilExpiry();

        if ($days === null || $days > 90) {
            return null;
        }

        return match ($days) {
            0 => 'Expires today',
            1 => 'Expires in 1 day',
            default => "Expires in {$days} days",
        };
    }
}
