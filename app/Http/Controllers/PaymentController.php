<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Payment;
use App\Services\ActivityLogger;
use App\Support\PdfRenderer;
use App\Support\PrivateDocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

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

    public function update(Request $request, Payment $payment, ActivityLogger $logger, PrivateDocumentStorage $documents)
    {
        Gate::authorize('recordPayment', $payment);
        $this->abortIfCancelled($payment);

        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:'.$payment->amount_due, 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque,other'],
            'proof_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096'],
            'notes' => ['nullable', 'string'],
        ]);

        $currentPaidMinor = $this->decimalToMinorUnits((string) $payment->amount_paid);
        $incomingPaidMinor = $this->decimalToMinorUnits((string) $data['amount_paid']);
        $amountDueMinor = $this->decimalToMinorUnits((string) $payment->amount_due);

        if ($currentPaidMinor > 0 && $incomingPaidMinor < $currentPaidMinor) {
            abort(422, __('payments.validation.paid_amount_cannot_decrease'));
        }

        $uploaded = null;
        $oldDisk = $payment->proof_disk;
        $oldPath = $payment->proof_image;

        if ($request->hasFile('proof_image')) {
            Gate::authorize('uploadProof', $payment);
            $uploaded = $documents->store(
                $request->file('proof_image'),
                $documents->paymentProofPrefix((int) $payment->organization_id, (int) $payment->id)
            );
            $data['proof_image'] = $uploaded['path'];
            $data['proof_disk'] = $uploaded['disk'];
        }

        $data['status'] = $incomingPaidMinor >= $amountDueMinor
            ? 'paid'
            : ($incomingPaidMinor > 0 ? 'partial' : ($payment->due_date->isPast() ? 'overdue' : 'pending'));
        $data['created_by'] = auth()->id();

        try {
            DB::transaction(function () use ($payment, $data, $logger): void {
                $payment->update($data);
                $logger->log('payment.recorded', $payment);
            });
        } catch (Throwable $exception) {
            if ($uploaded !== null) {
                $documents->delete($uploaded['disk'], $uploaded['path']);
            }

            throw $exception;
        }

        if ($uploaded !== null && $oldPath !== null) {
            $documents->deleteIfValid($oldDisk, $oldPath, [
                $documents->paymentProofPrefix((int) $payment->organization_id, (int) $payment->id),
                $documents->legacyPaymentProofPrefix(),
            ]);
        }

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

    public function downloadProof(Payment $payment, PrivateDocumentStorage $documents)
    {
        Gate::authorize('view', $payment);

        $disk = $documents->legacyDisk($payment->proof_disk);
        $hasStoredDisk = trim((string) $payment->proof_disk) !== '';
        $path = $documents->validatePath($payment->proof_image, $hasStoredDisk
            ? $documents->paymentProofPrefix((int) $payment->organization_id, (int) $payment->id)
            : $documents->legacyPaymentProofPrefix()
        );

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path, $this->safeDownloadName('payment-proof', $payment->id, $path), [
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

    private function safeDownloadName(string $label, int $id, string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $extension = preg_match('/^[A-Za-z0-9]{1,10}$/', $extension) ? strtolower($extension) : 'bin';

        return "{$label}-{$id}.{$extension}";
    }
}
