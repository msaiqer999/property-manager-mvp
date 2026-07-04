<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_index_form_and_show_render_english_system_text_without_changing_stored_values(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $payment = $this->localizedPayment($owner, [
            'building' => 'Payment Localization Tower',
            'unit' => 'PAY-101',
            'tenant' => 'Payment Localization Tenant',
            'contract' => 'PAY-EN-001',
            'amount_due' => 1234.56,
            'amount_paid' => 1234.56,
            'status' => 'paid',
            'payment_method' => 'cash',
            'payment_date' => '2026-06-10',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Payments')
            ->assertSee('Payments are generated from contracts and can be recorded when rent is collected.')
            ->assertSee('All statuses')
            ->assertSee('Due date')
            ->assertSee('data-mobile-payments-list', false)
            ->assertSee('data-payment-mobile-card', false)
            ->assertSee('data-payment-action', false)
            ->assertDontSee('min-w-[68rem]', false)
            ->assertSee('View receipt')
            ->assertSee('Paid');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.edit', $payment))
            ->assertOk()
            ->assertSee('Record payment')
            ->assertSee('data-payment-summary', false)
            ->assertSee('data-payment-record-form', false)
            ->assertSee('Payment summary')
            ->assertSee('Payment Localization Tenant')
            ->assertSee('Payment Localization Tower')
            ->assertSee('PAY-101')
            ->assertSee('PAY-EN-001')
            ->assertSee('Paid')
            ->assertSee('Amount paid')
            ->assertSee('Payment date')
            ->assertSee('Method')
            ->assertSee('Cash')
            ->assertSee('Proof image')
            ->assertSee('Notes')
            ->assertSee('Due <span dir="ltr">2026-06-01</span> / <span dir="ltr">1,234.56</span>', false)
            ->assertSee('value="cash"', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Payment')
            ->assertSee('data-payment-action', false)
            ->assertSee('Download receipt PDF')
            ->assertSee('Due')
            ->assertSee('2026-06-01')
            ->assertSee('Amount due')
            ->assertSee('1,234.56')
            ->assertSee('Paid')
            ->assertDontSee('Follow up')
            ->assertDontSee('data-payment-reminder', false);

        $freshPayment = $payment->fresh()->load('contract.tenant', 'contract.unit');
        $this->assertSame('Payment Localization Tenant', $freshPayment->contract->tenant->full_name);
        $this->assertSame('PAY-101', $freshPayment->contract->unit->unit_number);
        $this->assertSame('PAY-EN-001', $freshPayment->contract->contract_number);
        $this->assertSame('1234.56', number_format((float) $freshPayment->amount_due, 2, '.', ''));
        $this->assertSame('paid', $freshPayment->status);
        $this->assertSame('cash', $freshPayment->payment_method);
    }

    public function test_payment_pages_render_arabic_with_ltr_isolation_for_numbers_and_identifiers(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $payment = $this->localizedPayment($owner, [
            'building' => 'Arabic Payment Building',
            'unit' => 'AR-PAY-202',
            'tenant' => 'Arabic Payment Tenant',
            'contract' => 'PAY-AR-2026-002',
            'amount_due' => 9876.50,
            'amount_paid' => 5000.25,
            'status' => 'partial',
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-06-15',
        ]);

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('payments.title'))
            ->assertSee(__('payments.all_statuses'))
            ->assertSee(__('payments.columns.due_date'))
            ->assertSee('data-mobile-payments-list', false)
            ->assertSee(__('payments.statuses.partial_overdue'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.edit', $payment))
            ->assertOk()
            ->assertSee('data-payment-summary', false)
            ->assertSee('data-payment-record-form', false)
            ->assertSee(__('payments.record_payment'))
            ->assertSee(__('payments.form.summary'))
            ->assertSee(__('payments.form.building'))
            ->assertSee(__('payments.columns.contract'))
            ->assertSee(__('payments.show.status'))
            ->assertSee(__('payments.form.amount_paid'))
            ->assertSee(__('payments.form.payment_date'))
            ->assertSee(__('payments.form.method'))
            ->assertSee(__('payments.methods.bank_transfer'))
            ->assertSee('value="bank_transfer"', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee(__('payments.payment'))
            ->assertSee('data-payment-action', false)
            ->assertSee(__('payments.download_receipt_pdf'))
            ->assertSee('2026-06-01')
            ->assertSee('9,876.50')
            ->assertSee('5,000.25')
            ->assertSee(__('payments.statuses.partial_overdue'));

        $freshPayment = $payment->fresh()->load('contract.tenant', 'contract.unit');
        $this->assertSame('Arabic Payment Tenant', $freshPayment->contract->tenant->full_name);
        $this->assertSame('AR-PAY-202', $freshPayment->contract->unit->unit_number);
        $this->assertSame('PAY-AR-2026-002', $freshPayment->contract->contract_number);
        $this->assertSame('9876.50', number_format((float) $freshPayment->amount_due, 2, '.', ''));
        $this->assertSame('partial', $freshPayment->status);
        $this->assertSame('bank_transfer', $freshPayment->payment_method);
    }

    public function test_mobile_payment_cards_show_record_for_pending_and_receipt_for_paid(): void
    {
        $organization = Organization::create(['name' => 'Mobile Payment Experience Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Mobile Payment Owner',
            'email' => 'mobile-payment-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $pendingPayment = $this->localizedPayment($owner, [
            'building' => 'Pending Mobile Payment Building',
            'unit' => 'MOB-PEND-101',
            'tenant' => 'Pending Mobile Tenant',
            'contract' => 'MOB-PEND-001',
            'amount_due' => 2100,
            'amount_paid' => 0,
            'status' => 'pending',
            'payment_method' => null,
            'payment_date' => null,
        ]);
        $paidPayment = $this->localizedPayment($owner, [
            'building' => 'Paid Mobile Payment Building',
            'unit' => 'MOB-PAID-202',
            'tenant' => 'Paid Mobile Tenant',
            'contract' => 'MOB-PAID-002',
            'amount_due' => 2200,
            'amount_paid' => 2200,
            'status' => 'paid',
            'payment_method' => 'cash',
            'payment_date' => '2026-06-11',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('data-mobile-payments-list', false)
            ->assertSee('data-payment-mobile-card', false)
            ->assertSee('Pending Mobile Tenant')
            ->assertSee('Paid Mobile Tenant')
            ->assertSee('href="'.route('payments.edit', $pendingPayment).'"', false)
            ->assertSee('href="'.route('payments.show', $paidPayment).'"', false)
            ->assertSee('Record payment')
            ->assertSee('View receipt');
    }

    public function test_overdue_follow_up_filter_summary_and_reminder_are_rendered_without_affecting_paid_payments(): void
    {
        $organization = Organization::create(['name' => 'Payment Follow Up Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Payment Follow Up Owner',
            'email' => 'payment-follow-up-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $overduePayment = $this->localizedPayment($owner, [
            'building' => 'Follow Up Building',
            'unit' => 'FU-101',
            'tenant' => 'Follow Up Tenant',
            'contract' => 'FU-2026-001',
            'amount_due' => 1500,
            'amount_paid' => 250,
            'due_date' => '2026-06-01',
            'status' => 'partial',
            'payment_method' => 'cash',
            'payment_date' => '2026-06-04',
        ]);
        $paidPayment = $this->localizedPayment($owner, [
            'building' => 'Paid Follow Up Building',
            'unit' => 'FU-PAID-202',
            'tenant' => 'Paid Follow Up Tenant',
            'contract' => 'FU-PAID-002',
            'amount_due' => 900,
            'amount_paid' => 900,
            'due_date' => '2026-06-01',
            'status' => 'paid',
            'payment_method' => 'cash',
            'payment_date' => '2026-06-04',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.index', ['overdue' => 1]))
            ->assertOk()
            ->assertSee('Follow up')
            ->assertSee('Partially paid, balance overdue')
            ->assertSee('Follow Up Tenant')
            ->assertSee('Follow Up Building')
            ->assertSee('FU-101')
            ->assertSee('1,250.00')
            ->assertSee('href="'.route('payments.show', $overduePayment).'"', false)
            ->assertSee('href="'.route('payments.edit', $overduePayment).'"', false)
            ->assertSee('Record payment')
            ->assertDontSee('Paid Follow Up Tenant');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.show', $overduePayment))
            ->assertOk()
            ->assertSee('data-overdue-payment-summary', false)
            ->assertSee('Overdue payment summary')
            ->assertSee('Tenant phone')
            ->assertSee('0500000000')
            ->assertSee('Follow Up Tenant')
            ->assertSee('Follow Up Building')
            ->assertSee('FU-101')
            ->assertSee('FU-2026-001')
            ->assertSee('2026-06-01')
            ->assertSee('1,500.00')
            ->assertSee('250.00')
            ->assertSee('1,250.00')
            ->assertSee('Reminder message')
            ->assertSee('Copy reminder')
            ->assertSee('data-reminder-message', false)
            ->assertSee('Hello Follow Up Tenant, this is a reminder that rent for unit FU-101 was due on 2026-06-01. The remaining amount is 1,250.00. Please arrange payment when possible. Thank you.');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.show', $paidPayment))
            ->assertOk()
            ->assertDontSee('data-overdue-payment-summary', false)
            ->assertDontSee('data-payment-reminder', false)
            ->assertDontSee('Copy reminder');

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.show', $overduePayment))
            ->assertOk()
            ->assertSee(__('payments.follow_up'))
            ->assertSee(__('payments.overdue_summary.title'))
            ->assertSee(__('payments.reminder.title'))
            ->assertSee(__('payments.reminder.copy'));

        app()->setLocale('en');
    }

    public function test_payment_recording_page_defaults_context_success_message_and_receipt_action(): void
    {
        Carbon::setTestNow('2026-07-03');

        try {
            $organization = Organization::create(['name' => 'Payment Recording UX Organization']);
            $owner = User::create([
                'organization_id' => $organization->id,
                'name' => 'Payment Recording UX Owner',
                'email' => 'payment-recording-ux-owner@example.com',
                'password' => 'password',
                'role' => 'owner',
            ]);
            $payment = $this->localizedPayment($owner, [
                'building' => 'Payment Recording UX Building',
                'unit' => 'REC-101',
                'tenant' => 'Payment Recording UX Tenant',
                'contract' => 'REC-2026-001',
                'amount_due' => 1800,
                'amount_paid' => 0,
                'due_date' => '2026-07-15',
                'status' => 'pending',
                'payment_method' => null,
                'payment_date' => null,
            ]);

            $this->actingAs($owner)
                ->withSession(['locale' => 'en'])
                ->get(route('payments.edit', $payment))
                ->assertOk()
                ->assertSee('data-payment-summary', false)
                ->assertSee('Payment Recording UX Tenant')
                ->assertSee('Payment Recording UX Building')
                ->assertSee('REC-101')
                ->assertSee('REC-2026-001')
                ->assertSee('2026-07-15')
                ->assertSee('1,800.00')
                ->assertSee('Pending')
                ->assertSee('name="amount_paid"', false)
                ->assertSee('value="1800', false)
                ->assertSee('name="payment_date"', false)
                ->assertSee('value="2026-07-03"', false);

            $response = $this->actingAs($owner)
                ->withSession(['locale' => 'en'])
                ->put(route('payments.update', $payment), [
                    'amount_paid' => 1800,
                    'payment_date' => '2026-07-03',
                    'payment_method' => 'cash',
                    'notes' => 'Collected at the office.',
                ]);

            $response->assertRedirect(route('payments.show', $payment))
                ->assertSessionHas('status', __('payments.recorded_success'));

            $freshPayment = $payment->fresh();

            $this->assertSame('paid', $freshPayment->status);
            $this->assertSame('1800.00', number_format((float) $freshPayment->amount_paid, 2, '.', ''));
            $this->assertSame('2026-07-03', $freshPayment->payment_date->toDateString());
            $this->assertSame('cash', $freshPayment->payment_method);
            $this->assertSame($owner->id, $freshPayment->created_by);

            $this->actingAs($owner)
                ->withSession(['locale' => 'en', 'status' => __('payments.recorded_success')])
                ->get(route('payments.show', $payment))
                ->assertOk()
                ->assertSee(__('payments.recorded_success'))
                ->assertSee('Download receipt PDF')
                ->assertSee('href="'.route('payments.receipt', $payment).'"', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_payment_routes_authorization_organization_isolation_and_receipt_pdf_route_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $manager = User::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Payment Manager',
            'email' => 'payment-manager@example.com',
            'password' => 'password',
            'role' => 'manager',
        ]);
        $accountant = User::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Payment Accountant',
            'email' => 'payment-accountant@example.com',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $payment = Payment::with('contract')->where('status', '!=', 'paid')->firstOrFail();
        $otherPayment = $this->otherOrganizationPayment();

        $this->assertSame('/payments', route('payments.index', absolute: false));
        $this->assertSame("/payments/{$payment->id}", route('payments.show', $payment, absolute: false));
        $this->assertSame("/payments/{$payment->id}/edit", route('payments.edit', $payment, absolute: false));
        $this->assertSame("/payments/{$payment->id}/receipt", route('payments.receipt', $payment, absolute: false));

        $this->actingAs($owner)
            ->from(route('payments.show', $payment))
            ->post(route('locale.switch', 'ar'))
            ->assertRedirect(route('payments.show', $payment));

        $this->actingAs($owner)->get(route('payments.show', $otherPayment))->assertForbidden();
        $this->actingAs($owner)->get(route('payments.edit', $otherPayment))->assertForbidden();
        $this->actingAs($owner)->put(route('payments.update', $otherPayment), [
            'amount_paid' => 100,
            'payment_date' => '2026-06-10',
            'payment_method' => 'cash',
        ])->assertForbidden();
        $this->actingAs($owner)->get(route('payments.receipt', $otherPayment))->assertForbidden();

        $this->actingAs($owner)->get(route('payments.edit', $payment))->assertOk();
        $this->actingAs($manager)->get(route('payments.edit', $payment))->assertOk();
        $this->actingAs($accountant)->get(route('payments.edit', $payment))->assertOk();
        $this->actingAs($caretaker)->get(route('payments.index'))->assertOk();
        $this->actingAs($caretaker)->get(route('payments.edit', $payment))->assertOk();
        $this->actingAs($caretaker)->get(route('payments.index'))
            ->assertOk()
            ->assertSee('data-mobile-payments-list', false)
            ->assertDontSee('Reports')
            ->assertDontSee('Contracts')
            ->assertDontSee('Expenses')
            ->assertDontSee('Users')
            ->assertDontSee('Activity')
            ->assertDontSee('Net profit');
    }

    public function test_status_filter_preserves_internal_status_values_while_displaying_translated_labels(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $payment = $this->localizedPayment($owner, [
            'building' => 'Pending Payment Building',
            'unit' => 'PEND-303',
            'tenant' => 'Pending Payment Tenant',
            'contract' => 'PAY-PENDING-003',
            'amount_due' => 3210,
            'amount_paid' => 0,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'pending',
            'payment_method' => null,
            'payment_date' => null,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('value="pending" selected', false)
            ->assertSee(__('payments.statuses.pending'))
            ->assertSee($payment->contract->tenant->full_name)
            ->assertSee('<bdi dir="ltr">'.$payment->contract->contract_number.'</bdi>', false)
            ->assertDontSee('payments.statuses.pending');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_payment_status_clarity_receipt_actions_and_partial_receipt_amounts(): void
    {
        $organization = Organization::create(['name' => 'Payment Clarity Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Payment Clarity Owner',
            'email' => 'payment-clarity-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $paidPayment = $this->localizedPayment($owner, [
            'building' => 'Paid Clarity Building',
            'unit' => 'CLR-PAID-101',
            'tenant' => 'Paid Clarity Tenant',
            'contract' => 'CLR-PAID-001',
            'amount_due' => 1000,
            'amount_paid' => 1000,
            'status' => 'paid',
            'payment_method' => 'cash',
            'payment_date' => '2026-06-05',
        ]);
        $partialPayment = $this->localizedPayment($owner, [
            'building' => 'Partial Clarity Building',
            'unit' => 'CLR-PART-202',
            'tenant' => 'Partial Clarity Tenant',
            'contract' => 'CLR-PART-002',
            'amount_due' => 1000,
            'amount_paid' => 400,
            'status' => 'overdue',
            'payment_method' => 'cash',
            'payment_date' => '2026-06-06',
        ]);
        $unpaidPayment = $this->localizedPayment($owner, [
            'building' => 'Unpaid Clarity Building',
            'unit' => 'CLR-UNPAID-303',
            'tenant' => 'Unpaid Clarity Tenant',
            'contract' => 'CLR-UNPAID-003',
            'amount_due' => 1200,
            'amount_paid' => 0,
            'status' => 'overdue',
            'payment_method' => null,
            'payment_date' => null,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Paid')
            ->assertSee('Partially paid, balance overdue')
            ->assertSee('Overdue')
            ->assertSee('href="'.route('payments.show', $paidPayment).'"', false)
            ->assertSee('href="'.route('payments.show', $partialPayment).'"', false)
            ->assertSee('href="'.route('payments.edit', $partialPayment).'"', false)
            ->assertSee('href="'.route('payments.edit', $unpaidPayment).'"', false)
            ->assertSee('Follow up')
            ->assertSee('href="'.route('payments.show', $unpaidPayment).'"', false)
            ->assertDontSee('payments.statuses.partial_overdue');

        app()->setLocale('en');
        $receiptHtml = view('pdf.receipt', ['payment' => $partialPayment->fresh()->load('contract.tenant', 'contract.unit.building')])->render();

        $this->assertStringContainsString('Partially paid', $receiptHtml);
        $this->assertStringContainsString('Amount due', $receiptHtml);
        $this->assertStringContainsString('1,000.00', $receiptHtml);
        $this->assertStringContainsString('Paid', $receiptHtml);
        $this->assertStringContainsString('400.00', $receiptHtml);
        $this->assertStringContainsString('Remaining amount', $receiptHtml);
        $this->assertStringContainsString('600.00', $receiptHtml);
        $this->assertStringContainsString('This receipt confirms the received amount only. A remaining balance is still due.', $receiptHtml);
        $this->assertStringNotContainsString('Overdue', $receiptHtml);
        $this->assertStringNotContainsString('payments.', $receiptHtml);
    }

    private function localizedPayment(User $owner, array $values): Payment
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
            'status' => 'rented',
            'rent_amount' => $values['amount_due'],
        ]);

        $tenant = Tenant::create([
            'organization_id' => $owner->organization_id,
            'full_name' => $values['tenant'],
            'phone' => '0500000000',
        ]);

        $contract = Contract::create([
            'organization_id' => $owner->organization_id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => $values['contract'],
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'rent_amount' => $values['amount_due'],
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        return Payment::create([
            'organization_id' => $owner->organization_id,
            'contract_id' => $contract->id,
            'due_date' => $values['due_date'] ?? '2026-06-01',
            'amount_due' => $values['amount_due'],
            'amount_paid' => $values['amount_paid'],
            'payment_date' => $values['payment_date'],
            'status' => $values['status'],
            'payment_method' => $values['payment_method'],
            'created_by' => $owner->id,
        ])->load('contract.tenant', 'contract.unit.building');
    }

    private function otherOrganizationPayment(): Payment
    {
        $organization = Organization::create(['name' => 'Payment Localization Other Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Other Payment Owner',
            'email' => 'other-payment-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Other Payment Building',
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'OTHER-PAY-404',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 7700,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Other Payment Tenant',
            'phone' => '0500000044',
        ]);

        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'OTHER-PAY-004',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'rent_amount' => 7700,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        return Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-01',
            'amount_due' => 7700,
            'amount_paid' => 7700,
            'payment_date' => '2026-06-10',
            'status' => 'paid',
            'payment_method' => 'cash',
            'created_by' => $owner->id,
        ]);
    }
}
