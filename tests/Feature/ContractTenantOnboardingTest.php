<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_creates_contract_with_existing_tenant(): void
    {
        [$manager, $data] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'existing',
            ]))
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();

        $this->assertSame($data['tenant']->id, $contract->tenant_id);
        $this->assertSame($manager->organization_id, $contract->organization_id);
        $this->assertMatchesRegularExpression('/^CN-2026-\d{6}$/', $contract->contract_number);
        $this->assertGreaterThan(0, $contract->payments()->count());
        $this->assertSame('rented', $data['unit']->fresh()->status);
    }

    public function test_authorized_user_creates_contract_and_new_tenant_in_one_submission(): void
    {
        [$manager, $data] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'new_tenant' => $this->newTenantPayload([
                    'full_name' => 'Inline Tenant',
                    'email' => 'INLINE-TENANT@EXAMPLE.COM',
                    'id_number' => 'INLINE-ID-001',
                ]),
            ]))
            ->assertRedirect();

        $tenant = Tenant::where('full_name', 'Inline Tenant')->firstOrFail();
        $contract = Contract::latest('id')->firstOrFail();

        $this->assertSame($manager->organization_id, $tenant->organization_id);
        $this->assertSame('inline-tenant@example.com', $tenant->email);
        $this->assertSame($tenant->id, $contract->tenant_id);
        $this->assertSame($manager->organization_id, $contract->organization_id);
        $this->assertMatchesRegularExpression('/^CN-2026-\d{6}$/', $contract->contract_number);
        $this->assertGreaterThan(0, $contract->payments()->count());
        $this->assertSame('rented', $data['unit']->fresh()->status);
    }

    public function test_contract_number_is_generated_without_request_input_and_unique(): void
    {
        [$manager, $data] = $this->scenario();

        $firstPayload = $this->contractPayload($data);
        unset($firstPayload['contract_number']);

        $this->actingAs($manager)->post(route('contracts.store'), $firstPayload)->assertRedirect();

        $secondUnit = Unit::create([
            'building_id' => $data['building']->id,
            'unit_number' => 'A-202',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);

        $secondPayload = $this->contractPayload($data, ['unit_id' => $secondUnit->id]);
        unset($secondPayload['contract_number']);

        $this->actingAs($manager)->post(route('contracts.store'), $secondPayload)->assertRedirect();

        $numbers = Contract::orderByDesc('id')->take(2)->pluck('contract_number');

        $this->assertCount(2, $numbers);
        $this->assertNotSame($numbers[0], $numbers[1]);
        $this->assertMatchesRegularExpression('/^CN-2026-\d{6}$/', $numbers[0]);
        $this->assertMatchesRegularExpression('/^CN-2026-\d{6}$/', $numbers[1]);
    }

    public function test_contract_creation_succeeds_with_blank_deposit_and_stores_zero(): void
    {
        [$manager, $data] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'deposit_amount' => '',
            ]))
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();

        $this->assertSame('0.00', number_format((float) $contract->deposit_amount, 2, '.', ''));
    }

    public function test_contract_creation_stores_provided_deposit(): void
    {
        [$manager, $data] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'deposit_amount' => 750.25,
            ]))
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();

        $this->assertSame('750.25', number_format((float) $contract->deposit_amount, 2, '.', ''));
    }

    public function test_negative_deposit_is_rejected_without_partial_records(): void
    {
        [$manager, $data] = $this->scenario();

        $tenantCount = Tenant::count();
        $contractCount = Contract::count();
        $paymentCount = Payment::count();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'deposit_amount' => -1,
                'new_tenant' => $this->newTenantPayload([
                    'full_name' => 'Invalid Deposit Tenant',
                    'email' => 'invalid-deposit@example.com',
                    'id_number' => 'INVALID-DEPOSIT-ID',
                ]),
            ]))
            ->assertSessionHasErrors('deposit_amount');

        $this->assertSame($tenantCount, Tenant::count());
        $this->assertSame($contractCount, Contract::count());
        $this->assertSame($paymentCount, Payment::count());
        $this->assertSame('vacant', $data['unit']->fresh()->status);
        $this->assertDatabaseMissing('tenants', [
            'organization_id' => $manager->organization_id,
            'full_name' => 'Invalid Deposit Tenant',
        ]);
    }

    public function test_same_existing_tenant_can_rent_multiple_different_units(): void
    {
        [$manager, $data] = $this->scenario();

        $secondUnit = Unit::create([
            'building_id' => $data['building']->id,
            'unit_number' => 'A-202',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1100,
        ]);

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'existing',
                'unit_id' => $data['unit']->id,
                'deposit_amount' => '',
            ]))
            ->assertRedirect();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'existing',
                'unit_id' => $secondUnit->id,
                'deposit_amount' => 250,
            ]))
            ->assertRedirect();

        $contracts = Contract::where('tenant_id', $data['tenant']->id)->orderBy('id')->get();

        $this->assertCount(2, $contracts);
        $this->assertSame($data['unit']->id, $contracts[0]->unit_id);
        $this->assertSame($secondUnit->id, $contracts[1]->unit_id);
        $this->assertGreaterThan(0, Payment::where('contract_id', $contracts[0]->id)->count());
        $this->assertGreaterThan(0, Payment::where('contract_id', $contracts[1]->id)->count());
        $this->assertSame('rented', $data['unit']->fresh()->status);
        $this->assertSame('rented', $secondUnit->fresh()->status);
    }

    public function test_create_form_does_not_require_contract_number(): void
    {
        [$manager] = $this->scenario();

        $this->actingAs($manager)->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('The contract number will be generated automatically.')
            ->assertDontSee('name="contract_number"', false);
    }

    public function test_edit_form_shows_contract_number_read_only_and_update_does_not_change_it(): void
    {
        [$manager, $data] = $this->scenario();
        $contract = Contract::create([
            'organization_id' => $manager->organization_id,
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'contract_number' => 'EXISTING-CN-001',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
        ]);

        $this->actingAs($manager)->get(route('contracts.edit', $contract))
            ->assertOk()
            ->assertSee('EXISTING-CN-001')
            ->assertSee('readonly', false);

        $this->actingAs($manager)
            ->put(route('contracts.update', $contract), $this->contractPayload($data, [
                'contract_number' => 'SHOULD-NOT-CHANGE',
                'rent_amount' => 1200,
            ]))
            ->assertRedirect(route('contracts.show', $contract));

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'contract_number' => 'EXISTING-CN-001',
            'rent_amount' => 1200,
        ]);
    }

    public function test_cross_organization_existing_tenant_injection_is_rejected(): void
    {
        [$manager, $data, $otherData] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'existing',
                'tenant_id' => $otherData['tenant']->id,
                'contract_number' => 'CROSS-TENANT-INJECTION',
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('contracts', ['contract_number' => 'CROSS-TENANT-INJECTION']);
    }

    public function test_cross_organization_unit_injection_is_rejected(): void
    {
        [$manager, $data, $otherData] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'unit_id' => $otherData['unit']->id,
                'contract_number' => 'CROSS-UNIT-INJECTION',
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('contracts', ['contract_number' => 'CROSS-UNIT-INJECTION']);
    }

    public function test_new_tenant_submission_with_cross_organization_unit_does_not_leave_partial_tenant(): void
    {
        [$manager, $data, $otherData] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'unit_id' => $otherData['unit']->id,
                'contract_number' => 'NEW-TENANT-CROSS-UNIT',
                'new_tenant' => $this->newTenantPayload(['full_name' => 'Rollback Tenant']),
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('tenants', [
            'organization_id' => $manager->organization_id,
            'full_name' => 'Rollback Tenant',
        ]);
        $this->assertDatabaseMissing('contracts', ['contract_number' => 'NEW-TENANT-CROSS-UNIT']);
    }

    public function test_accountant_and_caretaker_cannot_use_contract_tenant_onboarding_workflow(): void
    {
        [, $data] = $this->scenario();
        $accountant = $this->user($data['organization'], 'accountant@example.com', 'accountant');
        $caretaker = $this->user($data['organization'], 'caretaker@example.com', 'caretaker');

        foreach ([$accountant, $caretaker] as $user) {
            $this->actingAs($user)
                ->post(route('contracts.store'), $this->contractPayload($data, [
                    'tenant_mode' => 'new',
                    'tenant_id' => null,
                    'contract_number' => "BLOCKED-{$user->id}",
                    'new_tenant' => $this->newTenantPayload(['full_name' => "Blocked {$user->id}"]),
                ]))
                ->assertForbidden();
        }
    }

    public function test_same_organization_duplicate_id_number_is_rejected(): void
    {
        [$manager, $data] = $this->scenario();

        Tenant::create([
            'organization_id' => $manager->organization_id,
            'full_name' => 'Existing Duplicate',
            'id_number' => 'DUP-ID',
        ]);

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'contract_number' => 'DUP-ID-CONTRACT',
                'new_tenant' => $this->newTenantPayload([
                    'full_name' => 'Different Name',
                    'id_number' => ' DUP-ID ',
                ]),
            ]))
            ->assertSessionHasErrors('new_tenant.full_name');

        $this->assertDatabaseMissing('contracts', ['contract_number' => 'DUP-ID-CONTRACT']);
    }

    public function test_same_organization_matching_full_name_and_email_is_rejected(): void
    {
        [$manager, $data] = $this->scenario();

        Tenant::create([
            'organization_id' => $manager->organization_id,
            'full_name' => 'Email Duplicate',
            'email' => 'duplicate@example.com',
        ]);

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'contract_number' => 'DUP-EMAIL-CONTRACT',
                'new_tenant' => $this->newTenantPayload([
                    'full_name' => 'Email Duplicate',
                    'email' => ' DUPLICATE@EXAMPLE.COM ',
                ]),
            ]))
            ->assertSessionHasErrors('new_tenant.full_name');

        $this->assertDatabaseMissing('contracts', ['contract_number' => 'DUP-EMAIL-CONTRACT']);
    }

    public function test_same_organization_matching_full_name_and_phone_is_rejected(): void
    {
        [$manager, $data] = $this->scenario();

        Tenant::create([
            'organization_id' => $manager->organization_id,
            'full_name' => 'Phone Duplicate',
            'phone' => '+971500000000',
        ]);

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'contract_number' => 'DUP-PHONE-CONTRACT',
                'new_tenant' => $this->newTenantPayload([
                    'full_name' => 'Phone Duplicate',
                    'phone' => ' +971500000000 ',
                ]),
            ]))
            ->assertSessionHasErrors('new_tenant.full_name');

        $this->assertDatabaseMissing('contracts', ['contract_number' => 'DUP-PHONE-CONTRACT']);
    }

    public function test_matching_tenant_in_different_organization_does_not_block_creation(): void
    {
        [$manager, $data, $otherData] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'contract_number' => 'OTHER-ORG-MATCH-ALLOWED',
                'new_tenant' => $this->newTenantPayload([
                    'full_name' => $otherData['tenant']->full_name,
                    'email' => $otherData['tenant']->email,
                    'phone' => $otherData['tenant']->phone,
                    'id_number' => $otherData['tenant']->id_number,
                ]),
            ]))
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();

        $this->assertSame($manager->organization_id, $contract->tenant->organization_id);
    }

    public function test_validation_failure_does_not_create_tenant_or_contract(): void
    {
        [$manager, $data] = $this->scenario();

        $tenantCount = Tenant::count();
        $contractCount = Contract::count();

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_mode' => 'new',
                'tenant_id' => null,
                'contract_number' => 'INVALID-NEW-TENANT',
                'new_tenant' => $this->newTenantPayload(['full_name' => '']),
            ]))
            ->assertSessionHasErrors('new_tenant.full_name');

        $this->assertSame($tenantCount, Tenant::count());
        $this->assertSame($contractCount, Contract::count());
        $this->assertDatabaseMissing('contracts', ['contract_number' => 'INVALID-NEW-TENANT']);
    }

    public function test_existing_contract_creation_behavior_remains_working_without_tenant_mode(): void
    {
        [$manager, $data] = $this->scenario();

        $payload = $this->contractPayload($data);
        unset($payload['tenant_mode']);

        $this->actingAs($manager)
            ->post(route('contracts.store'), $payload)
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();

        $this->assertSame($data['tenant']->id, $contract->tenant_id);
        $this->assertGreaterThan(0, Payment::where('contract_id', $contract->id)->count());
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Organization A']);
        $otherOrganization = Organization::create(['name' => 'Organization B']);

        $manager = $this->user($organization, 'manager@example.com', 'manager');

        return [
            $manager,
            $this->organizationData($organization, 'A'),
            $this->organizationData($otherOrganization, 'B'),
        ];
    }

    private function organizationData(Organization $organization, string $prefix): array
    {
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => "Building {$prefix}",
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => "{$prefix}-101",
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Tenant {$prefix}",
            'phone' => "+97150000000{$prefix}",
            'email' => "tenant-{$prefix}@example.com",
            'id_number' => "ID-{$prefix}",
        ]);

        return compact('organization', 'building', 'unit', 'tenant');
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function contractPayload(array $data, array $overrides = []): array
    {
        return array_replace_recursive([
            'tenant_mode' => 'existing',
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
            'notes' => 'Test contract',
        ], $overrides);
    }

    private function newTenantPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'New Tenant',
            'phone' => '+971501234567',
            'email' => 'new-tenant@example.com',
            'id_number' => 'NEW-ID',
            'nationality' => 'UAE',
            'notes' => 'Inline tenant',
        ], $overrides);
    }
}
