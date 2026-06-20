<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ValidationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_catalogs_match_installed_laravel_framework_structure_and_placeholders(): void
    {
        $vendor = include base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $english = include lang_path('en/validation.php');
        $arabic = include lang_path('ar/validation.php');

        $vendorFramework = $this->frameworkValidationCatalog($vendor);
        $englishFramework = $this->frameworkValidationCatalog($english);
        $arabicFramework = $this->frameworkValidationCatalog($arabic);

        $this->assertSame(
            $this->recursiveLeafPaths($vendorFramework),
            $this->recursiveLeafPaths($englishFramework)
        );

        $this->assertSame(
            $this->recursiveLeafPaths($vendorFramework),
            $this->recursiveLeafPaths($arabicFramework)
        );

        $this->assertSame(
            $this->recursiveLeafPaths($englishFramework),
            $this->recursiveLeafPaths($arabicFramework)
        );

        $vendorLeaves = $this->flattenLeafValues($vendorFramework);
        $englishLeaves = $this->flattenLeafValues($englishFramework);
        $arabicLeaves = $this->flattenLeafValues($arabicFramework);

        foreach ($englishLeaves as $path => $message) {
            $this->assertSame(
                $this->placeholders($vendorLeaves[$path]),
                $this->placeholders($message),
                "English placeholders differ from vendor for {$path}."
            );

            $this->assertSame(
                $this->placeholders($message),
                $this->placeholders($arabicLeaves[$path]),
                "Arabic placeholders differ from English for {$path}."
            );
        }
    }

    public function test_arabic_unique_registration_validation_uses_readable_attribute_without_creating_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $userCount = User::count();
        $organizationCount = Organization::count();

        $response = $this->withSession(['locale' => 'ar'])
            ->from(route('register'))
            ->post(route('register'), [
                'organization_name' => 'Unique Validation Organization',
                'name' => 'Unique Validation User',
                'email' => 'owner@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email']);

        $messages = $this->validationMessages();
        $messageText = implode(' ', $messages);

        $this->assertContains('قيمة حقل البريد الإلكتروني مستخدمة من قبل.', $messages);
        $this->assertStringNotContainsString('email', $messageText);
        $this->assertStringNotContainsString('validation.', $messageText);

        $this->assertSame($userCount, User::count());
        $this->assertSame($organizationCount, Organization::count());
        $this->assertDatabaseMissing('organizations', [
            'name' => 'Unique Validation Organization',
        ]);
        $this->assertDatabaseMissing('users', [
            'name' => 'Unique Validation User',
        ]);
    }

    public function test_english_registration_validation_covers_required_email_confirmed_min_max_and_old_input(): void
    {
        $this->seed(DatabaseSeeder::class);

        $userCount = User::count();
        $organizationCount = Organization::count();

        $response = $this->withSession(['locale' => 'en'])
            ->from(route('register'))
            ->post(route('register'), [
                'organization_name' => '',
                'name' => str_repeat('A', 256),
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'organization_name',
                'name',
                'email',
                'password',
            ])
            ->assertSessionHasInput('name', str_repeat('A', 256))
            ->assertSessionHasInput('email', 'not-an-email');

        $messages = $this->validationMessages();
        $messageText = implode(' ', $messages);

        $this->assertContains('The organization field is required.', $messages);
        $this->assertContains('The name field must not be greater than 255 characters.', $messages);
        $this->assertContains('The email address field must be a valid email address.', $messages);
        $this->assertContains('The password field must be at least 8 characters.', $messages);
        $this->assertContains('The password field confirmation does not match.', $messages);
        $this->assertStringNotContainsString('organization_name', $messageText);
        $this->assertStringNotContainsString('validation.', $messageText);

        $this->assertSame($userCount, User::count());
        $this->assertSame($organizationCount, Organization::count());
    }

    public function test_arabic_unit_validation_localizes_exists_required_numeric_integer_and_in_rules_without_creating_unit(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $otherBuilding = $this->otherOrganizationBuilding('Validation Other Unit Building');
        $unitCount = Unit::count();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->from(route('units.create'))
            ->post(route('units.store'), [
                'building_id' => $otherBuilding->id,
                'unit_number' => '',
                'type' => 'castle',
                'size' => 'large',
                'rooms' => 'many',
                'status' => 'leased',
                'rent_amount' => 'expensive',
                'notes' => 'This invalid unit must not be stored.',
            ]);

        $response->assertRedirect(route('units.create'))
            ->assertSessionHasErrors([
                'building_id',
                'unit_number',
                'type',
                'size',
                'rooms',
                'status',
                'rent_amount',
            ])
            ->assertSessionHasInput('notes', 'This invalid unit must not be stored.');

        $messages = $this->validationMessages();
        $messageText = implode(' ', $messages);

        $this->assertContains('قيمة حقل المبنى المحددة غير صالحة.', $messages);
        $this->assertContains('حقل رقم الوحدة مطلوب.', $messages);
        $this->assertContains('قيمة حقل النوع المحددة غير صالحة.', $messages);
        $this->assertContains('يجب أن يكون حقل المساحة رقماً.', $messages);
        $this->assertContains('يجب أن يكون حقل عدد الغرف عدداً صحيحاً.', $messages);
        $this->assertContains('قيمة حقل الحالة المحددة غير صالحة.', $messages);
        $this->assertContains('يجب أن يكون حقل مبلغ الإيجار رقماً.', $messages);
        $this->assertStringNotContainsString('building_id', $messageText);
        $this->assertStringNotContainsString('unit_number', $messageText);
        $this->assertStringNotContainsString('rent_amount', $messageText);
        $this->assertStringNotContainsString('validation.', $messageText);

        $this->assertSame($unitCount, Unit::count());
        $this->assertDatabaseMissing('units', [
            'notes' => 'This invalid unit must not be stored.',
        ]);
    }

    public function test_english_contract_validation_keeps_readable_messages_and_prevents_database_write(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $unit = Unit::whereHas('building', fn ($query) => $query->where('organization_id', $owner->organization_id))->firstOrFail();
        $contractCount = Contract::count();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->from(route('contracts.create'))
            ->post(route('contracts.store'), [
                'tenant_mode' => 'new',
                'unit_id' => $unit->id,
                'new_tenant' => [
                    'full_name' => '',
                    'email' => 'not-an-email',
                ],
                'start_date' => '2026-06-20',
                'end_date' => '2026-06-01',
                'rent_amount' => 'not-a-number',
                'payment_frequency' => 'weekly',
                'deposit_amount' => -5,
                'status' => 'unknown',
                'notes' => 'Invalid contract should not be stored.',
            ]);

        $response->assertRedirect(route('contracts.create'))
            ->assertSessionHasErrors([
                'new_tenant.full_name',
                'new_tenant.email',
                'end_date',
                'rent_amount',
                'payment_frequency',
                'deposit_amount',
                'status',
            ]);

        $messages = $this->validationMessages();
        $messageText = implode(' ', $messages);

        $this->assertContains('The end date field must be a date after start date.', $messages);
        $this->assertContains('The tenant full name field is required when tenant mode is new.', $messages);
        $this->assertContains('The tenant email field must be a valid email address.', $messages);
        $this->assertContains('The rent amount field must be a number.', $messages);
        $this->assertContains('The selected payment frequency is invalid.', $messages);
        $this->assertContains('The deposit amount field must be at least 0.', $messages);
        $this->assertContains('The selected status is invalid.', $messages);
        $this->assertStringNotContainsString('new_tenant.full_name', $messageText);
        $this->assertStringNotContainsString('validation.', $messageText);

        $this->assertSame($contractCount, Contract::count());
        $this->assertDatabaseMissing('contracts', [
            'notes' => 'Invalid contract should not be stored.',
        ]);
    }

    public function test_arabic_payment_validation_covers_max_date_in_image_and_preserves_old_input_without_update(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $payment = Payment::where('organization_id', $owner->organization_id)
            ->where('status', '!=', 'paid')
            ->firstOrFail();

        $originalAmountPaid = $payment->amount_paid;
        $originalStatus = $payment->status;
        $originalNotes = $payment->notes;

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->from(route('payments.edit', $payment))
            ->put(route('payments.update', $payment), [
                'amount_paid' => ((float) $payment->amount_due) + 1,
                'payment_date' => 'not-a-date',
                'payment_method' => 'gold',
                'proof_image' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'),
                'notes' => 'Invalid payment update should not be stored.',
            ]);

        $response->assertRedirect(route('payments.edit', $payment))
            ->assertSessionHasErrors([
                'amount_paid',
                'payment_date',
                'payment_method',
                'proof_image',
            ])
            ->assertSessionHasInput('notes', 'Invalid payment update should not be stored.');

        $messages = $this->validationMessages();

        $this->assertErrorContains($messages, 'يجب ألا تزيد قيمة حقل المبلغ المدفوع عن');
        $this->assertContains('يجب أن يكون حقل تاريخ الدفع تاريخاً صالحاً.', $messages);
        $this->assertContains('قيمة حقل طريقة الدفع المحددة غير صالحة.', $messages);
        $this->assertContains('يجب أن يكون حقل صورة الإثبات صورة.', $messages);

        $payment->refresh();

        $this->assertSame((string) $originalAmountPaid, (string) $payment->amount_paid);
        $this->assertSame($originalStatus, $payment->status);
        $this->assertSame($originalNotes, $payment->notes);
    }

    public function test_user_role_enum_validation_is_localized_without_creating_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $userCount = User::count();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Invalid Role User',
                'email' => 'invalid-role-user@example.com',
                'role' => 'super-admin',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertRedirect(route('users.create'))
            ->assertSessionHasErrors(['role']);

        $messages = $this->validationMessages();

        $this->assertContains('The selected role is invalid.', $messages);

        $this->assertSame($userCount, User::count());
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-role-user@example.com',
        ]);
    }

    public function test_unauthorized_role_receives_403_before_validation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->actingAs($caretaker)
            ->post(route('buildings.store'), [
                'name' => '',
                'location' => str_repeat('A', 300),
                'description' => 'Forbidden invalid building should not be validated.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('buildings', [
            'description' => 'Forbidden invalid building should not be validated.',
        ]);
    }

    public function test_cross_organization_building_id_remains_rejected_by_validation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $otherBuilding = $this->otherOrganizationBuilding('Validation Cross Organization Building');
        $unitCount = Unit::count();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->from(route('units.create'))
            ->post(route('units.store'), [
                'building_id' => $otherBuilding->id,
                'unit_number' => 'VALIDATION-CROSS-001',
                'type' => 'apartment',
                'size' => 50,
                'rooms' => 1,
                'status' => 'vacant',
                'rent_amount' => 1000,
                'notes' => 'Cross organization building must be rejected.',
            ]);

        $response->assertRedirect(route('units.create'))
            ->assertSessionHasErrors(['building_id']);

        $messages = $this->validationMessages();

        $this->assertContains('The selected building is invalid.', $messages);

        $this->assertSame($unitCount, Unit::count());
        $this->assertDatabaseMissing('units', [
            'unit_number' => 'VALIDATION-CROSS-001',
        ]);
    }

    private function validationMessages(): array
    {
        $errors = session('errors');

        $this->assertNotNull($errors);

        return $errors->all();
    }

    private function assertErrorContains(array $messages, string $expected): void
    {
        $this->assertTrue(
            collect($messages)->contains(fn (string $message) => str_contains($message, $expected)),
            "Failed asserting that any validation message contains [{$expected}]. Actual messages: ".implode(' | ', $messages)
        );
    }

    private function frameworkValidationCatalog(array $catalog): array
    {
        unset($catalog['custom'], $catalog['attributes']);

        return $catalog;
    }

    private function recursiveLeafPaths(array $catalog): array
    {
        $paths = array_keys($this->flattenLeafValues($catalog));

        sort($paths);

        return $paths;
    }

    private function flattenLeafValues(array $catalog, string $prefix = ''): array
    {
        $values = [];

        foreach ($catalog as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $values += $this->flattenLeafValues($value, $path);

                continue;
            }

            $values[$path] = $value;
        }

        ksort($values);

        return $values;
    }

    private function placeholders(string $message): array
    {
        preg_match_all('/:[A-Za-z_]+/', $message, $matches);

        $placeholders = $matches[0];
        sort($placeholders);

        return $placeholders;
    }

    private function otherOrganizationBuilding(string $name): Building
    {
        $organization = Organization::create(['name' => $name.' Organization']);

        User::create([
            'organization_id' => $organization->id,
            'name' => $name.' Owner',
            'email' => str($name)->slug().'-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        return Building::create([
            'organization_id' => $organization->id,
            'name' => $name,
            'location' => 'Other Organization Location',
            'description' => 'Other organization validation building.',
        ]);
    }
}
