<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OperationsVerify extends Command
{
    protected $signature = 'operations:verify';
    protected $description = 'Run safe, non-destructive production readiness checks.';

    public function handle(): int
    {
        $startedAt = now();
        $failed = 0;

        Log::info('Operations verification started.', [
            'command' => 'operations:verify',
            'started_at' => $startedAt->toIso8601String(),
        ]);

        $this->pass('Application boot');

        if (! $this->checkDatabase()) {
            $failed++;
        }

        $disk = config('filesystems.private_documents_disk');
        $diskConfig = $this->privateDiskConfig($disk);

        if (! $diskConfig) {
            $this->outputFailure('Private document disk configured');
            $failed++;
        } else {
            $this->pass('Private document disk configured');
        }

        $productionLocalDisk = $this->isProductionLocalDisk($disk, $diskConfig);

        if ($productionLocalDisk) {
            $this->outputFailure('Production private disk is durable');
            $failed++;
        } else {
            $this->pass('Production private disk is durable');
        }

        if ($diskConfig && ! $productionLocalDisk) {
            if (! $this->checkPrivateDiskProbe((string) $disk)) {
                $failed++;
            }
        } else {
            $this->outputFailure('Private document disk probe');
            $failed++;
        }

        Log::info('Operations verification completed.', [
            'command' => 'operations:verify',
            'started_at' => $startedAt->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'failed_count' => $failed,
        ]);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('select 1');

            $this->pass('Database connection');

            return true;
        } catch (Throwable $exception) {
            Log::warning('Operations verification database check failed.', [
                'command' => 'operations:verify',
                'exception_class' => get_class($exception),
            ]);

            $this->outputFailure('Database connection');

            return false;
        }
    }

    private function privateDiskConfig(mixed $disk): ?array
    {
        if (! is_string($disk) || $disk === '') {
            return null;
        }

        $config = config("filesystems.disks.{$disk}");

        return is_array($config) ? $config : null;
    }

    private function isProductionLocalDisk(mixed $disk, ?array $diskConfig): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        return $disk === 'local' || Arr::get($diskConfig ?? [], 'driver') === 'local';
    }

    private function checkPrivateDiskProbe(string $disk): bool
    {
        $path = 'operations/health-checks/'.Str::uuid().'.txt';
        $payload = 'property-manager-operations-verify';
        $cleanupNeeded = true;

        try {
            $storage = Storage::disk($disk);

            if ($storage->put($path, $payload) !== true) {
                throw new \RuntimeException('Storage probe write failed.');
            }

            if ($storage->get($path) !== $payload) {
                throw new \RuntimeException('Storage probe read failed.');
            }

            if ($storage->delete($path) !== true && $storage->exists($path)) {
                throw new \RuntimeException('Storage probe cleanup failed.');
            }

            $cleanupNeeded = false;
            $this->pass('Private document disk probe');

            return true;
        } catch (Throwable $exception) {
            Log::warning('Operations verification storage check failed.', [
                'command' => 'operations:verify',
                'exception_class' => get_class($exception),
            ]);

            $this->outputFailure('Private document disk probe');

            return false;
        } finally {
            if ($cleanupNeeded) {
                $this->cleanupProbe($disk, $path);
            }
        }
    }

    private function cleanupProbe(string $disk, string $path): void
    {
        try {
            Storage::disk($disk)->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Operations verification cleanup failed.', [
                'command' => 'operations:verify',
                'exception_class' => get_class($exception),
            ]);
        }
    }

    private function pass(string $check): void
    {
        $this->line("PASS {$check}");
    }

    private function outputFailure(string $check): void
    {
        $this->line("FAIL {$check}");
    }
}
