<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_index_renders_english_system_text(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('units.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Units')
            ->assertSee('Add unit')
            ->assertSee('All buildings')
            ->assertSee('All statuses')
            ->assertSee('Filter')
            ->assertSee('Unit')
            ->assertSee('Building')
            ->assertSee('Status')
            ->assertSee('Rent')
            ->assertSee('View');
    }

    public function test_unit_index_renders_arabic_system_text_and_translated_statuses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $this->localizedUnit($owner, [
            'building' => 'Arabic Unit Vacant Building',
            'unit' => 'AR-UNIT-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent' => 4321.09,
            'created_at' => now()->addDays(3),
        ]);
        $this->localizedUnit($owner, [
            'building' => 'Arabic Unit Rented Building',
            'unit' => 'AR-UNIT-202',
            'type' => 'warehouse',
            'status' => 'rented',
            'rent' => 8765.43,
            'created_at' => now()->addDays(2),
        ]);
        $this->localizedUnit($owner, [
            'building' => 'Arabic Unit Maintenance Building',
            'unit' => 'AR-UNIT-303',
            'type' => 'office',
            'status' => 'maintenance',
            'rent' => 2468.10,
            'created_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('units.index'));

        $response->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('الوحدات')
            ->assertSee('إضافة وحدة')
            ->assertSee('جميع المباني')
            ->assertSee('جميع الحالات')
            ->assertSee('تصفية')
            ->assertSee('الوحدة')
            ->assertSee('المبنى')
            ->assertSee('الحالة')
            ->assertSee('قيمة الإيجار')
            ->assertSee('شاغرة')
            ->assertSee('مؤجرة')
            ->assertSee('قيد الصيانة')
            ->assertSeeHtml('<bdi dir="ltr">AR-UNIT-101</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">4,321.09</bdi>')
            ->assertDontSee('>vacant</option>', false)
            ->assertDontSee('>rented</option>', false)
            ->assertDontSee('>maintenance</option>', false)
            ->assertDontSee('>vacant</span>', false)
            ->assertDontSee('>rented</span>', false)
            ->assertDontSee('>maintenance</span>', false);
    }

    public function test_unit_form_translates_types_and_statuses_while_preserving_internal_option_values(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('units.create'));

        $response->assertOk()
            ->assertSee('إضافة وحدة')
            ->assertSee('المبنى')
            ->assertSee('رقم الوحدة')
            ->assertSee('النوع')
            ->assertSee('الحالة')
            ->assertSee('المساحة')
            ->assertSee('عدد الغرف')
            ->assertSee('قيمة الإيجار')
            ->assertSee('ملاحظات')
            ->assertSee('حفظ')
            ->assertSee('شقة')
            ->assertSee('محل')
            ->assertSee('مكتب')
            ->assertSee('مستودع')
            ->assertSee('فيلا')
            ->assertSee('شاليه')
            ->assertSee('أخرى')
            ->assertSee('شاغرة')
            ->assertSee('مؤجرة')
            ->assertSee('قيد الصيانة');

        foreach (['apartment', 'shop', 'office', 'warehouse', 'villa', 'chalet', 'other'] as $type) {
            $response->assertSee('value="'.$type.'"', false)
                ->assertDontSee('>'.$type.'</option>', false);
        }

        foreach (['vacant', 'rented', 'maintenance'] as $status) {
            $response->assertSee('value="'.$status.'"', false)
                ->assertDontSee('>'.$status.'</option>', false);
        }
    }

    public function test_unit_show_page_uses_ltr_isolation_for_unit_number_and_rent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $unit = $this->localizedUnit($owner, [
            'building' => 'Unit Show Localization Building',
            'unit' => 'RTL-UNIT-909',
            'type' => 'warehouse',
            'status' => 'maintenance',
            'rent' => 9876.54,
            'size' => 125.5,
            'rooms' => 3,
            'notes' => 'Unit show notes remain visible.',
        ]);

        app()->setLocale('ar');
        $buildingLabel = __('units.labels.building');
        $statusLabel = __('units.labels.status');
        $typeLabel = __('units.labels.type');
        $rentLabel = __('units.labels.rent');
        $sizeLabel = __('units.fields.size').':';
        $roomsLabel = __('units.fields.rooms').':';
        $notesLabel = __('units.fields.notes').':';
        $maintenanceStatus = __('units.statuses.maintenance');
        $warehouseType = __('units.types.warehouse');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee(__('units.fields.unit'))
            ->assertSeeHtml('<bdi dir="ltr">RTL-UNIT-909</bdi>')
            ->assertSee($buildingLabel)
            ->assertSee('Unit Show Localization Building')
            ->assertSee($statusLabel)
            ->assertSee($maintenanceStatus)
            ->assertSee($typeLabel)
            ->assertSee($warehouseType)
            ->assertSee($rentLabel)
            ->assertSee($sizeLabel)
            ->assertSee($roomsLabel)
            ->assertSee($notesLabel)
            ->assertSeeHtml('<bdi dir="ltr">9,876.54</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">125.50</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">3</bdi>')
            ->assertSee('Unit show notes remain visible.');

        $freshUnit = $unit->fresh()->load('building');
        $this->assertSame('RTL-UNIT-909', $freshUnit->unit_number);
        $this->assertSame('warehouse', $freshUnit->type);
        $this->assertSame('maintenance', $freshUnit->status);
        $this->assertSame('9876.54', number_format((float) $freshUnit->rent_amount, 2, '.', ''));
        $this->assertSame('125.50', number_format((float) $freshUnit->size, 2, '.', ''));
        $this->assertSame(3, $freshUnit->rooms);
        $this->assertSame('Unit show notes remain visible.', $freshUnit->notes);
    }

    public function test_unit_routes_authorization_and_organization_isolation_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $unit = Unit::with('building')->firstOrFail();
        $otherUnit = $this->otherOrganizationUnit();

        $this->assertSame('/units', route('units.index', absolute: false));
        $this->assertSame('/units/create', route('units.create', absolute: false));
        $this->assertSame("/units/{$unit->id}", route('units.show', $unit, absolute: false));
        $this->assertSame("/units/{$unit->id}/edit", route('units.edit', $unit, absolute: false));

        $this->actingAs($owner)->get(route('units.index'))
            ->assertOk()
            ->assertDontSee($otherUnit->unit_number);
        $this->actingAs($owner)->get(route('units.show', $otherUnit))->assertForbidden();
        $this->actingAs($owner)->get(route('units.edit', $otherUnit))->assertForbidden();
        $this->actingAs($owner)->put(route('units.update', $otherUnit), $this->unitPayload($otherUnit->building))->assertForbidden();

        $this->actingAs($manager)->get(route('units.index'))->assertOk();
        $this->actingAs($manager)->get(route('units.show', $unit))->assertOk();
        $this->actingAs($manager)->get(route('units.create'))->assertOk();
        $this->actingAs($manager)->get(route('units.edit', $unit))->assertOk();
        $this->actingAs($manager)->delete(route('units.destroy', $unit))->assertForbidden();

        $this->actingAs($accountant)->get(route('units.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('units.create'))->assertForbidden();
        $this->actingAs($accountant)->get(route('units.show', $unit))->assertForbidden();
        $this->actingAs($accountant)->get(route('units.edit', $unit))->assertForbidden();

        $this->actingAs($caretaker)->get(route('units.index'))->assertForbidden();
        $this->actingAs($caretaker)->get(route('units.create'))->assertForbidden();
        $this->actingAs($caretaker)->get(route('units.show', $unit))->assertForbidden();
        $this->actingAs($caretaker)->get(route('units.edit', $unit))->assertForbidden();
    }

    private function localizedUnit(User $owner, array $values): Unit
    {
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => $values['building'],
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $values['unit'],
            'type' => $values['type'],
            'status' => $values['status'],
            'rent_amount' => $values['rent'],
            'size' => $values['size'] ?? null,
            'rooms' => $values['rooms'] ?? null,
            'notes' => $values['notes'] ?? null,
        ]);

        if (isset($values['created_at'])) {
            $createdAt = $values['created_at'];

            $unit->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();

            $unit->refresh();
        }

        return $unit->load('building');
    }

    private function otherOrganizationUnit(): Unit
    {
        $organization = Organization::create(['name' => 'Unit Localization Other Organization']);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Other Unit Building',
            'location' => 'Dubai',
        ]);

        return Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'OTHER-UNIT-404',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 999999,
        ])->load('building');
    }

    private function unitPayload(Building $building): array
    {
        return [
            'building_id' => $building->id,
            'unit_number' => 'Updated Unit',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ];
    }
}
