<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_index_renders_english_system_text_roles_and_statuses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $inactiveUser = $this->localizedUser($owner, [
            'name' => 'English Inactive User',
            'email' => 'english-inactive-user@example.com',
            'role' => 'caretaker',
            'is_active' => false,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Users')
            ->assertSee('Invite user')
            ->assertSeeHtml('>Owner</span>')
            ->assertSeeHtml('>Manager</span>')
            ->assertSeeHtml('>Accountant</span>')
            ->assertSeeHtml('>Caretaker</span>')
            ->assertSeeHtml('>Active</span>')
            ->assertSeeHtml('>Inactive</span>')
            ->assertSee('Edit')
            ->assertSee('Deactivate')
            ->assertSee('Reactivate')
            ->assertSee('English Inactive User')
            ->assertSeeHtml('<bdi dir="ltr">english-inactive-user@example.com</bdi>');

        $this->assertSame('caretaker', $inactiveUser->fresh()->role->value);
        $this->assertFalse($inactiveUser->fresh()->is_active);
    }

    public function test_user_index_renders_arabic_system_text_roles_statuses_and_ltr_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $inactiveUser = $this->localizedUser($owner, [
            'name' => 'Arabic Inactive User',
            'email' => 'arabic-inactive-user@example.com',
            'role' => 'caretaker',
            'is_active' => false,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('المستخدمون')
            ->assertSee('إضافة مستخدم')
            ->assertSeeHtml('>مالك</span>')
            ->assertSeeHtml('>مدير</span>')
            ->assertSeeHtml('>محاسب</span>')
            ->assertSeeHtml('>حارس</span>')
            ->assertSeeHtml('>نشط</span>')
            ->assertSeeHtml('>غير نشط</span>')
            ->assertSee('تعديل')
            ->assertSee('تعطيل')
            ->assertSee('إعادة تفعيل')
            ->assertSee('Arabic Inactive User')
            ->assertSeeHtml('<bdi dir="ltr">arabic-inactive-user@example.com</bdi>')
            ->assertDontSee('>owner</span>', false)
            ->assertDontSee('>manager</span>', false)
            ->assertDontSee('>accountant</span>', false)
            ->assertDontSee('>caretaker</span>', false);

        $this->assertSame('caretaker', $inactiveUser->fresh()->role->value);
        $this->assertFalse($inactiveUser->fresh()->is_active);
    }

    public function test_user_form_renders_arabic_labels_preserves_role_values_and_password_behavior(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $originalPassword = $manager->password;

        $createResponse = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('users.create'));

        $createResponse->assertOk()
            ->assertSee('إضافة مستخدم')
            ->assertSee('الاسم')
            ->assertSee('البريد الإلكتروني')
            ->assertSee('الدور')
            ->assertSee('كلمة المرور')
            ->assertSee('تأكيد كلمة المرور')
            ->assertSee('حفظ')
            ->assertSee('value="owner"', false)
            ->assertSee('value="manager"', false)
            ->assertSee('value="accountant"', false)
            ->assertSee('value="caretaker"', false)
            ->assertSeeHtml('>مالك</option>')
            ->assertSeeHtml('>مدير</option>')
            ->assertSeeHtml('>محاسب</option>')
            ->assertSeeHtml('>حارس</option>')
            ->assertDontSee('>owner</option>', false)
            ->assertDontSee('>manager</option>', false)
            ->assertDontSee('>accountant</option>', false)
            ->assertDontSee('>caretaker</option>', false);

        $this->assertMatchesRegularExpression('/<input[^>]*name="password"[^>]*required[^>]*>/i', $createResponse->getContent());
        $this->assertMatchesRegularExpression('/<input[^>]*name="password_confirmation"[^>]*required[^>]*>/i', $createResponse->getContent());

        $editResponse = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('users.edit', $manager));

        $editResponse->assertOk()
            ->assertSee('تعديل المستخدم')
            ->assertSee('value="'.$manager->name.'"', false)
            ->assertSee('value="'.$manager->email.'"', false)
            ->assertSee('value="manager"', false)
            ->assertSeeHtml('>مدير</option>')
            ->assertDontSee($manager->password);

        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="password"[^>]*required[^>]*>/i', $editResponse->getContent());
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="password_confirmation"[^>]*required[^>]*>/i', $editResponse->getContent());

        $this->actingAs($owner)->put(route('users.update', $manager), [
            'name' => 'Password Optional Manager',
            'email' => 'password-optional-manager@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.index'));

        $manager->refresh();
        $this->assertSame('Password Optional Manager', $manager->name);
        $this->assertSame('password-optional-manager@example.com', $manager->email);
        $this->assertSame('manager', $manager->role->value);
        $this->assertSame($originalPassword, $manager->password);
    }

    public function test_user_routes_authorization_and_organization_isolation_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $otherOwner = $this->otherOrganizationOwner();

        $this->assertSame('/users', route('users.index', absolute: false));
        $this->assertSame('/users/create', route('users.create', absolute: false));
        $this->assertSame("/users/{$manager->id}/edit", route('users.edit', $manager, absolute: false));
        $this->assertSame("/users/{$manager->id}/deactivate", route('users.deactivate', $manager, absolute: false));
        $this->assertSame("/users/{$manager->id}/reactivate", route('users.reactivate', $manager, absolute: false));

        $this->actingAs($owner)->get(route('users.index'))
            ->assertOk()
            ->assertSee($manager->email)
            ->assertDontSee($otherOwner->email);
        $this->actingAs($owner)->get(route('users.create'))->assertOk();
        $this->actingAs($owner)->get(route('users.edit', $manager))->assertOk();

        $this->actingAs($owner)->post(route('users.store'), [
            'name' => 'Owner Created Localization User',
            'email' => 'owner-created-localization-user@example.com',
            'role' => 'accountant',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'owner-created-localization-user@example.com',
            'role' => 'accountant',
            'organization_id' => $owner->organization_id,
        ]);

        $this->actingAs($owner)->put(route('users.update', $manager), [
            'name' => 'Owner Updated Localization Manager',
            'email' => 'owner-updated-localization-manager@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'name' => 'Owner Updated Localization Manager',
            'email' => 'owner-updated-localization-manager@example.com',
            'role' => 'manager',
            'organization_id' => $owner->organization_id,
        ]);

        $this->actingAs($owner)->patch(route('users.deactivate', $manager))
            ->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'organization_id' => $owner->organization_id,
            'is_active' => false,
        ]);

        $this->actingAs($owner)->patch(route('users.reactivate', $manager))
            ->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'organization_id' => $owner->organization_id,
            'is_active' => true,
        ]);

        foreach ([$manager, $accountant, $caretaker] as $user) {
            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('users.create'))->assertForbidden();
            $this->actingAs($user)->post(route('users.store'), [
                'name' => 'Blocked User Localization',
                'email' => "blocked-user-localization-{$user->id}@example.com",
                'role' => 'caretaker',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertForbidden();
            $this->actingAs($user)->get(route('users.edit', $owner))->assertForbidden();
            $this->actingAs($user)->put(route('users.update', $owner), [
                'name' => 'Blocked Owner Update',
                'email' => "blocked-owner-update-{$user->id}@example.com",
                'role' => 'owner',
                'password' => '',
                'password_confirmation' => '',
            ])->assertForbidden();
            $this->actingAs($user)->patch(route('users.deactivate', $owner))->assertForbidden();
            $this->actingAs($user)->patch(route('users.reactivate', $owner))->assertForbidden();
        }

        $this->actingAs($owner)->get(route('users.edit', $otherOwner))->assertForbidden();
        $this->actingAs($owner)->put(route('users.update', $otherOwner), [
            'name' => 'Cross Organization User Localization Update',
            'email' => 'cross-organization-user-localization-update@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])->assertForbidden();
        $this->actingAs($owner)->patch(route('users.deactivate', $otherOwner))->assertForbidden();
        $this->actingAs($owner)->patch(route('users.reactivate', $otherOwner))->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $otherOwner->id,
            'email' => 'other-user-localization-owner@example.com',
            'organization_id' => $otherOwner->organization_id,
        ]);
    }

    public function test_user_create_update_self_and_privilege_protections_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $otherOwner = $this->otherOrganizationOwner();

        $this->actingAs($owner)->post(route('users.store'), [
            'organization_id' => $otherOwner->organization_id,
            'name' => 'Forced Organization User Localization',
            'email' => 'forced-organization-user-localization@example.com',
            'role' => 'owner',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $createdOwner = User::where('email', 'forced-organization-user-localization@example.com')->firstOrFail();
        $this->assertSame($owner->organization_id, $createdOwner->organization_id);
        $this->assertSame('owner', $createdOwner->role->value);
        $this->assertTrue(Hash::check('password', $createdOwner->password));

        $this->assertDatabaseMissing('users', [
            'email' => 'forced-organization-user-localization@example.com',
            'organization_id' => $otherOwner->organization_id,
        ]);

        $this->actingAs($owner)->put(route('users.update', $manager), [
            'organization_id' => $otherOwner->organization_id,
            'name' => 'Organization Change Localization Attempt',
            'email' => 'organization-change-localization-attempt@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'email' => 'organization-change-localization-attempt@example.com',
            'organization_id' => $owner->organization_id,
        ]);
        $this->assertDatabaseMissing('users', [
            'id' => $manager->id,
            'organization_id' => $otherOwner->organization_id,
        ]);

        $singleOwnerOrganization = Organization::create(['name' => 'Single Owner User Localization Organization']);
        $singleOwner = User::create([
            'organization_id' => $singleOwnerOrganization->id,
            'name' => 'Single Owner Localization',
            'email' => 'single-owner-localization@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $this->actingAs($singleOwner)
            ->patch(route('users.deactivate', $singleOwner))
            ->assertStatus(422);

        $this->actingAs($singleOwner)
            ->put(route('users.update', $singleOwner), [
                'name' => $singleOwner->name,
                'email' => $singleOwner->email,
                'role' => 'manager',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $singleOwner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    private function localizedUser(User $owner, array $values): User
    {
        return User::create([
            'organization_id' => $owner->organization_id,
            'name' => $values['name'],
            'email' => $values['email'],
            'password' => $values['password'] ?? 'password',
            'role' => $values['role'],
            'is_active' => $values['is_active'] ?? true,
        ]);
    }

    private function otherOrganizationOwner(): User
    {
        $organization = Organization::create(['name' => 'User Localization Other Organization']);

        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Other User Localization Owner',
            'email' => 'other-user-localization-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
    }
}
