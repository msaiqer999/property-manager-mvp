<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PrivateDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_users_can_download_private_payment_proof_and_expense_invoice(): void
    {
        [$owner, , $accountant, , , $data] = $this->createTwoOrganizationScenario();
        Storage::fake('local');

        $data['payment']->update(['proof_image' => 'payment-proofs/private-proof.png']);
        $data['expense']->update(['invoice_image' => 'expense-invoices/private-invoice.png']);
        Storage::disk('local')->put('payment-proofs/private-proof.png', 'proof-bytes');
        Storage::disk('local')->put('expense-invoices/private-invoice.png', 'invoice-bytes');

        $proofResponse = $this->actingAs($owner)->get(route('payments.proof.download', $data['payment']));
        $proofResponse->assertOk();
        $this->assertStringContainsString('attachment;', (string) $proofResponse->headers->get('content-disposition'));
        $this->assertStringContainsString("payment-proof-{$data['payment']->id}.png", (string) $proofResponse->headers->get('content-disposition'));
        $this->assertStringContainsString('private', (string) $proofResponse->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $proofResponse->headers->get('cache-control'));
        $this->assertSame('nosniff', $proofResponse->headers->get('x-content-type-options'));

        $invoiceResponse = $this->actingAs($accountant)->get(route('expenses.invoice', $data['expense']));
        $invoiceResponse->assertOk();
        $this->assertStringContainsString('attachment;', (string) $invoiceResponse->headers->get('content-disposition'));
        $this->assertStringContainsString("expense-invoice-{$data['expense']->id}.png", (string) $invoiceResponse->headers->get('content-disposition'));
        $this->assertStringContainsString('private', (string) $invoiceResponse->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $invoiceResponse->headers->get('cache-control'));
        $this->assertSame('nosniff', $invoiceResponse->headers->get('x-content-type-options'));
    }

    public function test_private_storage_paths_are_not_rendered_in_html(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();

        $data['payment']->update(['proof_image' => 'payment-proofs/private-proof.png']);
        $data['expense']->update(['invoice_image' => 'expense-invoices/private-invoice.png']);

        $this->actingAs($owner)->get(route('payments.show', $data['payment']))
            ->assertOk()
            ->assertSee(route('payments.proof.download', $data['payment'], absolute: false))
            ->assertSee('Download proof')
            ->assertDontSee('payment-proofs/private-proof.png');

        $this->actingAs($owner)->get(route('expenses.show', $data['expense']))
            ->assertOk()
            ->assertSee(route('expenses.invoice', $data['expense'], absolute: false))
            ->assertSee('Download invoice')
            ->assertDontSee('expense-invoices/private-invoice.png');
    }

    public function test_expense_without_invoice_does_not_render_a_broken_invoice_link(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();

        $data['expense']->update(['invoice_image' => null]);

        $this->actingAs($owner)
            ->get(route('expenses.show', $data['expense']))
            ->assertOk()
            ->assertDontSee(route('expenses.invoice', $data['expense'], absolute: false))
            ->assertDontSee('Download invoice');
    }

    public function test_cross_organization_downloads_are_denied_before_file_state_disclosure(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $dataB['payment']->update(['proof_image' => '../outside.png']);
        $dataB['expense']->update(['invoice_image' => 'wrong-prefix/private.png']);

        $this->actingAs($ownerA)->get(route('payments.proof.download', $dataB['payment']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('expenses.invoice', $dataB['expense']))->assertForbidden();
    }

    public function test_unauthorized_role_behavior_matches_existing_view_policies(): void
    {
        [, , , $caretaker, , $data] = $this->createTwoOrganizationScenario();

        $this->actingAs($caretaker)->get(route('payments.proof.download', $data['payment']))->assertNotFound();
        $this->actingAs($caretaker)->get(route('expenses.invoice', $data['expense']))->assertForbidden();
    }

    public function test_missing_expense_invoice_file_redirects_with_clear_message_after_authorization(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        Storage::fake('local');

        $data['expense']->update(['invoice_image' => 'expense-invoices/missing.png']);

        $this->actingAs($owner)
            ->get(route('expenses.invoice', $data['expense']))
            ->assertRedirect(route('expenses.show', $data['expense']))
            ->assertSessionHas('status', __('expenses.invoice_missing'));
    }

    public function test_expense_show_displays_neutral_hint_after_missing_invoice_redirect(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();

        $data['expense']->update(['invoice_image' => 'expense-invoices/missing.png']);

        $this->actingAs($owner)
            ->withSession(['status' => __('expenses.invoice_missing'), 'locale' => 'en'])
            ->get(route('expenses.show', $data['expense']))
            ->assertOk()
            ->assertSee('Invoice file is currently unavailable.')
            ->assertDontSee(route('expenses.invoice', $data['expense'], absolute: false))
            ->assertDontSee('expense-invoices/missing.png');
    }

    public function test_invalid_private_document_paths_return_not_found_after_authorization(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        Storage::fake('local');

        foreach ([
            'payment-proofs/missing.png',
            '',
            '../secret.png',
            '/absolute/secret.png',
            'C:/absolute/secret.png',
            'wrong-prefix/secret.png',
        ] as $path) {
            $data['payment']->update(['proof_image' => $path]);
            $this->actingAs($owner)->get(route('payments.proof.download', $data['payment']))->assertNotFound();
        }

        foreach ([
            '',
            '../secret.png',
            '/absolute/secret.png',
            'C:/absolute/secret.png',
            'wrong-prefix/secret.png',
        ] as $path) {
            $data['expense']->update(['invoice_image' => $path]);
            $this->actingAs($owner)->get(route('expenses.invoice', $data['expense']))->assertNotFound();
        }
    }

    public function test_private_document_routes_do_not_create_public_storage_exposure(): void
    {
        $this->assertSame('/payments/1/proof', route('payments.proof.download', 1, absolute: false));
        $this->assertSame('/expenses/1/invoice', route('expenses.invoice', 1, absolute: false));
        $this->assertSame('/expenses/1/invoice/download', route('expenses.invoice.download', 1, absolute: false));
        $this->assertDirectoryDoesNotExist(public_path('storage'));
    }

    public function test_owner_can_upload_invoice_when_creating_an_expense_and_open_it(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        config(['filesystems.private_documents_disk' => 'private-docs-test']);
        Storage::fake('local');
        Storage::fake('private-docs-test');

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'building_id' => $data['building']->id,
                'unit_id' => $data['unit']->id,
                'category' => 'maintenance',
                'amount' => 125,
                'expense_date' => now()->toDateString(),
                'invoice_image' => $this->fakePngUpload('invoice.png'),
                'notes' => 'Created with invoice.',
            ])
            ->assertRedirect();

        $expense = Expense::where('notes', 'Created with invoice.')->firstOrFail();

        $this->assertNotNull($expense->invoice_image);
        $this->assertSame('private-docs-test', $expense->invoice_disk);
        $this->assertStringStartsWith("organizations/{$owner->organization_id}/expenses/{$expense->id}/invoices/", $expense->invoice_image);
        Storage::disk('private-docs-test')->assertExists($expense->invoice_image);
        Storage::disk('local')->assertMissing($expense->invoice_image);

        $this->actingAs($owner)
            ->get(route('expenses.invoice', $expense))
            ->assertOk();
    }

    public function test_payment_proof_upload_uses_configured_disk_and_stored_disk_download(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        config(['filesystems.private_documents_disk' => 'private-docs-test']);
        Storage::fake('local');
        Storage::fake('private-docs-test');

        $this->actingAs($owner)
            ->put(route('payments.update', $data['payment']), $this->paymentPayload($data['payment'], [
                'proof_image' => $this->fakePngUpload('proof.png'),
            ]))
            ->assertRedirect(route('payments.show', $data['payment']));

        $payment = $data['payment']->fresh();

        $this->assertSame('private-docs-test', $payment->proof_disk);
        $this->assertStringStartsWith("organizations/{$owner->organization_id}/payments/{$payment->id}/proofs/", $payment->proof_image);
        Storage::disk('private-docs-test')->assertExists($payment->proof_image);
        Storage::disk('local')->assertMissing($payment->proof_image);

        $this->actingAs($owner)
            ->get(route('payments.proof.download', $payment))
            ->assertOk();
    }

    public function test_payment_proof_replacement_removes_old_private_object_after_success(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        config(['filesystems.private_documents_disk' => 'private-docs-test']);
        Storage::fake('private-docs-test');

        $oldPath = "organizations/{$owner->organization_id}/payments/{$data['payment']->id}/proofs/original-proof.png";
        Storage::disk('private-docs-test')->put($oldPath, 'old-proof');
        $data['payment']->update([
            'proof_disk' => 'private-docs-test',
            'proof_image' => $oldPath,
        ]);

        $this->actingAs($owner)
            ->put(route('payments.update', $data['payment']), $this->paymentPayload($data['payment'], [
                'proof_image' => $this->fakePngUpload('replacement-proof.png'),
            ]))
            ->assertRedirect(route('payments.show', $data['payment']));

        $payment = $data['payment']->fresh();

        $this->assertSame('private-docs-test', $payment->proof_disk);
        $this->assertNotSame($oldPath, $payment->proof_image);
        Storage::disk('private-docs-test')->assertMissing($oldPath);
        Storage::disk('private-docs-test')->assertExists($payment->proof_image);
    }

    public function test_expense_invoice_replacement_removes_old_private_object_after_success(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        config(['filesystems.private_documents_disk' => 'private-docs-test']);
        Storage::fake('private-docs-test');

        $oldPath = "organizations/{$owner->organization_id}/expenses/{$data['expense']->id}/invoices/original-invoice.png";
        Storage::disk('private-docs-test')->put($oldPath, 'old-invoice');
        $data['expense']->update([
            'invoice_disk' => 'private-docs-test',
            'invoice_image' => $oldPath,
        ]);

        $this->actingAs($owner)
            ->put(route('expenses.update', $data['expense']), $this->expensePayload($data, [
                'invoice_image' => $this->fakePngUpload('replacement-invoice.png'),
            ]))
            ->assertRedirect(route('expenses.show', $data['expense']));

        $expense = $data['expense']->fresh();

        $this->assertSame('private-docs-test', $expense->invoice_disk);
        $this->assertNotSame($oldPath, $expense->invoice_image);
        Storage::disk('private-docs-test')->assertMissing($oldPath);
        Storage::disk('private-docs-test')->assertExists($expense->invoice_image);
    }

    public function test_uploaded_payment_proof_is_removed_when_database_work_fails(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        config(['filesystems.private_documents_disk' => 'private-docs-test']);
        Storage::fake('private-docs-test');

        $this->mock(ActivityLogger::class, function ($mock): void {
            $mock->shouldReceive('log')
                ->once()
                ->andThrow(new RuntimeException('forced payment log failure'));
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($owner)
                ->put(route('payments.update', $data['payment']), $this->paymentPayload($data['payment'], [
                    'proof_image' => $this->fakePngUpload('proof.png'),
                ]));

            $this->fail('The forced payment log failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced payment log failure', $exception->getMessage());
        }

        $payment = $data['payment']->fresh();

        $this->assertNull($payment->proof_disk);
        $this->assertNull($payment->proof_image);
        $this->assertSame([], Storage::disk('private-docs-test')->allFiles());
    }

    public function test_uploaded_expense_invoice_is_removed_when_database_work_fails(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        config(['filesystems.private_documents_disk' => 'private-docs-test']);
        Storage::fake('private-docs-test');

        $this->mock(ActivityLogger::class, function ($mock): void {
            $mock->shouldReceive('log')
                ->once()
                ->andThrow(new RuntimeException('forced expense log failure'));
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($owner)
                ->post(route('expenses.store'), [
                    'building_id' => $data['building']->id,
                    'unit_id' => $data['unit']->id,
                    'category' => 'maintenance',
                    'amount' => 125,
                    'expense_date' => now()->toDateString(),
                    'invoice_image' => $this->fakePngUpload('invoice.png'),
                    'notes' => 'Created then rolled back.',
                ]);

            $this->fail('The forced expense log failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced expense log failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('expenses', ['notes' => 'Created then rolled back.']);
        $this->assertSame([], Storage::disk('private-docs-test')->allFiles());
    }

    public function test_missing_stored_disk_objects_fail_after_authorization_without_path_disclosure(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        Storage::fake('private-docs-test');

        $data['payment']->update([
            'proof_disk' => 'private-docs-test',
            'proof_image' => "organizations/{$owner->organization_id}/payments/{$data['payment']->id}/proofs/missing.png",
        ]);
        $data['expense']->update([
            'invoice_disk' => 'private-docs-test',
            'invoice_image' => "organizations/{$owner->organization_id}/expenses/{$data['expense']->id}/invoices/missing.png",
        ]);

        $this->actingAs($owner)
            ->get(route('payments.proof.download', $data['payment']))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('expenses.invoice', $data['expense']))
            ->assertRedirect(route('expenses.show', $data['expense']))
            ->assertSessionHas('status', __('expenses.invoice_missing'));
    }

    public function test_payment_and_expense_upload_validation_rejects_unsafe_misleading_and_oversized_files(): void
    {
        [$owner, , , , , $data] = $this->createTwoOrganizationScenario();
        config(['filesystems.private_documents_disk' => 'private-docs-test']);
        Storage::fake('private-docs-test');

        foreach ([
            UploadedFile::fake()->create('proof.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->create('proof.png.exe', 10, 'image/png'),
            UploadedFile::fake()->create('proof.png', 4097, 'image/png'),
        ] as $file) {
            $this->actingAs($owner)
                ->from(route('payments.edit', $data['payment']))
                ->put(route('payments.update', $data['payment']), $this->paymentPayload($data['payment'], [
                    'proof_image' => $file,
                ]))
                ->assertRedirect(route('payments.edit', $data['payment']))
                ->assertSessionHasErrors('proof_image');
        }

        foreach ([
            UploadedFile::fake()->create('invoice.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->create('invoice.png.exe', 10, 'image/png'),
            UploadedFile::fake()->create('invoice.webp', 4097, 'image/webp'),
        ] as $file) {
            $this->actingAs($owner)
                ->from(route('expenses.edit', $data['expense']))
                ->put(route('expenses.update', $data['expense']), $this->expensePayload($data, [
                    'invoice_image' => $file,
                ]))
                ->assertRedirect(route('expenses.edit', $data['expense']))
                ->assertSessionHasErrors('invoice_image');
        }

        $this->assertSame([], Storage::disk('private-docs-test')->allFiles());
    }

    private function createTwoOrganizationScenario(): array
    {
        $organizationA = Organization::create(['name' => 'Private Docs A']);
        $organizationB = Organization::create(['name' => 'Private Docs B']);

        $ownerA = $this->user($organizationA, 'owner-a@example.com', 'owner');
        $managerA = $this->user($organizationA, 'manager-a@example.com', 'manager');
        $accountantA = $this->user($organizationA, 'accountant-a@example.com', 'accountant');
        $caretakerA = $this->user($organizationA, 'caretaker-a@example.com', 'caretaker');
        $ownerB = $this->user($organizationB, 'owner-b@example.com', 'owner');

        $dataA = $this->organizationData($organizationA, $ownerA, 'A', 1000);
        $dataB = $this->organizationData($organizationB, $ownerB, 'B', 9000);

        return [$ownerA, $managerA, $accountantA, $caretakerA, $dataB + ['owner' => $ownerB], $dataA + ['owner' => $ownerA]];
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function organizationData(Organization $organization, User $owner, string $suffix, int $amount): array
    {
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Building '.$suffix,
            'location' => 'Abu Dhabi',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $suffix.'-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => $amount,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Tenant '.$suffix,
            'phone' => '0500000000',
        ]);
        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'DOC-'.$suffix,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => $amount,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);
        $payment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => now()->startOfMonth()->toDateString(),
            'amount_due' => $amount,
            'amount_paid' => $amount,
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'payment_method' => 'cash',
            'created_by' => $owner->id,
        ]);
        $expense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => $amount,
            'expense_date' => now()->toDateString(),
            'created_by' => $owner->id,
        ]);

        return compact('building', 'unit', 'tenant', 'contract', 'payment', 'expense');
    }

    private function paymentPayload(Payment $payment, array $overrides = []): array
    {
        return array_merge([
            'amount_paid' => $payment->amount_due,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Private document test payment.',
        ], $overrides);
    }

    private function expensePayload(array $data, array $overrides = []): array
    {
        return array_merge([
            'building_id' => $data['building']->id,
            'unit_id' => $data['unit']->id,
            'category' => 'maintenance',
            'amount' => 125,
            'expense_date' => now()->toDateString(),
            'notes' => 'Private document test expense.',
        ], $overrides);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }
}
