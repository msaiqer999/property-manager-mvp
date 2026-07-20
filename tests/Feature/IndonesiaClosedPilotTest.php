<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Country;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\MoneyFormatter;
use App\Support\PdfRenderer;
use Database\Seeders\GlobalReadinessSeeder;
use Database\Seeders\IndonesiaClosedPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class IndonesiaClosedPilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_indonesia_registration_stores_country_defaults_and_uses_bahasa_locale(): void
    {
        Config::set('app.registration_enabled', true);
        $this->seed(GlobalReadinessSeeder::class);

        $indonesia = Country::where('code', 'ID')->firstOrFail();

        $this->post(route('register'), [
            'organization_name' => 'Pilot Kos Jakarta',
            'country_id' => $indonesia->id,
            'name' => 'Pemilik Kos Jakarta',
            'email' => 'pilot-kos-jakarta@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('dashboard'));

        $organization = Organization::where('name', 'Pilot Kos Jakarta')->firstOrFail();

        $this->assertSame($indonesia->id, $organization->country_id);
        $this->assertSame('IDR', $organization->currency_code);
        $this->assertSame('id', $organization->locale);
        $this->assertSame('Asia/Jakarta', $organization->timezone);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="id" dir="ltr">', false)
            ->assertSee('Dasbor')
            ->assertSee('Mulai kelola properti Anda')
            ->assertDontSee('app.dashboard.title');
    }

    public function test_indonesia_pilot_seed_data_creates_requested_sample_portfolio(): void
    {
        $this->seed(IndonesiaClosedPilotSeeder::class);

        $organization = Organization::where('name', 'Indonesia Closed Pilot Portfolio')->firstOrFail();
        $country = Country::where('code', 'ID')->firstOrFail();

        $this->assertSame($country->id, $organization->country_id);
        $this->assertSame('IDR', $organization->currency_code);
        $this->assertSame('id', $organization->locale);
        $this->assertSame('Asia/Jakarta', $organization->timezone);

        $this->assertBuildingUnitCount('Kos Putri Surabaya', 18);
        $this->assertBuildingUnitCount('Kontrakan Keluarga Malang', 6);
        $this->assertBuildingUnitCount('Ruko Sidoarjo', 4);
        $this->assertBuildingUnitCount('Gudang kecil Gresik', 2);

        $this->assertSame(18, Unit::whereHas('building', fn ($query) => $query->where('name', 'Kos Putri Surabaya'))->where('type', 'kamar')->count());
        $this->assertSame(6, Unit::whereHas('building', fn ($query) => $query->where('name', 'Kontrakan Keluarga Malang'))->where('type', 'kontrakan')->count());
        $this->assertSame(4, Unit::whereHas('building', fn ($query) => $query->where('name', 'Ruko Sidoarjo'))->where('type', 'ruko')->count());
        $this->assertSame(2, Unit::whereHas('building', fn ($query) => $query->where('name', 'Gudang kecil Gresik'))->where('type', 'warehouse')->count());

        $this->assertGreaterThanOrEqual(14, Tenant::where('organization_id', $organization->id)->count());
        $this->assertGreaterThan(0, Payment::where('organization_id', $organization->id)->where('amount_paid', '>', 0)->count());

        foreach (['kos', 'kamar', 'kontrakan', 'ruko', 'apartment', 'shop', 'warehouse'] as $code) {
            $this->assertDatabaseHas('property_types', [
                'country_id' => $country->id,
                'code' => $code,
                'is_active' => true,
            ]);
        }
    }

    public function test_indonesia_dashboard_and_reports_display_idr_currency(): void
    {
        $this->seed(IndonesiaClosedPilotSeeder::class);

        $owner = User::where('email', 'indonesia-owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="id" dir="ltr">', false)
            ->assertSee('IDR ')
            ->assertSee('Terkumpul bulan ini')
            ->assertSee('data-dashboard-with-roadmap', false)
            ->assertSee('data-dashboard-metrics', false)
            ->assertSee('data-dashboard-kpi-card', false)
            ->assertSee('data-dashboard-roadmap', false)
            ->assertSee('Segera hadir')
            ->assertDontSee('lg:grid-cols-[minmax(0,1fr)_16rem]', false)
            ->assertDontSee('xl:grid-cols-[minmax(0,1fr)_17rem]', false)
            ->assertDontSee('AED ');

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('IDR ')
            ->assertDontSee('AED ')
            ->assertDontSee('reports.');
    }

    public function test_indonesia_payments_page_displays_idr_without_cents(): void
    {
        $this->seed(IndonesiaClosedPilotSeeder::class);

        $owner = User::where('email', 'indonesia-owner@example.com')->firstOrFail();
        $payment = Payment::where('organization_id', $owner->organization_id)
            ->where('amount_paid', '>', 0)
            ->orderBy('due_date')
            ->firstOrFail();
        $expectedAmount = MoneyFormatter::forOrganization($owner->organization, $payment->amount_paid)
            .' / '.MoneyFormatter::forOrganization($owner->organization, $payment->amount_due);

        $this->actingAs($owner)
            ->withSession(['locale' => 'id'])
            ->get(route('payments.index', ['status' => $payment->status]))
            ->assertOk()
            ->assertSee('IDR ')
            ->assertSeeHtml('<bdi dir="ltr">'.$expectedAmount.'</bdi>')
            ->assertDontSee(number_format((float) $payment->amount_due, 2))
            ->assertDontSee('AED ');
    }

    public function test_indonesian_buildings_page_labels_and_help_modal_are_localized(): void
    {
        $this->seed(IndonesiaClosedPilotSeeder::class);

        $owner = User::where('email', 'indonesia-owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'id'])
            ->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('<html lang="id" dir="ltr">', false)
            ->assertSee('Properti')
            ->assertSee('Tambah properti')
            ->assertSee('Nama')
            ->assertSee('Lokasi')
            ->assertSee('Aksi')
            ->assertSee('Bantuan properti')
            ->assertSee('Untuk apa halaman ini')
            ->assertSee('Yang dapat Anda lakukan')
            ->assertSee('Langkah berikutnya yang disarankan')
            ->assertSee('Mengerti')
            ->assertSee('Jangan tampilkan lagi')
            ->assertSee('Masukan')
            ->assertSee('Bantuan')
            ->assertDontSee('Buildings')
            ->assertDontSee('Add building')
            ->assertDontSee('Name')
            ->assertDontSee('Location')
            ->assertDontSee('Action')
            ->assertDontSee('Buildings help')
            ->assertDontSee('What this page is for')
            ->assertDontSee('What you can do here')
            ->assertDontSee('Recommended next action')
            ->assertDontSee('Got it')
            ->assertDontSee('Do not show again')
            ->assertDontSee('Feedback')
            ->assertDontSee('Help');

        $this->actingAs($owner)
            ->withSession(['locale' => 'id'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-pilot-guide', false)
            ->assertSee('Panduan beta tertutup')
            ->assertSee('Buka panduan')
            ->assertSee('Masukan')
            ->assertSee('Bantuan')
            ->assertSee('Mengerti')
            ->assertSee('Jangan tampilkan lagi')
            ->assertDontSee('Closed beta guide')
            ->assertDontSee('Open guide')
            ->assertDontSee('Feedback')
            ->assertDontSee('Help');
    }

    public function test_indonesian_units_page_displays_idr_rent_without_cents(): void
    {
        $this->seed(IndonesiaClosedPilotSeeder::class);

        $owner = User::where('email', 'indonesia-owner@example.com')->firstOrFail();
        $kos = Building::where('organization_id', $owner->organization_id)
            ->where('name', 'Kos Putri Surabaya')
            ->firstOrFail();
        $kontrakan = Building::where('organization_id', $owner->organization_id)
            ->where('name', 'Kontrakan Keluarga Malang')
            ->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'id'])
            ->get(route('units.index', ['building_id' => $kos->id]))
            ->assertOk()
            ->assertSee('Kos Putri Surabaya')
            ->assertSeeHtml('<bdi dir="ltr">IDR 850,000</bdi>')
            ->assertDontSee('850,000.00')
            ->assertDontSee('AED ');

        $this->actingAs($owner)
            ->withSession(['locale' => 'id'])
            ->get(route('units.index', ['building_id' => $kontrakan->id]))
            ->assertOk()
            ->assertSee('Kontrakan Keluarga Malang')
            ->assertSeeHtml('<bdi dir="ltr">IDR 1,750,000</bdi>')
            ->assertDontSee('1,750,000.00')
            ->assertDontSee('AED ');
    }

    public function test_indonesian_report_and_pdf_use_telepon_label_and_idr_without_cents(): void
    {
        $this->seed(IndonesiaClosedPilotSeeder::class);

        $owner = User::where('email', 'indonesia-owner@example.com')->firstOrFail();
        $tenant = Tenant::where('organization_id', $owner->organization_id)
            ->where('email', 'tenant-id-01@example.com')
            ->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'id'])
            ->get(route('reports.index', ['tenant_id' => $tenant->id]))
            ->assertOk()
            ->assertSee('Telepon')
            ->assertSee($tenant->phone)
            ->assertSee('IDR 850,000')
            ->assertDontSee('Phone')
            ->assertDontSee('IDR 850,000.00')
            ->assertDontSee('AED ');

        $capturedPdf = null;
        $renderer = \Mockery::mock(PdfRenderer::class);
        $renderer->shouldReceive('download')
            ->once()
            ->andReturnUsing(function (string $view, array $data, string $filename) use (&$capturedPdf) {
                $capturedPdf = compact('view', 'data', 'filename');

                return response('fake '.$filename, 200);
            });
        $this->app->instance(PdfRenderer::class, $renderer);

        $this->actingAs($owner)
            ->withSession(['locale' => 'id'])
            ->get(route('reports.pdf', ['type' => 'unit-statement', 'tenant_id' => $tenant->id]))
            ->assertOk();

        $this->assertIsArray($capturedPdf);

        app()->setLocale('id');
        $html = view($capturedPdf['view'], $capturedPdf['data'])->render();

        $this->assertStringContainsString('Telepon', $html);
        $this->assertStringContainsString($tenant->phone, $html);
        $this->assertStringContainsString('IDR 850,000', $html);
        $this->assertStringNotContainsString('Phone', $html);
        $this->assertStringNotContainsString('IDR 850,000.00', $html);
        $this->assertStringNotContainsString('AED ', $html);
    }

    public function test_indonesia_unit_forms_accept_pilot_property_types_from_reference_data(): void
    {
        $this->seed(GlobalReadinessSeeder::class);

        $country = Country::where('code', 'ID')->firstOrFail();
        $organization = Organization::create([
            'name' => 'Indonesia Unit Type Pilot',
            'country_id' => $country->id,
            'currency_code' => $country->default_currency_code,
            'locale' => $country->default_locale,
            'timezone' => $country->default_timezone,
        ]);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Indonesia Unit Type Owner',
            'email' => 'indonesia-unit-type-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'country_id' => $country->id,
            'currency_code' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'name' => 'Indonesia Type Building',
            'location' => 'Jakarta',
        ]);

        $response = $this->actingAs($owner)->get(route('units.create', ['building_id' => $building->id]));

        foreach (['kos', 'kamar', 'kontrakan', 'ruko', 'apartment', 'shop', 'warehouse'] as $type) {
            $response->assertSee('value="'.$type.'"', false);
        }

        $this->actingAs($owner)
            ->post(route('units.store'), [
                'building_id' => $building->id,
                'unit_number' => 'RUKO-01',
                'type' => 'ruko',
                'status' => 'vacant',
                'rent_amount' => 4500000,
                'rooms' => 1,
                'size' => 70,
                'notes' => 'Ruko pilot Indonesia.',
            ])->assertRedirect();

        $unit = Unit::where('building_id', $building->id)->where('unit_number', 'RUKO-01')->firstOrFail();

        $this->assertSame('ruko', $unit->type);
        $this->assertSame('4500000.00', number_format((float) $unit->rent_amount, 2, '.', ''));

        $this->assertContains('kos', PropertyType::availableForCountry($country)->pluck('code')->all());
    }

    public function test_uae_demo_still_renders_aed_after_indonesia_pilot_changes(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Abu Dhabi Small Properties')
            ->assertSee('AED ')
            ->assertDontSee('IDR ');

        $payment = Payment::where('organization_id', $owner->organization_id)
            ->where('amount_paid', '>', 0)
            ->orderBy('due_date')
            ->firstOrFail();
        $expectedAmount = MoneyFormatter::forOrganization($owner->organization, $payment->amount_paid)
            .' / '.MoneyFormatter::forOrganization($owner->organization, $payment->amount_due);

        $this->actingAs($owner)
            ->get(route('payments.index', ['status' => $payment->status]))
            ->assertOk()
            ->assertSee('AED ')
            ->assertSeeHtml('<bdi dir="ltr">'.$expectedAmount.'</bdi>')
            ->assertSee(number_format((float) $payment->amount_due, 2))
            ->assertDontSee('IDR ');
    }

    private function assertBuildingUnitCount(string $buildingName, int $expectedCount): void
    {
        $building = Building::where('name', $buildingName)->firstOrFail();

        $this->assertSame($expectedCount, $building->units()->count());
    }
}
