<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PilotOwnerRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_password_is_reset_and_remember_token_rotates(): void
    {
        Log::spy();
        $owner = $this->user('OWNER@Example.COM', 'owner');
        $oldPasswordHash = $owner->password;
        $oldRememberToken = $owner->remember_token;
        $newPassword = 'NewOwnerPass!123';

        $this->artisan('pilot:reset-owner-password', ['email' => ' owner@example.com '])
            ->expectsQuestion('New password', $newPassword)
            ->expectsQuestion('Confirm new password', $newPassword)
            ->doesntExpectOutput($newPassword)
            ->expectsOutput('Pilot owner password reset for owner@example.com.')
            ->assertExitCode(0);

        $owner->refresh();

        $this->assertNotSame($oldPasswordHash, $owner->password);
        $this->assertNotSame($oldRememberToken, $owner->remember_token);
        $this->assertTrue(Hash::check($newPassword, $owner->password));
        $this->assertFalse(Hash::check('password', $owner->password));

        $this->post(route('login'), ['email' => 'OWNER@Example.COM', 'password' => $newPassword])
            ->assertRedirect(route('dashboard', absolute: false));
        auth()->logout();

        $this->post(route('login'), ['email' => 'OWNER@Example.COM', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        Log::shouldHaveReceived('notice')->with('pilot_owner_password_reset', [
            'user_id' => $owner->id,
            'organization_id' => $owner->organization_id,
        ]);
    }

    public function test_non_owner_and_unknown_email_are_rejected_safely(): void
    {
        $manager = $this->user('manager@example.com', 'manager');

        $this->artisan('pilot:reset-owner-password', ['email' => $manager->email])
            ->expectsOutput('Unable to reset the owner password.')
            ->assertExitCode(1);

        $this->artisan('pilot:reset-owner-password', ['email' => 'missing@example.com'])
            ->expectsOutput('Unable to reset the owner password.')
            ->assertExitCode(1);
    }

    public function test_weak_password_and_confirmation_mismatch_fail_without_leaking_secret(): void
    {
        $owner = $this->user('owner@example.com', 'owner');

        $this->artisan('pilot:reset-owner-password', ['email' => $owner->email])
            ->expectsQuestion('New password', 'weak-password')
            ->expectsQuestion('Confirm new password', 'weak-password')
            ->doesntExpectOutput('weak-password')
            ->expectsOutput('Unable to reset the owner password.')
            ->assertExitCode(1);

        $this->assertTrue(Hash::check('password', $owner->fresh()->password));

        $this->artisan('pilot:reset-owner-password', ['email' => $owner->email])
            ->expectsQuestion('New password', 'StrongPass!123')
            ->expectsQuestion('Confirm new password', 'DifferentPass!123')
            ->doesntExpectOutput('StrongPass!123')
            ->doesntExpectOutput('DifferentPass!123')
            ->expectsOutput('Unable to reset the owner password.')
            ->assertExitCode(1);

        $this->assertTrue(Hash::check('password', $owner->fresh()->password));
    }

    public function test_audit_event_contains_no_secret(): void
    {
        Log::spy();
        $owner = $this->user('owner@example.com', 'owner');
        $newPassword = 'SecretSafe!123';

        $this->artisan('pilot:reset-owner-password', ['email' => $owner->email])
            ->expectsQuestion('New password', $newPassword)
            ->expectsQuestion('Confirm new password', $newPassword)
            ->assertExitCode(0);

        Log::shouldHaveReceived('notice')->withArgs(function (string $message, array $context) use ($newPassword) {
            return $message === 'pilot_owner_password_reset'
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), $newPassword)
                && array_key_exists('user_id', $context)
                && array_key_exists('organization_id', $context);
        });
    }

    private function user(string $email, string $role): User
    {
        $organization = Organization::firstOrCreate(['name' => 'Pilot Recovery Org']);

        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'remember_token' => 'original-token',
        ]);
    }
}
