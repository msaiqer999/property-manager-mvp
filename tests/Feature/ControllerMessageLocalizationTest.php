<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ControllerMessageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_login_message_is_localized_generic_and_uses_same_error_key(): void
    {
        $organization = Organization::create(['name' => 'Login Message Org']);

        $activeUser = User::create([
            'organization_id' => $organization->id,
            'name' => 'Login Message Owner',
            'email' => 'login-message-owner@example.com',
            'password' => 'password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $inactiveUser = User::create([
            'organization_id' => $organization->id,
            'name' => 'Inactive Login User',
            'email' => 'inactive-login-user@example.com',
            'password' => 'password',
            'role' => Role::Manager,
            'is_active' => false,
        ]);

        $initialUserState = $this->userStateSnapshot();

        foreach ([
            [$activeUser->email, 'wrong-password'],
            ['missing-login-user@example.com', 'wrong-password'],
            [$inactiveUser->email, 'password'],
        ] as [$email, $password]) {
            $response = $this
                ->withSession(['locale' => 'en'])
                ->from(route('login'))
                ->post(route('login'), [
                    'email' => $email,
                    'password' => $password,
                ]);

            $this->assertFailedLoginResponse(
                $response,
                'Invalid login details.',
                $email,
                $initialUserState
            );
        }

        foreach ([
            [$activeUser->email, 'wrong-password'],
            ['missing-arabic-login-user@example.com', 'wrong-password'],
            [$inactiveUser->email, 'password'],
        ] as [$email, $password]) {
            $response = $this
                ->withSession(['locale' => 'ar'])
                ->from(route('login'))
                ->post(route('login'), [
                    'email' => $email,
                    'password' => $password,
                ]);

            $this->assertFailedLoginResponse(
                $response,
                'بيانات تسجيل الدخول غير صحيحة.',
                $email,
                $initialUserState
            );
        }
    }

    public function test_password_reset_status_is_localized_generic_and_non_enumerating(): void
    {
        Notification::fake();

        $organization = Organization::create(['name' => 'Password Reset Message Org']);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'Password Reset Owner',
            'email' => 'password-reset-owner@example.com',
            'password' => 'password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $userCount = User::count();

        $englishExistingStatus = $this->passwordResetStatusFor(
            'en',
            'password-reset-owner@example.com'
        );

        $englishMissingStatus = $this->passwordResetStatusFor(
            'en',
            'missing-password-reset-user@example.com'
        );

        $this->assertSame($englishExistingStatus, $englishMissingStatus);
        $this->assertSame('If the email exists, a reset link has been sent.', $englishExistingStatus);
        $this->assertStringNotContainsString('auth.password_reset_link_sent', $englishExistingStatus);

        $arabicExistingStatus = $this->passwordResetStatusFor(
            'ar',
            'password-reset-owner@example.com'
        );

        $arabicMissingStatus = $this->passwordResetStatusFor(
            'ar',
            'missing-arabic-password-reset-user@example.com'
        );

        $this->assertSame($arabicExistingStatus, $arabicMissingStatus);
        $this->assertSame(
            'إذا كان البريد الإلكتروني مسجلًا، فقد تم إرسال رابط إعادة تعيين كلمة المرور.',
            $arabicExistingStatus
        );
        $this->assertStringNotContainsString('auth.password_reset_link_sent', $arabicExistingStatus);

        $this->assertSame($userCount, User::count());
    }

    public function test_last_active_owner_messages_are_localized_and_preserve_user_state(): void
    {
        $organization = Organization::create(['name' => 'Last Owner Message Org']);

        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Last Active Owner',
            'email' => 'last-active-owner@example.com',
            'password' => 'password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $this->assertHttpExceptionMessage(
            fn () => $this
                ->actingAs($owner)
                ->withSession(['locale' => 'en'])
                ->put(route('users.update', $owner), [
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'role' => Role::Manager->value,
                    'password' => '',
                    'password_confirmation' => '',
                    'is_active' => '1',
                ]),
            422,
            'A workspace must have at least one active owner.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'role' => Role::Owner->value,
            'is_active' => true,
        ]);

        $this->assertHttpExceptionMessage(
            fn () => $this
                ->actingAs($owner)
                ->withSession(['locale' => 'ar'])
                ->patch(route('users.deactivate', $owner)),
            422,
            'يجب أن تضم المنشأة مالكًا نشطًا واحدًا على الأقل.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'role' => Role::Owner->value,
            'is_active' => true,
        ]);
    }

    public function test_last_active_owner_behavior_and_user_authorization_remain_unchanged(): void
    {
        $singleOwnerOrganization = Organization::create(['name' => 'Single Owner Org']);
        $otherOrganization = Organization::create(['name' => 'Other Owner Org']);

        $singleOwner = User::create([
            'organization_id' => $singleOwnerOrganization->id,
            'name' => 'Single Owner',
            'email' => 'single-owner@example.com',
            'password' => 'password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        User::create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Other Organization Owner',
            'email' => 'other-organization-owner@example.com',
            'password' => 'password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $this
            ->actingAs($singleOwner)
            ->put(route('users.update', $singleOwner), [
                'name' => $singleOwner->name,
                'email' => $singleOwner->email,
                'role' => Role::Manager->value,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $singleOwner->id,
            'role' => Role::Owner->value,
            'is_active' => true,
        ]);

        $secondOwner = User::create([
            'organization_id' => $singleOwnerOrganization->id,
            'name' => 'Second Owner',
            'email' => 'second-owner@example.com',
            'password' => 'password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $this
            ->actingAs($singleOwner)
            ->put(route('users.update', $secondOwner), [
                'name' => $secondOwner->name,
                'email' => $secondOwner->email,
                'role' => Role::Manager->value,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $secondOwner->id,
            'role' => Role::Manager->value,
            'is_active' => true,
        ]);

        $targetOwner = User::create([
            'organization_id' => $singleOwnerOrganization->id,
            'name' => 'Disposable Owner',
            'email' => 'disposable-owner@example.com',
            'password' => 'password',
            'role' => Role::Owner,
            'is_active' => true,
        ]);

        $this
            ->actingAs($singleOwner)
            ->patch(route('users.deactivate', $targetOwner))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $targetOwner->id,
            'role' => Role::Owner->value,
            'is_active' => false,
        ]);

        foreach ([Role::Manager, Role::Accountant, Role::Caretaker] as $role) {
            $actor = User::create([
                'organization_id' => $singleOwnerOrganization->id,
                'name' => 'Forbidden '.$role->value,
                'email' => 'forbidden-'.$role->value.'@example.com',
                'password' => 'password',
                'role' => $role,
                'is_active' => true,
            ]);

            $this
                ->actingAs($actor)
                ->get(route('users.index'))
                ->assertForbidden();

            $this
                ->actingAs($actor)
                ->put(route('users.update', $singleOwner), [
                    'name' => $singleOwner->name,
                    'email' => $singleOwner->email,
                    'role' => Role::Manager->value,
                    'password' => '',
                    'password_confirmation' => '',
                    'is_active' => '1',
                ])
                ->assertForbidden();

            $this
                ->actingAs($actor)
                ->patch(route('users.deactivate', $singleOwner))
                ->assertForbidden();
        }
    }

    private function assertFailedLoginResponse($response, string $expectedMessage, string $submittedEmail, array $initialUserState): void
    {
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email'])
            ->assertSessionHasInput('email', $submittedEmail);

        $errors = session('errors')->get('email');
        $this->assertSame([$expectedMessage], $errors);

        $messageText = implode(' ', $errors);
        $this->assertStringNotContainsString('auth.failed', $messageText);

        $this->assertGuest();
        $this->assertSame($initialUserState, $this->userStateSnapshot());
    }

    private function passwordResetStatusFor(string $locale, string $email): string
    {
        $response = $this
            ->withSession(['locale' => $locale])
            ->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => $email,
            ]);

        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        return session('status');
    }

    private function assertHttpExceptionMessage(callable $request, int $statusCode, string $expectedMessage): void
    {
        $this->withoutExceptionHandling();

        try {
            $request();

            $this->fail('Expected HTTP exception was not thrown.');
        } catch (HttpException $exception) {
            $this->assertSame($statusCode, $exception->getStatusCode());
            $this->assertSame($expectedMessage, $exception->getMessage());
            $this->assertStringNotContainsString('users.validation.last_active_owner_required', $exception->getMessage());
        } finally {
            $this->withExceptionHandling();
        }
    }

    private function userStateSnapshot(): array
    {
        return DB::table('users')
            ->select('id', 'organization_id', 'name', 'email', 'role', 'is_active')
            ->orderBy('id')
            ->get()
            ->map(fn ($user) => (array) $user)
            ->all();
    }
}
