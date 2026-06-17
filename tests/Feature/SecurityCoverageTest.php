<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_access_another_organizations_record_pages_or_pdfs(): void
    {
        [$ownerA, , , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('buildings.show', $dataB['building']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('buildings.edit', $dataB['building']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('buildings.update', $dataB['building']), [
            'name' => 'Cross Org Building',
        ])->assertForbidden();

        $this->actingAs($ownerA)->get(route('units.show', $dataB['unit']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('units.edit', $dataB['unit']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('units.update', $dataB['unit']), $this->unitPayload($dataB['building']))
            ->assertForbidden();

        $this->actingAs($ownerA)->get(route('tenants.show', $dataB['tenant']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('tenants.edit', $dataB['tenant']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('tenants.update', $dataB['tenant']), [
            'full_name' => 'Cross Org Tenant',
        ])->assertForbidden();

        $this->actingAs($ownerA)->get(route('contracts.show', $dataB['contract']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('contracts.edit', $dataB['contract']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('contracts.update', $dataB['contract']), $this->contractPayload($dataB))
            ->assertForbidden();
        $this->actingAs($ownerA)->get(route('contracts.pdf', $dataB['contract']))->assertForbidden();

        $this->actingAs($ownerA)->get(route('payments.show', $dataB['payment']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('payments.edit', $dataB['payment']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('payments.update', $dataB['payment']), [
            'amount_paid' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertForbidden();
        $this->actingAs($ownerA)->get(route('payments.receipt', $dataB['payment']))->assertForbidden();

        $this->actingAs($ownerA)->get(route('expenses.show', $dataB['expense']))->assertForbidden();
        $this->actingAs($ownerA)->get(route('expenses.edit', $dataB['expense']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('expenses.update', $dataB['expense']), $this->expensePayload($dataB))
            ->assertForbidden();

        $this->actingAs($ownerA)->get(route('users.edit', $dataB['owner']))->assertForbidden();
        $this->actingAs($ownerA)->put(route('users.update', $dataB['owner']), [
            'name' => 'Cross Org User',
            'email' => 'owner-b@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])->assertForbidden();
    }

    public function test_cross_organization_inputs_cannot_create_payment_schedules_or_expenses(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $paymentCount = Payment::count();

        $this->actingAs($ownerA)->post(route('contracts.store'), $this->contractPayload($dataB) + [
            'contract_number' => 'CROSS-SCHEDULE-001',
        ])->assertForbidden();

        $this->assertSame($paymentCount, Payment::count());

        $this->actingAs($ownerA)->post(route('expenses.store'), $this->expensePayload($dataB))
            ->assertForbidden();

        $this->assertDatabaseMissing('expenses', [
            'organization_id' => $ownerA->organization_id,
            'building_id' => $dataB['building']->id,
        ]);
    }

    public function test_index_pages_reports_users_and_activity_logs_do_not_expose_another_organization_data(): void
    {
        [$ownerA, , , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('buildings.index'))
            ->assertOk()
            ->assertDontSee($dataB['building']->name);

        $this->actingAs($ownerA)->get(route('units.index'))
            ->assertOk()
            ->assertDontSee($dataB['unit']->unit_number);

        $this->actingAs($ownerA)->get(route('tenants.index'))
            ->assertOk()
            ->assertDontSee($dataB['tenant']->full_name);

        $this->actingAs($ownerA)->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('Contracts connect tenants to units and generate the rent schedule.')
            ->assertSee('Contract number')
            ->assertSee('Tenant')
            ->assertSee('Unit')
            ->assertSee('Start date')
            ->assertSee('End date')
            ->assertSee('Rent')
            ->assertSee('Frequency')
            ->assertSee('Status')
            ->assertSee('Action')
            ->assertSee('View contract')
            ->assertSee($dataA['contract']->contract_number)
            ->assertSee($dataA['tenant']->full_name)
            ->assertSee($dataA['unit']->unit_number)
            ->assertDontSee($dataB['contract']->contract_number);

        $this->actingAs($ownerA)->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Payments are generated from contracts and can be recorded when rent is collected.')
            ->assertSee('Due date')
            ->assertSee('Tenant')
            ->assertSee('Unit')
            ->assertSee('Contract')
            ->assertSee('Amount')
            ->assertSee('Status')
            ->assertSee('Paid date')
            ->assertSee('Action')
            ->assertSee($dataA['tenant']->full_name)
            ->assertSee($dataA['unit']->unit_number)
            ->assertSee($dataA['contract']->contract_number)
            ->assertSee('View receipt')
            ->assertDontSee('90,000.00');

        $this->actingAs($ownerA)->get(route('expenses.index'))
            ->assertOk()
            ->assertDontSee('Security');

        $this->actingAs($ownerA)->get(route('users.index'))
            ->assertOk()
            ->assertDontSee($dataB['owner']->email);

        $this->actingAs($ownerA)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertDontSee('Organization B private log');

        $this->actingAs($ownerA)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('500.00')
            ->assertDontSee('90,000.00')
            ->assertDontSee('10,000.00')
            ->assertDontSee('80,000.00');
    }

    public function test_role_restrictions_match_current_mvp_permissions(): void
    {
        [$ownerA, $managerA, $accountantA, $caretakerA, $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->get(route('users.index'))->assertForbidden();
        $this->actingAs($managerA)->get(route('users.create'))->assertForbidden();
        $this->actingAs($managerA)->post(route('users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'role' => 'caretaker',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
        $this->actingAs($managerA)->get(route('users.edit', $ownerA))->assertForbidden();

        $this->actingAs($accountantA)->get(route('contracts.edit', $dataA['contract']))->assertForbidden();
        $this->actingAs($accountantA)->put(route('contracts.update', $dataA['contract']), $this->contractPayload($dataA))
            ->assertForbidden();

        $this->actingAs($caretakerA)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('reports.pdf', 'net-profit'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('reports.pdf', 'monthly-summary'))->assertForbidden();

        $this->actingAs($caretakerA)->get(route('payments.edit', $dataA['payment']))->assertOk();

        Storage::fake('local');

        $this->actingAs($caretakerA)->put(route('payments.update', $dataA['payment']), [
            'amount_paid' => 250,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'proof_image' => UploadedFile::fake()->createWithContent(
                'proof.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect(route('payments.show', $dataA['payment']));

        $this->assertDatabaseHas('payments', [
            'id' => $dataA['payment']->id,
            'amount_paid' => 250,
            'created_by' => $caretakerA->id,
        ]);

        $this->actingAs($caretakerA)->put(route('payments.update', $dataB['payment']), [
            'amount_paid' => 250,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    public function test_caretaker_cannot_access_or_modify_expenses(): void
    {
        [, , , $caretakerA, , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($caretakerA)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('expenses.create'))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('expenses.show', $dataA['expense']))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('expenses.edit', $dataA['expense']))->assertForbidden();

        $this->actingAs($caretakerA)->post(route('expenses.store'), $this->expensePayload($dataA))
            ->assertForbidden();

        $this->actingAs($caretakerA)->put(route('expenses.update', $dataA['expense']), $this->expensePayload($dataA))
            ->assertForbidden();

        $this->actingAs($caretakerA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertForbidden();
    }

    public function test_only_owner_can_delete_own_organization_expenses(): void
    {
        [$ownerA, $managerA, $accountantA, , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $dataA['expense']->id]);

        $this->actingAs($accountantA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $dataA['expense']->id]);

        $this->actingAs($ownerA)->delete(route('expenses.destroy', $dataA['expense']))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $dataA['expense']->id]);
    }

    public function test_cross_organization_invoice_upload_update_is_denied(): void
    {
        [, $managerA, , , $dataB] = $this->createTwoOrganizationScenario();

        Storage::fake('local');

        $this->actingAs($managerA)->put(route('expenses.update', $dataB['expense']), $this->expensePayload($dataB) + [
            'invoice_image' => $this->fakePngUpload('cross-org-invoice.png'),
        ])->assertForbidden();

        $this->assertDatabaseHas('expenses', [
            'id' => $dataB['expense']->id,
            'invoice_image' => null,
        ]);
    }

    public function test_same_organization_authorized_user_can_upload_expense_invoice(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        Storage::fake('local');

        $this->actingAs($managerA)->put(route('expenses.update', $dataA['expense']), $this->expensePayload($dataA) + [
            'invoice_image' => $this->fakePngUpload('invoice.png'),
        ])->assertRedirect(route('expenses.show', $dataA['expense']));

        $this->assertDatabaseMissing('expenses', [
            'id' => $dataA['expense']->id,
            'invoice_image' => null,
        ]);
    }

    public function test_manager_can_view_create_and_update_own_organization_building(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->get(route('buildings.index'))->assertOk();
        $this->actingAs($managerA)->get(route('buildings.show', $dataA['building']))->assertOk();
        $this->actingAs($managerA)->get(route('buildings.create'))->assertOk();
        $this->actingAs($managerA)->get(route('buildings.edit', $dataA['building']))->assertOk();

        $this->actingAs($managerA)->post(route('buildings.store'), [
            'name' => 'Manager Created Building',
            'location' => 'Abu Dhabi',
            'description' => 'Created by manager',
        ])->assertRedirect();

        $created = Building::where('name', 'Manager Created Building')->firstOrFail();

        $this->assertSame($managerA->organization_id, $created->organization_id);

        $this->actingAs($managerA)->put(route('buildings.update', $dataA['building']), [
            'name' => 'Manager Updated Building',
            'location' => 'Abu Dhabi',
            'description' => 'Updated by manager',
        ])->assertRedirect(route('buildings.show', $dataA['building']));

        $this->assertDatabaseHas('buildings', [
            'id' => $dataA['building']->id,
            'name' => 'Manager Updated Building',
        ]);
    }

    public function test_manager_cannot_delete_building(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->delete(route('buildings.destroy', $dataA['building']))
            ->assertForbidden();

        $this->assertDatabaseHas('buildings', ['id' => $dataA['building']->id]);
    }

    public function test_owner_can_delete_own_organization_building(): void
    {
        [$ownerA, , , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('buildings.destroy', $dataA['building']))
            ->assertRedirect(route('buildings.index'));

        $this->assertSoftDeleted('buildings', ['id' => $dataA['building']->id]);
    }

    public function test_accountant_and_caretaker_cannot_access_building_pages(): void
    {
        [, , $accountantA, $caretakerA, , $dataA] = $this->createTwoOrganizationScenario();

        foreach ([$accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('buildings.index'))->assertForbidden();
            $this->actingAs($user)->get(route('buildings.create'))->assertForbidden();
            $this->actingAs($user)->get(route('buildings.show', $dataA['building']))->assertForbidden();
            $this->actingAs($user)->get(route('buildings.edit', $dataA['building']))->assertForbidden();
        }
    }

    public function test_cross_organization_building_delete_is_denied(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('buildings.destroy', $dataB['building']))
            ->assertForbidden();

        $this->assertDatabaseHas('buildings', ['id' => $dataB['building']->id]);
    }

    public function test_building_creation_assigns_current_users_organization(): void
    {
        [, $managerA, , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->post(route('buildings.store'), [
            'organization_id' => $dataB['building']->organization_id,
            'name' => 'Organization Assignment Check',
            'location' => 'Abu Dhabi',
        ])->assertRedirect();

        $this->assertDatabaseHas('buildings', [
            'name' => 'Organization Assignment Check',
            'organization_id' => $managerA->organization_id,
        ]);

        $this->assertDatabaseMissing('buildings', [
            'name' => 'Organization Assignment Check',
            'organization_id' => $dataB['building']->organization_id,
        ]);
    }

    public function test_manager_can_view_create_and_update_own_organization_unit(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->get(route('units.index'))->assertOk();
        $this->actingAs($managerA)->get(route('units.show', $dataA['unit']))->assertOk();
        $this->actingAs($managerA)->get(route('units.create'))->assertOk();
        $this->actingAs($managerA)->get(route('units.edit', $dataA['unit']))->assertOk();

        $this->actingAs($managerA)->post(route('units.store'), array_merge(
            $this->unitPayload($dataA['building']),
            ['unit_number' => 'A-202'],
        ))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $created = Unit::where('unit_number', 'A-202')->firstOrFail();

        $this->assertSame($dataA['building']->id, $created->building_id);
        $this->assertDatabaseHas('units', [
            'id' => $created->id,
            'unit_number' => 'A-202',
            'building_id' => $dataA['building']->id,
        ]);

        $this->actingAs($managerA)->put(route('units.update', $dataA['unit']), array_merge(
            $this->unitPayload($dataA['building']),
            ['unit_number' => 'A-303'],
        ))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('units.show', $dataA['unit']));

        $this->assertDatabaseHas('units', [
            'id' => $dataA['unit']->id,
            'unit_number' => 'A-303',
            'building_id' => $dataA['building']->id,
        ]);
    }

    public function test_manager_cannot_delete_unit(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->delete(route('units.destroy', $dataA['unit']))
            ->assertForbidden();

        $this->assertDatabaseHas('units', ['id' => $dataA['unit']->id]);
    }

    public function test_owner_can_delete_own_organization_unit(): void
    {
        [$ownerA, , , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('units.destroy', $dataA['unit']))
            ->assertRedirect(route('units.index'));

        $this->assertSoftDeleted('units', ['id' => $dataA['unit']->id]);
    }

    public function test_accountant_and_caretaker_cannot_access_unit_pages(): void
    {
        [, , $accountantA, $caretakerA, , $dataA] = $this->createTwoOrganizationScenario();

        foreach ([$accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('units.index'))->assertForbidden();
            $this->actingAs($user)->get(route('units.create'))->assertForbidden();
            $this->actingAs($user)->get(route('units.show', $dataA['unit']))->assertForbidden();
            $this->actingAs($user)->get(route('units.edit', $dataA['unit']))->assertForbidden();
        }
    }

    public function test_cross_organization_unit_delete_is_denied(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('units.destroy', $dataB['unit']))
            ->assertForbidden();

        $this->assertDatabaseHas('units', ['id' => $dataB['unit']->id]);
    }

    public function test_unit_creation_cannot_use_another_organizations_building(): void
    {
        [, $managerA, , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->post(route('units.store'), $this->unitPayload($dataB['building']) + [
            'unit_number' => 'CROSS-UNIT',
        ])->assertSessionHasErrors('building_id');

        $this->assertDatabaseMissing('units', [
            'unit_number' => 'CROSS-UNIT',
            'building_id' => $dataB['building']->id,
        ]);
    }

    public function test_unit_update_cannot_move_unit_to_another_organizations_building(): void
    {
        [, $managerA, , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->put(route('units.update', $dataA['unit']), $this->unitPayload($dataB['building']) + [
            'unit_number' => 'MOVED-CROSS-ORG',
        ])->assertSessionHasErrors('building_id');

        $this->assertDatabaseHas('units', [
            'id' => $dataA['unit']->id,
            'building_id' => $dataA['building']->id,
        ]);
    }

    public function test_manager_can_view_create_and_update_own_organization_tenant(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->get(route('tenants.index'))->assertOk();
        $this->actingAs($managerA)->get(route('tenants.show', $dataA['tenant']))->assertOk();
        $this->actingAs($managerA)->get(route('tenants.create'))->assertOk();
        $this->actingAs($managerA)->get(route('tenants.edit', $dataA['tenant']))->assertOk();

        $this->actingAs($managerA)->post(route('tenants.store'), [
            'full_name' => 'Manager Created Tenant',
            'phone' => '0501112222',
            'email' => 'manager-created-tenant@example.com',
            'id_number' => 'TENANT-001',
            'nationality' => 'UAE',
            'notes' => 'Created by manager',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $created = Tenant::where('email', 'manager-created-tenant@example.com')->firstOrFail();

        $this->assertSame($managerA->organization_id, $created->organization_id);

        $this->actingAs($managerA)->put(route('tenants.update', $dataA['tenant']), [
            'full_name' => 'Manager Updated Tenant',
            'phone' => '0503334444',
            'email' => 'manager-updated-tenant@example.com',
            'id_number' => 'TENANT-002',
            'nationality' => 'UAE',
            'notes' => 'Updated by manager',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tenants.show', $dataA['tenant']));

        $this->assertDatabaseHas('tenants', [
            'id' => $dataA['tenant']->id,
            'full_name' => 'Manager Updated Tenant',
            'organization_id' => $managerA->organization_id,
        ]);
    }

    public function test_manager_cannot_delete_tenant(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->delete(route('tenants.destroy', $dataA['tenant']))
            ->assertForbidden();

        $this->assertDatabaseHas('tenants', ['id' => $dataA['tenant']->id]);
    }

    public function test_owner_can_delete_own_organization_tenant(): void
    {
        [$ownerA, , , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('tenants.destroy', $dataA['tenant']))
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $dataA['tenant']->id]);
    }

    public function test_accountant_and_caretaker_cannot_access_tenant_pages(): void
    {
        [, , $accountantA, $caretakerA, , $dataA] = $this->createTwoOrganizationScenario();

        foreach ([$accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('tenants.index'))->assertForbidden();
            $this->actingAs($user)->get(route('tenants.create'))->assertForbidden();
            $this->actingAs($user)->get(route('tenants.show', $dataA['tenant']))->assertForbidden();
            $this->actingAs($user)->get(route('tenants.edit', $dataA['tenant']))->assertForbidden();
        }
    }

    public function test_cross_organization_tenant_delete_is_denied(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('tenants.destroy', $dataB['tenant']))
            ->assertForbidden();

        $this->assertDatabaseHas('tenants', ['id' => $dataB['tenant']->id]);
    }

    public function test_tenant_creation_assigns_current_users_organization(): void
    {
        [, $managerA, , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->post(route('tenants.store'), [
            'organization_id' => $dataB['tenant']->organization_id,
            'full_name' => 'Organization Assignment Tenant',
            'phone' => '0505556666',
            'email' => 'org-assignment-tenant@example.com',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'email' => 'org-assignment-tenant@example.com',
            'organization_id' => $managerA->organization_id,
        ]);

        $this->assertDatabaseMissing('tenants', [
            'email' => 'org-assignment-tenant@example.com',
            'organization_id' => $dataB['tenant']->organization_id,
        ]);
    }

    public function test_manager_can_view_create_update_and_export_own_organization_contract(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $paymentCount = Payment::count();

        $this->actingAs($managerA)->get(route('contracts.index'))->assertOk();
        $this->actingAs($managerA)->get(route('contracts.show', $dataA['contract']))->assertOk();
        $this->actingAs($managerA)->get(route('contracts.create'))->assertOk();
        $this->actingAs($managerA)->get(route('contracts.edit', $dataA['contract']))->assertOk();

        $availableUnit = Unit::create([
            'building_id' => $dataA['building']->id,
            'unit_number' => 'MANAGER-CONTRACT-UNIT',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1200,
        ]);

        $this->actingAs($managerA)->post(route('contracts.store'), array_merge(
            $this->contractPayload($dataA),
            [
                'contract_number' => 'MANAGER-CONTRACT-001',
                'unit_id' => $availableUnit->id,
                'rent_amount' => 1200,
            ],
        ))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $created = Contract::latest('id')->firstOrFail();

        $this->assertSame($managerA->organization_id, $created->organization_id);
        $this->assertMatchesRegularExpression('/^CN-\d{4}-\d{6}$/', $created->contract_number);
        $this->assertNotSame('MANAGER-CONTRACT-001', $created->contract_number);
        $this->assertGreaterThan($paymentCount, Payment::count());

        $this->actingAs($managerA)->put(route('contracts.update', $dataA['contract']), array_merge(
            $this->contractPayload($dataA),
            [
                'contract_number' => 'MANAGER-CONTRACT-UPDATED',
                'deposit_amount' => 1300,
                'notes' => 'Manager allowed non-schedule update.',
            ],
        ))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.show', $dataA['contract']));

        $this->assertDatabaseHas('contracts', [
            'id' => $dataA['contract']->id,
            'contract_number' => $dataA['contract']->contract_number,
            'organization_id' => $managerA->organization_id,
            'deposit_amount' => 1300,
            'notes' => 'Manager allowed non-schedule update.',
        ]);

        $this->actingAs($managerA)->get(route('contracts.pdf', $dataA['contract']))->assertOk();
    }

    public function test_manager_cannot_delete_contract(): void
    {
        [, $managerA, , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($managerA)->delete(route('contracts.destroy', $dataA['contract']))
            ->assertForbidden();

        $this->assertDatabaseHas('contracts', ['id' => $dataA['contract']->id]);
    }

    public function test_owner_can_delete_own_organization_contract(): void
    {
        [$ownerA, , , , , $dataA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('contracts.destroy', $dataA['contract']))
            ->assertRedirect(route('contracts.index'));

        $this->assertDatabaseMissing('contracts', ['id' => $dataA['contract']->id]);
    }

    public function test_accountant_and_caretaker_cannot_access_contract_pages(): void
    {
        [, , $accountantA, $caretakerA, , $dataA] = $this->createTwoOrganizationScenario();

        foreach ([$accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('contracts.index'))->assertForbidden();
            $this->actingAs($user)->get(route('contracts.create'))->assertForbidden();
            $this->actingAs($user)->get(route('contracts.show', $dataA['contract']))->assertForbidden();
            $this->actingAs($user)->get(route('contracts.edit', $dataA['contract']))->assertForbidden();
        }
    }

    public function test_cross_organization_contract_delete_and_pdf_are_denied(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->delete(route('contracts.destroy', $dataB['contract']))
            ->assertForbidden();

        $this->assertDatabaseHas('contracts', ['id' => $dataB['contract']->id]);

        $this->actingAs($ownerA)->get(route('contracts.pdf', $dataB['contract']))
            ->assertForbidden();
    }

    public function test_contract_creation_cannot_use_another_organizations_tenant_or_unit(): void
    {
        [, $managerA, , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $paymentCount = Payment::count();

        $this->actingAs($managerA)->post(route('contracts.store'), array_merge(
            $this->contractPayload($dataA),
            [
                'tenant_id' => $dataB['tenant']->id,
                'contract_number' => 'CROSS-TENANT-CONTRACT',
            ],
        ))->assertForbidden();

        $this->assertSame($paymentCount, Payment::count());
        $this->assertDatabaseMissing('contracts', ['contract_number' => 'CROSS-TENANT-CONTRACT']);

        $this->actingAs($managerA)->post(route('contracts.store'), array_merge(
            $this->contractPayload($dataA),
            [
                'unit_id' => $dataB['unit']->id,
                'contract_number' => 'CROSS-UNIT-CONTRACT',
            ],
        ))->assertForbidden();

        $this->assertSame($paymentCount, Payment::count());
        $this->assertDatabaseMissing('contracts', ['contract_number' => 'CROSS-UNIT-CONTRACT']);
    }

    public function test_contract_update_cannot_move_to_another_organizations_tenant_or_unit(): void
    {
        [, $managerA, , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $paymentCount = Payment::count();

        $this->actingAs($managerA)->put(route('contracts.update', $dataA['contract']), array_merge(
            $this->contractPayload($dataA),
            ['tenant_id' => $dataB['tenant']->id],
        ))->assertForbidden();

        $this->assertSame($paymentCount, Payment::count());
        $this->assertDatabaseHas('contracts', [
            'id' => $dataA['contract']->id,
            'tenant_id' => $dataA['tenant']->id,
            'unit_id' => $dataA['unit']->id,
        ]);

        $this->actingAs($managerA)->put(route('contracts.update', $dataA['contract']), array_merge(
            $this->contractPayload($dataA),
            ['unit_id' => $dataB['unit']->id],
        ))->assertForbidden();

        $this->assertSame($paymentCount, Payment::count());
        $this->assertDatabaseHas('contracts', [
            'id' => $dataA['contract']->id,
            'tenant_id' => $dataA['tenant']->id,
            'unit_id' => $dataA['unit']->id,
        ]);
    }

    public function test_owner_manager_and_accountant_can_view_reports_index(): void
    {
        [$ownerA, $managerA, $accountantA] = $this->createTwoOrganizationScenario();

        foreach ([$ownerA, $managerA, $accountantA] as $user) {
            $this->actingAs($user)->get(route('reports.index'))->assertOk();
        }
    }

    public function test_caretaker_cannot_view_reports_index(): void
    {
        [, , , $caretakerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($caretakerA)->get(route('reports.index'))->assertForbidden();
    }

    public function test_owner_manager_and_accountant_can_export_all_report_pdf_types(): void
    {
        [$ownerA, $managerA, $accountantA] = $this->createTwoOrganizationScenario();
        $types = $this->reportTypes();
        $this->fakeReportPdf(count($types) * 3);

        foreach ([$ownerA, $managerA, $accountantA] as $user) {
            foreach ($types as $type) {
                $this->actingAs($user)->get(route('reports.pdf', $type))->assertOk();
            }
        }
    }

    public function test_caretaker_cannot_export_any_report_pdf_type(): void
    {
        [, , , $caretakerA] = $this->createTwoOrganizationScenario();

        foreach ($this->reportTypes() as $type) {
            $this->actingAs($caretakerA)->get(route('reports.pdf', $type))->assertForbidden();
        }
    }

    public function test_invalid_report_type_returns_not_found(): void
    {
        [$ownerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('reports.pdf', 'invalid-report'))->assertNotFound();
    }

    public function test_reports_index_does_not_expose_another_organizations_financial_totals(): void
    {
        [$ownerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('500.00')
            ->assertDontSee('90,000.00')
            ->assertDontSee('10,000.00')
            ->assertDontSee('80,000.00');
    }

    public function test_report_pdfs_remain_scoped_to_current_organization(): void
    {
        [$ownerA, , , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        Payment::create([
            'organization_id' => $ownerA->organization_id,
            'contract_id' => $dataA['contract']->id,
            'due_date' => now()->subMonth()->toDateString(),
            'amount_due' => 111,
            'amount_paid' => 0,
            'status' => 'overdue',
        ]);

        Payment::create([
            'organization_id' => $dataB['contract']->organization_id,
            'contract_id' => $dataB['contract']->id,
            'due_date' => now()->subMonth()->toDateString(),
            'amount_due' => 222,
            'amount_paid' => 0,
            'status' => 'overdue',
        ]);

        $capturedReports = [];
        $this->fakeReportPdf(count($this->reportTypes()), $capturedReports);

        foreach ($this->reportTypes() as $type) {
            $this->actingAs($ownerA)->get(route('reports.pdf', $type))->assertOk();
        }

        $buildingRows = $capturedReports['building-income']['rows'];
        $this->assertTrue($buildingRows->contains(fn ($row) => $row->name === $dataA['building']->name));
        $this->assertFalse($buildingRows->contains(fn ($row) => $row->name === $dataB['building']->name));

        $unitRows = $capturedReports['unit-statement']['rows'];
        $this->assertTrue($unitRows->contains(fn (Unit $unit) => $unit->unit_number === $dataA['unit']->unit_number));
        $this->assertFalse($unitRows->contains(fn (Unit $unit) => $unit->unit_number === $dataB['unit']->unit_number));

        $expenseRows = $capturedReports['expenses']['rows'];
        $this->assertTrue($expenseRows->contains(fn (Expense $expense) => (int) $expense->amount === 500));
        $this->assertFalse($expenseRows->contains(fn (Expense $expense) => (int) $expense->amount === 10000));

        $overdueRows = $capturedReports['overdue']['rows'];
        $this->assertTrue($overdueRows->contains(fn (Payment $payment) => (int) $payment->amount_due === 111));
        $this->assertFalse($overdueRows->contains(fn (Payment $payment) => (int) $payment->amount_due === 222));

        foreach (['net-profit', 'monthly-summary'] as $type) {
            $this->assertSame(1000.0, (float) $capturedReports[$type]['income']);
            $this->assertSame(500.0, (float) $capturedReports[$type]['expensesTotal']);
            $this->assertSame(500.0, (float) $capturedReports[$type]['netProfit']);
        }
    }

    public function test_all_current_mvp_roles_can_view_dashboard(): void
    {
        [$ownerA, $managerA, $accountantA, $caretakerA] = $this->createTwoOrganizationScenario();

        foreach ([$ownerA, $managerA, $accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }
    }

    public function test_owner_only_navigation_links_are_hidden_from_non_owner_roles(): void
    {
        [$ownerA, $managerA, $accountantA, $caretakerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Users<', false)
            ->assertSee('>Activity<', false);

        foreach ([$managerA, $accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('>Users<', false)
                ->assertDontSee('>Activity<', false);
        }
    }

    public function test_dashboard_financial_totals_are_scoped_to_current_organization(): void
    {
        [$ownerA, , , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        Payment::create([
            'organization_id' => $ownerA->organization_id,
            'contract_id' => $dataA['contract']->id,
            'due_date' => now()->subMonth()->toDateString(),
            'amount_due' => 111,
            'amount_paid' => 11,
            'status' => 'overdue',
        ]);

        Payment::create([
            'organization_id' => $dataB['contract']->organization_id,
            'contract_id' => $dataB['contract']->id,
            'due_date' => now()->subMonth()->toDateString(),
            'amount_due' => 222,
            'amount_paid' => 22,
            'status' => 'overdue',
        ]);

        $this->actingAs($ownerA)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Organization A')
            ->assertSee('1,000.00')
            ->assertSee('500.00')
            ->assertSee('100.00')
            ->assertDontSee('Organization B')
            ->assertDontSee('90,000.00')
            ->assertDontSee('10,000.00')
            ->assertDontSee('80,000.00')
            ->assertDontSee('200.00');
    }

    public function test_dashboard_lists_are_scoped_to_current_organization(): void
    {
        [$ownerA, , , , $dataB, $dataA] = $this->createTwoOrganizationScenario();

        $dataA['contract']->update([
            'contract_number' => 'A-DASH-END',
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $dataB['contract']->update([
            'contract_number' => 'B-DASH-END',
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($ownerA)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('A-DASH-END')
            ->assertSee('maintenance')
            ->assertDontSee('B-DASH-END')
            ->assertDontSee($dataB['building']->name)
            ->assertDontSee($dataB['unit']->unit_number)
            ->assertDontSee($dataB['tenant']->full_name)
            ->assertDontSee('security')
            ->assertDontSee('90,000.00');
    }

    public function test_owner_can_view_create_and_update_own_organization_users(): void
    {
        [$ownerA, $managerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('users.index'))->assertOk();

        $this->actingAs($ownerA)->post(route('users.store'), [
            'name' => 'Owner Created User',
            'email' => 'owner-created-user@example.com',
            'role' => 'accountant',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $created = User::where('email', 'owner-created-user@example.com')->firstOrFail();

        $this->assertSame($ownerA->organization_id, $created->organization_id);

        $this->actingAs($ownerA)->put(route('users.update', $managerA), [
            'name' => 'Owner Updated Manager',
            'email' => 'owner-updated-manager@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managerA->id,
            'name' => 'Owner Updated Manager',
            'email' => 'owner-updated-manager@example.com',
            'organization_id' => $ownerA->organization_id,
        ]);
    }

    public function test_user_creation_forces_current_users_organization(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->post(route('users.store'), [
            'organization_id' => $dataB['owner']->organization_id,
            'name' => 'Forced Organization User',
            'email' => 'forced-organization-user@example.com',
            'role' => 'caretaker',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'forced-organization-user@example.com',
            'organization_id' => $ownerA->organization_id,
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'forced-organization-user@example.com',
            'organization_id' => $dataB['owner']->organization_id,
        ]);
    }

    public function test_user_update_cannot_change_organization(): void
    {
        [$ownerA, $managerA, , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->put(route('users.update', $managerA), [
            'organization_id' => $dataB['owner']->organization_id,
            'name' => 'Organization Change Attempt',
            'email' => 'organization-change-attempt@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managerA->id,
            'email' => 'organization-change-attempt@example.com',
            'organization_id' => $ownerA->organization_id,
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $managerA->id,
            'organization_id' => $dataB['owner']->organization_id,
        ]);
    }

    public function test_owner_can_deactivate_a_non_owner_team_member_in_same_organization(): void
    {
        [$ownerA, $managerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)
            ->patch(route('users.deactivate', $managerA))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managerA->id,
            'organization_id' => $ownerA->organization_id,
            'is_active' => false,
        ]);
    }

    public function test_owner_can_reactivate_a_team_member_in_same_organization(): void
    {
        [$ownerA, $managerA] = $this->createTwoOrganizationScenario();

        $managerA->update(['is_active' => false]);

        $this->actingAs($ownerA)
            ->patch(route('users.reactivate', $managerA))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managerA->id,
            'organization_id' => $ownerA->organization_id,
            'is_active' => true,
        ]);
    }

    public function test_deactivated_user_cannot_log_in_or_access_application(): void
    {
        [, $managerA] = $this->createTwoOrganizationScenario();

        $managerA->update(['is_active' => false]);

        $this->post(route('login'), [
            'email' => $managerA->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->actingAs($managerA)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_cannot_deactivate_or_delete_last_owner(): void
    {
        [$ownerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)
            ->patch(route('users.deactivate', $ownerA))
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $ownerA->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($ownerA)
            ->delete('/users/'.$ownerA->id)
            ->assertMethodNotAllowed();
    }

    public function test_owner_cannot_demote_last_owner_to_non_owner_role(): void
    {
        [$ownerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)
            ->put(route('users.update', $ownerA), [
                'name' => $ownerA->name,
                'email' => $ownerA->email,
                'role' => 'manager',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $ownerA->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    public function test_non_owner_roles_cannot_manage_user_access_or_change_roles(): void
    {
        [$ownerA, $managerA, $accountantA, $caretakerA] = $this->createTwoOrganizationScenario();

        foreach ([$managerA, $accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->patch(route('users.deactivate', $ownerA))->assertForbidden();
            $this->actingAs($user)->patch(route('users.reactivate', $ownerA))->assertForbidden();

            $this->actingAs($user)->put(route('users.update', $ownerA), [
                'name' => 'Blocked Role Change',
                'email' => "blocked-role-change-{$user->id}@example.com",
                'role' => 'manager',
                'password' => '',
                'password_confirmation' => '',
            ])->assertForbidden();
        }
    }

    public function test_cross_organization_user_access_management_is_forbidden(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)
            ->patch(route('users.deactivate', $dataB['owner']))
            ->assertForbidden();

        $this->actingAs($ownerA)
            ->patch(route('users.reactivate', $dataB['owner']))
            ->assertForbidden();

        $this->actingAs($ownerA)
            ->put(route('users.update', $dataB['owner']), [
                'name' => 'Cross Organization Role Change',
                'email' => 'cross-org-role-change@example.com',
                'role' => 'manager',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertForbidden();
    }

    public function test_user_access_changes_are_recorded_in_activity_logs(): void
    {
        [$ownerA, $managerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)
            ->put(route('users.update', $managerA), [
                'name' => $managerA->name,
                'email' => $managerA->email,
                'role' => 'accountant',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('users.index'));

        $this->actingAs($ownerA)
            ->patch(route('users.deactivate', $managerA))
            ->assertRedirect(route('users.index'));

        $this->actingAs($ownerA)
            ->patch(route('users.reactivate', $managerA))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $ownerA->organization_id,
            'user_id' => $ownerA->id,
            'action' => 'user.role_changed',
            'subject_type' => User::class,
            'subject_id' => $managerA->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $ownerA->organization_id,
            'user_id' => $ownerA->id,
            'action' => 'user.deactivated',
            'subject_type' => User::class,
            'subject_id' => $managerA->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $ownerA->organization_id,
            'user_id' => $ownerA->id,
            'action' => 'user.reactivated',
            'subject_type' => User::class,
            'subject_id' => $managerA->id,
        ]);
    }

    public function test_owner_can_view_own_organization_activity_logs(): void
    {
        [$ownerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Organization A visible log');
    }

    public function test_non_owner_roles_cannot_view_activity_logs(): void
    {
        [, $managerA, $accountantA, $caretakerA] = $this->createTwoOrganizationScenario();

        foreach ([$managerA, $accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('activity-logs.index'))->assertForbidden();
        }
    }

    public function test_activity_logs_index_only_shows_current_organization_logs(): void
    {
        [$ownerA] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Organization A visible log')
            ->assertDontSee('Organization B private log');
    }

    public function test_owner_cannot_edit_or_update_another_organizations_user(): void
    {
        [$ownerA, , , , $dataB] = $this->createTwoOrganizationScenario();

        $this->actingAs($ownerA)->get(route('users.edit', $dataB['owner']))
            ->assertForbidden();

        $this->actingAs($ownerA)->put(route('users.update', $dataB['owner']), [
            'name' => 'Cross Organization User Update',
            'email' => 'cross-organization-user-update@example.com',
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'id' => $dataB['owner']->id,
            'email' => 'cross-organization-user-update@example.com',
        ]);
    }

    public function test_non_owner_roles_cannot_manage_users(): void
    {
        [$ownerA, $managerA, $accountantA, $caretakerA] = $this->createTwoOrganizationScenario();

        foreach ([$managerA, $accountantA, $caretakerA] as $user) {
            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('users.create'))->assertForbidden();
            $this->actingAs($user)->post(route('users.store'), [
                'name' => 'Blocked Managed User',
                'email' => "blocked-managed-user-{$user->id}@example.com",
                'role' => 'caretaker',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertForbidden();
            $this->actingAs($user)->get(route('users.edit', $ownerA))->assertForbidden();
            $this->actingAs($user)->put(route('users.update', $ownerA), [
                'name' => 'Blocked Owner Update',
                'email' => "blocked-owner-update-{$user->id}@example.com",
                'role' => 'owner',
                'password' => '',
                'password_confirmation' => '',
            ])->assertForbidden();
        }
    }

    private function createTwoOrganizationScenario(): array
    {
        $organizationA = Organization::create(['name' => 'Organization A']);
        $organizationB = Organization::create(['name' => 'Organization B']);

        $ownerA = $this->user($organizationA, 'owner-a@example.com', 'owner');
        $managerA = $this->user($organizationA, 'manager-a@example.com', 'manager');
        $accountantA = $this->user($organizationA, 'accountant-a@example.com', 'accountant');
        $caretakerA = $this->user($organizationA, 'caretaker-a@example.com', 'caretaker');
        $ownerB = $this->user($organizationB, 'owner-b@example.com', 'owner');

        $dataA = $this->organizationData($organizationA, $ownerA, [
            'building' => 'Org A Tower',
            'unit' => 'A-101',
            'tenant' => 'Org A Tenant',
            'contract' => 'ORG-A-001',
            'payment' => 1000,
            'paid' => 1000,
            'expense' => 500,
            'category' => 'maintenance',
        ]);

        $dataB = $this->organizationData($organizationB, $ownerB, [
            'building' => 'Org B Private Tower',
            'unit' => 'B-909',
            'tenant' => 'Org B Private Tenant',
            'contract' => 'ORG-B-999',
            'payment' => 90000,
            'paid' => 90000,
            'expense' => 10000,
            'category' => 'security',
        ]);

        return [$ownerA, $managerA, $accountantA, $caretakerA, $dataB + ['owner' => $ownerB], $dataA + ['owner' => $ownerA]];
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function organizationData(Organization $organization, User $owner, array $values): array
    {
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => $values['building'],
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $values['unit'],
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => $values['payment'],
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => $values['tenant'],
            'phone' => '0500000000',
        ]);

        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => $values['contract'],
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => $values['payment'],
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => now()->startOfMonth()->toDateString(),
            'amount_due' => $values['payment'],
            'amount_paid' => $values['paid'],
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'payment_method' => 'cash',
            'created_by' => $owner->id,
        ]);

        $expense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => $values['category'],
            'amount' => $values['expense'],
            'expense_date' => now()->toDateString(),
            'created_by' => $owner->id,
        ]);

        ActivityLog::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'action' => $organization->name === 'Organization B' ? 'private.log' : 'visible.log',
            'subject_type' => Building::class,
            'subject_id' => $building->id,
            'description' => $organization->name === 'Organization B'
                ? 'Organization B private log'
                : 'Organization A visible log',
        ]);

        return compact('building', 'unit', 'tenant', 'contract', 'payment', 'expense');
    }

    private function unitPayload(Building $building): array
    {
        return [
            'building_id' => $building->id,
            'unit_number' => 'Updated',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ];
    }

    private function contractPayload(array $data): array
    {
        return [
            'unit_id' => $data['unit']->id,
            'tenant_id' => $data['tenant']->id,
            'contract_number' => $data['contract']->contract_number,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => $data['contract']->rent_amount,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ];
    }

    private function expensePayload(array $data): array
    {
        return [
            'building_id' => $data['building']->id,
            'unit_id' => $data['unit']->id,
            'category' => 'security',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
        ];
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }

    private function reportTypes(): array
    {
        return [
            'building-income',
            'unit-statement',
            'expenses',
            'overdue',
            'net-profit',
            'monthly-summary',
        ];
    }

    private function fakeReportPdf(int $times, array &$capturedReports = []): void
    {
        $pdf = \Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('download')
            ->times($times)
            ->andReturnUsing(fn (string $filename) => response("fake {$filename}", 200));

        Pdf::shouldReceive('loadView')
            ->times($times)
            ->withArgs(function (string $view, array $data) use (&$capturedReports) {
                $capturedReports[$data['type']] = $data;

                return $view === 'pdf.report';
            })
            ->andReturn($pdf);
    }
}
