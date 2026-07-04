<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentFollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PaymentFollowUpController extends Controller
{
    public function store(Request $request, Payment $payment)
    {
        Gate::authorize('recordPayment', $payment);

        $data = $request->validate([
            'type' => ['required', Rule::in(PaymentFollowUp::TYPES)],
            'note' => ['nullable', 'string', 'required_unless:type,'.PaymentFollowUp::TYPE_PROMISE_TO_PAY],
            'promised_date' => ['nullable', 'date', 'required_if:type,'.PaymentFollowUp::TYPE_PROMISE_TO_PAY],
            'promised_amount' => ['nullable', 'numeric', 'min:0', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
        ]);

        PaymentFollowUp::create([
            'organization_id' => $payment->organization_id,
            'payment_id' => $payment->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'promised_date' => $data['promised_date'] ?? null,
            'promised_amount' => $data['promised_amount'] ?? null,
        ]);

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', __('payments.follow_ups.saved'));
    }
}
