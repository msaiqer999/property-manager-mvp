<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\PaymentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractOccupancyProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_overlapping_active_contracts_for_same_unit(): void
    {
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $contractCount = Contract::count();
        $paymentCount = Payment::count();

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'start_date' => '2026-06-01',
                'end_date' => '2026-07-01',
            ]))
            ->assertSessionHasErrors([
                'unit_id' => __('contracts.validation.overlap'),
            ]);

        $this->assertSame($contractCount, Contract::count());
        $this->assertSame($paymentCount, Payment::count());
    }

    public function test_direct_manipulated_overlapping_request_is_rejected_server_side(): void
    {
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'unit_id' => $data['unit']->id,
                'start_date' => '2026-02-01',
                'end_date' => '2026-03-01',
            ]))
            ->assertSessionHasErrors('unit_id');
    }

    public function test_can_create_future_non_overlapping_active_contract_for_same_unit(): void
    {
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'start_date' => '2027-01-01',
                'end_date' => '2027-12-31',
            ]))
            ->assertRedirect();

        $this->assertSame(2, Contract::where('unit_id', $data['unit']->id)->count());
    }

    public function test_contract_beginning_day_after_existing_contract_ends_is_allowed(): void
    {
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'start_date' => '2026-04-01',
                'end_date' => '2026-12-31',
            ]))
            ->assertRedirect();
    }

    public function test_contract_beginning_on_existing_end_date_is_rejected(): void
    {
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'start_date' => '2026-03-31',
                'end_date' => '2026-12-31',
            ]))
            ->assertSessionHasErrors('unit_id');
    }

    public function test_expired_and_terminated_contracts_do_not_block_new_contract(): void
    {
        [$owner, $data] = $this->scenario();
        $this->contract($data, ['status' => 'expired']);
        $this->contract($data, [
            'unit_id' => $data['secondUnit']->id,
            'status' => 'terminated',
        ]);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data))
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'unit_id' => $data['secondUnit']->id,
            ]))
            ->assertRedirect();
    }

    public function test_contracts_in_another_organization_do_not_affect_or_leak_availability(): void
    {
        [$owner, $data, $otherData] = $this->scenario();
        $this->contract($otherData, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->actingAs($owner)
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertSee($data['unit']->unit_number)
            ->assertDontSee($otherData['unit']->unit_number);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]))
            ->assertRedirect();
    }

    public function test_create_contract_tenant_selector_lists_only_tenants_in_current_organization(): void
    {
        [$owner, $data, $otherData] = $this->scenario();
        $activeTenantName = $this->unicode('\u0645\u0633\u062a\u0623\u062c\u0631 \u0627\u062e\u062a\u0628\u0627\u0631 \u0627\u0644\u0645\u0631\u062d\u0644\u0629 \u0627\u0644\u0623\u0648\u0644\u0649');
        $otherTenantName = $this->unicode('\u0645\u0633\u062a\u0623\u062c\u0631 \u0645\u0646\u0638\u0645\u0629 \u0623\u062e\u0631\u0649 \u0644\u0627 \u064a\u0638\u0647\u0631');
        $selectTenantLabel = $this->unicode('\u0627\u062e\u062a\u0631 \u0627\u0644\u0645\u0633\u062a\u0623\u062c\u0631');

        $activeTenant = Tenant::create([
            'organization_id' => $data['organization']->id,
            'full_name' => $activeTenantName,
        ]);
        Tenant::create([
            'organization_id' => $otherData['organization']->id,
            'full_name' => $otherTenantName,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('Select tenant')
            ->assertSee('name="tenant_id"', false)
            ->assertSee('value="'.$activeTenant->id.'"', false)
            ->assertSeeText($activeTenantName)
            ->assertDontSeeText($otherTenantName);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertSeeText($selectTenantLabel)
            ->assertSee('value="'.$activeTenant->id.'"', false)
            ->assertSeeText($activeTenantName)
            ->assertDontSeeText($otherTenantName);
    }

    public function test_tenant_and_unit_cannot_be_changed_after_contract_creation(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data);

        $this->actingAs($owner)
            ->put(route('contracts.update', $contract), $this->contractPayload($data, [
                'tenant_id' => $data['secondTenant']->id,
            ]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('contracts.update', $contract), $this->contractPayload($data, [
                'unit_id' => $data['secondUnit']->id,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
        ]);
    }

    public function test_edit_form_shows_tenant_and_unit_as_read_only(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data);

        $this->actingAs($owner)
            ->get(route('contracts.edit', $contract))
            ->assertOk()
            ->assertSee('Tenant is locked after contract creation.')
            ->assertSee('Unit is locked after contract creation.')
            ->assertDontSee('name="tenant_id"', false)
            ->assertDontSee('name="unit_id"', false);
    }

    public function test_schedule_changing_fields_can_be_corrected_before_payments_are_recorded(): void
    {
        [$owner, $data] = $this->scenario();

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'rent_amount' => 1000,
            ]))
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();

        $this->actingAs($owner)
            ->put(route('contracts.update', $contract), $this->updatePayload($contract, [
                'rent_amount' => 1200,
            ]))
            ->assertRedirect(route('contracts.show', $contract));

        $this->assertSame(['1200.00', '1200.00', '1200.00'], $contract->payments()->orderBy('due_date')->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all());
    }

    public function test_schedule_changing_fields_are_blocked_after_payment_is_recorded(): void
    {
        [$owner, $data] = $this->scenario();

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data))
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();
        $contract->payments()->firstOrFail()->update([
            'amount_paid' => 100,
            'payment_date' => '2026-01-05',
        ]);

        $this->actingAs($owner)
            ->put(route('contracts.update', $contract), $this->updatePayload($contract, [
                'rent_amount' => 1500,
            ]))
            ->assertSessionHasErrors([
                'start_date' => 'Contract payment terms cannot be changed after a payment has been recorded.',
            ]);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'rent_amount' => 1000,
        ]);
    }

    public function test_notes_deposit_and_expired_status_remain_editable_after_payment_is_recorded(): void
    {
        [$owner, $data] = $this->scenario();

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data))
            ->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();
        $contract->payments()->firstOrFail()->update([
            'amount_paid' => 100,
            'payment_date' => '2026-01-05',
        ]);

        $this->actingAs($owner)
            ->put(route('contracts.update', $contract), $this->updatePayload($contract, [
                'deposit_amount' => 900,
                'notes' => 'Updated without schedule rewrite',
                'status' => 'expired',
            ]))
            ->assertRedirect(route('contracts.show', $contract));

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'deposit_amount' => 900,
            'notes' => 'Updated without schedule rewrite',
            'status' => 'expired',
        ]);
    }

    public function test_active_contract_can_transition_to_expired_but_not_terminated_through_generic_update(): void
    {
        [$owner, $data] = $this->scenario();
        $expired = $this->contract($data);
        $terminated = $this->contract($data, ['unit_id' => $data['secondUnit']->id]);

        $this->actingAs($owner)
            ->put(route('contracts.update', $expired), $this->updatePayload($expired, ['status' => 'expired']))
            ->assertRedirect(route('contracts.show', $expired));

        $this->actingAs($owner)
            ->put(route('contracts.update', $terminated), $this->updatePayload($terminated, ['status' => 'terminated']))
            ->assertSessionHasErrors('status');

        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('active', $terminated->fresh()->status);
    }

    public function test_terminal_contracts_cannot_be_reactivated_or_switched(): void
    {
        [$owner, $data] = $this->scenario();
        $expired = $this->contract($data, ['status' => 'expired']);
        $terminated = $this->contract($data, ['unit_id' => $data['secondUnit']->id, 'status' => 'terminated']);

        $this->actingAs($owner)
            ->put(route('contracts.update', $expired), $this->updatePayload($expired, ['status' => 'active']))
            ->assertSessionHasErrors('status');

        $this->actingAs($owner)
            ->put(route('contracts.update', $terminated), $this->updatePayload($terminated, ['status' => 'expired']))
            ->assertStatus(422);
    }

    public function test_active_contract_marks_unit_rented_even_when_start_date_is_future(): void
    {
        [$owner, $data] = $this->scenario();

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'start_date' => now()->subMonth()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'unit_id' => $data['unit']->id,
            'status' => 'active',
        ]);
        $this->assertSame('rented', $data['unit']->fresh()->status);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'unit_id' => $data['secondUnit']->id,
                'start_date' => now()->addYear()->toDateString(),
                'end_date' => now()->addYear()->addMonth()->toDateString(),
            ]))
            ->assertRedirect();

        $this->assertSame('rented', $data['secondUnit']->fresh()->status);
    }

    public function test_ending_contract_makes_unit_vacant_unless_another_active_contract_exists(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data, [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        PaymentSchedule::createFor($contract);
        $data['unit']->update(['status' => 'rented']);

        $this->actingAs($owner)
            ->patch(route('contracts.terminate', $contract), ['termination_reason' => 'Ending current occupancy'])
            ->assertRedirect(route('contracts.show', $contract));

        $this->assertSame('vacant', $data['unit']->fresh()->status);

        $otherActive = $this->contract($data, [
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ]);
        $data['unit']->update(['status' => 'rented']);

        $this->actingAs($owner)
            ->put(route('contracts.update', $otherActive), $this->updatePayload($otherActive, ['status' => 'expired']))
            ->assertRedirect(route('contracts.show', $otherActive));

        $this->assertSame('vacant', $data['unit']->fresh()->status);
    }

    public function test_another_active_contract_keeps_unit_rented_and_maintenance_is_not_overwritten(): void
    {
        [$owner, $data] = $this->scenario();
        $first = $this->contract($data, [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        $this->contract($data, [
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ]);
        $data['unit']->update(['status' => 'rented']);

        $this->actingAs($owner)
            ->patch(route('contracts.terminate', $first), ['termination_reason' => 'Another active contract remains'])
            ->assertRedirect(route('contracts.show', $first));

        $this->assertSame('rented', $data['unit']->fresh()->status);

        $data['secondUnit']->update(['status' => 'maintenance']);

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'unit_id' => $data['secondUnit']->id,
                'start_date' => now()->subMonth()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
            ]))
            ->assertRedirect();

        $this->assertSame('maintenance', $data['secondUnit']->fresh()->status);
    }

    public function test_owner_and_manager_keep_permissions_while_accountant_and_caretaker_remain_blocked(): void
    {
        [$owner, $data] = $this->scenario();
        $manager = $this->user($data['organization'], 'manager@example.com', 'manager');
        $accountant = $this->user($data['organization'], 'accountant@example.com', 'accountant');
        $caretaker = $this->user($data['organization'], 'caretaker@example.com', 'caretaker');

        $this->actingAs($owner)->get(route('contracts.create'))->assertOk();
        $this->actingAs($manager)->get(route('contracts.create'))->assertOk();

        foreach ([$accountant, $caretaker] as $user) {
            $this->actingAs($user)->get(route('contracts.create'))->assertForbidden();
            $this->actingAs($user)->post(route('contracts.store'), $this->contractPayload($data))->assertForbidden();
        }
    }

    public function test_default_create_mode_hides_occupied_units_and_shows_vacant_units_only(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-11-30',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('contracts.create', ['building_id' => $data['building']->id]));

        $response->assertOk()
            ->assertDontSee('Occupied until');

        $unitOptions = $this->unitSelectOptions($response->getContent());

        $this->assertSame($data['secondUnit']->unit_number, $unitOptions[(string) $data['secondUnit']->id] ?? null);
        $this->assertArrayNotHasKey((string) $data['unit']->id, $unitOptions);
        $this->assertStringNotContainsString('Available after 2026-11-30', implode(' ', $unitOptions));

        Carbon::setTestNow();
    }

    public function test_future_contract_mode_shows_occupied_units_with_available_after_label(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('contracts.create', [
                'building_id' => $data['building']->id,
                'contract_mode' => 'future',
            ]));

        $response->assertOk()
            ->assertDontSee('Occupied until')
            ->assertDontSee('+1 more');

        $unitOptions = $this->unitSelectOptions($response->getContent());

        $this->assertSame($data['unit']->unit_number.' - Available after 2027-07-31', $unitOptions[(string) $data['unit']->id] ?? null);

        Carbon::setTestNow();
    }

    public function test_future_contract_mode_uses_latest_active_contract_end_date(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-11-30',
        ]);
        $this->contract($data, [
            'start_date' => '2026-12-31',
            'end_date' => '2027-12-31',
        ]);

        $this->actingAs($owner)
            ->get(route('contracts.create', [
                'building_id' => $data['building']->id,
                'contract_mode' => 'future',
            ]))
            ->assertOk()
            ->assertSee('<option value="'.$data['unit']->id.'"', false)
            ->assertSee($data['unit']->unit_number.' - Available after 2027-12-31')
            ->assertDontSee('Occupied until')
            ->assertDontSee('+1 more');

        Carbon::setTestNow();
    }

    public function test_future_contract_mode_does_not_use_old_more_count_label(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-11-30',
        ]);
        $this->contract($data, [
            'start_date' => '2026-12-31',
            'end_date' => '2027-12-31',
        ]);
        $this->contract($data, [
            'start_date' => '2028-01-01',
            'end_date' => '2028-12-31',
        ]);

        $this->actingAs($owner)
            ->get(route('contracts.create', [
                'building_id' => $data['building']->id,
                'contract_mode' => 'future',
            ]))
            ->assertOk()
            ->assertSee($data['unit']->unit_number.' - Available after 2028-12-31')
            ->assertDontSee('Occupied until')
            ->assertDontSee('+1 more');

        Carbon::setTestNow();
    }

    public function test_future_contract_mode_ignores_old_nearest_future_contract_summary(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);
        $this->contract($data, [
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);
        $this->contract($data, [
            'start_date' => '2028-01-01',
            'end_date' => '2028-12-31',
        ]);

        $this->actingAs($owner)
            ->get(route('contracts.create', [
                'building_id' => $data['building']->id,
                'contract_mode' => 'future',
            ]))
            ->assertOk()
            ->assertSee($data['unit']->unit_number.' - Available after 2028-12-31')
            ->assertDontSee('Available now; future contract')
            ->assertDontSee('+2 more');

        Carbon::setTestNow();
    }

    public function test_default_create_mode_shows_vacant_unit_numbers_without_availability_label(): void
    {
        [$owner, $data] = $this->scenario();

        $this->actingAs($owner)
            ->get(route('contracts.create', ['building_id' => $data['building']->id]))
            ->assertOk()
            ->assertSee('<option value="'.$data['unit']->id.'"', false)
            ->assertSee($data['unit']->unit_number)
            ->assertDontSee('Available now')
            ->assertDontSee('Occupied until');
    }

    public function test_future_contract_mode_keeps_maintenance_unit_available_after_label_clear(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data] = $this->scenario();
        $data['unit']->update(['status' => 'maintenance']);
        $this->contract($data, [
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
        ]);

        $this->actingAs($owner)
            ->get(route('contracts.create', [
                'building_id' => $data['building']->id,
                'contract_mode' => 'future',
            ]))
            ->assertOk()
            ->assertSee($data['unit']->unit_number.' - Available after 2027-07-31')
            ->assertDontSee('Maintenance; future contract');

        Carbon::setTestNow();
    }

    public function test_create_form_unit_options_never_display_other_organization_contract_dates(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data, $otherData] = $this->scenario();
        $this->contract($otherData, [
            'start_date' => '2030-01-01',
            'end_date' => '2030-12-31',
        ]);

        $this->actingAs($owner)
            ->get(route('contracts.create', ['building_id' => $data['building']->id]))
            ->assertOk()
            ->assertSee('<option value="'.$data['unit']->id.'"', false)
            ->assertSee($data['unit']->unit_number)
            ->assertDontSee('2030-01-01')
            ->assertDontSee('+1 more')
            ->assertDontSee($otherData['unit']->unit_number);

        Carbon::setTestNow();
    }

    public function test_future_contract_mode_does_not_count_expired_or_terminated_contracts(): void
    {
        Carbon::setTestNow('2026-06-17');
        [$owner, $data] = $this->scenario();
        $this->contract($data, [
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
        ]);
        $this->contract($data, [
            'start_date' => '2027-08-01',
            'end_date' => '2028-07-31',
            'status' => 'expired',
        ]);
        $this->contract($data, [
            'start_date' => '2028-08-01',
            'end_date' => '2029-07-31',
            'status' => 'terminated',
        ]);

        $this->actingAs($owner)
            ->get(route('contracts.create', [
                'building_id' => $data['building']->id,
                'contract_mode' => 'future',
            ]))
            ->assertOk()
            ->assertSee($data['unit']->unit_number.' - Available after 2027-07-31')
            ->assertDontSee('+1 more')
            ->assertDontSee('2027-08-01')
            ->assertDontSee('2028-08-01');

        Carbon::setTestNow();
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Org A']);
        $otherOrganization = Organization::create(['name' => 'Org B']);
        $owner = $this->user($organization, 'owner@example.com', 'owner');

        return [
            $owner,
            $this->organizationData($organization, 'A'),
            $this->organizationData($otherOrganization, 'B'),
        ];
    }

    private function organizationData(Organization $organization, string $prefix): array
    {
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => "Building {$prefix}",
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => "{$prefix}-101",
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);

        $secondUnit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => "{$prefix}-102",
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1200,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Tenant {$prefix}",
        ]);

        $secondTenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Second Tenant {$prefix}",
        ]);

        return compact('organization', 'building', 'unit', 'secondUnit', 'tenant', 'secondTenant');
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function contract(array $data, array $overrides = []): Contract
    {
        return Contract::create(array_merge([
            'organization_id' => $data['organization']->id,
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'contract_number' => 'TEST-'.uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
            'notes' => 'Existing contract',
        ], $overrides));
    }

    private function contractPayload(array $data, array $overrides = []): array
    {
        return array_replace([
            'tenant_mode' => 'existing',
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
            'notes' => 'Test contract',
        ], $overrides);
    }

    private function updatePayload(Contract $contract, array $overrides = []): array
    {
        return array_replace([
            'start_date' => $contract->start_date->toDateString(),
            'end_date' => $contract->end_date->toDateString(),
            'rent_amount' => $contract->rent_amount,
            'payment_frequency' => $contract->payment_frequency,
            'deposit_amount' => $contract->deposit_amount,
            'status' => $contract->status,
            'notes' => $contract->notes,
        ], $overrides);
    }

    private function unitSelectOptions(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $options = [];
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//select[@name="unit_id"]/option') as $option) {
            $value = $option->getAttribute('value');

            if ($value === '') {
                continue;
            }

            $options[$value] = trim((string) preg_replace('/\s+/', ' ', $option->textContent));
        }

        return $options;
    }

    private function unicode(string $escaped): string
    {
        return json_decode('"'.$escaped.'"', true, flags: JSON_THROW_ON_ERROR);
    }
}
