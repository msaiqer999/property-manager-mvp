<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_index_renders_english_system_text(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Buildings')
            ->assertSee('Add building')
            ->assertSee('Name')
            ->assertSee('Location')
            ->assertSee('View');
    }

    public function test_building_index_renders_arabic_system_text(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('المباني')
            ->assertSee('إضافة مبنى')
            ->assertSee('اسم المبنى')
            ->assertSee('الموقع')
            ->assertSee('عرض');
    }

    public function test_building_form_renders_arabic_labels_and_preserves_stored_values(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = $this->localizedBuilding($owner, [
            'name' => 'Building Localization Tower',
            'location' => 'Saadiyat Island, Abu Dhabi',
            'description' => 'Building localization description stays unchanged.',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('buildings.create'))
            ->assertOk()
            ->assertSee('إضافة مبنى')
            ->assertSee('اسم المبنى')
            ->assertSee('الموقع')
            ->assertSee('الوصف')
            ->assertSee('حفظ');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('buildings.edit', $building))
            ->assertOk()
            ->assertSee('تعديل المبنى')
            ->assertSee('اسم المبنى')
            ->assertSee('الموقع')
            ->assertSee('الوصف')
            ->assertSee('value="Building Localization Tower"', false)
            ->assertSee('value="Saadiyat Island, Abu Dhabi"', false)
            ->assertSee('Building localization description stays unchanged.');

        $freshBuilding = $building->fresh();
        $this->assertSame('Building Localization Tower', $freshBuilding->name);
        $this->assertSame('Saadiyat Island, Abu Dhabi', $freshBuilding->location);
        $this->assertSame('Building localization description stays unchanged.', $freshBuilding->description);
    }

    public function test_building_show_renders_arabic_interface_without_translating_database_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = $this->localizedBuilding($owner, [
            'name' => 'Arabic Building Data Name',
            'location' => 'Al Reem Island, Abu Dhabi',
            'description' => 'Arabic building description remains user content.',
        ]);
        $this->localizedUnit($building, [
            'unit' => 'BLDG-UNIT-101',
            'status' => 'vacant',
            'rent' => 1234.56,
        ]);
        $this->localizedUnit($building, [
            'unit' => 'BLDG-UNIT-202',
            'status' => 'rented',
            'rent' => 2345.67,
        ]);
        $this->localizedUnit($building, [
            'unit' => 'BLDG-UNIT-303',
            'status' => 'maintenance',
            'rent' => 3456.78,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('buildings.show', $building));

        $response->assertOk()
            ->assertSee('تعديل')
            ->assertSee('الوحدات')
            ->assertSee('Arabic Building Data Name')
            ->assertSee('Al Reem Island, Abu Dhabi')
            ->assertSee('Arabic building description remains user content.')
            ->assertSee('شاغرة')
            ->assertSee('مؤجرة')
            ->assertSee('قيد الصيانة')
            ->assertSeeHtml('<bdi dir="ltr">BLDG-UNIT-101</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">1,234.56</bdi>')
            ->assertDontSee('>vacant</td>', false)
            ->assertDontSee('>rented</td>', false)
            ->assertDontSee('>maintenance</td>', false);

        $freshBuilding = $building->fresh();
        $this->assertSame('Arabic Building Data Name', $freshBuilding->name);
        $this->assertSame('Al Reem Island, Abu Dhabi', $freshBuilding->location);
        $this->assertSame('Arabic building description remains user content.', $freshBuilding->description);
    }

    public function test_building_routes_authorization_and_organization_isolation_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $building = Building::where('organization_id', $owner->organization_id)->firstOrFail();
        $ownerDisposable = $this->localizedBuilding($owner, [
            'name' => 'Owner Disposable Building For Delete',
            'location' => 'Abu Dhabi',
            'description' => 'Owner disposable building.',
        ]);
        $managerDisposable = $this->localizedBuilding($owner, [
            'name' => 'Manager Disposable Building For Delete',
            'location' => 'Abu Dhabi',
            'description' => 'Manager disposable building.',
        ]);
        $otherBuilding = $this->otherOrganizationBuilding();

        $this->assertSame('/buildings', route('buildings.index', absolute: false));
        $this->assertSame('/buildings/create', route('buildings.create', absolute: false));
        $this->assertSame("/buildings/{$building->id}", route('buildings.show', $building, absolute: false));
        $this->assertSame("/buildings/{$building->id}/edit", route('buildings.edit', $building, absolute: false));

        $this->actingAs($owner)->get(route('buildings.index'))
            ->assertOk()
            ->assertDontSee($otherBuilding->name);
        $this->actingAs($owner)->get(route('buildings.show', $building))->assertOk();
        $this->actingAs($owner)->get(route('buildings.create'))->assertOk();
        $this->actingAs($owner)->put(route('buildings.update', $building), [
            'name' => 'Owner Updated Building Localization Unique',
            'location' => 'Owner Updated Location',
            'description' => 'Owner updated building description.',
        ])->assertRedirect(route('buildings.show', $building));
        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'organization_id' => $owner->organization_id,
            'name' => 'Owner Updated Building Localization Unique',
        ]);
        $this->actingAs($owner)->delete(route('buildings.destroy', $ownerDisposable))
            ->assertRedirect(route('buildings.index'));
        $this->assertSoftDeleted('buildings', [
            'id' => $ownerDisposable->id,
            'organization_id' => $owner->organization_id,
        ]);

        $this->actingAs($manager)->get(route('buildings.index'))->assertOk();
        $this->actingAs($manager)->get(route('buildings.show', $building))->assertOk();
        $this->actingAs($manager)->get(route('buildings.create'))->assertOk();
        $this->actingAs($manager)->get(route('buildings.edit', $building))->assertOk();
        $this->actingAs($manager)->put(route('buildings.update', $building), [
            'name' => 'Manager Updated Building Localization Unique',
            'location' => 'Manager Updated Location',
            'description' => 'Manager updated building description.',
        ])->assertRedirect(route('buildings.show', $building));
        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'organization_id' => $owner->organization_id,
            'name' => 'Manager Updated Building Localization Unique',
        ]);
        $this->actingAs($manager)->delete(route('buildings.destroy', $managerDisposable))->assertForbidden();
        $this->assertDatabaseHas('buildings', [
            'id' => $managerDisposable->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($accountant)->get(route('buildings.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('buildings.create'))->assertForbidden();
        $this->actingAs($accountant)->get(route('buildings.show', $building))->assertForbidden();
        $this->actingAs($accountant)->get(route('buildings.edit', $building))->assertForbidden();

        $this->actingAs($caretaker)->get(route('buildings.index'))->assertForbidden();
        $this->actingAs($caretaker)->get(route('buildings.create'))->assertForbidden();
        $this->actingAs($caretaker)->get(route('buildings.show', $building))->assertForbidden();
        $this->actingAs($caretaker)->get(route('buildings.edit', $building))->assertForbidden();

        $this->actingAs($owner)->get(route('buildings.show', $otherBuilding))->assertForbidden();
        $this->actingAs($owner)->get(route('buildings.edit', $otherBuilding))->assertForbidden();
        $this->actingAs($owner)->put(route('buildings.update', $otherBuilding), $this->buildingPayload('Cross Org Update Attempt'))->assertForbidden();
        $this->actingAs($owner)->delete(route('buildings.destroy', $otherBuilding))->assertForbidden();
        $this->assertDatabaseHas('buildings', [
            'id' => $otherBuilding->id,
            'name' => 'Other Organization Building Localization Tower',
            'deleted_at' => null,
        ]);
    }

    public function test_building_creation_forces_current_users_organization(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $otherBuilding = $this->otherOrganizationBuilding();

        $this->actingAs($manager)->post(route('buildings.store'), [
            'organization_id' => $otherBuilding->organization_id,
            'name' => 'Forced Organization Building Localization Unique',
            'location' => 'Forced Organization Location',
            'description' => 'Forced organization description.',
        ])->assertRedirect();

        $this->assertDatabaseHas('buildings', [
            'name' => 'Forced Organization Building Localization Unique',
            'organization_id' => $manager->organization_id,
        ]);

        $this->assertDatabaseMissing('buildings', [
            'name' => 'Forced Organization Building Localization Unique',
            'organization_id' => $otherBuilding->organization_id,
        ]);
    }

    private function localizedBuilding(User $owner, array $values): Building
    {
        return Building::create([
            'organization_id' => $owner->organization_id,
            'name' => $values['name'],
            'location' => $values['location'],
            'description' => $values['description'],
        ]);
    }

    private function localizedUnit(Building $building, array $values): Unit
    {
        return Unit::create([
            'building_id' => $building->id,
            'unit_number' => $values['unit'],
            'type' => 'apartment',
            'status' => $values['status'],
            'rent_amount' => $values['rent'],
        ]);
    }

    private function otherOrganizationBuilding(): Building
    {
        $organization = Organization::create(['name' => 'Building Localization Other Organization']);

        return Building::create([
            'organization_id' => $organization->id,
            'name' => 'Other Organization Building Localization Tower',
            'location' => 'Dubai',
            'description' => 'Other organization building description.',
        ]);
    }

    private function buildingPayload(string $name = 'Updated Building'): array
    {
        return [
            'name' => $name,
            'location' => 'Abu Dhabi',
            'description' => 'Updated description.',
        ];
    }
}
