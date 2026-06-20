<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_index_renders_english_system_text(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('tenants.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Tenants')
            ->assertSee('Add tenant')
            ->assertSee('Search tenants')
            ->assertSeeHtml('>Search</button>')
            ->assertSee('View');
    }

    public function test_tenant_index_renders_arabic_system_text_and_ltr_phone(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $tenant = $this->localizedTenant($owner, [
            'full_name' => 'Arabic Tenant Index Name',
            'phone' => '+971501234567',
            'email' => 'arabic-tenant-index@example.com',
            'id_number' => 'TEN-ID-1001',
            'nationality' => 'Emirati',
            'notes' => 'Arabic tenant index note remains content.',
            'created_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('المستأجرون')
            ->assertSee('إضافة مستأجر')
            ->assertSee('بحث في المستأجرين')
            ->assertSeeHtml('>بحث</button>')
            ->assertSee('عرض')
            ->assertSee('Arabic Tenant Index Name')
            ->assertSeeHtml('<bdi dir="ltr">+971501234567</bdi>');

        $freshTenant = $tenant->fresh();
        $this->assertSame('Arabic Tenant Index Name', $freshTenant->full_name);
        $this->assertSame('+971501234567', $freshTenant->phone);
    }

    public function test_tenant_form_renders_arabic_labels_and_preserves_stored_values(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $tenant = $this->localizedTenant($owner, [
            'full_name' => 'Tenant Localization Form Name',
            'phone' => '+971509998888',
            'email' => 'tenant-localization-form@example.com',
            'id_number' => 'TEN-FORM-2002',
            'nationality' => 'Jordanian',
            'notes' => 'Tenant localization form note stays unchanged.',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.create'))
            ->assertOk()
            ->assertSee('إضافة مستأجر')
            ->assertSee('اسم المستأجر')
            ->assertSee('رقم الهاتف')
            ->assertSee('البريد الإلكتروني')
            ->assertSee('رقم الهوية')
            ->assertSee('الجنسية')
            ->assertSee('ملاحظات')
            ->assertSee('حفظ');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.edit', $tenant))
            ->assertOk()
            ->assertSee('تعديل المستأجر')
            ->assertSee('اسم المستأجر')
            ->assertSee('رقم الهاتف')
            ->assertSee('البريد الإلكتروني')
            ->assertSee('رقم الهوية')
            ->assertSee('الجنسية')
            ->assertSee('ملاحظات')
            ->assertSee('value="Tenant Localization Form Name"', false)
            ->assertSee('value="+971509998888"', false)
            ->assertSee('value="tenant-localization-form@example.com"', false)
            ->assertSee('value="TEN-FORM-2002"', false)
            ->assertSee('value="Jordanian"', false)
            ->assertSee('Tenant localization form note stays unchanged.');

        $freshTenant = $tenant->fresh();
        $this->assertSame('Tenant Localization Form Name', $freshTenant->full_name);
        $this->assertSame('+971509998888', $freshTenant->phone);
        $this->assertSame('tenant-localization-form@example.com', $freshTenant->email);
        $this->assertSame('TEN-FORM-2002', $freshTenant->id_number);
        $this->assertSame('Jordanian', $freshTenant->nationality);
        $this->assertSame('Tenant localization form note stays unchanged.', $freshTenant->notes);
    }

    public function test_tenant_show_renders_arabic_interface_bidi_values_and_translated_contract_statuses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $tenant = $this->localizedTenant($owner, [
            'full_name' => 'Arabic Tenant Show Name',
            'phone' => '+971501112222',
            'email' => 'arabic-tenant-show@example.com',
            'id_number' => 'TEN-SHOW-3003',
            'nationality' => 'Lebanese',
            'notes' => 'Tenant show note remains stored content.',
        ]);

        $activeContract = $this->tenantContract($owner, $tenant, [
            'contract_number' => 'TENANT-CONTRACT-ACTIVE-404',
            'status' => 'active',
            'rent' => 4567.89,
        ]);
        $expiredContract = $this->tenantContract($owner, $tenant, [
            'contract_number' => 'TENANT-CONTRACT-EXPIRED-405',
            'status' => 'expired',
            'rent' => 5678.90,
        ]);
        $terminatedContract = $this->tenantContract($owner, $tenant, [
            'contract_number' => 'TENANT-CONTRACT-TERMINATED-406',
            'status' => 'terminated',
            'rent' => 6789.01,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertSee('تعديل')
            ->assertSee('العقود')
            ->assertSee('Arabic Tenant Show Name')
            ->assertSeeHtml('<bdi dir="ltr">+971501112222</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">arabic-tenant-show@example.com</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">TEN-SHOW-3003</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">TENANT-CONTRACT-ACTIVE-404</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">TENANT-CONTRACT-EXPIRED-405</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">TENANT-CONTRACT-TERMINATED-406</bdi>')
            ->assertSee('نشط')
            ->assertSee('منتهي')
            ->assertSee('تم إنهاؤه')
            ->assertSee('عرض')
            ->assertDontSee('>active</td>', false)
            ->assertDontSee('>expired</td>', false)
            ->assertDontSee('>terminated</td>', false);

        $this->assertSame('TENANT-CONTRACT-ACTIVE-404', $activeContract->fresh()->contract_number);
        $this->assertSame('active', $activeContract->fresh()->status);
        $this->assertSame('TENANT-CONTRACT-EXPIRED-405', $expiredContract->fresh()->contract_number);
        $this->assertSame('expired', $expiredContract->fresh()->status);
        $this->assertSame('TENANT-CONTRACT-TERMINATED-406', $terminatedContract->fresh()->contract_number);
        $this->assertSame('terminated', $terminatedContract->fresh()->status);
        $this->assertSame('Arabic Tenant Show Name', $tenant->fresh()->full_name);
    }

    public function test_tenant_routes_authorization_and_organization_isolation_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $tenant = Tenant::where('organization_id', $owner->organization_id)->firstOrFail();
        $ownerDisposable = $this->localizedTenant($owner, [
            'full_name' => 'Owner Disposable Tenant For Delete',
            'phone' => '0507000001',
            'email' => 'owner-disposable-tenant@example.com',
            'id_number' => 'OWNER-DEL-1',
            'nationality' => 'UAE',
            'notes' => 'Owner disposable tenant.',
        ]);
        $managerDisposable = $this->localizedTenant($owner, [
            'full_name' => 'Manager Disposable Tenant For Delete',
            'phone' => '0507000002',
            'email' => 'manager-disposable-tenant@example.com',
            'id_number' => 'MANAGER-DEL-1',
            'nationality' => 'UAE',
            'notes' => 'Manager disposable tenant.',
        ]);
        $otherTenant = $this->otherOrganizationTenant();

        $this->assertSame('/tenants', route('tenants.index', absolute: false));
        $this->assertSame('/tenants/create', route('tenants.create', absolute: false));
        $this->assertSame("/tenants/{$tenant->id}", route('tenants.show', $tenant, absolute: false));
        $this->assertSame("/tenants/{$tenant->id}/edit", route('tenants.edit', $tenant, absolute: false));

        $this->actingAs($owner)->get(route('tenants.index'))
            ->assertOk()
            ->assertDontSee($otherTenant->full_name);
        $this->actingAs($owner)->get(route('tenants.show', $tenant))->assertOk();
        $this->actingAs($owner)->get(route('tenants.create'))->assertOk();
        $this->actingAs($owner)->put(route('tenants.update', $tenant), $this->tenantPayload([
            'full_name' => 'Owner Updated Tenant Localization Unique',
            'email' => 'owner-updated-tenant-localization@example.com',
        ]))->assertRedirect(route('tenants.show', $tenant));
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'organization_id' => $owner->organization_id,
            'full_name' => 'Owner Updated Tenant Localization Unique',
            'email' => 'owner-updated-tenant-localization@example.com',
        ]);
        $this->actingAs($owner)->delete(route('tenants.destroy', $ownerDisposable))
            ->assertRedirect(route('tenants.index'));
        $this->assertDatabaseMissing('tenants', ['id' => $ownerDisposable->id]);

        $this->actingAs($manager)->get(route('tenants.index'))->assertOk();
        $this->actingAs($manager)->get(route('tenants.show', $tenant))->assertOk();
        $this->actingAs($manager)->get(route('tenants.create'))->assertOk();
        $this->actingAs($manager)->get(route('tenants.edit', $tenant))->assertOk();
        $this->actingAs($manager)->put(route('tenants.update', $tenant), $this->tenantPayload([
            'full_name' => 'Manager Updated Tenant Localization Unique',
            'email' => 'manager-updated-tenant-localization@example.com',
        ]))->assertRedirect(route('tenants.show', $tenant));
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'organization_id' => $owner->organization_id,
            'full_name' => 'Manager Updated Tenant Localization Unique',
            'email' => 'manager-updated-tenant-localization@example.com',
        ]);
        $this->actingAs($manager)->delete(route('tenants.destroy', $managerDisposable))->assertForbidden();
        $this->assertDatabaseHas('tenants', ['id' => $managerDisposable->id]);

        $this->actingAs($accountant)->get(route('tenants.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('tenants.create'))->assertForbidden();
        $this->actingAs($accountant)->get(route('tenants.show', $tenant))->assertForbidden();
        $this->actingAs($accountant)->get(route('tenants.edit', $tenant))->assertForbidden();

        $this->actingAs($caretaker)->get(route('tenants.index'))->assertForbidden();
        $this->actingAs($caretaker)->get(route('tenants.create'))->assertForbidden();
        $this->actingAs($caretaker)->get(route('tenants.show', $tenant))->assertForbidden();
        $this->actingAs($caretaker)->get(route('tenants.edit', $tenant))->assertForbidden();

        $this->actingAs($owner)->get(route('tenants.show', $otherTenant))->assertForbidden();
        $this->actingAs($owner)->get(route('tenants.edit', $otherTenant))->assertForbidden();
        $this->actingAs($owner)->put(route('tenants.update', $otherTenant), $this->tenantPayload([
            'full_name' => 'Cross Organization Tenant Update Attempt',
        ]))->assertForbidden();
        $this->actingAs($owner)->delete(route('tenants.destroy', $otherTenant))->assertForbidden();
        $this->assertDatabaseHas('tenants', [
            'id' => $otherTenant->id,
            'full_name' => 'Other Organization Tenant Localization Name',
        ]);
    }

    public function test_tenant_create_and_update_keep_current_users_organization(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $tenant = Tenant::where('organization_id', $manager->organization_id)->firstOrFail();
        $otherTenant = $this->otherOrganizationTenant();

        $this->actingAs($manager)->post(route('tenants.store'), [
            'organization_id' => $otherTenant->organization_id,
            'full_name' => 'Forced Organization Tenant Localization Unique',
            'phone' => '0508000001',
            'email' => 'forced-organization-tenant-localization@example.com',
            'id_number' => 'FORCED-TENANT-1',
            'nationality' => 'UAE',
            'notes' => 'Forced organization tenant note.',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'email' => 'forced-organization-tenant-localization@example.com',
            'organization_id' => $manager->organization_id,
        ]);

        $this->assertDatabaseMissing('tenants', [
            'email' => 'forced-organization-tenant-localization@example.com',
            'organization_id' => $otherTenant->organization_id,
        ]);

        $this->actingAs($manager)->put(route('tenants.update', $tenant), $this->tenantPayload([
            'organization_id' => $otherTenant->organization_id,
            'full_name' => 'Update Ignores Organization Tenant Localization Unique',
            'email' => 'update-ignores-organization-tenant@example.com',
        ]))->assertRedirect(route('tenants.show', $tenant));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'email' => 'update-ignores-organization-tenant@example.com',
            'organization_id' => $manager->organization_id,
        ]);

        $this->assertDatabaseMissing('tenants', [
            'id' => $tenant->id,
            'organization_id' => $otherTenant->organization_id,
        ]);
    }

    private function localizedTenant(User $owner, array $values): Tenant
    {
        $tenant = Tenant::create([
            'organization_id' => $owner->organization_id,
            'full_name' => $values['full_name'],
            'phone' => $values['phone'],
            'email' => $values['email'],
            'id_number' => $values['id_number'],
            'nationality' => $values['nationality'],
            'notes' => $values['notes'],
        ]);

        if (isset($values['created_at'])) {
            $createdAt = $values['created_at'];

            $tenant->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();

            $tenant->refresh();
        }

        return $tenant;
    }

    private function tenantContract(User $owner, Tenant $tenant, array $values): Contract
    {
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Tenant Localization Contract Building '.$values['contract_number'],
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'TENANT-UNIT-'.$values['contract_number'],
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => $values['rent'],
        ]);

        return Contract::create([
            'organization_id' => $owner->organization_id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => $values['contract_number'],
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => $values['rent'],
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => $values['status'],
        ]);
    }

    private function otherOrganizationTenant(): Tenant
    {
        $organization = Organization::create(['name' => 'Tenant Localization Other Organization']);

        return Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Other Organization Tenant Localization Name',
            'phone' => '0509990000',
            'email' => 'other-tenant-localization@example.com',
            'id_number' => 'OTHER-TENANT-1',
            'nationality' => 'UAE',
            'notes' => 'Other organization tenant note.',
        ]);
    }

    private function tenantPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Updated Tenant',
            'phone' => '0501234567',
            'email' => 'updated-tenant@example.com',
            'id_number' => 'UPDATED-TENANT-1',
            'nationality' => 'UAE',
            'notes' => 'Updated tenant note.',
        ], $overrides);
    }
}
