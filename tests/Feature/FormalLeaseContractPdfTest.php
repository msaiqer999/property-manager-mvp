<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FormalLeaseContractPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_pdf_renders_as_formal_english_lease_agreement(): void
    {
        [$owner, $contract] = $this->formalLeaseScenario();

        $response = $this->withSession(['locale' => 'en'])
            ->actingAs($owner)
            ->get(route('contracts.pdf', $contract));

        $this->assertPdfResponse($response, "contract-{$contract->contract_number}.pdf");

        $html = $this->renderContractHtml($contract, 'en');

        $this->assertStringContainsString('Lease Agreement', $html);
        $this->assertStringContainsString(__('contracts.pdf.title_alt', [], 'en'), $html);
        $this->assertStringContainsString($contract->contract_number, $html);
        $this->assertStringContainsString($contract->organization->name, $html);
        $this->assertStringContainsString($contract->tenant->full_name, $html);
        $this->assertStringContainsString($contract->tenant->phone, $html);
        $this->assertStringContainsString($contract->tenant->email, $html);
        $this->assertStringContainsString($contract->tenant->id_number, $html);
        $this->assertStringContainsString($contract->tenant->nationality, $html);
        $this->assertStringContainsString($contract->unit->building->name, $html);
        $this->assertStringContainsString($contract->unit->building->location, $html);
        $this->assertStringContainsString($contract->unit->unit_number, $html);
        $this->assertStringContainsString(number_format((float) $contract->rent_amount, 2), $html);
        $this->assertStringContainsString(number_format((float) $contract->deposit_amount, 2), $html);
        $this->assertStringContainsString('Monthly', $html);
        $this->assertStringContainsString('Payment schedule', $html);
        $this->assertStringContainsString('Payment #', $html);
        $this->assertStringContainsString('Basic terms', $html);
        $this->assertStringContainsString('Lessor / management signature', $html);
        $this->assertStringContainsString('Tenant signature', $html);
        $this->assertStringContainsString('Signature', $html);
        $this->assertStringNotContainsString('Additional Notes', $html);
        $this->assertStringNotContainsString('Printable lease contract draft for review and signature', $html);
        $this->assertStringNotContainsString('This draft is subject to legal review according to applicable regulations.', $html);
        $this->assertStringNotContainsString('Draft', $html);
    }

    public function test_contract_pdf_renders_arabic_without_using_unsupported_locale_translation(): void
    {
        [$owner, $contract] = $this->formalLeaseScenario([
            'organization_name' => $this->u('0645 0646 0634 0623 0629 0020 0627 0644 0627 062e 062a 0628 0627 0631'),
            'building_name' => $this->u('0645 0628 0646 0649 0020 0627 0644 0627 062e 062a 0628 0627 0631'),
            'building_location' => $this->u('0627 0644 0631 064a 0627 0636'),
            'tenant_name' => $this->u('0645 0633 062a 0623 062c 0631 0020 0627 062e 062a 0628 0627 0631'),
            'nationality' => $this->u('0633 0639 0648 062f 064a'),
        ]);

        $response = $this->withSession(['locale' => 'ar'])
            ->actingAs($owner)
            ->get(route('contracts.pdf', $contract));

        $this->assertPdfResponse($response, "contract-{$contract->contract_number}.pdf");

        $html = $this->renderContractHtml($contract, 'ar');

        $this->assertStringContainsString(__('contracts.pdf.title', [], 'ar'), $html);
        $this->assertStringContainsString('Lease Agreement', $html);
        $this->assertStringContainsString($contract->contract_number, $html);
        $this->assertStringContainsString($contract->tenant->full_name, $html);
        $this->assertStringContainsString($contract->unit->building->name, $html);
        $this->assertStringContainsString($contract->unit->unit_number, $html);
        $this->assertStringContainsString(__('contracts.pdf.basic_terms', [], 'ar'), $html);
        $this->assertStringContainsString(__('contracts.pdf.signatures', [], 'ar'), $html);
        $this->assertStringContainsString(__('contracts.pdf.signature', [], 'ar'), $html);
        $this->assertStringNotContainsString('ملاحظات إضافية', $html);
        $this->assertStringNotContainsString(__('contracts.pdf.subtitle', [], 'ar'), $html);
        $this->assertStringNotContainsString(__('contracts.pdf.legal_review_notice', [], 'ar'), $html);
        $this->assertStringNotContainsString('contracts.pdf.', $html);
    }

    public function test_contract_pdf_falls_back_to_english_for_new_operational_locales(): void
    {
        [, $contract] = $this->formalLeaseScenario();

        foreach (['bn', 'ur', 'hi'] as $locale) {
            $html = $this->renderContractHtml($contract, $locale);

            $this->assertStringContainsString('<html lang="en" dir="ltr">', $html);
            $this->assertStringContainsString('Lease Agreement', $html);
            $this->assertStringNotContainsString('contracts.pdf.', $html);
        }
    }

    public function test_caretaker_cannot_export_contract_pdf_but_receipt_pdf_still_renders(): void
    {
        [$owner, $contract, $payment] = $this->formalLeaseScenario();
        $caretaker = User::create([
            'organization_id' => $owner->organization_id,
            'name' => 'PDF Caretaker',
            'email' => 'formal-lease-caretaker@example.test',
            'password' => 'password',
            'role' => 'caretaker',
        ]);

        $this->actingAs($caretaker)->get(route('contracts.pdf', $contract))->assertForbidden();

        $response = $this->actingAs($owner)->get(route('payments.receipt', $payment));

        $this->assertPdfResponse($response, "payment-receipt-{$payment->id}.pdf");
    }

    private function formalLeaseScenario(array $overrides = []): array
    {
        $organization = Organization::create([
            'name' => $overrides['organization_name'] ?? 'Formal Lease Organization',
        ]);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Formal Lease Owner',
            'email' => 'formal-lease-owner-'.Str::random(8).'@example.test',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => $overrides['building_name'] ?? 'Formal Lease Building',
            'location' => $overrides['building_location'] ?? 'Riyadh',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'FL-101',
            'type' => 'apartment',
            'size' => 120,
            'rooms' => 3,
            'status' => 'rented',
            'rent_amount' => 4500,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => $overrides['tenant_name'] ?? 'Formal Lease Tenant',
            'phone' => '+966500000001',
            'email' => 'formal-tenant@example.test',
            'id_number' => 'ID-FORMAL-001',
            'nationality' => $overrides['nationality'] ?? 'Saudi',
        ]);
        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'FL-2026-0001',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'rent_amount' => 4500,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 4500,
            'status' => 'active',
            'notes' => 'Signed during closed beta readiness review.',
        ]);
        $payment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-07-01',
            'amount_due' => 4500,
            'amount_paid' => 4500,
            'payment_date' => '2026-07-01',
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
        ]);
        Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-08-01',
            'amount_due' => 4500,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);

        return [$owner, $contract->load('organization', 'tenant', 'unit.building', 'payments'), $payment];
    }

    private function renderContractHtml(Contract $contract, string $locale): string
    {
        app()->setLocale($locale);

        return view('pdf.contract', ['contract' => $contract->fresh()->load('organization', 'tenant', 'unit.building', 'payments')])->render();
    }

    private function assertPdfResponse(TestResponse $response, string $filename): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString($filename, (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }

    private function u(string $hex): string
    {
        return collect(explode(' ', $hex))
            ->map(fn (string $code) => mb_chr(hexdec($code), 'UTF-8'))
            ->implode('');
    }
}
