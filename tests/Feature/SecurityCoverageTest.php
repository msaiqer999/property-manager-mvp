<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_access_another_organizations_record_pages_or_pdfs(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('buildings.show', $dataB['building']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('buildings.edit', $dataB['building']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('buildings.update', $dataB['building']), [
            'name' => 'Cross Org Building',
        ])->assertForbidden();

        $this->actingAs($ownerA)->get(route('units.show', $dataB['unit']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('units.edit', $dataB['unit']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('units.update', $dataB['unit']), $this->unitPayload($dataB['building']))
            ->assertForbidden();

        $this->actingAs($ownerA)->get(route('tenants.show', $dataB['tenant']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('tenants.edit', $dataB['tenant']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('tenants.update', $dataB['tenant']), [
            'full_name' => 'Cross Org Tenant',
        ])->assertForbidden();

        $this->actingAs($ownerA)->get(route('contracts.show', $dataB['contract']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('contracts.edit', $dataB['contract']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('contracts.update', $dataB['contract']), $this->contractPayload($dataB))
            ->assertForbidden();
        $this->actingAs($ownerA)->get(route('contracts.pdf', $dataB['contract']))->assertForbidden();

        $this->actingAs($ownerA)->get(route('payments.show', $dataB['payment']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('payments.edit', $dataB['payment']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('payments.update', $dataB['payment']), [
            'amount_paid' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertForbidden();
        $this->actingAs($ownerA)->get(route('payments.receipt', $dataB['payment']))->assertForbidden();

        $this->actingAs($ownerA)->get(route('expenses.show', $dataB['expense']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('expenses.edit', $dataB['expense']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('expenses.update', $dataB['expense']), $this->expensePayload($dataB))
            ->assertForbidden();

        $this->actingAs($ownerA)->get(route('users.edit', $dataB['owner']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('users.update', $dataB['owner']), [
            'name' => 'Cross Org User',
            'email' => 'owner-b@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])->assertForbidden();
    }

    public function test_cross_organization_inputs_cannot_create_payment_schedules_or_expenses(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $paymentCount = Payment::count();

        $this->actingAs($ownerA)->post(route('contracts.store'), $this->contractPayload($dataB) + [
            'contract_number' => 'CROSS-SCHEDULE-001',
        ])->assertForbidden();

        $this->assertSame($paymentCount, Payment::count());

        $this->actingAs($ownerA)->post(route('expenses.store'), $this->expensePayload($dataB))
            ->assertForbidden();

        $this->assertDatabaseMissing('expenses', [
            'organization_id' => $ownerA->organization_id,
            'building_id' => $dataB['building']->id,
        ]);
    }

    public function test_index_pages_reports_users_and_activity_logs_do_not_expose_another_organization_data(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('buildings.index'))
            ->assertOk()
            ->assertDontSee($dataB['building']->name);

        $this->actingAs($ownerA)->get(route('units.index'))
            ->assertOk()
            ->assertDontSee($dataB['unit']->unit_number);

        $this->actingAs($ownerA)->get(route('tenants.index'))
            ->assertOk()
            ->assertDontSee($dataB['tenant']->full_name);

        $this->actingAs($ownerA)->get(route('contracts.index'))
            ->assertOk()
            ->assertDontSee($dataB['contract']->contract_number);

        $this->actingAs($ownerA)->get(route('payments.index'))
            ->assertOk()
            ->assertDontSee('90,000.00');

        $this->actingAs($ownerA)->get(route('expenses.index'))
            ->assertOk()
            ->assertDontSee('Security');

        $this->actingAs($ownerA)->get(route('users.index'))
            ->assertOk()
            ->assertDontSee($dataB['owner']->email);

        $this->actingAs($ownerA)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertDontSee('Organization B private log');

        $this->actingAs($ownerA)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('500.00')
            ->assertDontSee('90,000.00')
            ->assertDontSee('10,000.00')
            ->assertDontSee('80,000.00');
    }

    public function test_role_restrictions_match_current_mvp_permissions(): void
    {
        [$ownerA, $managerA, $accountantA, $caretakerA, $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->get(route('users.index'))->assertForbidden();
        $this->actingAs($managerA)->get(route('users.create'))->assertForbidden();
        $this->actingAs($managerA)->post(route('users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'role' => 'caretaker',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
        $this->actingAs($managerA)->get(route('users.edit', $ownerA))->assertForbidden();

        $this->actingAs($accountantA)->get(route('contracts.edit', $dataA['contract']))->assertForbidden();
        $this->actingAs($accountantA)->put(route('contracts.update', $dataA['contract']), $this->contractPayload($dataA))
            ->assertForbidden();

        $this->actingAs($caretakerA)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('reports.pdf', 'net-profit'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('reports.pdf', 'monthly-summary'))->assertForbidden();

        $this->actingAs($caretakerA)->get(route('payments.edit', $dataA['payment']))->assertOk();

        Storage::fake('local');

        $this->actingAs($caretakerA)->put(route('payments.update', $dataA['payment']), [
            'amount_paid' => 250,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'proof_image' => UploadedFile::fake()->createWithContent(
                'proof.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect(route('payments.show', $dataA['payment']));

        $this->assertDatabaseHas('payments', [
            'id' => $dataA['payment']->id,
            'amount_paid' => 250,
            'created_by' => $caretakerA->id,
        ]);

        $this->actingAs($caretakerA)->put(route('payments.update', $dataB['payment']), [
            'amount_paid' => 250,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    public function test_caretaker_cannot_access_or_modify_expenses(): void
    {
        [, , , $caretakerA, , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($caretakerA)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('expenses.create'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('expenses.show', $dataA['expense']))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('expenses.edit', $dataA['expense']))->assertForbidden();

        $this->actingAs($caretakerA)->post(route('expenses.store'), $this->expensePayload($dataA))
            ->assertForbidden();

        $this->actingAs($caretakerA)->put(route('expenses.update', $dataA['expense']), $this->expensePayload($dataA))
            ->assertForbidden();

        $this->actingAs($caretakerA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertForbidden();
    }

    public function test_only_owner_can_delete_own_organization_expenses(): void
    {
        [$ownerA, $managerA, $accountantA, , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $dataA['expense']->id]);

        $this->actingAs($accountantA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $dataA['expense']->id]);

        $this->actingAs($ownerA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $dataA['expense']->id]);
    }

    public function test_cross_organization_invoice_upload_update_is_denied(): void
    {
        [, $managerA, , , $dataB] = $this->createTwoOrganizationScenario();

        Storage::fake('local');

        $this->actingAs($managerA)->put(route('expenses.update', $dataB['expense']), $this->expensePayload($dataB) + [
            'invoice_image' => $this->fakePngUpload('cross-org-invoice.png'),
        ])->assertForbidden();

        $this->assertDatabaseHas('expenses', [
            'id' => $dataB['expense']->id,
            'invoice_image' => null,
        ]);
    }

    public function test_same_organization_authorized_user_can_upload_expense_invoice(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        Storage::fake('local');

        $this->actingAs($managerA)->put(route('expenses.update', $dataA['expense']), $this->expensePayload($dataA) + [
            'invoice_image' => $this->fakePngUpload('invoice.png'),
        ])->assertRedirect(route('expenses.show', $dataA['expense']));

        $this->assertDatabaseMissing('expenses', [
            'id' => $dataA['expense']->id,
            'invoice_image' => null,
        ]);
    }

    public function test_manager_can_view_create_and_update_own_organization_building(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->get(route('buildings.index'))->assertOk();
        $this->actingAs($managerA)->get(route('buildings.show', $dataA['building']))->assertOk();
        $this->actingAs($managerA)->get(route('buildings.create'))->assertOk();
        $this->actingAs($managerA)->get(route('buildings.edit', $dataA['building']))->assertOk();

        $this->actingAs($managerA)->post(route('buildings.store'), [
            'name' => 'Manager Created Building',
            'location' => 'Abu Dhabi',
            'description' => 'Created by manager',
        ])->assertRedirect();

        $created = Building::where('name', 'Manager Created Building')->firstOrFail();

        $this->assertSame($managerA->organization_id, $created->organization_id);

        $this->actingAs($managerA)->put(route('buildings.update', $dataA['building']), [
            'name' => 'Manager Updated Building',
            'location' => 'Abu Dhabi',
            'description' => 'Updated by manager',
        ])->assertRedirect(route('buildings.show', $dataA['building']));

        $this->assertDatabaseHas('buildings', [
            'id' => $dataA['building']->id,
            'name' => 'Manager Updated Building',
        ]);
    }

    public function test_manager_cannot_delete_building(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->delete(route('buildings.destroy', $dataA['building']))
            ->assertForbidden();

        $this->assertDatabaseHas('buildings', ['id' => $dataA['building']->id]);
    }

    public function test_owner_can_delete_own_organization_building(): void
    {
        [$ownerA, , , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('buildings.destroy', $dataA['building']))
            ->assertRedirect(route('buildings.index'));

        $this->assertSoftDeleted('buildings', ['id' => $dataA['building']->id]);
    }

    public function test_accountant_and_caretaker_cannot_access_building_pages(): void
    {
        [, , $accountantA, $caretakerA, , $dataA] = $this->createTwoOrganizationScenario();

        foreach ([$accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('buildings.index'))->assertForbidden();
            $this->actingAs($user)->get(route('buildings.create'))->assertForbidden();
            $this->actingAs($user)->get(route('buildings.show', $dataA['building']))->assertForbidden();
            $this->actingAs($user)->get(route('buildings.edit', $dataA['building']))->assertForbidden();
        }
    }

    public function test_cross_organization_building_delete_is_denied(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('buildings.destroy', $dataB['building']))
            ->assertForbidden();

        $this->assertDatabaseHas('buildings', ['id' => $dataB['building']->id]);
    }

    public function test_building_creation_assigns_current_users_organization(): void
    {
        [, $managerA, , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->post(route('buildings.store'), [
            'organization_id' => $dataB['building']->organization_id,
            'name' => 'Organization Assignment Check',
            'location' => 'Abu Dhabi',
        ])->assertRedirect();

        $this->assertDatabaseHas('buildings', [
            'name' => 'Organization Assignment Check',
            'organization_id' => $managerA->organization_id,
        ]);

        $this->assertDatabaseMissing('buildings', [
            'name' => 'Organization Assignment Check',
            'organization_id' => $dataB['building']->organization_id,
        ]);
    }

    private function createTwoOrganizationScenario(): array
    {
        $organizationA = Organization::create(['name' => 'Organization A']);
        $organizationB = Organization::create(['name' => 'Organization B']);

        $ownerA = $this->user($organizationA, 'owner-a@example.com', 'owner');
        $managerA = $this->user($organizationA, 'manager-a@example.com', 'manager');
        $accountantA = $this->user($organizationA, 'accountant-a@example.com', 'accountant');
        $caretakerA = $this->user($organizationA, 'caretaker-a@example.com', 'caretaker');
        $ownerB = $this->user($organizationB, 'owner-b@example.com', 'owner');

        $dataA = $this->organizationData($organizationA, $ownerA, [
            'building' => 'Org A Tower',
            'unit' => 'A-101',
            'tenant' => 'Org A Tenant',
            'contract' => 'ORG-A-001',
            'payment' => 1000,
            'paid' => 1000,
            'expense' => 500,
            'category' => 'maintenance',
        ]);

        $dataB = $this->organizationData($organizationB, $ownerB, [
            'building' => 'Org B Private Tower',
            'unit' => 'B-909',
            'tenant' => 'Org B Private Tenant',
            'contract' => 'ORG-B-999',
            'payment' => 90000,
            'paid' => 90000,
            'expense' => 10000,
            'category' => 'security',
        ]);

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

    private function organizationData(Organization $organization, User $owner, array $values): array
    {
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => $values['building'],
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $values['unit'],
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => $values['payment'],
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => $values['tenant'],
            'phone' => '0500000000',
        ]);

        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => $values['contract'],
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => $values['payment'],
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => now()->startOfMonth()->toDateString(),
            'amount_due' => $values['payment'],
            'amount_paid' => $values['paid'],
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'payment_method' => 'cash',
            'created_by' => $owner->id,
        ]);

        $expense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => $values['category'],
            'amount' => $values['expense'],
            'expense_date' => now()->toDateString(),
            'created_by' => $owner->id,
        ]);

        ActivityLog::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'action' => $organization->name === 'Organization B' ? 'private.log' : 'visible.log',
            'subject_type' => Building::class,
            'subject_id' => $building->id,
            'description' => $organization->name === 'Organization B'
                ? 'Organization B private log'
                : 'Organization A visible log',
        ]);

        return compact('building', 'unit', 'tenant', 'contract', 'payment', 'expense');
    }

    private function unitPayload(Building $building): array
    {
        return [
            'building_id' => $building->id,
            'unit_number' => 'Updated',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ];
    }

    private function contractPayload(array $data): array
    {
        return [
            'unit_id' => $data['unit']->id,
            'tenant_id' => $data['tenant']->id,
            'contract_number' => $data['contract']->contract_number,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => $data['contract']->rent_amount,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ];
    }

    private function expensePayload(array $data): array
    {
        return [
            'building_id' => $data['building']->id,
            'unit_id' => $data['unit']->id,
            'category' => 'security',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
        ];
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }
}
