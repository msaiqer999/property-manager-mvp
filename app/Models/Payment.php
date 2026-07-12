<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'organization_id', 'contract_id', 'due_date', 'amount_due',
        'amount_paid', 'payment_date', 'status', 'payment_method',
        'proof_image', 'proof_disk', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'payment_date' => 'date'];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function followUps() { return $this->hasMany(PaymentFollowUp::class); }

    public function latestPromise()
    {
        return $this->hasOne(PaymentFollowUp::class)
            ->where('type', PaymentFollowUp::TYPE_PROMISE_TO_PAY)
            ->latestOfMany();
    }

    public function getAmountDueMinorAttribute(): int
    {
        return $this->decimalToMinorUnits((string) $this->amount_due);
    }

    public function getAmountPaidMinorAttribute(): int
    {
        return $this->decimalToMinorUnits((string) $this->amount_paid);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, ($this->amount_due_minor - $this->amount_paid_minor) / 100);
    }

    public function getDisplayStatusKeyAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->amount_paid_minor >= $this->amount_due_minor) {
            return 'paid';
        }

        if ($this->amount_paid_minor > 0) {
            return $this->due_date->isPast() ? 'partial_overdue' : 'partial';
        }

        return $this->due_date->isPast() ? 'overdue' : 'pending';
    }

    public function getReceiptStatusKeyAttribute(): string
    {
        return $this->amount_paid_minor >= $this->amount_due_minor ? 'paid' : 'partial';
    }

    private function decimalToMinorUnits(string $value): int
    {
        $value = trim($value);

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
