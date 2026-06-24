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

class ContractLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_index_renders_english_and_arabic_without_changing_stored_values(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $contract = Contract::with(['tenant', 'unit.building'])->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Contracts')
            ->assertSee('Contract number')
            ->assertSee('View contract')
            ->assertSee('Active')
            ->assertSee($contract->contract_number)
            ->assertSee($contract->tenant->full_name)
            ->assertSee($contract->unit->unit_number)
            ->assertSee(number_format($contract->rent_amount, 2));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('العقود')
            ->assertSee('رقم العقد')
            ->assertSee('عرض العقد')
            ->assertSee('نشط')
            ->assertSee($contract->contract_number)
            ->assertSee($contract->tenant->full_name)
            ->assertSee($contract->unit->unit_number)
            ->assertSee(number_format($contract->rent_amount, 2));

        $this->assertSame('active', $contract->fresh()->status);
    }

    public function test_create_show_edit_and_renewal_pages_render_localized_system_text_and_preserve_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $contract = Contract::with(['tenant', 'unit.building'])->firstOrFail();
        $paidPayment = $contract->payments()->firstOrFail();
        $paidPayment->update([
            'amount_paid' => $paidPayment->amount_due,
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
        ]);
        $renewalContract = Contract::with(['tenant', 'unit.building'])
            ->where('status', 'active')
            ->get()
            ->first(fn (Contract $candidate) => $candidate->isRenewalEligible());

        $this->assertNotNull($renewalContract);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('Add contract')
            ->assertSee('Select existing tenant')
            ->assertSee($contract->tenant->full_name)
            ->assertSee($contract->unit->building->name)
            ->assertSee($contract->unit->unit_number);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('إضافة عقد')
            ->assertSee('اختيار مستأجر حالي')
            ->assertSee($contract->tenant->full_name)
            ->assertSee($contract->unit->building->name)
            ->assertSee($contract->unit->unit_number);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('جدول الدفعات')
            ->assertSee('تنزيل ملف العقد PDF')
            ->assertSee($contract->contract_number)
            ->assertSee($contract->tenant->full_name)
            ->assertSee($contract->unit->unit_number)
            ->assertSee(number_format($contract->rent_amount, 2))
            ->assertSee('href="'.route('payments.show', $paidPayment).'"', false)
            ->assertDontSee('href="'.route('payments.edit', $paidPayment).'"', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.edit', $contract))
            ->assertOk()
            ->assertSee('تعديل العقد')
            ->assertSee('رقم العقد')
            ->assertSee($contract->contract_number)
            ->assertSee($contract->tenant->full_name)
            ->assertSee($contract->unit->building->name)
            ->assertSee($contract->unit->unit_number);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.create', ['renew_from' => $renewalContract->id]))
            ->assertOk()
            ->assertSee('إعداد التجديد')
            ->assertSee('لن يتم تغيير العقد الحالي')
            ->assertSee($renewalContract->tenant->full_name)
            ->assertSee($renewalContract->unit->building->name)
            ->assertSee($renewalContract->unit->unit_number);
    }

    public function test_contract_urls_authorization_organization_isolation_and_pdf_routes_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $contract = Contract::firstOrFail();
        $otherContract = $this->createOtherOrganizationContract();

        $this->assertSame('/contracts', route('contracts.index', absolute: false));
        $this->assertSame('/contracts/create', route('contracts.create', absolute: false));
        $this->assertSame("/contracts/{$contract->id}", route('contracts.show', $contract, absolute: false));
        $this->assertSame("/contracts/{$contract->id}/edit", route('contracts.edit', $contract, absolute: false));
        $this->assertSame("/contracts/{$contract->id}/pdf", route('contracts.pdf', $contract, absolute: false));

        $this->actingAs($owner)
            ->from(route('contracts.index'))
            ->post(route('locale.switch', 'ar'))
            ->assertRedirect(route('contracts.index'));

        $this->actingAs($accountant)->get(route('contracts.edit', $contract))->assertForbidden();
        $this->actingAs($owner)->get(route('contracts.show', $otherContract))->assertForbidden();
        $this->actingAs($owner)->get(route('contracts.pdf', $otherContract))->assertForbidden();
        $this->actingAs($owner)->get(route('contracts.pdf', $contract))->assertOk();
    }

    public function test_contract_validation_uses_the_active_locale(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->from(route('contracts.create'))
            ->post(route('contracts.store'), [])
            ->assertRedirect(route('contracts.create'))
            ->assertSessionHasErrors([
                'start_date' => 'حقل تاريخ البداية مطلوب.',
                'unit_id' => 'حقل الوحدة مطلوب.',
            ]);
    }

    private function createOtherOrganizationContract(): Contract
    {
        $organization = Organization::create(['name' => 'Contract Localization Other Organization']);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Contract Localization Other Building',
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'OTHER-AR-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 8500,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Contract Localization Other Tenant',
            'phone' => '0500000088',
        ]);

        return Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'OTHER-AR-CONTRACT-001',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => 8500,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 1000,
            'status' => 'active',
        ]);
    }
}
