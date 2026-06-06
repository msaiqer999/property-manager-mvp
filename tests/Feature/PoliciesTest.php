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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Building $building;
    protected Unit $unit;
    protected Tenant $tenant;
    protected Contract $contract;
    protected Payment $payment;
    protected Expense $expense;

    protected User $owner;
    protected User $manager;
    protected User $accountant;
    protected User $caretaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'Owner', 'guard_name' => 'web']);
        Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        Role::create(['name' => 'Accountant', 'guard_name' => 'web']);
        Role::create(['name' => 'Caretaker', 'guard_name' => 'web']);

        // Create organization
        $this->org = Organization::create(['name' => 'Test Org']);

        // Create users
        $this->owner = User::factory()->create(['organization_id' => $this->org->id])->assignRole('Owner');
        $this->manager = User::factory()->create(['organization_id' => $this->org->id])->assignRole('Manager');
        $this->accountant = User::factory()->create(['organization_id' => $this->org->id])->assignRole('Accountant');
        $this->caretaker = User::factory()->create(['organization_id' => $this->org->id])->assignRole('Caretaker');

        // Create building
        $this->building = Building::create([
            'organization_id' => $this->org->id,
            'name' => 'Test Building',
            'address' => '123 Main St',
        ]);

        // Create unit
        $this->unit = Unit::create([
            'building_id' => $this->building->id,
            'unit_number' => '101',
            'status' => 'vacant',
        ]);

        // Create tenant
        $this->tenant = Tenant::create([
            'organization_id' => $this->org->id,
            'name' => 'John Doe',
            'phone' => '1234567890',
            'email' => 'tenant@test.com',
        ]);

        // Create contract
        $this->contract = Contract::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'monthly_rent' => 1000,
        ]);

        // Create payment
        $this->payment = Payment::create([
            'contract_id' => $this->contract->id,
            'scheduled_date' => now(),
            'amount' => 1000,
            'status' => 'pending',
        ]);

        // Create expense
        $this->expense = Expense::create([
            'building_id' => $this->building->id,
            'description' => 'Maintenance',
            'amount' => 500,
            'date' => now(),
        ]);
    }

    public function test_owner_can_create_building()
    {
        $this->assertTrue($this->owner->can('create', Building::class));
    }

    public function test_manager_can_create_building()
    {
        $this->assertTrue($this->manager->can('create', Building::class));
    }

    public function test_accountant_cannot_create_building()
    {
        $this->assertFalse($this->accountant->can('create', Building::class));
    }

    public function test_caretaker_cannot_create_building()
    {
        $this->assertFalse($this->caretaker->can('create', Building::class));
    }

    public function test_owner_can_delete_building()
    {
        $this->assertTrue($this->owner->can('delete', $this->building));
    }

    public function test_manager_cannot_delete_building()
    {
        $this->assertFalse($this->manager->can('delete', $this->building));
    }

    public function test_accountant_cannot_edit_contract()
    {
        $this->assertFalse($this->accountant->can('update', $this->contract));
    }

    public function test_accountant_can_view_contract()
    {
        $this->assertTrue($this->accountant->can('view', $this->contract));
    }

    public function test_accountant_can_export_contract_pdf()
    {
        $this->assertTrue($this->accountant->can('exportPdf', $this->contract));
    }

    public function test_manager_can_edit_contract()
    {
        $this->assertTrue($this->manager->can('update', $this->contract));
    }

    public function test_caretaker_can_record_payment()
    {
        $this->assertTrue($this->caretaker->can('create', Payment::class));
    }

    public function test_caretaker_can_upload_payment_proof()
    {
        $this->assertTrue($this->caretaker->can('uploadProof', $this->payment));
    }

    public function test_caretaker_cannot_delete_payment()
    {
        $this->assertFalse($this->caretaker->can('delete', $this->payment));
    }

    public function test_caretaker_can_export_payment_receipt()
    {
        $this->assertTrue($this->caretaker->can('exportReceipt', $this->payment));
    }

    public function test_owner_can_view_reports()
    {
        $this->assertTrue($this->owner->can('viewAny', \App\Models\Report::class));
    }

    public function test_manager_can_view_reports()
    {
        $this->assertTrue($this->manager->can('viewAny', \App\Models\Report::class));
    }

    public function test_accountant_can_view_reports()
    {
        $this->assertTrue($this->accountant->can('viewAny', \App\Models\Report::class));
    }

    public function test_caretaker_cannot_view_reports()
    {
        $this->assertFalse($this->caretaker->can('viewAny', \App\Models\Report::class));
    }

    public function test_only_owner_can_manage_users()
    {
        $this->assertTrue($this->owner->can('viewAny', User::class));
        $this->assertFalse($this->manager->can('viewAny', User::class));
        $this->assertFalse($this->accountant->can('viewAny', User::class));
        $this->assertFalse($this->caretaker->can('viewAny', User::class));
    }

    public function test_user_from_other_organization_cannot_view_building()
    {
        $otherOrg = Organization::create(['name' => 'Other Org']);
        $otherUser = User::factory()->create(['organization_id' => $otherOrg->id])->assignRole('Owner');

        $this->assertFalse($otherUser->can('view', $this->building));
    }

    public function test_user_from_other_organization_cannot_edit_unit()
    {
        $otherOrg = Organization::create(['name' => 'Other Org']);
        $otherUser = User::factory()->create(['organization_id' => $otherOrg->id])->assignRole('Manager');

        $this->assertFalse($otherUser->can('update', $this->unit));
    }

    public function test_user_from_other_organization_cannot_delete_contract()
    {
        $otherOrg = Organization::create(['name' => 'Other Org']);
        $otherUser = User::factory()->create(['organization_id' => $otherOrg->id])->assignRole('Owner');

        $this->assertFalse($otherUser->can('delete', $this->contract));
    }

    public function test_owner_can_delete_expense()
    {
        $this->assertTrue($this->owner->can('delete', $this->expense));
    }

    public function test_manager_cannot_delete_expense()
    {
        $this->assertFalse($this->manager->can('delete', $this->expense));
    }

    public function test_caretaker_cannot_create_expense()
    {
        $this->assertFalse($this->caretaker->can('create', Expense::class));
    }
}
