<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_throttling_remains_active_while_successful_login_still_works(): void
    {
        $user = $this->user('owner@example.com');

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        auth()->logout();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), ['email' => 'missing@example.com', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login'), ['email' => 'missing@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_password_reset_email_requests_are_throttled_without_account_enumeration(): void
    {
        $this->user('owner@example.com');

        $existingStatus = $this->post(route('password.email'), ['email' => 'owner@example.com'])
            ->assertRedirect()
            ->getSession()
            ->get('status');

        $missingStatus = $this->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertRedirect()
            ->getSession()
            ->get('status');

        $this->assertSame($existingStatus, $missingStatus);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('password.email'), ['email' => 'missing@example.com'])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertTooManyRequests();
    }

    public function test_password_reset_submissions_are_throttled_and_successful_reset_still_works(): void
    {
        $user = $this->user('owner@example.com');
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword!123',
            'password_confirmation' => 'NewPassword!123',
        ])->assertRedirect(route('login', absolute: false));

        $invalidPayload = [
            'token' => 'invalid-token-context',
            'email' => $user->email,
            'password' => 'AnotherPassword!123',
            'password_confirmation' => 'AnotherPassword!123',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.store'), $invalidPayload)->assertSessionHasErrors('email');
        }

        $this->post(route('password.store'), $invalidPayload)
            ->assertTooManyRequests();
    }

    private function user(string $email): User
    {
        $organization = Organization::create(['name' => 'Throttle Org']);

        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Throttle Owner',
            'email' => $email,
            'password' => 'password',
            'role' => 'owner',
        ]);
    }
}
