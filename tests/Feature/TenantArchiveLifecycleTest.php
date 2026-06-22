<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class TenantArchiveLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_archives_unused_tenant_with_normalized_reason_and_metadata(): void
    {
        Carbon::setTestNow('2026-06-22 10:15:00');
        [$owner, $data] = $this->scenario();
        $tenant = $this->tenant($data['organization'], 'Archive Candidate', [
            'phone' => '0501112222',
            'email' => 'archive-candidate@example.com',
            'id_number' => 'ARCH-001',
            'nationality' => 'UAE',
            'notes' => 'Original archive candidate notes.',
        ]);
        $original = $tenant->only(['organization_id', 'full_name', 'phone', 'email', 'id_number', 'nationality', 'notes', 'created_at', 'updated_at']);

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $tenant), [
                'archive_reason' => "  duplicate\nrecord\tfor same tenant  ",
            ])
            ->assertRedirect(route('tenants.show', $tenant));

        $tenant->refresh();

        foreach ($original as $field => $value) {
            $this->assertEquals($value, $tenant->{$field});
        }

        $this->assertSame('2026-06-22 10:15:00', $tenant->archived_at->toDateTimeString());
        $this->assertSame($owner->id, $tenant->archived_by);
        $this->assertSame('duplicate record for same tenant', $tenant->archive_reason);
        $this->assertSame(1, $this->archiveLogCount($tenant));
        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'action' => 'tenant.archived',
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'description' => 'duplicate record for same tenant',
        ]);

        Carbon::setTestNow();
    }

    public function test_historical_expired_and_terminated_contracts_do_not_block_archive_and_remain_viewable(): void
    {
        [$owner, $data] = $this->scenario();
        $tenant = $this->tenant($data['organization'], 'Historical Tenant');
        $expired = $this->contract($data, $tenant, [
            'contract_number' => 'ARCH-HIST-EXPIRED',
            'status' => 'expired',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $terminated = $this->contract($data, $tenant, [
            'contract_number' => 'ARCH-HIST-TERMINATED',
            'unit_id' => $data['secondUnit']->id,
            'status' => 'terminated',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $payment = $expired->payments()->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'Historical tenant no longer active.'])
            ->assertRedirect(route('tenants.show', $tenant));

        $this->assertDatabaseHas('contracts', ['id' => $expired->id, 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('contracts', ['id' => $terminated->id, 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'contract_id' => $expired->id]);

        $this->actingAs($owner)
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Archived')
            ->assertSee('Historical tenant no longer active.')
            ->assertSee('ARCH-HIST-EXPIRED')
            ->assertSee('ARCH-HIST-TERMINATED');

        $this->actingAs($owner)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Historical Tenant');
    }

    public function test_current_and_future_active_contracts_block_archive(): void
    {
        [$owner, $data] = $this->scenario();
        $currentTenant = $this->tenant($data['organization'], 'Current Active Tenant');
        $futureTenant = $this->tenant($data['organization'], 'Future Active Tenant');
        $this->contract($data, $currentTenant, [
            'contract_number' => 'ARCH-CURRENT',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);
        $this->contract($data, $futureTenant, [
            'contract_number' => 'ARCH-FUTURE',
            'unit_id' => $data['secondUnit']->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'status' => 'active',
        ]);

        foreach ([$currentTenant, $futureTenant] as $tenant) {
            $this->actingAs($owner)
                ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'Attempt archive active tenant.'])
                ->assertStatus(422);

            $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'archived_at' => null]);
            $this->assertSame(0, $this->archiveLogCount($tenant));
        }
    }

    public function test_duplicate_archive_is_idempotent_and_already_archived_short_circuits_validation(): void
    {
        [$owner, $data] = $this->scenario();
        $tenant = $this->tenant($data['organization'], 'Duplicate Archive Tenant');

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'First archive reason.'])
            ->assertRedirect(route('tenants.show', $tenant));

        $first = $tenant->fresh();

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'Second archive reason.'])
            ->assertStatus(422);

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $tenant), [])
            ->assertStatus(422);

        $tenant->refresh();

        $this->assertEquals($first->archived_at, $tenant->archived_at);
        $this->assertSame($first->archived_by, $tenant->archived_by);
        $this->assertSame('First archive reason.', $tenant->archive_reason);
        $this->assertSame(1, $this->archiveLogCount($tenant));
    }

    public function test_logger_failure_rolls_back_archive_metadata(): void
    {
        [$owner, $data] = $this->scenario();
        $tenant = $this->tenant($data['organization'], 'Rollback Archive Tenant');

        $this->app->bind(ActivityLogger::class, fn () => new class extends ActivityLogger
        {
            public function log(string $action, Model $subject, ?string $description = null): void
            {
                throw new RuntimeException('Archive log failed.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($owner)
                ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'Rollback reason.']);
            $this->fail('Expected logger exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Archive log failed.', $exception->getMessage());
        }

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'archived_at' => null,
            'archived_by' => null,
            'archive_reason' => null,
        ]);
        $this->assertSame(0, $this->archiveLogCount($tenant));
    }

    public function test_archive_authorization_roles_and_cross_organization_isolation(): void
    {
        [$owner, $data, $otherData] = $this->scenario();
        $manager = $this->user($data['organization'], 'archive-manager@example.com', 'manager');
        $accountant = $this->user($data['organization'], 'archive-accountant@example.com', 'accountant');
        $caretaker = $this->user($data['organization'], 'archive-caretaker@example.com', 'caretaker');
        $tenant = $this->tenant($data['organization'], 'Role Archive Tenant');
        $otherTenant = $this->tenant($otherData['organization'], 'Other Archive Tenant');

        $this->actingAs($manager)
            ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'Manager attempt.'])
            ->assertForbidden();
        $this->actingAs($accountant)
            ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'Accountant attempt.'])
            ->assertForbidden();
        $this->actingAs($caretaker)
            ->patch(route('tenants.archive', $tenant), ['archive_reason' => 'Caretaker attempt.'])
            ->assertForbidden();
        $this->actingAs($owner)
            ->patch(route('tenants.archive', $otherTenant), ['archive_reason' => 'Cross organization attempt.'])
            ->assertForbidden();

        $this->actingAs($manager)->get(route('tenants.create'))->assertOk();
        $this->actingAs($manager)->put(route('tenants.update', $tenant), $this->tenantPayload(['full_name' => 'Manager Still Updates']))->assertRedirect(route('tenants.show', $tenant));

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'full_name' => 'Manager Still Updates', 'archived_at' => null]);
        $this->assertDatabaseHas('tenants', ['id' => $otherTenant->id, 'archived_at' => null]);
    }

    public function test_archive_reason_validation(): void
    {
        [$owner, $data] = $this->scenario();
        $missing = $this->tenant($data['organization'], 'Missing Reason Tenant');
        $blank = $this->tenant($data['organization'], 'Blank Reason Tenant');
        $overlong = $this->tenant($data['organization'], 'Overlong Reason Tenant');

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $missing), [])
            ->assertSessionHasErrors('archive_reason');

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $blank), ['archive_reason' => " \n\t "])
            ->assertSessionHasErrors('archive_reason');

        $this->actingAs($owner)
            ->patch(route('tenants.archive', $overlong), ['archive_reason' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('archive_reason');

        foreach ([$missing, $blank, $overlong] as $tenant) {
            $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'archived_at' => null]);
        }
    }

    public function test_archived_tenant_is_read_only_but_show_remains_accessible(): void
    {
        [$owner, $data] = $this->scenario();
        $tenant = $this->tenant($data['organization'], 'Read Only Archived Tenant');
        $contract = $this->contract($data, $tenant, ['status' => 'expired']);
        $this->archive($owner, $tenant, 'Read only archive reason.');
        $updateLogCount = ActivityLog::where('action', 'tenant.updated')->where('subject_id', $tenant->id)->count();

        $this->actingAs($owner)
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Archived')
            ->assertSee('Read only archive reason.')
            ->assertSee($owner->name)
            ->assertSee($contract->contract_number)
            ->assertDontSee('href="'.route('tenants.edit', $tenant).'"', false);

        $this->actingAs($owner)->get(route('tenants.edit', $tenant))->assertStatus(422);

        $this->actingAs($owner)
            ->put(route('tenants.update', $tenant), $this->tenantPayload(['full_name' => 'Should Not Mutate']))
            ->assertStatus(422);

        $tenant->refresh();

        $this->assertSame('Read Only Archived Tenant', $tenant->full_name);
        $this->assertSame($updateLogCount, ActivityLog::where('action', 'tenant.updated')->where('subject_id', $tenant->id)->count());
    }

    public function test_lifecycle_filters_search_and_pagination_query_strings(): void
    {
        [$owner, $data, $otherData] = $this->scenario();
        $activeTenant = $this->tenant($data['organization'], 'Filter Active Tenant');
        $archivedTenant = $this->tenant($data['organization'], 'Filter Archived Tenant');
        $this->archive($owner, $archivedTenant, 'Filter archive reason.');

        $this->actingAs($owner)
            ->get(route('tenants.index'))
            ->assertOk()
            ->assertSee('Filter Active Tenant')
            ->assertDontSee('Filter Archived Tenant');

        $this->actingAs($owner)
            ->get(route('tenants.index', ['lifecycle' => 'archived']))
            ->assertOk()
            ->assertSee('Filter Archived Tenant')
            ->assertDontSee('Filter Active Tenant');

        $this->actingAs($owner)
            ->get(route('tenants.index', ['lifecycle' => 'all']))
            ->assertOk()
            ->assertSee('Filter Active Tenant')
            ->assertSee('Filter Archived Tenant');

        $this->actingAs($owner)
            ->get(route('tenants.index', ['lifecycle' => 'invalid']))
            ->assertOk()
            ->assertSee('Filter Active Tenant')
            ->assertDontSee('Filter Archived Tenant');

        $otherOrganizationArchivedTenant = $this->tenant($otherData['organization'], 'Other Filter Archived Tenant');

        foreach (range(1, 15) as $index) {
            $tenant = $this->tenant($data['organization'], sprintf('Filter Archived Pagination Tenant %02d', $index));
            $this->archive($owner, $tenant, 'Filter archive pagination reason.');
        }

        $otherOrganizationArchivedTenant->forceFill([
            'archived_at' => now(),
            'archived_by' => $owner->id,
            'archive_reason' => 'Other organization archive reason.',
        ])->save();

        $this->actingAs($owner)
            ->get(route('tenants.index', ['lifecycle' => 'archived', 'search' => 'Archived']))
            ->assertOk()
            ->assertSee('Filter Archived Pagination Tenant')
            ->assertDontSee('Filter Active Tenant')
            ->assertDontSee('Other Filter Archived Tenant')
            ->assertSee('lifecycle=archived', false)
            ->assertSee('search=Archived', false);
    }

    public function test_delete_rules_for_active_archived_and_contract_linked_tenants(): void
    {
        [$owner, $data] = $this->scenario();
        $activeUnused = $this->tenant($data['organization'], 'Active Delete Tenant');
        $archivedUnused = $this->tenant($data['organization'], 'Archived Delete Tenant');
        $linked = $this->tenant($data['organization'], 'Linked Delete Tenant');
        $this->archive($owner, $archivedUnused, 'No hard delete after archive.');
        $this->contract($data, $linked, ['status' => 'expired']);

        $this->actingAs($owner)
            ->delete(route('tenants.destroy', $activeUnused))
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $activeUnused->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'tenant.deleted', 'subject_type' => Tenant::class, 'subject_id' => $activeUnused->id]);

        $this->actingAs($owner)->delete(route('tenants.destroy', $archivedUnused))->assertStatus(422);
        $this->actingAs($owner)->delete(route('tenants.destroy', $linked))->assertStatus(422);

        $this->assertDatabaseHas('tenants', ['id' => $archivedUnused->id]);
        $this->assertDatabaseHas('tenants', ['id' => $linked->id]);
    }

    public function test_contract_selector_submission_edit_and_renewal_behavior_with_archived_tenants(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $archivedTenant = $this->tenant($data['organization'], 'Archived Selector Tenant');
        $this->archive($owner, $archivedTenant, 'Exclude from new contracts.');
        $expiredContract = $this->contract($data, $archivedTenant, [
            'contract_number' => 'ARCHIVED-EDIT-CONTRACT',
            'status' => 'expired',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $renewalSource = $this->contract($data, $archivedTenant, [
            'contract_number' => 'ARCHIVED-RENEWAL-SOURCE',
            'unit_id' => $data['secondUnit']->id,
            'status' => 'active',
            'start_date' => '2025-07-01',
            'end_date' => now()->addDays(30)->toDateString(),
        ]);
        $contractCount = Contract::count();
        $paymentCount = Payment::count();

        $this->actingAs($owner)
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertDontSee('Archived Selector Tenant');

        $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($data, [
                'tenant_id' => $archivedTenant->id,
                'contract_number' => 'ARCHIVED-DIRECT-SUBMIT',
            ]))
            ->assertSessionHasErrors('tenant_id');

        $this->actingAs($owner)
            ->get(route('contracts.edit', $expiredContract))
            ->assertOk()
            ->assertSee('Archived Selector Tenant')
            ->assertDontSee('name="tenant_id"', false);

        $this->actingAs($owner)
            ->get(route('contracts.create', ['renew_from' => $renewalSource->id]))
            ->assertStatus(422);

        $this->actingAs($owner)
            ->post(route('contracts.store'), ['renew_from' => $renewalSource->id])
            ->assertStatus(422);

        $this->assertSame($contractCount, Contract::count());
        $this->assertSame($paymentCount, Payment::count());

        Carbon::setTestNow();
    }

    public function test_duplicate_inline_tenant_checks_include_archived_tenants(): void
    {
        [$owner, $data] = $this->scenario();
        $archived = $this->tenant($data['organization'], 'Archived Duplicate Tenant', [
            'phone' => '+971500000000',
            'email' => 'archived-duplicate@example.com',
            'id_number' => 'ARCH-DUP-ID',
        ]);
        $this->archive($owner, $archived, 'Duplicate identity retained.');

        foreach ([
            ['id_number' => ' ARCH-DUP-ID ', 'email' => 'different@example.com', 'phone' => '+971511111111'],
            ['full_name' => 'Archived Duplicate Tenant', 'email' => ' ARCHIVED-DUPLICATE@EXAMPLE.COM ', 'phone' => '+971522222222', 'id_number' => 'DIFFERENT-ID'],
            ['full_name' => 'Archived Duplicate Tenant', 'email' => 'different@example.com', 'phone' => ' +971500000000 ', 'id_number' => 'DIFFERENT-ID-2'],
        ] as $index => $newTenant) {
            $this->actingAs($owner)
                ->post(route('contracts.store'), $this->contractPayload($data, [
                    'tenant_mode' => 'new',
                    'tenant_id' => null,
                    'unit_id' => $index === 0 ? $data['unit']->id : $data['secondUnit']->id,
                    'new_tenant' => $this->newTenantPayload($newTenant),
                ]))
                ->assertSessionHasErrors('new_tenant.full_name');
        }
    }

    public function test_historical_pdf_report_and_receipt_display_archived_tenant_names(): void
    {
        [$owner, $data] = $this->scenario();
        $tenant = $this->tenant($data['organization'], 'Archived PDF Tenant');
        $contract = $this->contract($data, $tenant, [
            'contract_number' => 'ARCH-PDF-CONTRACT',
            'status' => 'expired',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
        ]);
        $payment = $contract->payments()->firstOrFail();
        $payment->update([
            'amount_paid' => $payment->amount_due,
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);
        $this->archive($owner, $tenant, 'Historical PDF display.');

        $this->actingAs($owner)->get(route('contracts.show', $contract))->assertOk()->assertSee('Archived PDF Tenant');
        $this->actingAs($owner)->get(route('payments.index'))->assertOk()->assertSee('Archived PDF Tenant');
        $this->actingAs($owner)->get(route('reports.index'))->assertOk();
        $this->actingAs($owner)->get(route('contracts.pdf', $contract))->assertOk();
        $this->actingAs($owner)->get(route('payments.receipt', $payment))->assertOk();
        $this->actingAs($owner)->get(route('reports.pdf', 'monthly-summary'))->assertOk();
    }

    public function test_english_and_arabic_archive_localization_and_mobile_table_safety(): void
    {
        [$owner, $data] = $this->scenario();
        $tenant = $this->tenant($data['organization'], 'Localized Archive Tenant');
        $this->archive($owner, $tenant, 'Localized archive reason.');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('tenants.index', ['lifecycle' => 'archived']))
            ->assertOk()
            ->assertSee('Archived')
            ->assertSee('All')
            ->assertSee('data-mobile-table', false)
            ->assertSee('tap-target', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertSee('مؤرشف')
            ->assertSee('سبب الأرشفة')
            ->assertSee('تاريخ الأرشفة')
            ->assertSee('تمت الأرشفة بواسطة');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('تمت أرشفة المستأجر');
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Tenant Archive Org']);
        $otherOrganization = Organization::create(['name' => 'Other Tenant Archive Org']);
        $owner = $this->user($organization, 'archive-owner@example.com', 'owner');

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
            'name' => "Archive Building {$prefix}",
            'location' => 'Abu Dhabi',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => "ARCH-{$prefix}-101",
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => '1000.00',
        ]);
        $secondUnit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => "ARCH-{$prefix}-202",
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => '1000.00',
        ]);

        return compact('organization', 'building', 'unit', 'secondUnit');
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' Archive User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function tenant(Organization $organization, string $name, array $overrides = []): Tenant
    {
        return Tenant::create($overrides + [
            'organization_id' => $organization->id,
            'full_name' => $name,
            'phone' => '0500000000',
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.com',
            'id_number' => strtoupper(str_replace(' ', '-', $name)).'-ID',
            'nationality' => 'UAE',
            'notes' => $name.' notes.',
        ]);
    }

    private function contract(array $data, Tenant $tenant, array $overrides = []): Contract
    {
        $contract = Contract::create($overrides + [
            'organization_id' => $data['organization']->id,
            'unit_id' => $data['unit']->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'ARCH-C-'.uniqid(),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => '1000.00',
            'payment_frequency' => 'monthly',
            'deposit_amount' => '0.00',
            'status' => 'active',
        ]);

        Payment::create([
            'organization_id' => $data['organization']->id,
            'contract_id' => $contract->id,
            'due_date' => $contract->start_date->toDateString(),
            'amount_due' => $contract->rent_amount,
            'amount_paid' => '0.00',
            'status' => 'pending',
        ]);

        return $contract;
    }

    private function archive(User $owner, Tenant $tenant, string $reason): Tenant
    {
        $this->actingAs($owner)
            ->patch(route('tenants.archive', $tenant), ['archive_reason' => $reason])
            ->assertRedirect(route('tenants.show', $tenant));

        return $tenant->refresh();
    }

    private function tenantPayload(array $overrides = []): array
    {
        return $overrides + [
            'full_name' => 'Updated Tenant',
            'phone' => '0501234567',
            'email' => 'updated-tenant@example.com',
            'id_number' => 'UPDATED-TENANT-ID',
            'nationality' => 'UAE',
            'notes' => 'Updated tenant notes.',
        ];
    }

    private function contractPayload(array $data, array $overrides = []): array
    {
        return $overrides + [
            'tenant_mode' => 'existing',
            'tenant_id' => $overrides['tenant_id'] ?? $this->tenant($data['organization'], 'Default Contract Tenant')->id,
            'unit_id' => $data['unit']->id,
            'start_date' => now()->addYears(2)->startOfMonth()->toDateString(),
            'end_date' => now()->addYears(2)->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => '1000.00',
            'payment_frequency' => 'monthly',
            'deposit_amount' => '0.00',
            'status' => 'active',
            'notes' => 'Contract payload notes.',
        ];
    }

    private function newTenantPayload(array $overrides = []): array
    {
        return $overrides + [
            'full_name' => 'New Archive Tenant',
            'phone' => '+971599999999',
            'email' => 'new-archive-tenant@example.com',
            'id_number' => 'NEW-ARCHIVE-TENANT-ID',
            'nationality' => 'UAE',
            'notes' => 'New tenant notes.',
        ];
    }

    private function archiveLogCount(Tenant $tenant): int
    {
        return ActivityLog::where([
            'action' => 'tenant.archived',
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
        ])->count();
    }
}
