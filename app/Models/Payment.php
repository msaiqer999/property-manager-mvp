<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'organization_id', 'contract_id', 'due_date', 'amount_due',
        'amount_paid', 'payment_date', 'status', 'payment_method',
        'proof_image', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'payment_date' => 'date'];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
