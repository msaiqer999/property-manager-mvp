<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentFollowUp;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_follow_up_note_and_history_renders(): void
    {
        [$owner, $payment] = $this->paymentScenario('owner');

        $this->actingAs($owner)
            ->post(route('payment-follow-ups.store', $payment), [
                'type' => 'note',
                'note' => 'Tenant asked for two more days.',
                'organization_id' => 999999,
                'user_id' => 999999,
            ])
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('status', __('payments.follow_ups.saved'));

        $this->assertDatabaseHas('payment_follow_ups', [
            'organization_id' => $payment->organization_id,
            'payment_id' => $payment->id,
            'user_id' => $owner->id,
            'type' => PaymentFollowUp::TYPE_NOTE,
            'note' => 'Tenant asked for two more days.',
        ]);

        $this->assertDatabaseMissing('payment_follow_ups', [
            'organization_id' => 999999,
            'user_id' => 999999,
        ]);

        $this->actingAs($owner)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Follow-up history')
            ->assertSee('Tenant asked for two more days.')
            ->assertSee($owner->name)
            ->assertDontSee('payments.follow_ups');
    }

    public function test_current_payment_recording_roles_can_add_follow_ups(): void
    {
        [$owner, $payment] = $this->paymentScenario('owner');

        foreach (['manager', 'accountant', 'caretaker'] as $role) {
            $user = User::create([
                'organization_id' => $owner->organization_id,
                'name' => "Follow Up {$role}",
                'email' => "follow-up-{$role}@example.com",
                'password' => 'password',
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->post(route('payment-follow-ups.store', $payment), [
                    'type' => 'reminder_logged',
                    'note' => "Reminder logged by {$role}.",
                ])
                ->assertRedirect(route('payments.show', $payment));

            $this->assertDatabaseHas('payment_follow_ups', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'type' => PaymentFollowUp::TYPE_REMINDER_LOGGED,
            ]);
        }
    }

    public function test_cross_organization_follow_up_creation_is_rejected(): void
    {
        [$owner] = $this->paymentScenario('owner');
        [, $otherPayment] = $this->paymentScenario('owner', 'other');

        $this->actingAs($owner)
            ->post(route('payment-follow-ups.store', $otherPayment), [
                'type' => 'note',
                'note' => 'This should not be accepted.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('payment_follow_ups', [
            'payment_id' => $otherPayment->id,
            'note' => 'This should not be accepted.',
        ]);
    }

    public function test_promise_to_pay_validation_and_index_indicator(): void
    {
        [$owner, $payment] = $this->paymentScenario('owner');

        $this->actingAs($owner)
            ->post(route('payment-follow-ups.store', $payment), [
                'type' => 'promise_to_pay',
                'note' => 'Tenant promised to pay.',
            ])
            ->assertSessionHasErrors('promised_date');

        $this->actingAs($owner)
            ->post(route('payment-follow-ups.store', $payment), [
                'type' => 'promise_to_pay',
                'note' => 'Tenant promised to pay.',
                'promised_date' => '2026-07-15',
                'promised_amount' => 'not-a-number',
            ])
            ->assertSessionHasErrors('promised_amount');

        $this->actingAs($owner)
            ->post(route('payment-follow-ups.store', $payment), [
                'type' => 'promise_to_pay',
                'note' => 'Tenant promised to pay.',
                'promised_date' => '2026-07-15',
                'promised_amount' => '500.00',
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->actingAs($owner)
            ->get(route('payments.index', ['overdue' => 1]))
            ->assertOk()
            ->assertSee('Promise')
            ->assertSee('2026-07-15')
            ->assertDontSee('payments.follow_ups');
    }

    public function test_reminder_logged_and_arabic_labels_render(): void
    {
        [$owner, $payment] = $this->paymentScenario('owner');

        $this->actingAs($owner)
            ->post(route('payment-follow-ups.store', $payment), [
                'type' => 'reminder_logged',
                'note' => 'Reminder was sent manually.',
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee(__('payments.follow_ups.title'))
            ->assertSee(__('payments.follow_ups.types.reminder_logged'))
            ->assertSee(__('payments.follow_ups.log_reminder'))
            ->assertDontSee('payments.follow_ups');
    }

    public function test_guest_cannot_create_follow_up(): void
    {
        [, $payment] = $this->paymentScenario('owner');

        $this->post(route('payment-follow-ups.store', $payment), [
            'type' => 'note',
            'note' => 'Guest note.',
        ])->assertRedirect(route('login'));
    }

    private function paymentScenario(string $role, string $prefix = 'main'): array
    {
        $organization = Organization::create(['name' => "Follow Up {$prefix} Organization"]);
        $user = User::create([
            'organization_id' => $organization->id,
            'name' => "Follow Up {$prefix} User",
            'email' => "follow-up-{$prefix}-{$role}@example.com",
            'password' => 'password',
            'role' => $role,
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => "Follow Up {$prefix} Building",
            'location' => 'Dubai',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => "FU-{$prefix}",
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 1000,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Follow Up {$prefix} Tenant",
            'phone' => '0500000000',
        ]);
        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => "FU-{$prefix}-001",
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);
        $payment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-01',
            'amount_due' => 1000,
            'amount_paid' => 0,
            'status' => 'overdue',
        ]);

        return [$user, $payment];
    }
}
