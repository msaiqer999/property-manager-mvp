<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_pages_render_english_system_text_without_changing_stored_values(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $expense = $this->localizedExpense($owner, [
            'building' => 'Expense Localization Tower',
            'unit' => 'EXP-101',
            'category' => 'maintenance',
            'amount' => 2345.67,
            'date' => '2026-06-12',
            'notes' => 'Expense localization note stays unchanged.',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Expenses')
            ->assertSee('Add expense')
            ->assertSee('data-mobile-expenses-list', false)
            ->assertSee('View');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('Add expense')
            ->assertSee('Building')
            ->assertSee('Unit')
            ->assertSee('Category')
            ->assertSee('Maintenance')
            ->assertSee('Amount')
            ->assertSee('Date')
            ->assertSee('Invoice')
            ->assertSee('Choose the building this expense belongs to.')
            ->assertSee('No specific unit')
            ->assertSee('Optional. Select a unit only when the expense belongs to a specific unit.')
            ->assertSee('Optional image attachment. Storage and download behavior stay private.')
            ->assertSee('Notes')
            ->assertSee('Save')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="invoice_image"', false)
            ->assertSee('value="maintenance"', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('Edit expense')
            ->assertSee('value="maintenance" selected', false)
            ->assertSee('value="2345.67"', false)
            ->assertSee('value="2026-06-12"', false);

        $expense->update(['invoice_image' => 'expense-invoices/localization-invoice.png']);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertSee('Expense')
            ->assertSee('Edit')
            ->assertSee('Expense details')
            ->assertSee('Building: Expense Localization Tower')
            ->assertSeeHtml('Unit: <span dir="ltr">EXP-101</span>')
            ->assertSee('Category: Maintenance')
            ->assertSeeHtml('Amount: <span dir="ltr">2,345.67</span>')
            ->assertSeeHtml('Date: <span dir="ltr">2026-06-12</span>')
            ->assertSee('Notes: Expense localization note stays unchanged.')
            ->assertSee('Invoice attachment')
            ->assertSee(route('expenses.invoice', $expense, absolute: false))
            ->assertDontSee('expense-invoices/localization-invoice.png');

        $freshExpense = $expense->fresh()->load('building', 'unit');
        $this->assertSame('maintenance', $freshExpense->category);
        $this->assertSame('Expense Localization Tower', $freshExpense->building->name);
        $this->assertSame('EXP-101', $freshExpense->unit->unit_number);
        $this->assertSame('2345.67', number_format((float) $freshExpense->amount, 2, '.', ''));
        $this->assertSame('2026-06-12', $freshExpense->expense_date->toDateString());
        $this->assertSame('Expense localization note stays unchanged.', $freshExpense->notes);
    }

    public function test_expense_pages_render_arabic_with_ltr_isolation_and_preserved_database_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $expense = $this->localizedExpense($owner, [
            'building' => 'Arabic Expense Building',
            'unit' => 'AR-EXP-202',
            'category' => 'security',
            'amount' => 7654.32,
            'date' => '2026-06-15',
            'notes' => 'Arabic expense note remains database content.',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('المصروفات')
            ->assertSee('إضافة مصروف')
            ->assertSee('عرض');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('إضافة مصروف')
            ->assertSee('المبنى')
            ->assertSee('الوحدة')
            ->assertSee('الفئة')
            ->assertSee('أمن')
            ->assertSee('المبلغ')
            ->assertSee('التاريخ')
            ->assertSee('الفاتورة')
            ->assertSee('ملاحظات')
            ->assertSee('حفظ')
            ->assertSee('value="security"', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('تعديل المصروف')
            ->assertSee('value="security" selected', false)
            ->assertSee('value="7654.32"', false)
            ->assertSee('value="2026-06-15"', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertSee('المصروف')
            ->assertSee('تعديل')
            ->assertSee('المبنى: Arabic Expense Building')
            ->assertSeeHtml('الوحدة: <span dir="ltr">AR-EXP-202</span>')
            ->assertSee('الفئة: أمن')
            ->assertSeeHtml('المبلغ: <span dir="ltr">7,654.32</span>')
            ->assertSeeHtml('التاريخ: <span dir="ltr">2026-06-15</span>')
            ->assertSee('ملاحظات: Arabic expense note remains database content.');

        $freshExpense = $expense->fresh()->load('building', 'unit');
        $this->assertSame('security', $freshExpense->category);
        $this->assertSame('Arabic Expense Building', $freshExpense->building->name);
        $this->assertSame('AR-EXP-202', $freshExpense->unit->unit_number);
        $this->assertSame('7654.32', number_format((float) $freshExpense->amount, 2, '.', ''));
        $this->assertSame('2026-06-15', $freshExpense->expense_date->toDateString());
        $this->assertSame('Arabic expense note remains database content.', $freshExpense->notes);
    }

    public function test_expense_index_uses_operational_translations_for_new_locales(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        foreach ([
            'bn' => 'ltr',
            'ur' => 'rtl',
            'hi' => 'ltr',
        ] as $locale => $direction) {
            app()->setLocale($locale);

            $this->actingAs($owner)
                ->withSession(['locale' => $locale])
                ->get(route('expenses.index'))
                ->assertOk()
                ->assertSee('<html lang="'.$locale.'" dir="'.$direction.'">', false)
                ->assertSee('data-mobile-expenses-list', false)
                ->assertSee(__('expenses.title'))
                ->assertSee(__('expenses.add'))
                ->assertSee(__('expenses.form.building'))
                ->assertSee(__('expenses.form.unit'))
                ->assertSee(__('expenses.form.category'))
                ->assertSee(__('expenses.show.status'))
                ->assertSee(__('expenses.filters.all_buildings'))
                ->assertSee(__('expenses.filters.all_units'))
                ->assertSee(__('expenses.filters.all_categories'))
                ->assertSee(__('expenses.show.date'))
                ->assertSee(__('expenses.show.amount'))
                ->assertSee(__('expenses.show.action'))
                ->assertDontSee('>Expenses<', false)
                ->assertDontSee('>Add expense<', false)
                ->assertDontSee('>Building<', false)
                ->assertDontSee('>Unit<', false)
                ->assertDontSee('>Category<', false)
                ->assertDontSee('>Status<', false)
                ->assertDontSee('>Active<', false)
                ->assertDontSee('>Date<', false)
                ->assertDontSee('>Amount<', false)
                ->assertDontSee('>Action<', false)
                ->assertDontSee('All buildings')
                ->assertDontSee('All units')
                ->assertDontSee('All categories')
                ->assertDontSee('No expenses found.')
                ->assertDontSee('expenses.title')
                ->assertDontSee('expenses.filters.all_buildings')
                ->assertDontSee('expenses.show.status');
        }
    }

    public function test_expense_routes_authorization_and_organization_isolation_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $expense = Expense::firstOrFail();
        $otherExpense = $this->otherOrganizationExpense();

        $this->assertSame('/expenses', route('expenses.index', absolute: false));
        $this->assertSame('/expenses/create', route('expenses.create', absolute: false));
        $this->assertSame("/expenses/{$expense->id}", route('expenses.show', $expense, absolute: false));
        $this->assertSame("/expenses/{$expense->id}/edit", route('expenses.edit', $expense, absolute: false));

        $this->actingAs($owner)->get(route('expenses.index'))->assertOk();
        $this->actingAs($owner)->get(route('expenses.show', $otherExpense))->assertForbidden();
        $this->actingAs($owner)->get(route('expenses.edit', $otherExpense))->assertForbidden();
        $this->actingAs($owner)->put(route('expenses.update', $otherExpense), $this->expensePayload($otherExpense))->assertForbidden();

        $this->actingAs($manager)->get(route('expenses.index'))->assertOk();
        $this->actingAs($manager)->get(route('expenses.create'))->assertOk();
        $this->actingAs($manager)->get(route('expenses.edit', $expense))->assertOk();
        $this->actingAs($manager)->delete(route('expenses.destroy', $expense))->assertForbidden();

        $this->actingAs($accountant)->get(route('expenses.index'))->assertOk();
        $this->actingAs($accountant)->get(route('expenses.index'))->assertDontSee('Add expense');
        $this->actingAs($accountant)->get(route('expenses.create'))->assertForbidden();
        $this->actingAs($accountant)->get(route('expenses.edit', $expense))->assertForbidden();

        $this->actingAs($caretaker)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($caretaker)->get(route('expenses.show', $expense))->assertForbidden();
    }

    public function test_category_values_and_form_contract_remain_stable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('expenses.create'));

        $response->assertOk()
            ->assertSee('method="post"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="building_id"', false)
            ->assertSee('name="unit_id"', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="amount"', false)
            ->assertSee('name="expense_date"', false)
            ->assertSee('name="invoice_image"', false)
            ->assertSee('name="notes"', false);

        foreach (['maintenance', 'electricity', 'water', 'cleaning', 'security', 'management', 'other'] as $category) {
            $response->assertSee('value="'.$category.'"', false);
        }
    }

    public function test_expense_unit_must_belong_to_selected_building_and_filters_stay_scoped(): void
    {
        $organization = Organization::create(['name' => 'Expense Unit Scope Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Expense Unit Scope Owner',
            'email' => 'expense-unit-scope-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $buildingA = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Expense Scope Building A',
            'location' => 'Riyadh',
        ]);
        $buildingB = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Expense Scope Building B',
            'location' => 'Riyadh',
        ]);
        $unitA = Unit::create([
            'building_id' => $buildingA->id,
            'unit_number' => 'EXP-SCOPE-A',
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => 1000,
        ]);
        $unitB = Unit::create([
            'building_id' => $buildingB->id,
            'unit_number' => 'EXP-SCOPE-B',
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => 1000,
        ]);

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'building_id' => $buildingA->id,
                'unit_id' => $unitB->id,
                'category' => 'maintenance',
                'amount' => 100,
                'expense_date' => '2026-06-20',
                'notes' => 'Mismatched unit should fail.',
            ])
            ->assertSessionHasErrors('unit_id');

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'building_id' => $buildingA->id,
                'unit_id' => $unitA->id,
                'category' => 'maintenance',
                'amount' => 100,
                'expense_date' => '2026-06-20',
                'notes' => 'Building A visible expense.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'organization_id' => $organization->id,
            'building_id' => $buildingA->id,
            'unit_id' => $unitA->id,
            'notes' => 'Building A visible expense.',
        ]);

        $expenseA = Expense::where('notes', 'Building A visible expense.')->firstOrFail();

        Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $buildingB->id,
            'unit_id' => $unitB->id,
            'category' => 'security',
            'amount' => 200,
            'expense_date' => '2026-06-21',
            'notes' => 'Building B hidden expense.',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('expenses.show', $expenseA))
            ->assertOk()
            ->assertSee('Expense Scope Building A')
            ->assertSeeHtml('Unit: <span dir="ltr">EXP-SCOPE-A</span>')
            ->assertSeeHtml('Amount: <span dir="ltr">100.00</span>');

        $this->actingAs($owner)
            ->get(route('expenses.index', ['building_id' => $buildingA->id]))
            ->assertOk()
            ->assertSee($expenseA->building->name)
            ->assertSee('EXP-SCOPE-A')
            ->assertSee('data-building-id="'.$buildingA->id.'"', false)
            ->assertDontSee('200.00')
            ->assertDontSee('Expense Scope Building B</td>', false);

        $this->actingAs($owner)
            ->get(route('expenses.index', [
                'building_id' => $buildingA->id,
                'unit_id' => $unitB->id,
            ]))
            ->assertForbidden();

        $otherExpense = $this->otherOrganizationExpense();

        $this->actingAs($owner)
            ->get(route('expenses.index', ['unit_id' => $otherExpense->unit_id]))
            ->assertForbidden();
    }

    public function test_active_expense_appears_with_lifecycle_and_unit_only_filters(): void
    {
        $organization = Organization::create(['name' => 'Expense Lifecycle Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Expense Lifecycle Owner',
            'email' => 'expense-lifecycle-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Expense Lifecycle Building',
            'location' => 'Riyadh',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'EXP-ACTIVE-101',
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => 1000,
        ]);
        Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => 250,
            'expense_date' => '2026-06-27',
            'notes' => 'Active lifecycle unit-only expense.',
            'created_by' => $owner->id,
        ]);
        $voidedExpense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => 999,
            'expense_date' => '2026-06-27',
            'notes' => 'Voided lifecycle unit-only expense.',
            'created_by' => $owner->id,
        ]);
        $voidedExpense->forceFill([
            'voided_at' => now(),
            'voided_by' => $owner->id,
            'void_reason' => 'Exclude from active filter.',
        ])->saveQuietly();

        $this->actingAs($owner)
            ->get(route('expenses.index', [
                'unit_id' => $unit->id,
                'lifecycle' => 'active',
            ]))
            ->assertOk()
            ->assertSee('EXP-ACTIVE-101')
            ->assertSee('250.00')
            ->assertDontSee('999.00');

        $this->actingAs($owner)
            ->get(route('expenses.index', [
                'unit_id' => $unit->id,
                'lifecycle' => 'all',
            ]))
            ->assertOk()
            ->assertSee('EXP-ACTIVE-101')
            ->assertSee('250.00')
            ->assertSee('999.00');
    }

    private function localizedExpense(User $owner, array $values): Expense
    {
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => $values['building'],
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $values['unit'],
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => $values['amount'],
        ]);

        return Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => $values['category'],
            'amount' => $values['amount'],
            'expense_date' => $values['date'],
            'notes' => $values['notes'],
            'created_by' => $owner->id,
        ])->load('building', 'unit');
    }

    private function otherOrganizationExpense(): Expense
    {
        $organization = Organization::create(['name' => 'Expense Localization Other Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Other Expense Owner',
            'email' => 'other-expense-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Other Expense Building',
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'OTHER-EXP-404',
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => 7700,
        ]);

        return Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'security',
            'amount' => 7700,
            'expense_date' => '2026-06-20',
            'notes' => 'Other organization expense note.',
            'created_by' => $owner->id,
        ]);
    }

    private function expensePayload(Expense $expense): array
    {
        return [
            'building_id' => $expense->building_id,
            'unit_id' => $expense->unit_id,
            'category' => $expense->category,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date->toDateString(),
            'notes' => $expense->notes,
        ];
    }
}
