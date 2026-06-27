<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\PdfRenderer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    private const REPORT_TYPES = [
        'building-income',
        'unit-statement',
        'expenses',
        'overdue',
        'net-profit',
        'monthly-summary',
    ];

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
        $this->assertPdfResponse($contractResponse, "contract-{$contract->contract_number}.pdf");

        $this->actingAs($owner)->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Download receipt PDF');

        $this->actingAs($owner)->get(route('payments.show', $unrecordedPayment))
            ->assertOk()
            ->assertDontSee('Download receipt PDF')
            ->assertSee('Record payment');

        $receiptResponse = $this->actingAs($owner)->get(route('payments.receipt', $payment));
        $this->assertPdfResponse($receiptResponse, "payment-receipt-{$payment->id}.pdf");

        $this->actingAs($owner)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Download unit statement PDF')
            ->assertSee('Export building income PDF')
            ->assertSee('Export expenses PDF')
            ->assertSee('Export overdue payments PDF')
            ->assertSee('Export monthly summary PDF')
            ->assertSee('Export net profit PDF');

        foreach (self::REPORT_TYPES as $type) {
            $response = $this->actingAs($owner)->get(route('reports.pdf', $type));
            $this->assertPdfResponse($response, $this->expectedReportFilename($type));
        }
    }

    public function test_arabic_contract_pdf_uses_translated_labels_and_values(): void
    {
        [$owner, $contract] = $this->arabicPdfScenario();

        $response = $this->withSession(['locale' => 'ar'])->actingAs($owner)->get(route('contracts.pdf', $contract));
        $this->assertPdfResponse($response, "contract-{$contract->contract_number}.pdf");

        $html = $this->renderContractHtml($contract, 'ar');
        $this->assertStringContainsString('عقد إيجار', $html);
        $this->assertStringContainsString('أحمد سالم التجريبي', $html);
        $this->assertStringContainsString('منشأة المدير العقاري التجريبية', $html);
        $this->assertStringContainsString('شهري', $html);
        $this->assertStringContainsString('نشط', $html);
        $this->assertStringNotContainsString('???', $html);
        $this->assertRawValuesAbsent($html, ['monthly', 'active']);
    }

    public function test_arabic_payment_receipt_pdf_uses_translated_method_and_status(): void
    {
        [$owner, , $payment] = $this->arabicPdfScenario();

        $response = $this->withSession(['locale' => 'ar'])->actingAs($owner)->get(route('payments.receipt', $payment));
        $this->assertPdfResponse($response, "payment-receipt-{$payment->id}.pdf");

        $html = $this->renderReceiptHtml($payment, 'ar');
        $this->assertStringContainsString('إيصال دفع', $html);
        $this->assertStringContainsString('أحمد سالم التجريبي', $html);
        $this->assertStringContainsString((string) $payment->id, $html);
        $this->assertStringContainsString($payment->contract->contract_number, $html);
        $this->assertStringContainsString($payment->contract->unit->unit_number, $html);
        $this->assertStringContainsString($payment->due_date->toDateString(), $html);
        $this->assertStringContainsString($payment->payment_date->toDateString(), $html);
        $this->assertStringContainsString(number_format((float) $payment->amount_due, 2), $html);
        $this->assertStringContainsString(number_format((float) $payment->amount_paid, 2), $html);
        $this->assertStringContainsString('تحويل بنكي', $html);
        $this->assertStringContainsString('مدفوع', $html);
        $this->assertStringNotContainsString('???', $html);
        $this->assertRawValuesAbsent($html, ['bank_transfer', 'paid']);
    }

    public function test_arabic_report_pdfs_cover_every_report_type_without_raw_identifiers(): void
    {
        [$owner] = $this->arabicPdfScenario();

        foreach (self::REPORT_TYPES as $type) {
            $response = $this->withSession(['locale' => 'ar'])->actingAs($owner)->get(route('reports.pdf', $type));
            $this->assertPdfResponse($response, $this->expectedReportFilename($type));

            $html = $this->renderReportHtml($type, 'ar');
            $this->assertStringContainsString(__('reports.types.'.$type), $html);
            $this->assertStringContainsString('منشأة المدير العقاري التجريبية', $html);
            $this->assertStringNotContainsString('???', $html);
            $this->assertRawValuesAbsent($html, array_merge(self::REPORT_TYPES, [
                'monthly',
                'bank_transfer',
                'paid',
                'pending',
                'overdue',
                'voided',
            ]));
        }
    }

    public function test_english_pdfs_render_human_readable_values(): void
    {
        [$owner, $contract, $payment] = $this->arabicPdfScenario();

        $this->assertPdfResponse(
            $this->withSession(['locale' => 'en'])->actingAs($owner)->get(route('contracts.pdf', $contract)),
            "contract-{$contract->contract_number}.pdf"
        );
        $this->assertPdfResponse(
            $this->withSession(['locale' => 'en'])->actingAs($owner)->get(route('payments.receipt', $payment)),
            "payment-receipt-{$payment->id}.pdf"
        );

        $contractHtml = $this->renderContractHtml($contract, 'en');
        $receiptHtml = $this->renderReceiptHtml($payment, 'en');
        $reportHtml = $this->renderReportHtml('monthly-summary', 'en');

        $this->assertStringContainsString('Lease Agreement', $contractHtml);
        $this->assertStringContainsString('Monthly', $contractHtml);
        $this->assertStringContainsString('Active', $contractHtml);
        $this->assertStringContainsString('Payment Receipt', $receiptHtml);
        $this->assertStringContainsString('Bank transfer', $receiptHtml);
        $this->assertStringContainsString('Paid', $receiptHtml);
        $this->assertStringContainsString('Monthly summary', $reportHtml);
        $this->assertRawValuesAbsent($contractHtml.$receiptHtml.$reportHtml, ['monthly', 'bank_transfer']);
    }

    public function test_pdf_translation_keys_cover_all_allowed_enum_values_and_report_types(): void
    {
        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);

            foreach (['monthly', 'quarterly', 'semi_annual', 'annual'] as $value) {
                $this->assertTranslatedValueDiffersFromRaw("contracts.frequencies.{$value}", $value);
            }

            foreach (['active', 'expired', 'terminated'] as $value) {
                $this->assertTranslatedValueDiffersFromRaw("contracts.statuses.{$value}", $value);
            }

            foreach (['cash', 'bank_transfer', 'cheque', 'other'] as $value) {
                $this->assertTranslatedValueDiffersFromRaw("payments.methods.{$value}", $value);
            }

            foreach (['pending', 'paid', 'partial', 'overdue', 'cancelled'] as $value) {
                $this->assertTranslatedValueDiffersFromRaw("payments.statuses.{$value}", $value);
            }

            foreach (['active', 'voided'] as $value) {
                $this->assertTranslatedValueDiffersFromRaw("expenses.lifecycle.{$value}", $value);
            }

            foreach (self::REPORT_TYPES as $value) {
                $this->assertTranslatedValueDiffersFromRaw("reports.types.{$value}", $value);
            }
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

    public function test_pdf_renderer_uses_local_mpdf_defaults_and_dedicated_temp_directory(): void
    {
        $renderer = app(PdfRenderer::class);
        $mpdf = $renderer->mpdf();
        $tempDir = storage_path('framework/cache/mpdf');

        $this->assertDirectoryExists($tempDir);
        $this->assertTrue(is_writable($tempDir));
        $this->assertSame('dejavusans', $mpdf->default_font);
        $this->assertTrue($mpdf->autoScriptToLang);
        $this->assertTrue($mpdf->autoArabic);
        $this->assertArrayHasKey('dejavusans', $mpdf->fontdata);
        $this->assertSame(255, $mpdf->fontdata['dejavusans']['useOTL']);
        $this->assertSame(75, $mpdf->fontdata['dejavusans']['useKashida']);
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

        foreach (self::REPORT_TYPES as $type) {
            $this->assertPdfResponse(
                $this->actingAs($owner)->get(route('reports.pdf', $type)),
                $this->expectedReportFilename($type)
            );
        }
    }

    public function test_expense_report_pdf_uses_formal_layout_and_unit_isolation(): void
    {
        [$owner, , , $expense] = $this->arabicPdfScenario();

        $this->actingAs($owner);
        $html = $this->renderReportHtml('expenses', 'en');

        $this->assertStringContainsString('class="hero"', $html);
        $this->assertStringContainsString('class="summary-label"', $html);
        $this->assertStringContainsString('class="text-end"', $html);
        $this->assertStringContainsString($expense->building->name, $html);
        $this->assertStringContainsString($expense->unit->unit_number, $html);
        $this->assertStringContainsString('<bdi class="ltr">'.$expense->unit->unit_number.'</bdi>', $html);
        $this->assertStringContainsString(number_format((float) $expense->amount, 2), $html);
    }

    private function assertPdfResponse(TestResponse $response, string $filename): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString($filename, (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }

    private function assertRawValuesAbsent(string $html, array $rawValues): void
    {
        foreach ($rawValues as $rawValue) {
            $this->assertStringNotContainsString($rawValue, $html);
        }
    }

    private function assertTranslatedValueDiffersFromRaw(string $key, string $raw): void
    {
        $translation = __($key);

        $this->assertNotSame($key, $translation);
        $this->assertNotSame($raw, $translation);
    }

    private function renderContractHtml(Contract $contract, string $locale): string
    {
        app()->setLocale($locale);

        return view('pdf.contract', ['contract' => $contract->loadMissing('tenant', 'unit.building')])->render();
    }

    private function renderReceiptHtml(Payment $payment, string $locale): string
    {
        app()->setLocale($locale);

        return view('pdf.receipt', ['payment' => $payment->loadMissing('contract.tenant', 'contract.unit.building')])->render();
    }

    private function renderReportHtml(string $type, string $locale): string
    {
        app()->setLocale($locale);
        $controller = app(\App\Http\Controllers\ReportController::class);
        $method = new \ReflectionMethod($controller, 'reportData');
        $method->setAccessible(true);

        return view('pdf.report', $method->invoke($controller, $type) + ['type' => $type])->render();
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

    private function arabicPdfScenario(): array
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();
        $organization = Organization::create(['name' => 'منشأة المدير العقاري التجريبية']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => 'arabic-pdf-owner@example.test',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'منشأة المدير العقاري التجريبية',
            'location' => 'دبي',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'Unit 101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 5000,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'أحمد سالم التجريبي',
            'phone' => '+971500000001',
            'email' => 'tenant.test@example.test',
            'id_number' => 'ID-2026-000001',
        ]);
        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'CN-2026-000001',
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodStart->copy()->addYear()->subDay()->toDateString(),
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 5000,
            'status' => 'active',
            'notes' => '<script>alert("escaped")</script>',
        ]);
        $payment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => $periodStart->toDateString(),
            'amount_due' => 5000,
            'amount_paid' => 5000,
            'payment_date' => $periodStart->toDateString(),
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
        ]);
        Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => $periodEnd->toDateString(),
            'amount_due' => 5000,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'status' => 'overdue',
        ]);
        $expense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => 250,
            'expense_date' => $periodStart->copy()->addDays(2)->toDateString(),
            'notes' => '<strong>escaped</strong>',
            'created_by' => $owner->id,
        ]);

        return [$owner, $contract, $payment, $expense->load('building', 'unit')];
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
