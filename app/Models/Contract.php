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
}
