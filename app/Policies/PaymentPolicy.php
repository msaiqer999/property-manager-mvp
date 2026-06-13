<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->can('view-payments');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->role->can('view-payments')
            && $payment->organization_id === $user->organization_id;
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->recordPayment($user, $payment);
    }

    public function recordPayment(User $user, Payment $payment): bool
    {
        return $user->role->can('record-payment')
            && $payment->organization_id === $user->organization_id;
    }

    public function uploadProof(User $user, Payment $payment): bool
    {
        return $this->recordPayment($user, $payment);
    }

    public function exportReceiptPdf(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }
}
