<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_export_contract_receipt_and_report_pdfs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $contract = Contract::firstOrFail();
        $payment = Payment::where(fn ($query) => $query->where('amount_paid', '>', 0)->orWhereNotNull('payment_date'))->firstOrFail();
        $unrecordedPayment = Payment::where('amount_paid', 0)->whereNull('payment_date')->firstOrFail();

        $this->actingAs($owner)->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('Download contract PDF');

        $contractResponse = $this->actingAs($owner)->get(route('contracts.pdf', $contract));
        $contractResponse->assertOk();
        $this->assertStringContainsString("contract-{$contract->contract_number}.pdf", (string) $contractResponse->headers->get('content-disposition'));

        $this->actingAs($owner)->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Download receipt PDF');

        $this->actingAs($owner)->get(route('payments.show', $unrecordedPayment))
            ->assertOk()
            ->assertDontSee('Download receipt PDF')
            ->assertSee('Record payment');

        $receiptResponse = $this->actingAs($owner)->get(route('payments.receipt', $payment));
        $receiptResponse->assertOk();
        $this->assertStringContainsString("payment-receipt-{$payment->id}.pdf", (string) $receiptResponse->headers->get('content-disposition'));

        $this->actingAs($owner)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Download unit statement PDF')
            ->assertSee('Export building income PDF')
            ->assertSee('Export expenses PDF')
            ->assertSee('Export overdue payments PDF')
            ->assertSee('Export monthly summary PDF')
            ->assertSee('Export net profit PDF');

        foreach (['building-income', 'unit-statement', 'expenses', 'overdue', 'net-profit', 'monthly-summary'] as $type) {
            $response = $this->actingAs($owner)->get(route('reports.pdf', $type));
            $response->assertOk();
            $this->assertStringContainsString($this->expectedReportFilename($type), (string) $response->headers->get('content-disposition'));
        }
    }

    public function test_pdf_routes_remain_organization_scoped_and_role_restricted(): void
    {
        [$ownerA, $ownerB, $caretakerA, $contractB, $paymentB] = $this->twoOrganizationPdfScenario();

        $this->actingAs($ownerA)->get(route('contracts.pdf', $contractB))->assertForbidden();
        $this->actingAs($ownerA)->get(route('payments.receipt', $paymentB))->assertForbidden();
        $this->actingAs($caretakerA)->get(route('reports.pdf', 'net-profit'))->assertForbidden();
        $this->actingAs($ownerB)->get(route('contracts.pdf', $contractB))->assertOk();
        $this->actingAs($ownerB)->get(route('payments.receipt', $paymentB))->assertOk();
    }

    public function test_empty_report_pdf_does_not_error(): void
    {
        $organization = Organization::create(['name' => 'Empty Report Org']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => 'empty-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        foreach (['building-income', 'unit-statement', 'expenses', 'overdue', 'net-profit', 'monthly-summary'] as $type) {
            $this->actingAs($owner)->get(route('reports.pdf', $type))->assertOk();
        }
    }

    private function expectedReportFilename(string $type): string
    {
        $period = now()->format('Y-m');

        return match ($type) {
            'building-income' => "building-income-{$period}.pdf",
            'unit-statement' => "unit-statement-{$period}.pdf",
            'expenses' => "expenses-{$period}.pdf",
            'overdue' => "overdue-payments-{$period}.pdf",
            'net-profit' => "net-profit-{$period}.pdf",
            'monthly-summary' => "monthly-summary-{$period}.pdf",
        };
    }

    private function twoOrganizationPdfScenario(): array
    {
        $organizationA = Organization::create(['name' => 'PDF Org A']);
        $organizationB = Organization::create(['name' => 'PDF Org B']);

        $ownerA = User::create([
            'organization_id' => $organizationA->id,
            'name' => 'Owner A',
            'email' => 'pdf-owner-a@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $caretakerA = User::create([
            'organization_id' => $organizationA->id,
            'name' => 'Caretaker A',
            'email' => 'pdf-caretaker-a@example.com',
            'password' => 'password',
            'role' => 'caretaker',
        ]);
        $ownerB = User::create([
            'organization_id' => $organizationB->id,
            'name' => 'Owner B',
            'email' => 'pdf-owner-b@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $buildingB = Building::create([
            'organization_id' => $organizationB->id,
            'name' => 'PDF Building B',
        ]);
        $unitB = Unit::create([
            'building_id' => $buildingB->id,
            'unit_number' => 'B-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 1000,
        ]);
        $tenantB = Tenant::create([
            'organization_id' => $organizationB->id,
            'full_name' => 'PDF Tenant B',
        ]);
        $contractB = Contract::create([
            'organization_id' => $organizationB->id,
            'unit_id' => $unitB->id,
            'tenant_id' => $tenantB->id,
            'contract_number' => 'PDF-B-001',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
        ]);
        $paymentB = Payment::create([
            'organization_id' => $organizationB->id,
            'contract_id' => $contractB->id,
            'due_date' => now()->startOfMonth()->toDateString(),
            'amount_due' => 1000,
            'amount_paid' => 1000,
            'payment_date' => now()->startOfMonth()->toDateString(),
            'status' => 'paid',
        ]);

        return [$ownerA, $ownerB, $caretakerA, $contractB, $paymentB];
    }
}
