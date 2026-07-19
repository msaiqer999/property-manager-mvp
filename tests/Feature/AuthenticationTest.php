<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\GlobalReadinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_login(): void
    {
        $organization = Organization::create(['name' => 'Test Org']);
        $user = User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');
    }

    public function test_login_form_has_icon_only_password_visibility_toggle(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('id="login-password"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-target="login-password"', false)
            ->assertSee('data-eye-open', false)
            ->assertSee('data-eye-closed', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertDontSee('>Show password</button>', false);
    }

    public function test_owner_can_register_without_entering_organization_name(): void
    {
        $this->seed(GlobalReadinessSeeder::class);

        $this->post('/register', [
            'organization_name' => '',
            'name' => 'Noura Saad',
            'email' => 'noura-register@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/');

        $user = User::where('email', 'noura-register@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->organization_id);
        $this->assertSame("Noura Saad's Property Account", $user->organization->name);
    }

    public function test_registration_form_marks_organization_optional_and_has_password_toggles(): void
    {
        $this->seed(GlobalReadinessSeeder::class);

        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('Organization or account name (optional)')
            ->assertSee('name="organization_name"', false)
            ->assertDontSee('name="organization_name" class="tap-target mt-1 w-full rounded border p-2" required', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-target="register-password"', false)
            ->assertSee('data-target="register-password-confirmation"', false)
            ->assertSee('data-eye-open', false)
            ->assertSee('data-eye-closed', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertDontSee('>Show password</button>', false);
    }
}
