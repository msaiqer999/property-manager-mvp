<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class OperationsVerifyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_verify_succeeds_with_test_database_and_fake_private_disk(): void
    {
        $this->fakePrivateDisk();

        $this->artisan('operations:verify')
            ->expectsOutput('PASS Application boot')
            ->expectsOutput('PASS Database connection')
            ->expectsOutput('PASS Private document disk configured')
            ->expectsOutput('PASS Production private disk is durable')
            ->expectsOutput('PASS Private document disk probe')
            ->assertSuccessful();

        $this->assertSame([], Storage::disk('private-documents')->allFiles('operations/health-checks'));
    }

    public function test_operations_verify_fails_safely_when_database_check_fails(): void
    {
        $this->fakePrivateDisk();

        DB::shouldReceive('select')
            ->with('select 1')
            ->andThrow(new RuntimeException('secret_database_name'));

        $this->artisan('operations:verify')
            ->expectsOutput('PASS Application boot')
            ->expectsOutput('FAIL Database connection')
            ->doesntExpectOutputToContain('secret_database_name')
            ->assertExitCode(1);
    }

    public function test_operations_verify_fails_safely_when_storage_probe_fails(): void
    {
        $this->configurePrivateDisk();

        Storage::shouldReceive('disk')
            ->with('private-documents')
            ->twice()
            ->andReturn(new class {
                public function put(string $path, string $contents): bool
                {
                    throw new RuntimeException('secret-bucket https://storage.internal.example '.$path);
                }

                public function delete(string $path): bool
                {
                    return true;
                }
            });

        $this->artisan('operations:verify')
            ->expectsOutput('PASS Application boot')
            ->expectsOutput('PASS Database connection')
            ->expectsOutput('PASS Private document disk configured')
            ->expectsOutput('PASS Production private disk is durable')
            ->expectsOutput('FAIL Private document disk probe')
            ->doesntExpectOutputToContain('secret-bucket')
            ->doesntExpectOutputToContain('storage.internal.example')
            ->doesntExpectOutputToContain('operations/health-checks')
            ->assertExitCode(1);
    }

    public function test_operations_verify_fails_safely_when_storage_read_fails(): void
    {
        $this->configurePrivateDisk();

        Storage::shouldReceive('disk')
            ->with('private-documents')
            ->twice()
            ->andReturn(new class {
                public function put(string $path, string $contents): bool
                {
                    return true;
                }

                public function get(string $path): string
                {
                    return 'unexpected-content';
                }

                public function delete(string $path): bool
                {
                    return true;
                }
            });

        $this->artisan('operations:verify')
            ->expectsOutput('FAIL Private document disk probe')
            ->assertExitCode(1);
    }

    public function test_operations_verify_fails_safely_when_storage_delete_fails(): void
    {
        $this->configurePrivateDisk();

        Storage::shouldReceive('disk')
            ->with('private-documents')
            ->twice()
            ->andReturn(new class {
                public function put(string $path, string $contents): bool
                {
                    return true;
                }

                public function get(string $path): string
                {
                    return 'property-manager-operations-verify';
                }

                public function delete(string $path): bool
                {
                    return false;
                }

                public function exists(string $path): bool
                {
                    return true;
                }
            });

        $this->artisan('operations:verify')
            ->expectsOutput('FAIL Private document disk probe')
            ->assertExitCode(1);
    }

    public function test_operations_verify_fails_when_production_private_disk_is_local(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Config::set('filesystems.private_documents_disk', 'local');

        $this->artisan('operations:verify')
            ->expectsOutput('PASS Application boot')
            ->expectsOutput('PASS Database connection')
            ->expectsOutput('PASS Private document disk configured')
            ->expectsOutput('FAIL Production private disk is durable')
            ->expectsOutput('FAIL Private document disk probe')
            ->assertExitCode(1);
    }

    private function fakePrivateDisk(): void
    {
        Config::set('filesystems.private_documents_disk', 'private-documents');
        Storage::fake('private-documents');
        Config::set('filesystems.disks.private-documents', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/private-documents'),
            'throw' => false,
        ]);
    }

    private function configurePrivateDisk(): void
    {
        Config::set('filesystems.private_documents_disk', 'private-documents');
        Config::set('filesystems.disks.private-documents', [
            'driver' => 's3',
            'bucket' => 'secret-bucket',
            'endpoint' => 'https://storage.internal.example',
        ]);
    }
}
