<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PrivateDocumentStorage
{
    public function disk(): string
    {
        $disk = trim((string) config('filesystems.private_documents_disk', 'local'));

        return $disk !== '' ? $disk : 'local';
    }

    public function legacyDisk(?string $disk): string
    {
        $disk = trim((string) $disk);

        return $disk !== '' ? $disk : 'local';
    }

    public function paymentProofPrefix(int $organizationId, int $paymentId): string
    {
        return "organizations/{$organizationId}/payments/{$paymentId}/proofs";
    }

    public function expenseInvoicePrefix(int $organizationId, int $expenseId): string
    {
        return "organizations/{$organizationId}/expenses/{$expenseId}/invoices";
    }

    public function unitDocumentPrefix(int $organizationId, int $unitId): string
    {
        return "organizations/{$organizationId}/units/{$unitId}/documents";
    }

    public function legacyPaymentProofPrefix(): string
    {
        return 'payment-proofs';
    }

    public function legacyExpenseInvoicePrefix(): string
    {
        return 'expense-invoices';
    }

    public function legacyUnitDocumentPrefix(int $organizationId, int $unitId): string
    {
        return "unit-documents/{$organizationId}/{$unitId}";
    }

    public function store(UploadedFile $file, string $prefix, ?string $disk = null): array
    {
        $disk = $this->legacyDisk($disk ?: $this->disk());
        $path = Storage::disk($disk)->putFile($prefix, $file);

        if (! is_string($path) || trim($path) === '') {
            throw new RuntimeException('Private document upload failed.');
        }

        return ['disk' => $disk, 'path' => $path];
    }

    public function delete(?string $disk, ?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return true;
        }

        try {
            return Storage::disk($this->legacyDisk($disk))->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Private document delete failed.', [
                'disk' => $this->legacyDisk($disk),
                'path' => $path,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    public function deleteIfValid(?string $disk, ?string $path, array|string $prefixes): bool
    {
        try {
            return $this->delete($disk, $this->validatePath($path, $prefixes));
        } catch (Throwable) {
            return false;
        }
    }

    public function validatePath(?string $storedPath, array|string $prefixes): string
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

        if (in_array('..', $segments, true) || in_array('', $segments, true)) {
            abort(404);
        }

        foreach ((array) $prefixes as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && Str::startsWith($path, $prefix.'/')) {
                return $path;
            }
        }

        abort(404);
    }
}
