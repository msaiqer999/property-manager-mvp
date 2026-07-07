<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUnitCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_bulk_unit_creation_for_their_building(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->get(route('buildings.units.bulk.create', $building))
            ->assertOk()
            ->assertSee('Add multiple units')
            ->assertSee('Start number')
            ->assertSee('End number');
    }

    public function test_owner_can_open_general_bulk_unit_entry_page(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->get(route('units.bulk-create'))
            ->assertOk()
            ->assertSee('Add multiple units')
            ->assertSee('Choose building')
            ->assertSee($building->name)
            ->assertSee('data-bulk-unit-entry-row', false)
            ->assertSee('name="units[4][unit_number]"', false)
            ->assertSee('action="'.route('units.bulk-store').'"', false);
    }

    public function test_manager_can_open_bulk_unit_creation_for_their_building(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();
        $manager = $this->user($owner->organization, 'manager');

        $this->actingAs($manager)
            ->get(route('buildings.units.bulk.create', $building))
            ->assertOk()
            ->assertSee('Add multiple units');
    }

    public function test_manager_can_open_general_bulk_unit_entry_page(): void
    {
        [$owner] = $this->ownerWithBuilding();
        $manager = $this->user($owner->organization, 'manager');

        $this->actingAs($manager)
            ->get(route('units.bulk-create'))
            ->assertOk()
            ->assertSee('Add multiple units');
    }

    public function test_guest_cannot_open_general_bulk_unit_entry_page(): void
    {
        $this->get(route('units.bulk-create'))
            ->assertRedirect(route('login'));
    }

    public function test_preview_generates_units_from_101_to_105(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->post(route('buildings.units.bulk.preview', $building), $this->templatePayload([
                'start_number' => 101,
                'end_number' => 105,
            ]))
            ->assertOk()
            ->assertSee('Preview units')
            ->assertSee('name="units[0][unit_number]"', false)
            ->assertSee('value="101"', false)
            ->assertSee('value="105"', false);
    }

    public function test_bulk_create_form_has_preview_post_action_csrf_and_submit_button(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->get(route('buildings.units.bulk.create', $building))
            ->assertOk()
            ->assertSee('method="post"', false)
            ->assertSee('action="'.route('buildings.units.bulk.preview', $building).'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('type="submit"', false)
            ->assertSee('Generate preview');
    }

    public function test_valid_bulk_create_form_submission_returns_preview_page(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->post(route('buildings.units.bulk.preview', $building), $this->templatePayload([
                'start_number' => 301,
                'end_number' => 305,
                'rent_amount' => 50000,
                'rooms' => 2,
                'size' => 120,
                'status' => 'vacant',
            ]))
            ->assertOk()
            ->assertSee('Preview units')
            ->assertSee('value="301"', false)
            ->assertSee('value="305"', false);
    }

    public function test_invalid_bulk_create_submission_shows_visible_validation_errors(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->followingRedirects()
            ->from(route('buildings.units.bulk.create', $building))
            ->post(route('buildings.units.bulk.preview', $building), $this->templatePayload([
                'start_number' => '',
            ]))
            ->assertOk()
            ->assertSee(__('app.validation.check_fields'))
            ->assertSee('The start number field is required.');
    }

    public function test_get_preview_redirects_to_bulk_create_with_clear_message(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->get(route('buildings.units.bulk.preview.expired', $building))
            ->assertRedirect(route('buildings.units.bulk.create', $building))
            ->assertSessionHas('status', __('units.bulk.preview_expired'));
    }

    public function test_final_save_creates_units_in_the_selected_building(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->post(route('buildings.units.bulk.store', $building), [
                'units' => $this->unitRows(['101', '102', '103']),
            ])
            ->assertRedirect(route('buildings.show', $building));

        foreach (['101', '102', '103'] as $unitNumber) {
            $this->assertDatabaseHas('units', [
                'building_id' => $building->id,
                'unit_number' => $unitNumber,
                'status' => 'vacant',
            ]);
        }
    }

    public function test_user_can_edit_preview_row_before_final_save(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();
        $rows = $this->unitRows(['101', '102']);
        $rows[0]['rent_amount'] = 1750.25;
        $rows[0]['size'] = 88.5;
        $rows[0]['rooms'] = 3;

        $this->actingAs($owner)
            ->post(route('buildings.units.bulk.store', $building), ['units' => $rows])
            ->assertRedirect(route('buildings.show', $building));

        $unit = Unit::where('building_id', $building->id)
            ->where('unit_number', '101')
            ->firstOrFail();

        $this->assertSame('1750.25', number_format((float) $unit->rent_amount, 2, '.', ''));
        $this->assertSame('88.50', number_format((float) $unit->size, 2, '.', ''));
        $this->assertSame(3, $unit->rooms);
    }

    public function test_existing_unit_numbers_are_skipped_without_blocking_new_units(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();
        Unit::create([
            'building_id' => $building->id,
            'unit_number' => '101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);

        $this->actingAs($owner)
            ->from(route('buildings.units.bulk.create', $building))
            ->post(route('buildings.units.bulk.store', $building), [
                'units' => $this->unitRows(['101', '102', '103']),
            ])
            ->assertRedirect(route('buildings.show', $building))
            ->assertSessionHas('status', __('units.bulk.created_with_skips', [
                'count' => 2,
                'skipped' => '101',
            ]));

        $this->assertSame(1, Unit::where('building_id', $building->id)->where('unit_number', '101')->count());

        foreach (['102', '103'] as $unitNumber) {
            $this->assertDatabaseHas('units', [
                'building_id' => $building->id,
                'unit_number' => $unitNumber,
            ]);
        }
    }

    public function test_duplicate_unit_number_inside_request_is_rejected(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->from(route('buildings.units.bulk.create', $building))
            ->post(route('buildings.units.bulk.store', $building), [
                'units' => $this->unitRows(['101', '101']),
            ])
            ->assertRedirect(route('buildings.units.bulk.create', $building))
            ->assertSessionHasErrors('units');
    }

    public function test_general_bulk_entry_creates_valid_rows_and_ignores_empty_rows(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();
        $rows = $this->unitRows(['201', '202']);
        $rows[] = [
            'unit_number' => '',
            'type' => 'apartment',
            'rent_amount' => '',
            'rooms' => '',
            'size' => '',
            'status' => 'vacant',
            'notes' => '',
        ];

        $this->actingAs($owner)
            ->post(route('units.bulk-store'), [
                'building_id' => $building->id,
                'units' => $rows,
            ])
            ->assertRedirect(route('units.index', ['building_id' => $building->id]))
            ->assertSessionHas('status', __('units.bulk.manual_created_success'));

        $this->assertDatabaseHas('units', [
            'building_id' => $building->id,
            'unit_number' => '201',
        ]);
        $this->assertDatabaseHas('units', [
            'building_id' => $building->id,
            'unit_number' => '202',
        ]);
        $this->assertSame(2, Unit::where('building_id', $building->id)->count());
    }

    public function test_general_bulk_entry_rejects_duplicate_unit_numbers_in_same_request(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        $this->actingAs($owner)
            ->from(route('units.bulk-create'))
            ->post(route('units.bulk-store'), [
                'building_id' => $building->id,
                'units' => $this->unitRows(['301', '301']),
            ])
            ->assertRedirect(route('units.bulk-create'))
            ->assertSessionHasErrors('units');
    }

    public function test_general_bulk_entry_rejects_existing_unit_number_in_selected_building(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();
        Unit::create([
            'building_id' => $building->id,
            'unit_number' => '401',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);

        $this->actingAs($owner)
            ->from(route('units.bulk-create'))
            ->post(route('units.bulk-store'), [
                'building_id' => $building->id,
                'units' => $this->unitRows(['401', '402']),
            ])
            ->assertRedirect(route('units.bulk-create'))
            ->assertSessionHasErrors('units');

        $this->assertDatabaseMissing('units', [
            'building_id' => $building->id,
            'unit_number' => '402',
        ]);
    }

    public function test_caretaker_cannot_open_preview_or_save_bulk_units(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();
        $caretaker = $this->user($owner->organization, 'caretaker');

        $this->actingAs($caretaker)->get(route('buildings.units.bulk.create', $building))->assertForbidden();
        $this->actingAs($caretaker)->get(route('units.bulk-create'))->assertForbidden();
        $this->actingAs($caretaker)->post(route('buildings.units.bulk.preview', $building), $this->templatePayload())->assertForbidden();
        $this->actingAs($caretaker)->post(route('buildings.units.bulk.store', $building), [
            'units' => $this->unitRows(['101']),
        ])->assertForbidden();
        $this->actingAs($caretaker)->post(route('units.bulk-store'), [
            'building_id' => $building->id,
            'units' => $this->unitRows(['101']),
        ])->assertForbidden();
    }

    public function test_accountant_cannot_open_preview_or_save_bulk_units(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();
        $accountant = $this->user($owner->organization, 'accountant');

        $this->actingAs($accountant)->get(route('buildings.units.bulk.create', $building))->assertForbidden();
        $this->actingAs($accountant)->get(route('units.bulk-create'))->assertForbidden();
        $this->actingAs($accountant)->post(route('buildings.units.bulk.preview', $building), $this->templatePayload())->assertForbidden();
        $this->actingAs($accountant)->post(route('buildings.units.bulk.store', $building), [
            'units' => $this->unitRows(['101']),
        ])->assertForbidden();
        $this->actingAs($accountant)->post(route('units.bulk-store'), [
            'building_id' => $building->id,
            'units' => $this->unitRows(['101']),
        ])->assertForbidden();
    }

    public function test_user_from_another_organization_cannot_use_bulk_units_for_building(): void
    {
        [, $building] = $this->ownerWithBuilding();
        $otherOrganization = Organization::create(['name' => 'Other Bulk Organization']);
        $otherOwner = $this->user($otherOrganization, 'owner');

        $this->actingAs($otherOwner)->get(route('buildings.units.bulk.create', $building))->assertForbidden();
        $this->actingAs($otherOwner)->get(route('units.bulk-create'))->assertOk();
        $this->actingAs($otherOwner)->post(route('buildings.units.bulk.preview', $building), $this->templatePayload())->assertForbidden();
        $this->actingAs($otherOwner)->post(route('buildings.units.bulk.store', $building), [
            'units' => $this->unitRows(['101']),
        ])->assertForbidden();
        $this->actingAs($otherOwner)->post(route('units.bulk-store'), [
            'building_id' => $building->id,
            'units' => $this->unitRows(['101']),
        ])->assertForbidden();
    }

    public function test_arabic_bulk_unit_text_appears_in_arabic_locale(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        app()->setLocale('ar');
        $title = __('units.bulk.add_multiple');
        $startNumber = __('units.bulk.start_number');
        $generatePreview = __('units.bulk.generate_preview');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('buildings.units.bulk.create', $building))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee($title)
            ->assertSee($startNumber)
            ->assertSee($generatePreview);
    }

    public function test_general_bulk_unit_entry_text_appears_in_arabic_locale(): void
    {
        [$owner, $building] = $this->ownerWithBuilding();

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('units.bulk-create'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('units.bulk.manual_title'))
            ->assertSee(__('units.bulk.choose_building'))
            ->assertSee($building->name)
            ->assertSee(__('units.bulk.save_manual_units'));
    }

    private function ownerWithBuilding(): array
    {
        $organization = Organization::create(['name' => 'Bulk Unit Organization']);
        $owner = $this->user($organization, 'owner');
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Bulk Unit Building',
            'location' => 'Abu Dhabi',
            'description' => 'Bulk unit creation building.',
        ]);

        return [$owner, $building];
    }

    private function user(Organization $organization, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => "Bulk {$role}",
            'email' => "bulk-{$role}-".uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function templatePayload(array $overrides = []): array
    {
        return array_merge([
            'prefix' => '',
            'start_number' => 101,
            'end_number' => 105,
            'type' => 'apartment',
            'rent_amount' => 1200,
            'rooms' => 2,
            'size' => 75.5,
            'status' => 'vacant',
            'notes' => 'Generated from bulk preview.',
        ], $overrides);
    }

    private function unitRows(array $unitNumbers): array
    {
        return collect($unitNumbers)
            ->map(fn (string $unitNumber) => [
                'unit_number' => $unitNumber,
                'type' => 'apartment',
                'rent_amount' => 1200,
                'rooms' => 2,
                'size' => 75.5,
                'status' => 'vacant',
                'notes' => 'Generated from editable preview.',
            ])
            ->all();
    }
}
