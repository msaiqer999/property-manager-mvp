<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Payment;
use App\Services\ActivityLogger;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::with(['contract.tenant', 'contract.unit.building', 'latestPromise'])
            ->where('organization_id', $this->organizationId())
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->overdue, fn ($q) => $q
                ->where('status', '!=', 'cancelled')
                ->whereDate('due_date', '<=', now()->toDateString())
                ->whereColumn('amount_paid', '<', 'amount_due'))
            ->orderBy('due_date')
            ->paginate(20);

        return view('payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        Gate::authorize('view', $payment);
        $payment->loadMissing('contract.tenant', 'contract.unit.building', 'followUps.user');

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        Gate::authorize('recordPayment', $payment);
        $this->abortIfCancelled($payment);
        $payment->loadMissing('contract.tenant', 'contract.unit.building');

        return view('payments.form', compact('payment'));
    }

    public function update(Request $request, Payment $payment, ActivityLogger $logger)
    {
        Gate::authorize('recordPayment', $payment);
        $this->abortIfCancelled($payment);

        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:'.$payment->amount_due, 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque,other'],
            'proof_image' => ['nullable', 'image', 'max:4096'],
            'notes' => ['nullable', 'string'],
        ]);

        $currentPaidMinor = $this->decimalToMinorUnits((string) $payment->amount_paid);
        $incomingPaidMinor = $this->decimalToMinorUnits((string) $data['amount_paid']);
        $amountDueMinor = $this->decimalToMinorUnits((string) $payment->amount_due);

        if ($currentPaidMinor > 0 && $incomingPaidMinor < $currentPaidMinor) {
            abort(422, __('payments.validation.paid_amount_cannot_decrease'));
        }

        if ($request->hasFile('proof_image')) {
            Gate::authorize('uploadProof', $payment);
            $data['proof_image'] = $request->file('proof_image')->store('payment-proofs');
        }

        $data['status'] = $incomingPaidMinor >= $amountDueMinor
            ? 'paid'
            : ($incomingPaidMinor > 0 ? 'partial' : ($payment->due_date->isPast() ? 'overdue' : 'pending'));
        $data['created_by'] = auth()->id();
        $payment->update($data);

        $logger->log('payment.recorded', $payment);

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', __('payments.recorded_success'));
    }

    private function decimalToMinorUnits(string $value): int
    {
        $value = trim($value);

        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $value)) {
            abort(422);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function receipt(Payment $payment, PdfRenderer $pdf)
    {
        Gate::authorize('exportReceiptPdf', $payment);

        if ($this->decimalToMinorUnits((string) $payment->amount_paid) <= 0) {
            abort(422, __('payments.validation.receipt_unavailable_without_recorded_money'));
        }

        return $pdf->download('pdf.receipt', compact('payment'), "payment-receipt-{$payment->id}.pdf");
    }

    public function downloadProof(Payment $payment)
    {
        Gate::authorize('view', $payment);

        $path = $this->validatedPrivatePath($payment->proof_image, 'payment-proofs');

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $this->safeDownloadName('payment-proof', $payment->id, $path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function abortIfCancelled(Payment $payment): void
    {
        if ($payment->status === 'cancelled') {
            abort(422, __('payments.validation.cannot_record_cancelled'));
        }
    }

    private function validatedPrivatePath(?string $storedPath, string $prefix): string
    {
        $rawPath = trim((string) $storedPath);

        if ($rawPath === ''
            || str_contains($rawPath, '\\')
            || str_starts_with($rawPath, '/')
            || preg_match('/^[A-Za-z]:\//', $rawPath)
        ) {
            abort(404);
        }

        $path = preg_replace('#/+#', '/', $rawPath);
        $segments = explode('/', $path);

        if (in_array('..', $segments, true)
            || in_array('', $segments, true)
            || ! Str::startsWith($path, $prefix.'/')
        ) {
            abort(404);
        }

        return $path;
    }

    private function safeDownloadName(string $label, int $id, string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $extension = preg_match('/^[A-Za-z0-9]{1,10}$/', $extension) ? strtolower($extension) : 'bin';

        return "{$label}-{$id}.{$extension}";
    }
}
