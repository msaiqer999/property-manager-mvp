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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_organization_direct_urls_remain_inaccessible(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $other = $this->createOtherOrganizationRecords();

        $this->actingAs($owner)->get(route('buildings.show', $other['building']))->assertForbidden();
        $this->actingAs($owner)->get(route('units.show', $other['unit']))->assertForbidden();
        $this->actingAs($owner)->get(route('tenants.show', $other['tenant']))->assertForbidden();
        $this->actingAs($owner)->get(route('contracts.show', $other['contract']))->assertForbidden();
        $this->actingAs($owner)->get(route('payments.show', $other['payment']))->assertForbidden();
        $this->actingAs($owner)->get(route('expenses.show', $other['expense']))->assertForbidden();
        $this->actingAs($owner)->get(route('users.edit', $other['owner']))->assertForbidden();
    }

    public function test_restricted_roles_cannot_update_or_delete_protected_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $building = Building::firstOrFail();
        $contract = Contract::firstOrFail();
        $expense = Expense::firstOrFail();

        $this->actingAs($manager)->delete(route('buildings.destroy', $building))->assertForbidden();
        $this->actingAs($accountant)->get(route('contracts.edit', $contract))->assertForbidden();
        $this->actingAs($caretaker)->get(route('expenses.edit', $expense))->assertForbidden();

        $this->assertDatabaseHas('buildings', ['id' => $building->id]);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_authorized_roles_retain_their_current_model_access(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $building = Building::firstOrFail();
        $unit = Unit::firstOrFail();
        $tenant = Tenant::firstOrFail();
        $contract = Contract::firstOrFail();
        $expense = Expense::firstOrFail();

        $this->actingAs($manager)->get(route('buildings.edit', $building))->assertOk();
        $this->actingAs($manager)->get(route('units.edit', $unit))->assertOk();
        $this->actingAs($manager)->get(route('tenants.edit', $tenant))->assertOk();
        $this->actingAs($manager)->get(route('contracts.edit', $contract))->assertOk();
        $this->actingAs($manager)->get(route('expenses.edit', $expense))->assertOk();
        $this->actingAs($accountant)->get(route('payments.index'))->assertOk();
        $this->actingAs($owner)->get(route('users.index'))->assertOk();
    }

    public function test_caretaker_payment_permissions_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $payment = Payment::where('status', '!=', 'paid')->firstOrFail();

        $this->actingAs($caretaker)->get(route('payments.edit', $payment))->assertOk();

        $this->actingAs($caretaker)
            ->put(route('payments.update', $payment), [
                'amount_paid' => 100,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'notes' => 'Recorded by caretaker policy test.',
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount_paid' => 100,
            'created_by' => $caretaker->id,
        ]);
    }

    public function test_users_and_activity_logs_remain_owner_only(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->actingAs($owner)->get(route('users.index'))->assertOk();
        $this->actingAs($owner)->get(route('activity-logs.index'))->assertOk();

        foreach ([$manager, $accountant, $caretaker] as $user) {
            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('activity-logs.index'))->assertForbidden();
        }
    }

    private function createOtherOrganizationRecords(): array
    {
        $organization = Organization::create(['name' => 'Policy Other Organization']);

        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Policy Other Owner',
            'email' => 'policy-other-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Policy Other Building',
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'POLICY-B-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 5000,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Policy Other Tenant',
            'phone' => '0500000099',
        ]);

        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'POLICY-OTHER-001',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => now()->toDateString(),
            'amount_due' => 5000,
            'amount_paid' => 0,
            'status' => 'pending',
        ]);

        $expense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => 250,
            'expense_date' => now()->toDateString(),
            'created_by' => $owner->id,
        ]);

        ActivityLog::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'action' => 'policy.other',
            'subject_type' => Building::class,
            'subject_id' => $building->id,
            'description' => 'Policy other organization log.',
        ]);

        return compact('owner', 'building', 'unit', 'tenant', 'contract', 'payment', 'expense');
    }
}
