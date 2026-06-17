<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Payment;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::with(['contract.tenant', 'contract.unit'])
            ->where('organization_id', $this->organizationId())
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->overdue, fn ($q) => $q->where('due_date', '<', now())->where('status', '!=', 'paid'))
            ->orderBy('due_date')
            ->paginate(20);

        return view('payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $this->authorizePayment($payment);
        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        abort_unless(auth()->user()->role->can('record-payment'), 403);
        Gate::authorize('recordPayment', $payment);
        $this->authorizePayment($payment);
        return view('payments.form', compact('payment'));
    }

    public function update(Request $request, Payment $payment, ActivityLogger $logger)
    {
        abort_unless(auth()->user()->role->can('record-payment'), 403);
        Gate::authorize('recordPayment', $payment);
        $this->authorizePayment($payment);

        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:'.$payment->amount_due],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque,other'],
            'proof_image' => ['nullable', 'image', 'max:4096'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('proof_image')) {
            Gate::authorize('uploadProof', $payment);
            $data['proof_image'] = $request->file('proof_image')->store('payment-proofs');
        }

        $data['status'] = $data['amount_paid'] >= $payment->amount_due
            ? 'paid'
            : ($data['amount_paid'] > 0 ? 'partial' : ($payment->due_date->isPast() ? 'overdue' : 'pending'));
        $data['created_by'] = auth()->id();
        $payment->update($data);

        $logger->log('payment.recorded', $payment);

        return redirect()->route('payments.show', $payment);
    }

    public function receipt(Payment $payment)
    {
        Gate::authorize('exportReceiptPdf', $payment);
        $this->authorizePayment($payment);
        return Pdf::loadView('pdf.receipt', compact('payment'))->download("payment-receipt-{$payment->id}.pdf");
    }

    private function authorizePayment(Payment $payment): void
    {
        Gate::authorize('view', $payment);
        abort_unless($payment->organization_id === $this->organizationId(), 403);
    }
}
