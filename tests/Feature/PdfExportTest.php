<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Payment;
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

        $this->actingAs($owner)
            ->get(route('contracts.pdf', Contract::firstOrFail()))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('payments.receipt', Payment::firstOrFail()))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('reports.pdf', 'monthly-summary'))
            ->assertOk();
    }
}
