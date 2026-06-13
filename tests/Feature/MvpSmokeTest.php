<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MvpSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_users_can_log_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['owner@example.com', 'manager@example.com', 'accountant@example.com', 'caretaker@example.com'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'password'])
                ->assertRedirect('/');

            $this->post('/logout');
        }
    }

    public function test_owner_can_open_main_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        foreach (['/', '/buildings', '/units', '/tenants', '/contracts', '/payments', '/expenses', '/reports', '/users', '/activity-logs'] as $path) {
            $this->actingAs($owner)->get($path)->assertOk();
        }
    }

    public function test_role_restrictions_are_enforced(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $contract = Contract::firstOrFail();

        $this->actingAs($manager)->get('/users')->assertForbidden();
        $this->actingAs($accountant)->get(route('contracts.edit', $contract))->assertForbidden();
        $this->actingAs($caretaker)->get('/reports')->assertForbidden();
    }

    public function test_organization_isolation_blocks_cross_organization_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $otherOrganization = Organization::create(['name' => 'Other Org']);
        $otherOwner = User::create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Other Owner',
            'email' => 'other@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $building = Building::firstOrFail();
        $unit = Unit::firstOrFail();
        $tenant = Tenant::firstOrFail();
        $contract = Contract::firstOrFail();
        $payment = Payment::firstOrFail();

        $this->actingAs($otherOwner)->get(route('buildings.show', $building))->assertForbidden();
        $this->actingAs($otherOwner)->get(route('units.show', $unit))->assertForbidden();
        $this->actingAs($otherOwner)->get(route('tenants.show', $tenant))->assertForbidden();
        $this->actingAs($otherOwner)->get(route('contracts.show', $contract))->assertForbidden();
        $this->actingAs($otherOwner)->get(route('payments.show', $payment))->assertForbidden();
    }
}
