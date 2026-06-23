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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

        $invoiceResponse = $this->actingAs($accountant)->get(route('expenses.invoice.download', $data['expense']));
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
            ->assertSee(route('expenses.invoice.download', $data['expense'], absolute: false))
            ->assertSee('Download invoice')
            ->assertDontSee('expense-invoices/private-invoice.png');
    }

    public function test_cross_organization_downloads_are_denied_before_file_state_disclosure(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $dataB['payment']->update(['proof_image' => '../outside.png']);
        $dataB['expense']->update(['invoice_image' => 'wrong-prefix/private.png']);

        $this->actingAs($ownerA)->get(route('payments.proof.download', $dataB['payment']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('expenses.invoice.download', $dataB['expense']))->assertForbidden();
    }

    public function test_unauthorized_role_behavior_matches_existing_view_policies(): void
    {
        [, , , $caretaker, , $data] = $this->createTwoOrganizationScenario();

        $this->actingAs($caretaker)->get(route('payments.proof.download', $data['payment']))->assertNotFound();
        $this->actingAs($caretaker)->get(route('expenses.invoice.download', $data['expense']))->assertForbidden();
    }

    public function test_missing_and_invalid_private_document_paths_return_not_found_after_authorization(): void
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
            'expense-invoices/missing.png',
            '',
            '../secret.png',
            '/absolute/secret.png',
            'C:/absolute/secret.png',
            'wrong-prefix/secret.png',
        ] as $path) {
            $data['expense']->update(['invoice_image' => $path]);
            $this->actingAs($owner)->get(route('expenses.invoice.download', $data['expense']))->assertNotFound();
        }
    }

    public function test_private_document_routes_do_not_create_public_storage_exposure(): void
    {
        $this->assertSame('/payments/1/proof', route('payments.proof.download', 1, absolute: false));
        $this->assertSame('/expenses/1/invoice', route('expenses.invoice.download', 1, absolute: false));
        $this->assertDirectoryDoesNotExist(public_path('storage'));
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
}
