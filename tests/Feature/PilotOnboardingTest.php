<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\GlobalReadinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class PilotOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_enabled_preserves_existing_public_registration_flow_and_localization(): void
    {
        Config::set('app.registration_enabled', true);
        $this->seed(GlobalReadinessSeeder::class);

        $this->get(route('register'))->assertOk();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('register'), false);

        $this->withSession(['locale' => 'ar'])
            ->get(route('register'))
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('app.auth.create_owner_account'));

        $response = $this->post(route('register'), [
            'organization_name' => 'Enabled Registration Org',
            'name' => 'Enabled Registration Owner',
            'email' => 'enabled-owner@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $organization = Organization::where('name', 'Enabled Registration Org')->firstOrFail();
        $user = User::where('email', 'enabled-owner@example.com')->firstOrFail();

        $this->assertSame($organization->id, $user->organization_id);
        $this->assertSame(Role::Owner, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotSame('password-123', $user->password);
        $this->assertTrue(Hash::check('password-123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_disabled_blocks_public_registration_but_keeps_login_and_password_reset_available(): void
    {
        Config::set('app.registration_enabled', false);

        $this->get(route('register'))->assertNotFound();

        $this->post(route('register'), [
            'organization_name' => 'Blocked Org',
            'name' => 'Blocked Owner',
            'email' => 'blocked-owner@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertNotFound();

        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('users', 0);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee(route('register'), false);

        $this->get(route('password.request'))->assertOk();
    }

    public function test_real_pilot_posture_uses_console_owner_creation_without_public_registration(): void
    {
        Config::set('app.registration_enabled', false);

        $this->get(route('register'))->assertNotFound();

        $this->post(route('register'), [
            'organization_name' => 'Blocked Pilot Org',
            'name' => 'Blocked Pilot Owner',
            'email' => 'blocked-pilot-owner@example.com',
            'password' => 'blocked-secret-123',
            'password_confirmation' => 'blocked-secret-123',
        ])->assertNotFound();

        $exitCode = $this->runPilotOwnerCommand([
            'Pilot Family Properties',
            'Pilot Owner',
            'pilot-secret-123',
            'pilot-secret-123',
        ], 'pilot-owner@example.com', [
            'Pilot organization and owner created successfully.',
            'Organization: Pilot Family Properties',
            'Owner email: pilot-owner@example.com',
        ], [
            'pilot-secret-123',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $organization = Organization::where('name', 'Pilot Family Properties')->firstOrFail();
        $user = User::where('email', 'pilot-owner@example.com')->firstOrFail();

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('buildings', 0);
        $this->assertDatabaseCount('units', 0);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('contracts', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('activity_logs', 0);

        $this->assertSame($organization->id, $user->organization_id);
        $this->assertSame(Role::Owner, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotSame('pilot-secret-123', $user->password);
        $this->assertTrue(Hash::check('pilot-secret-123', $user->password));

        $this->get(route('register'))->assertNotFound();

        $this->post(route('register'), [
            'organization_name' => 'Still Blocked Org',
            'name' => 'Still Blocked Owner',
            'email' => 'still-blocked@example.com',
            'password' => 'blocked-secret-456',
            'password_confirmation' => 'blocked-secret-456',
        ])->assertNotFound();

        $this->get(route('password.request'))->assertOk();

        $this->post(route('login'), [
            'email' => 'pilot-owner@example.com',
            'password' => 'pilot-secret-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_pilot_owner_command_refuses_duplicate_email_case_insensitively_before_collecting_password(): void
    {
        $organization = Organization::create(['name' => 'Existing Org']);

        $existingUser = User::create([
            'organization_id' => $organization->id,
            'name' => 'Existing Owner',
            'email' => 'Existing.Owner@Example.COM',
            'password' => 'existing-password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $exitCode = $this->runPilotOwnerCommand([
            'New Pilot Org',
            'New Pilot Owner',
        ], 'EXISTING.OWNER@example.com', [
            'A user with this email already exists.',
        ], [
            'secret',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('users', 1);

        $existingUser->refresh();

        $this->assertSame($organization->id, $existingUser->organization_id);
        $this->assertSame('Existing Owner', $existingUser->name);
        $this->assertSame('Existing.Owner@Example.COM', $existingUser->email);
        $this->assertSame(Role::Owner, $existingUser->role);
        $this->assertTrue($existingUser->is_active);
        $this->assertTrue(Hash::check('existing-password', $existingUser->password));
    }

    public function test_pilot_owner_command_refuses_duplicate_organization_name_before_collecting_password(): void
    {
        Organization::create(['name' => 'Existing Pilot Org']);

        $exitCode = $this->runPilotOwnerCommand([
            'existing pilot org',
            'Pilot Owner',
        ], 'new-owner@example.com', [
            'An organization with this name already exists.',
        ], [
            'secret',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_pilot_owner_command_creates_second_owner_in_separate_organization_without_changing_existing_records(): void
    {
        $existingOrganization = Organization::create(['name' => 'Existing Pilot Org']);
        $existingOwner = User::create([
            'organization_id' => $existingOrganization->id,
            'name' => 'Existing Pilot Owner',
            'email' => 'existing-pilot-owner@example.com',
            'password' => 'existing-password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $exitCode = $this->runPilotOwnerCommand([
            'Second Pilot Org',
            'Second Pilot Owner',
            'second-secret-123',
            'second-secret-123',
        ], 'second-owner@example.com', [
            'Pilot organization and owner created successfully.',
            'Organization: Second Pilot Org',
            'Owner email: second-owner@example.com',
        ], [
            'second-secret-123',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $secondOrganization = Organization::where('name', 'Second Pilot Org')->firstOrFail();
        $secondOwner = User::where('email', 'second-owner@example.com')->firstOrFail();

        $this->assertDatabaseCount('organizations', 2);
        $this->assertDatabaseCount('users', 2);
        $this->assertNotSame($existingOrganization->id, $secondOrganization->id);
        $this->assertSame($secondOrganization->id, $secondOwner->organization_id);
        $this->assertSame(Role::Owner, $secondOwner->role);
        $this->assertTrue($secondOwner->is_active);
        $this->assertTrue(Hash::check('second-secret-123', $secondOwner->password));

        $existingOwner->refresh();
        $this->assertSame($existingOrganization->id, $existingOwner->organization_id);
        $this->assertSame('Existing Pilot Owner', $existingOwner->name);
        $this->assertSame('existing-pilot-owner@example.com', $existingOwner->email);
        $this->assertTrue(Hash::check('existing-password', $existingOwner->password));
    }

    public function test_pilot_owner_command_validation_failure_does_not_create_records_or_print_passwords(): void
    {
        $exitCode = $this->runPilotOwnerCommand([
            'Validation Pilot Org',
            'Validation Owner',
            'short-secret',
            'different-secret',
        ], 'validation-owner@example.com', [], [
            'short-secret',
            'different-secret',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_pilot_owner_command_rolls_back_organization_when_owner_creation_fails(): void
    {
        $existingOrganization = Organization::create(['name' => 'Existing Safe Org']);
        $existingOwner = User::create([
            'organization_id' => $existingOrganization->id,
            'name' => 'Existing Safe Owner',
            'email' => 'existing-safe-owner@example.com',
            'password' => 'existing-password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        Event::listen('eloquent.creating: '.User::class, function (User $user): void {
            if ($user->email === 'rollback-owner@example.com') {
                throw new RuntimeException('Simulated owner creation failure.');
            }
        });

        try {
            $exitCode = $this->runPilotOwnerCommand([
                'Rollback Pilot Org',
                'Rollback Owner',
                'rollback-secret-123',
                'rollback-secret-123',
            ], 'rollback-owner@example.com', [
                'Unable to create the pilot owner. No records were kept.',
            ], [
                'rollback-secret-123',
            ]);

            $this->assertSame(Command::FAILURE, $exitCode);

            $this->assertDatabaseCount('organizations', 1);
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseMissing('organizations', ['name' => 'Rollback Pilot Org']);
            $this->assertDatabaseMissing('users', ['email' => 'rollback-owner@example.com']);

            $existingOwner->refresh();
            $this->assertSame($existingOrganization->id, $existingOwner->organization_id);
            $this->assertSame('Existing Safe Owner', $existingOwner->name);
            $this->assertTrue(Hash::check('existing-password', $existingOwner->password));
        } finally {
            Event::forget('eloquent.creating: '.User::class);
        }
    }

    private function runPilotOwnerCommand(
        array $inputs,
        string $email,
        array $expectedOutputSubstrings = [],
        array $unexpectedOutputSubstrings = []
    ): int {
        $pending = $this->artisan('pilot:create-owner')
            ->expectsQuestion('Organization name', $inputs[0])
            ->expectsQuestion('Owner name', $inputs[1])
            ->expectsQuestion('Owner email', $email);

        if (array_key_exists(2, $inputs)) {
            $pending
                ->expectsQuestion('Password', $inputs[2])
                ->expectsQuestion('Confirm password', $inputs[3]);
        }

        foreach ($expectedOutputSubstrings as $substring) {
            $pending->expectsOutputToContain($substring);
        }

        foreach ($unexpectedOutputSubstrings as $substring) {
            $pending->doesntExpectOutputToContain($substring);
        }

        return $pending->run();
    }
}
