<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoricalDeletionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_deletion_returns_422_and_preserves_contract_payments_and_activity_logs(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $data['contract'];
        $paymentIds = $contract->payments()->pluck('id')->all();
        $activityCount = $this->activityCount('contract.deleted', Contract::class, $contract->id);

        $this->actingAs($owner)
            ->delete(route('contracts.destroy', $contract))
            ->assertStatus(422);

        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);

        foreach ($paymentIds as $paymentId) {
            $this->assertDatabaseHas('payments', ['id' => $paymentId]);
        }

        $this->assertSame($activityCount, $this->activityCount('contract.deleted', Contract::class, $contract->id));
    }

    public function test_tenant_with_contract_cannot_be_deleted_and_preserves_history_and_activity_logs(): void
    {
        [$owner, $data] = $this->scenario();
        $tenant = $data['tenant'];
        $activityCount = $this->activityCount('tenant.deleted', Tenant::class, $tenant->id);

        $this->actingAs($owner)
            ->delete(route('tenants.destroy', $tenant))
            ->assertStatus(422);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('contracts', ['id' => $data['contract']->id]);
        $this->assertDatabaseHas('payments', ['contract_id' => $data['contract']->id]);
        $this->assertSame($activityCount, $this->activityCount('tenant.deleted', Tenant::class, $tenant->id));
    }

    public function test_unused_tenant_can_be_deleted_and_logs_exact_action(): void
    {
        [$owner] = $this->scenario();
        $tenant = $this->tenant($owner, 'Unused Tenant For Safe Delete');

        $this->assertSame(0, $tenant->contracts()->count());

        $this->actingAs($owner)
            ->delete(route('tenants.destroy', $tenant))
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'action' => 'tenant.deleted',
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_expense_deletion_returns_422_and_preserves_invoice_path_and_activity_logs(): void
    {
        [$owner, $data] = $this->scenario();
        $expense = $data['expense'];
        $expense->update(['invoice_image' => 'expense-invoices/original-invoice.png']);
        $activityCount = $this->activityCount('expense.deleted', Expense::class, $expense->id);

        $this->actingAs($owner)
            ->delete(route('expenses.destroy', $expense))
            ->assertStatus(422);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'invoice_image' => 'expense-invoices/original-invoice.png',
        ]);
        $this->assertSame($activityCount, $this->activityCount('expense.deleted', Expense::class, $expense->id));
    }

    public function test_expense_can_be_voided_without_mutating_financial_fields_and_logs_reason(): void
    {
        [$owner, $data] = $this->scenario();
        $expense = $data['expense'];
        $activityCount = $this->activityCount('expense.voided', Expense::class, $expense->id);

        $this->actingAs($owner)
            ->patch(route('expenses.void', $expense), [
                'void_reason' => 'Duplicate invoice entered by mistake.',
            ])
            ->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();

        $this->assertNotNull($expense->voided_at);
        $this->assertSame($owner->id, $expense->voided_by);
        $this->assertSame('Duplicate invoice entered by mistake.', $expense->void_reason);
        $this->assertSame('250.00', number_format((float) $expense->amount, 2, '.', ''));
        $this->assertSame('2026-06-10', $expense->expense_date->toDateString());
        $this->assertSame('expense-invoices/original.png', $expense->invoice_image);
        $this->assertSame('Historical safety expense.', $expense->notes);
        $this->assertSame($activityCount + 1, $this->activityCount('expense.voided', Expense::class, $expense->id));
        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'action' => 'expense.voided',
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,
            'description' => 'Duplicate invoice entered by mistake.',
        ]);
    }

    public function test_voided_expense_cannot_be_edited_or_voided_again(): void
    {
        [$owner, $data] = $this->scenario();
        $expense = $data['expense'];

        $this->actingAs($owner)
            ->patch(route('expenses.void', $expense), [
                'void_reason' => 'Original void reason.',
            ])
            ->assertRedirect(route('expenses.show', $expense));

        $this->actingAs($owner)
            ->get(route('expenses.edit', $expense))
            ->assertStatus(422);

        $this->actingAs($owner)
            ->put(route('expenses.update', $expense), $this->expensePayload($expense, [
                'amount' => '999.99',
                'notes' => 'Attempted voided edit.',
            ]))
            ->assertStatus(422);

        $this->actingAs($owner)
            ->patch(route('expenses.void', $expense), [
                'void_reason' => 'Second void reason.',
            ])
            ->assertStatus(422);

        $expense->refresh();

        $this->assertSame('250.00', number_format((float) $expense->amount, 2, '.', ''));
        $this->assertSame('Historical safety expense.', $expense->notes);
        $this->assertSame('Original void reason.', $expense->void_reason);
        $this->assertSame(1, $this->activityCount('expense.voided', Expense::class, $expense->id));
    }

    public function test_expense_lifecycle_filter_defaults_to_active_records(): void
    {
        [$owner, $data] = $this->scenario();
        $voidedExpense = $data['expense'];
        $activeExpense = Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $data['building']->id,
            'unit_id' => $data['unit']->id,
            'category' => 'cleaning',
            'amount' => '125.00',
            'expense_date' => '2026-06-11',
            'invoice_image' => 'expense-invoices/active.png',
            'notes' => 'Active expense remains visible.',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->patch(route('expenses.void', $voidedExpense), [
                'void_reason' => 'Filter test void.',
            ])
            ->assertRedirect(route('expenses.show', $voidedExpense));

        $this->actingAs($owner)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('125.00')
            ->assertDontSee('250.00');

        $this->actingAs($owner)
            ->get(route('expenses.index', ['lifecycle' => 'voided']))
            ->assertOk()
            ->assertSee('250.00')
            ->assertDontSee('125.00');

        $this->actingAs($owner)
            ->get(route('expenses.index', ['lifecycle' => 'all']))
            ->assertOk()
            ->assertSee('250.00')
            ->assertSee('125.00');

        $this->assertDatabaseHas('expenses', ['id' => $activeExpense->id, 'voided_at' => null]);
    }

    public function test_building_with_active_unit_cannot_be_archived(): void
    {
        [$owner] = $this->scenario();
        $building = $this->building($owner, 'Building With Active Unit');
        $this->unit($building, 'ACTIVE-UNIT-1');
        $activityCount = $this->activityCount('building.deleted', Building::class, $building->id);

        $this->actingAs($owner)
            ->delete(route('buildings.destroy', $building))
            ->assertStatus(422);

        $this->assertNotSoftDeleted('buildings', ['id' => $building->id]);
        $this->assertSame($activityCount, $this->activityCount('building.deleted', Building::class, $building->id));
    }

    public function test_building_with_only_soft_deleted_unit_cannot_be_archived(): void
    {
        [$owner] = $this->scenario();
        $building = $this->building($owner, 'Building With Soft Deleted Unit');
        $unit = $this->unit($building, 'SOFT-DELETED-UNIT-1');
        $unit->delete();

        $this->assertSame(0, $building->units()->count());
        $this->assertSame(1, $building->units()->withTrashed()->count());

        $activityCount = $this->activityCount('building.deleted', Building::class, $building->id);

        $this->actingAs($owner)
            ->delete(route('buildings.destroy', $building))
            ->assertStatus(422);

        $this->assertNotSoftDeleted('buildings', ['id' => $building->id]);
        $this->assertSame($activityCount, $this->activityCount('building.deleted', Building::class, $building->id));
    }

    public function test_building_with_expense_but_no_units_cannot_be_archived(): void
    {
        [$owner] = $this->scenario();
        $building = $this->building($owner, 'Building With Expense Only');

        Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $building->id,
            'unit_id' => null,
            'category' => 'maintenance',
            'amount' => '250.00',
            'expense_date' => '2026-06-10',
            'invoice_image' => 'expense-invoices/building-expense.png',
            'notes' => 'Building-level expense.',
            'created_by' => $owner->id,
        ]);

        $this->assertSame(0, $building->units()->withTrashed()->count());

        $activityCount = $this->activityCount('building.deleted', Building::class, $building->id);

        $this->actingAs($owner)
            ->delete(route('buildings.destroy', $building))
            ->assertStatus(422);

        $this->assertNotSoftDeleted('buildings', ['id' => $building->id]);
        $this->assertSame($activityCount, $this->activityCount('building.deleted', Building::class, $building->id));
    }

    public function test_completely_unused_building_can_be_soft_deleted_and_logs_exact_action(): void
    {
        [$owner] = $this->scenario();
        $building = $this->building($owner, 'Completely Unused Building');

        $this->assertSame(0, $building->units()->withTrashed()->count());
        $this->assertSame(0, $building->expenses()->count());

        $this->actingAs($owner)
            ->delete(route('buildings.destroy', $building))
            ->assertRedirect(route('buildings.index'));

        $this->assertSoftDeleted('buildings', ['id' => $building->id]);
        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'action' => 'building.deleted',
            'subject_type' => Building::class,
            'subject_id' => $building->id,
        ]);
    }

    public function test_unit_with_contract_but_no_expense_cannot_be_archived(): void
    {
        [$owner, $data] = $this->scenario();
        $unit = $this->unit($data['building'], 'UNIT-WITH-CONTRACT-ONLY');
        $tenant = $this->tenant($owner, 'Unit Contract Tenant');

        Contract::create([
            'organization_id' => $owner->organization_id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'UNIT-CONTRACT-ONLY-001',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'rent_amount' => '1000.00',
            'payment_frequency' => 'monthly',
            'deposit_amount' => '0.00',
            'status' => 'active',
            'notes' => 'Contract-only dependency.',
        ]);

        $this->assertSame(1, $unit->contracts()->count());
        $this->assertSame(0, $unit->expenses()->count());

        $activityCount = $this->activityCount('unit.deleted', Unit::class, $unit->id);

        $this->actingAs($owner)
            ->delete(route('units.destroy', $unit))
            ->assertStatus(422);

        $this->assertNotSoftDeleted('units', ['id' => $unit->id]);
        $this->assertSame($activityCount, $this->activityCount('unit.deleted', Unit::class, $unit->id));
    }

    public function test_unit_with_expense_but_no_contract_cannot_be_archived(): void
    {
        [$owner, $data] = $this->scenario();
        $unit = $this->unit($data['building'], 'UNIT-WITH-EXPENSE-ONLY');

        Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $data['building']->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => '175.00',
            'expense_date' => '2026-07-10',
            'invoice_image' => 'expense-invoices/unit-expense.png',
            'notes' => 'Unit-level expense.',
            'created_by' => $owner->id,
        ]);

        $this->assertSame(0, $unit->contracts()->count());
        $this->assertSame(1, $unit->expenses()->count());

        $activityCount = $this->activityCount('unit.deleted', Unit::class, $unit->id);

        $this->actingAs($owner)
            ->delete(route('units.destroy', $unit))
            ->assertStatus(422);

        $this->assertNotSoftDeleted('units', ['id' => $unit->id]);
        $this->assertSame($activityCount, $this->activityCount('unit.deleted', Unit::class, $unit->id));
    }

    public function test_completely_unused_unit_can_be_soft_deleted_and_logs_exact_action(): void
    {
        [$owner, $data] = $this->scenario();
        $unit = $this->unit($data['building'], 'COMPLETELY-UNUSED-UNIT');

        $this->assertSame(0, $unit->contracts()->count());
        $this->assertSame(0, $unit->expenses()->count());

        $this->actingAs($owner)
            ->delete(route('units.destroy', $unit))
            ->assertRedirect(route('units.index'));

        $this->assertSoftDeleted('units', ['id' => $unit->id]);
        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'action' => 'unit.deleted',
            'subject_type' => Unit::class,
            'subject_id' => $unit->id,
        ]);
    }

    public function test_existing_positive_payment_amount_submitted_unchanged_is_accepted(): void
    {
        [$owner, $data] = $this->scenario();
        $payment = $this->recordedPayment($data['contract'], '500.50', 'partial');

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '500.50',
                'payment_date' => '2026-06-02',
                'payment_method' => 'cash',
                'notes' => 'Equal payment amount accepted.',
            ]))
            ->assertRedirect(route('payments.show', $payment));

        $payment->refresh();

        $this->assertSameMinorUnits('500.50', $payment->amount_paid);
        $this->assertSame('partial', $payment->status);
        $this->assertSame('2026-06-02', $payment->payment_date->toDateString());
        $this->assertSame('cash', $payment->payment_method);
        $this->assertSame('Equal payment amount accepted.', $payment->notes);
        $this->assertSame($owner->id, $payment->created_by);
    }

    public function test_existing_positive_payment_amount_increased_by_one_minor_unit_is_accepted(): void
    {
        [$owner, $data] = $this->scenario();
        $payment = $this->recordedPayment($data['contract'], '500.50', 'partial');

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '500.51',
                'payment_date' => '2026-06-03',
                'payment_method' => 'bank_transfer',
                'notes' => 'Increased payment amount accepted.',
            ]))
            ->assertRedirect(route('payments.show', $payment));

        $payment->refresh();

        $this->assertSameMinorUnits('500.51', $payment->amount_paid);
        $this->assertSame('partial', $payment->status);
        $this->assertSame('2026-06-03', $payment->payment_date->toDateString());
        $this->assertSame('bank_transfer', $payment->payment_method);
        $this->assertSame('Increased payment amount accepted.', $payment->notes);
        $this->assertSame($owner->id, $payment->created_by);
    }

    public function test_paid_amount_decreased_by_one_minor_unit_is_rejected(): void
    {
        [$owner, $data] = $this->scenario();
        $payment = $this->recordedPayment($data['contract'], '1000.00', 'paid', 'payment-proofs/original-paid.png');

        $activityCount = $this->activityCount('payment.recorded', Payment::class, $payment->id);

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '999.99',
                'payment_date' => '2026-06-04',
                'payment_method' => 'cash',
                'notes' => 'Rejected paid decrease.',
            ]))
            ->assertStatus(422);

        $payment->refresh();

        $this->assertSameMinorUnits('1000.00', $payment->amount_paid);
        $this->assertSame('paid', $payment->status);
        $this->assertSame('payment-proofs/original-paid.png', $payment->proof_image);
        $this->assertSame($activityCount, $this->activityCount('payment.recorded', Payment::class, $payment->id));
    }

    public function test_partial_amount_decreased_by_one_minor_unit_is_rejected(): void
    {
        [$owner, $data] = $this->scenario();
        $payment = $this->recordedPayment($data['contract'], '500.50', 'partial');

        $activityCount = $this->activityCount('payment.recorded', Payment::class, $payment->id);

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '500.49',
                'payment_date' => '2026-06-04',
                'payment_method' => 'cash',
                'notes' => 'Rejected partial decrease.',
            ]))
            ->assertStatus(422);

        $payment->refresh();

        $this->assertSameMinorUnits('500.50', $payment->amount_paid);
        $this->assertSame('partial', $payment->status);
        $this->assertSame($activityCount, $this->activityCount('payment.recorded', Payment::class, $payment->id));
    }

    public function test_positive_payment_amount_reduced_to_zero_is_rejected(): void
    {
        [$owner, $data] = $this->scenario();
        $payment = $this->recordedPayment($data['contract'], '500.50', 'partial');

        $activityCount = $this->activityCount('payment.recorded', Payment::class, $payment->id);

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '0.00',
                'payment_date' => '2026-06-04',
                'payment_method' => null,
                'notes' => 'Rejected zero reduction.',
            ]))
            ->assertStatus(422);

        $payment->refresh();

        $this->assertSameMinorUnits('500.50', $payment->amount_paid);
        $this->assertSame('partial', $payment->status);
        $this->assertSame($activityCount, $this->activityCount('payment.recorded', Payment::class, $payment->id));
    }

    #[DataProvider('authorizedPaymentRecorderRoles')]
    public function test_payment_decrease_guard_applies_to_every_authorized_payment_recorder(Role $role): void
    {
        [$owner, $data] = $this->scenario();
        $actor = $role === Role::Owner
            ? $owner
            : $this->user($owner->organization, $role, $role->value.'-recorder@example.com');

        $payment = $this->recordedPayment($data['contract'], '500.50', 'partial');
        $activityCount = $this->activityCount('payment.recorded', Payment::class, $payment->id);

        $this->actingAs($actor)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '500.49',
                'payment_date' => '2026-06-05',
                'payment_method' => 'cash',
                'notes' => 'Authorized recorder decrease rejected.',
            ]))
            ->assertStatus(422);

        $payment->refresh();

        $this->assertSameMinorUnits('500.50', $payment->amount_paid);
        $this->assertSame('partial', $payment->status);
        $this->assertSame($activityCount, $this->activityCount('payment.recorded', Payment::class, $payment->id));
    }

    public static function authorizedPaymentRecorderRoles(): array
    {
        return [
            'owner' => [Role::Owner],
            'manager' => [Role::Manager],
            'accountant' => [Role::Accountant],
            'caretaker' => [Role::Caretaker],
        ];
    }

    public function test_rejected_payment_decrease_does_not_store_replacement_upload(): void
    {
        $disk = config('filesystems.default', 'local');
        Storage::fake($disk);

        [$owner, $data] = $this->scenario();
        $payment = $this->recordedPayment($data['contract'], '1000.00', 'paid', 'payment-proofs/original.png');
        Storage::disk($disk)->put('payment-proofs/original.png', 'original');

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '999.99',
                'proof_image' => $this->fakePngUpload('replacement.png'),
            ]))
            ->assertStatus(422);

        Storage::disk($disk)->assertExists('payment-proofs/original.png');
        $this->assertSame(['payment-proofs/original.png'], Storage::disk($disk)->files('payment-proofs'));

        $payment->refresh();

        $this->assertSame('payment-proofs/original.png', $payment->proof_image);
        $this->assertSameMinorUnits('1000.00', $payment->amount_paid);
    }

    public function test_cross_organization_attempts_remain_forbidden(): void
    {
        [$ownerA] = $this->scenario('Historical Safety Org A', 'owner-a@example.com');
        [, $dataB] = $this->scenario('Historical Safety Org B', 'owner-b@example.com');

        $this->actingAs($ownerA)->delete(route('contracts.destroy', $dataB['contract']))->assertForbidden();
        $this->actingAs($ownerA)->delete(route('tenants.destroy', $dataB['tenant']))->assertForbidden();
        $this->actingAs($ownerA)->delete(route('expenses.destroy', $dataB['expense']))->assertForbidden();
        $this->actingAs($ownerA)->delete(route('buildings.destroy', $dataB['building']))->assertForbidden();
        $this->actingAs($ownerA)->delete(route('units.destroy', $dataB['unit']))->assertForbidden();

        $payment = $dataB['contract']->payments()->firstOrFail();
        $this->actingAs($ownerA)
            ->put(route('payments.update', $payment), $this->paymentPayload($payment, [
                'amount_paid' => '100.00',
            ]))
            ->assertForbidden();
    }

    public function test_manager_accountant_and_caretaker_cannot_bypass_owner_only_delete_policies(): void
    {
        [$owner, $data] = $this->scenario();
        $manager = $this->user($owner->organization, Role::Manager, 'manager-delete@example.com');
        $accountant = $this->user($owner->organization, Role::Accountant, 'accountant-delete@example.com');
        $caretaker = $this->user($owner->organization, Role::Caretaker, 'caretaker-delete@example.com');

        foreach ([$manager, $accountant, $caretaker] as $actor) {
            $this->actingAs($actor)->delete(route('buildings.destroy', $data['building']))->assertForbidden();
            $this->actingAs($actor)->delete(route('units.destroy', $data['unit']))->assertForbidden();
            $this->actingAs($actor)->delete(route('tenants.destroy', $data['tenant']))->assertForbidden();
            $this->actingAs($actor)->delete(route('contracts.destroy', $data['contract']))->assertForbidden();
            $this->actingAs($actor)->delete(route('expenses.destroy', $data['expense']))->assertForbidden();
        }
    }

    public function test_repeated_blocked_contract_deletion_is_idempotent(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $data['contract'];
        $paymentCount = $contract->payments()->count();
        $activityCount = $this->activityCount('contract.deleted', Contract::class, $contract->id);

        $this->actingAs($owner)->delete(route('contracts.destroy', $contract))->assertStatus(422);
        $this->actingAs($owner)->delete(route('contracts.destroy', $contract))->assertStatus(422);

        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
        $this->assertSame($paymentCount, $contract->payments()->count());
        $this->assertSame($activityCount, $this->activityCount('contract.deleted', Contract::class, $contract->id));
    }

    private function scenario(string $organizationName = 'Historical Safety Org', string $ownerEmail = 'owner@example.com'): array
    {
        $organization = Organization::create(['name' => $organizationName]);
        $owner = $this->user($organization, Role::Owner, $ownerEmail);

        $building = $this->building($owner, $organizationName.' Building');
        $unit = $this->unit($building, '101');
        $tenant = $this->tenant($owner, $organizationName.' Tenant');

        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => $organizationName.'-C-001',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'rent_amount' => '1000.00',
            'payment_frequency' => 'monthly',
            'deposit_amount' => '0.00',
            'status' => 'active',
            'notes' => 'Historical safety contract.',
        ]);

        Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-01',
            'amount_due' => '1000.00',
            'amount_paid' => '0.00',
            'payment_date' => null,
            'status' => 'pending',
            'payment_method' => null,
            'proof_image' => null,
            'notes' => null,
            'created_by' => null,
        ]);

        $expense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => '250.00',
            'expense_date' => '2026-06-10',
            'invoice_image' => 'expense-invoices/original.png',
            'notes' => 'Historical safety expense.',
            'created_by' => $owner->id,
        ]);

        return [$owner, compact('organization', 'building', 'unit', 'tenant', 'contract', 'expense')];
    }

    private function user(Organization $organization, Role $role, string $email): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role->value).' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function building(User $owner, string $name): Building
    {
        return Building::create([
            'organization_id' => $owner->organization_id,
            'name' => $name,
            'location' => 'Abu Dhabi',
            'description' => $name.' description.',
        ]);
    }

    private function unit(Building $building, string $unitNumber): Unit
    {
        return Unit::create([
            'building_id' => $building->id,
            'unit_number' => $unitNumber,
            'type' => 'apartment',
            'size' => '90.00',
            'rooms' => 2,
            'status' => 'vacant',
            'rent_amount' => '1000.00',
            'notes' => $unitNumber.' notes.',
        ]);
    }

    private function tenant(User $owner, string $name): Tenant
    {
        return Tenant::create([
            'organization_id' => $owner->organization_id,
            'full_name' => $name,
            'phone' => '0500000000',
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.com',
            'id_number' => strtoupper(str_replace(' ', '-', $name)).'-ID',
            'nationality' => 'UAE',
            'notes' => $name.' notes.',
        ]);
    }

    private function recordedPayment(Contract $contract, string $amountPaid, string $status, ?string $proofImage = null): Payment
    {
        $payment = $contract->payments()->firstOrFail();

        $payment->update([
            'amount_due' => '1000.00',
            'amount_paid' => $amountPaid,
            'payment_date' => '2026-06-01',
            'status' => $status,
            'payment_method' => 'cash',
            'proof_image' => $proofImage,
            'notes' => 'Original recorded payment.',
        ]);

        return $payment->refresh();
    }

    private function paymentPayload(Payment $payment, array $overrides = []): array
    {
        return $overrides + [
            'amount_paid' => (string) $payment->amount_paid,
            'payment_date' => optional($payment->payment_date)->toDateString(),
            'payment_method' => $payment->payment_method,
            'notes' => $payment->notes,
        ];
    }

    private function expensePayload(Expense $expense, array $overrides = []): array
    {
        return $overrides + [
            'building_id' => $expense->building_id,
            'unit_id' => $expense->unit_id,
            'category' => $expense->category,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date->toDateString(),
            'notes' => $expense->notes,
        ];
    }

    private function activityCount(string $action, string $subjectType, int $subjectId): int
    {
        return ActivityLog::where([
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
        ])->count();
    }

    private function assertSameMinorUnits(string $expected, mixed $actual): void
    {
        $this->assertSame($this->decimalToMinorUnits($expected), $this->decimalToMinorUnits((string) $actual));
    }

    private function decimalToMinorUnits(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', trim($value), 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }
}
