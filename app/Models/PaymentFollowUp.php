<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFollowUp extends Model
{
    public const TYPE_NOTE = 'note';
    public const TYPE_REMINDER_LOGGED = 'reminder_logged';
    public const TYPE_PROMISE_TO_PAY = 'promise_to_pay';

    public const TYPES = [
        self::TYPE_NOTE,
        self::TYPE_REMINDER_LOGGED,
        self::TYPE_PROMISE_TO_PAY,
    ];

    protected $fillable = [
        'organization_id',
        'payment_id',
        'user_id',
        'type',
        'note',
        'promised_amount',
        'promised_date',
    ];

    protected function casts(): array
    {
        return [
            'promised_amount' => 'decimal:2',
            'promised_date' => 'date',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
