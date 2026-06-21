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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseVoidLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_void_expense_without_mutating_original_financial_record(): void
    {
        [$owner, $data] = $this->scenario();
        $expense = $data['voidableExpense'];

        $this->assertSame('/expenses/'.$expense->id.'/void', route('expenses.void', $expense, absolute: false));

        $this->actingAs($owner)
            ->patch(route('expenses.void', $expense), [
                'void_reason' => "  Duplicate invoice\nentered by mistake.  ",
            ])
            ->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();

        $this->assertNotNull($expense->voided_at);
        $this->assertSame($owner->id, $expense->voided_by);
        $this->assertSame('Duplicate invoice entered by mistake.', $expense->void_reason);
        $this->assertSame('250.00', number_format((float) $expense->amount, 2, '.', ''));
        $this->assertSame('2026-06-10', $expense->expense_date->toDateString());
        $this->assertSame('expense-invoices/original.png', $expense->invoice_image);
        $this->assertSame('Original expense notes remain unchanged.', $expense->notes);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'action' => 'expense.voided',
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,
            'description' => 'Duplicate invoice entered by mistake.',
        ]);
    }

    public function test_voided_expense_is_read_only_and_cannot_be_voided_twice(): void
    {
        [$owner, $data] = $this->scenario();
        $expense = $data['voidableExpense'];

        $this->actingAs($owner)
            ->patch(route('expenses.void', $expense), [
                'void_reason' => 'Approved void reason.',
            ])
            ->assertRedirect(route('expenses.show', $expense));

        $this->actingAs($owner)->get(route('expenses.edit', $expense))->assertStatus(422);

        $this->actingAs($owner)
            ->put(route('expenses.update', $expense), $this->expensePayload($expense, [
                'amount' => '999.99',
                'notes' => 'Attempted mutation after void.',
            ]))
            ->assertStatus(422);

        $this->actingAs($owner)
            ->patch(route('expenses.void', $expense), [
                'void_reason' => 'Second void reason.',
            ])
            ->assertStatus(422);

        $expense->refresh();

        $this->assertSame('250.00', number_format((float) $expense->amount, 2, '.', ''));
        $this->assertSame('Original expense notes remain unchanged.', $expense->notes);
        $this->assertSame('Approved void reason.', $expense->void_reason);
        $this->assertSame(1, ActivityLog::where('action', 'expense.voided')->where('subject_id', $expense->id)->count());
    }

    public function test_only_owner_can_void_own_organization_expense(): void
    {
        [$owner, $data] = $this->scenario();
        $manager = $data['manager'];
        $accountant = $data['accountant'];
        $caretaker = $data['caretaker'];
        $expense = $data['voidableExpense'];
        $otherExpense = $this->otherOrganizationExpense();

        $this->actingAs($manager)
            ->patch(route('expenses.void', $expense), ['void_reason' => 'Manager attempt.'])
            ->assertForbidden();

        $this->actingAs($accountant)
            ->patch(route('expenses.void', $expense), ['void_reason' => 'Accountant attempt.'])
            ->assertForbidden();

        $this->actingAs($caretaker)
            ->patch(route('expenses.void', $expense), ['void_reason' => 'Caretaker attempt.'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('expenses.void', $otherExpense), ['void_reason' => 'Cross organization attempt.'])
            ->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'voided_at' => null]);
        $this->assertDatabaseHas('expenses', ['id' => $otherExpense->id, 'voided_at' => null]);
    }

    public function test_expense_screens_and_filters_separate_active_voided_and_all_records(): void
    {
        [$owner, $data] = $this->scenario();
        $activeExpense = $data['activeExpense'];
        $voidedExpense = $this->voidExpense($owner, $data['voidableExpense']);

        $this->actingAs($owner)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('125.00')
            ->assertSee('Active')
            ->assertDontSee('250.00');

        $this->actingAs($owner)
            ->get(route('expenses.index', ['lifecycle' => 'voided']))
            ->assertOk()
            ->assertSee('250.00')
            ->assertSee('Voided')
            ->assertDontSee('125.00');

        $this->actingAs($owner)
            ->get(route('expenses.index', ['lifecycle' => 'all']))
            ->assertOk()
            ->assertSee('125.00')
            ->assertSee('250.00');

        $this->actingAs($owner)
            ->get(route('expenses.show', $activeExpense))
            ->assertOk()
            ->assertSee('Void financial record')
            ->assertSee('Void reason');

        $this->actingAs($owner)
            ->get(route('expenses.show', $voidedExpense))
            ->assertOk()
            ->assertSee('Voided')
            ->assertSee('Lifecycle filter test void.')
            ->assertSee($owner->name)
            ->assertDontSee('Void financial record')
            ->assertDontSee('href="'.route('expenses.edit', $voidedExpense).'"', false);
    }

    public function test_voided_expenses_are_excluded_from_dashboard_and_reports(): void
    {
        [$owner, $data] = $this->scenario();
        $this->voidExpense($owner, $data['voidableExpense']);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('125.00')
            ->assertDontSee('250.00');

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('1,000.00')
            ->assertSee('125.00')
            ->assertSee('875.00')
            ->assertDontSee('625.00');
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Expense Void Lifecycle Org']);
        $owner = $this->user($organization, 'owner', 'void-owner@example.com');
        $manager = $this->user($organization, 'manager', 'void-manager@example.com');
        $accountant = $this->user($organization, 'accountant', 'void-accountant@example.com');
        $caretaker = $this->user($organization, 'caretaker', 'void-caretaker@example.com');
        $building = $this->building($organization, 'Expense Void Building');
        $unit = $this->unit($building, 'VOID-101');
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Expense Void Tenant',
            'phone' => '0500000000',
        ]);
        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'EXP-VOID-001',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => '1000.00',
            'payment_frequency' => 'monthly',
            'deposit_amount' => '0.00',
            'status' => 'active',
        ]);

        Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-01',
            'amount_due' => '1000.00',
            'amount_paid' => '1000.00',
            'payment_date' => now()->startOfMonth()->toDateString(),
            'status' => 'paid',
            'payment_method' => 'cash',
            'created_by' => $owner->id,
        ]);

        $activeExpense = $this->expense($owner, $building, $unit, [
            'amount' => '125.00',
            'category' => 'cleaning',
            'date' => now()->startOfMonth()->toDateString(),
            'invoice' => 'expense-invoices/active.png',
            'notes' => 'Active expense remains in reports.',
        ]);

        $voidableExpense = $this->expense($owner, $building, $unit, [
            'amount' => '250.00',
            'category' => 'maintenance',
            'date' => '2026-06-10',
            'invoice' => 'expense-invoices/original.png',
            'notes' => 'Original expense notes remain unchanged.',
        ]);

        return [$owner, compact('organization', 'manager', 'accountant', 'caretaker', 'building', 'unit', 'activeExpense', 'voidableExpense')];
    }

    private function user(Organization $organization, string $role, string $email): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' Expense Void User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function building(Organization $organization, string $name): Building
    {
        return Building::create([
            'organization_id' => $organization->id,
            'name' => $name,
            'location' => 'Abu Dhabi',
        ]);
    }

    private function unit(Building $building, string $unitNumber): Unit
    {
        return Unit::create([
            'building_id' => $building->id,
            'unit_number' => $unitNumber,
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => '1000.00',
        ]);
    }

    private function expense(User $owner, Building $building, Unit $unit, array $values): Expense
    {
        return Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => $values['category'],
            'amount' => $values['amount'],
            'expense_date' => $values['date'],
            'invoice_image' => $values['invoice'],
            'notes' => $values['notes'],
            'created_by' => $owner->id,
        ]);
    }

    private function voidExpense(User $owner, Expense $expense): Expense
    {
        $this->actingAs($owner)
            ->patch(route('expenses.void', $expense), [
                'void_reason' => 'Lifecycle filter test void.',
            ])
            ->assertRedirect(route('expenses.show', $expense));

        return $expense->refresh();
    }

    private function otherOrganizationExpense(): Expense
    {
        $organization = Organization::create(['name' => 'Other Expense Void Org']);
        $owner = $this->user($organization, 'owner', 'other-void-owner@example.com');
        $building = $this->building($organization, 'Other Expense Void Building');
        $unit = $this->unit($building, 'OTHER-VOID-101');

        return $this->expense($owner, $building, $unit, [
            'amount' => '300.00',
            'category' => 'security',
            'date' => now()->startOfMonth()->toDateString(),
            'invoice' => 'expense-invoices/other.png',
            'notes' => 'Other organization expense.',
        ]);
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
}
