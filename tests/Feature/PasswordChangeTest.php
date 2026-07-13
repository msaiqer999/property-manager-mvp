<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_requires_authentication(): void
    {
        $this->get(route('password.change'))->assertRedirect(route('login'));

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));
    }

    public function test_password_change_page_renders_english_and_arabic_ui(): void
    {
        $user = $this->user('owner-password-page@example.com');

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee(__('app.auth.change_password'))
            ->assertSee(__('app.auth.current_password'))
            ->assertSee(__('app.auth.new_password'))
            ->assertSee(__('app.auth.confirm_new_password'))
            ->assertDontSee('app.auth.');

        app()->setLocale('ar');

        $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.auth.change_password'))
            ->assertSee(__('app.auth.current_password'))
            ->assertSee(__('app.auth.update_password'))
            ->assertDontSee('app.auth.');
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = $this->user('owner-wrong-current@example.com');

        $this->actingAs($user)
            ->from(route('password.change'))
            ->put(route('password.update'), [
                'current_password' => 'wrong-current-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('password.change'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
    }

    public function test_password_change_rejects_invalid_or_unconfirmed_new_password(): void
    {
        $user = $this->user('owner-invalid-new@example.com');

        $this->actingAs($user)
            ->from(route('password.change'))
            ->put(route('password.update'), [
                'current_password' => 'original-password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect(route('password.change'))
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->from(route('password.change'))
            ->put(route('password.update'), [
                'current_password' => 'original-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password-123',
            ])
            ->assertRedirect(route('password.change'))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
    }

    public function test_password_change_updates_only_authenticated_user_and_rotates_remember_token(): void
    {
        $user = $this->user('owner-change-password@example.com', 'owner', 'Primary Org', 'original-token');
        $otherUser = $this->user('other-owner-change-password@example.com', 'owner', 'Other Org', 'other-token');

        $this->assertSame('original-token', $user->fresh()->remember_token);
        $this->assertSame('other-token', $otherUser->fresh()->remember_token);

        $this->actingAs($user)
            ->from(route('password.change'))
            ->put(route('password.update'), [
                'current_password' => 'original-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('password.change'))
            ->assertSessionHas('status', __('app.auth.password_changed'));

        $user->refresh();
        $otherUser->refresh();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertFalse(Hash::check('original-password', $user->password));
        $this->assertNotSame('original-token', $user->remember_token);
        $this->assertSame(Role::Owner, $user->role);
        $this->assertSame('Primary Org', $user->organization->name);

        $this->assertTrue(Hash::check('original-password', $otherUser->password));
        $this->assertSame('other-token', $otherUser->remember_token);
        $this->assertSame(Role::Owner, $otherUser->role);
        $this->assertSame('Other Org', $otherUser->organization->name);
    }

    public function test_password_change_uses_arabic_validation_and_does_not_flash_password_values(): void
    {
        $user = $this->user('owner-arabic-password@example.com');
        app()->setLocale('ar');

        $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->from(route('password.change'))
            ->put(route('password.update'), [
                'current_password' => 'wrong-current-secret',
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password-123',
            ])
            ->assertRedirect(route('password.change'))
            ->assertSessionHasErrors(['current_password', 'password']);

        $errors = session('errors');
        $this->assertSame(__('validation.current_password'), $errors->get('current_password')[0]);

        $oldInput = session()->getOldInput();
        $this->assertArrayNotHasKey('current_password', $oldInput);
        $this->assertArrayNotHasKey('password', $oldInput);
        $this->assertArrayNotHasKey('password_confirmation', $oldInput);
        $this->assertStringNotContainsString('wrong-current-secret', json_encode($oldInput, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('new-password-123', json_encode($oldInput, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('different-password-123', json_encode($oldInput, JSON_THROW_ON_ERROR));
    }

    private function user(string $email, string $role = 'owner', string $organizationName = 'Password Change Org', ?string $rememberToken = null): User
    {
        $organization = Organization::create(['name' => $organizationName]);

        $user = User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' Password User',
            'email' => $email,
            'password' => 'original-password',
            'role' => $role,
        ]);

        if ($rememberToken !== null) {
            $user->forceFill(['remember_token' => $rememberToken])->save();
        }

        return $user;
    }
}
