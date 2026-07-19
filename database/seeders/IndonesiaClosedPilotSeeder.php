<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Country;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\PaymentSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class IndonesiaClosedPilotSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(GlobalReadinessSeeder::class);

        $country = Country::where('code', 'ID')->firstOrFail();

        $organization = Organization::updateOrCreate(
            ['name' => 'Indonesia Closed Pilot Portfolio'],
            [
                'country_id' => $country->id,
                'currency_code' => $country->default_currency_code,
                'locale' => $country->default_locale,
                'timezone' => $country->default_timezone,
            ]
        );

        $owner = User::updateOrCreate(
            ['email' => 'indonesia-owner@example.com'],
            [
                'organization_id' => $organization->id,
                'name' => 'Pemilik Pilot Indonesia',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]
        );

        User::updateOrCreate(
            ['email' => 'indonesia-manager@example.com'],
            [
                'organization_id' => $organization->id,
                'name' => 'Operator Pilot Indonesia',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );

        $buildings = collect([
            $this->building($organization, 'Kos Putri Surabaya', 'Wonokromo, Surabaya', 'Kos putri dengan kamar bulanan dan pembayaran transfer bank.'),
            $this->building($organization, 'Kontrakan Keluarga Malang', 'Lowokwaru, Malang', 'Kontrakan keluarga sederhana dengan unit tahunan.'),
            $this->building($organization, 'Ruko Sidoarjo', 'Waru, Sidoarjo', 'Ruko kecil untuk usaha lokal.'),
            $this->building($organization, 'Gudang kecil Gresik', 'Manyar, Gresik', 'Gudang kecil untuk penyimpanan usaha.'),
        ]);

        $units = collect()
            ->merge($this->units($buildings[0], 'KP-', 18, 'kamar', 850000, 35000, 12, 1))
            ->merge($this->units($buildings[1], 'KK-', 6, 'kontrakan', 1750000, 125000, 45, 2))
            ->merge($this->units($buildings[2], 'RK-', 4, 'ruko', 4500000, 250000, 70, 1))
            ->merge($this->units($buildings[3], 'GD-', 2, 'warehouse', 6500000, 500000, 120, 0));

        $this->contracts($organization, $owner, $units);
        $this->payments($organization, $owner);
        $this->expenses($organization, $owner, $buildings, $units);
    }

    private function building(Organization $organization, string $name, string $location, string $description): Building
    {
        return Building::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => $name,
            ],
            [
                'country_id' => $organization->country_id,
                'currency_code' => $organization->currency_code,
                'timezone' => $organization->timezone,
                'location' => $location,
                'description' => $description,
            ]
        );
    }

    private function units(
        Building $building,
        string $prefix,
        int $count,
        string $type,
        int $baseRent,
        int $rentStep,
        int $baseSize,
        int $rooms
    ): array {
        $units = [];

        for ($index = 1; $index <= $count; $index++) {
            $units[] = Unit::updateOrCreate(
                [
                    'building_id' => $building->id,
                    'unit_number' => $prefix.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                ],
                [
                    'type' => $type,
                    'size' => $baseSize + (($index - 1) * 2),
                    'rooms' => $rooms,
                    'status' => $index % 9 === 0 ? 'maintenance' : 'vacant',
                    'rent_amount' => $baseRent + (($index - 1) * $rentStep),
                    'notes' => $type === 'kamar' ? 'Kamar kos pilot Indonesia.' : 'Unit pilot Indonesia.',
                ]
            );
        }

        return $units;
    }

    private function contracts(Organization $organization, User $owner, $units): void
    {
        $tenantNames = [
            'Siti Aminah',
            'Dewi Lestari',
            'Rina Wulandari',
            'Maya Pratiwi',
            'Nur Aisyah',
            'Fajar Nugroho',
            'Budi Santoso',
            'Agus Setiawan',
            'Rizky Pratama',
            'Andi Wijaya',
            'Toko Sumber Rejeki',
            'CV Maju Bersama',
            'Warung Nusantara',
            'PT Logistik Gresik',
        ];

        foreach ($tenantNames as $index => $name) {
            $tenant = Tenant::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'email' => 'tenant-id-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'@example.com',
                ],
                [
                    'full_name' => $name,
                    'phone' => '+62812'.str_pad((string) (34000000 + ($index * 27193)), 8, '0', STR_PAD_LEFT),
                    'id_number' => 'ID'.str_pad((string) (3276000000000000 + ($index * 9137)), 16, '0', STR_PAD_LEFT),
                    'nationality' => 'Indonesian',
                    'notes' => $index < 5 ? 'Penyewa kos membayar bulanan.' : 'Kontak pilot Indonesia.',
                ]
            );

            $unit = $units[$index];
            $start = now()->subMonths($index % 4)->startOfMonth();
            $end = $start->copy()->addYear()->subDay();

            $contract = Contract::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'contract_number' => 'ID-PILOT-2026-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                ],
                [
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'rent_amount' => $unit->rent_amount,
                    'payment_frequency' => 'monthly',
                    'deposit_amount' => $unit->rent_amount,
                    'status' => 'active',
                    'notes' => 'Kontrak sampel untuk pilot tertutup Indonesia.',
                ]
            );

            PaymentSchedule::createFor($contract);
            $unit->update(['status' => 'rented']);
        }
    }

    private function payments(Organization $organization, User $owner): void
    {
        Payment::where('organization_id', $organization->id)
            ->where('due_date', '<', now()->startOfMonth())
            ->orderBy('due_date')
            ->take(10)
            ->get()
            ->each(function (Payment $payment, int $index) use ($owner): void {
                $payment->update([
                    'amount_paid' => $payment->amount_due,
                    'payment_date' => $payment->due_date->copy()->addDays($index % 4),
                    'status' => 'paid',
                    'payment_method' => $index % 3 === 0 ? 'cash' : 'bank_transfer',
                    'created_by' => $owner->id,
                    'notes' => 'Pembayaran historis pilot Indonesia.',
                ]);
            });

        Payment::where('organization_id', $organization->id)
            ->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderBy('amount_due', 'desc')
            ->take(8)
            ->get()
            ->each(function (Payment $payment, int $index) use ($owner): void {
                $payment->update([
                    'amount_paid' => $payment->amount_due,
                    'payment_date' => now()->startOfMonth()->addDays($index + 1),
                    'status' => 'paid',
                    'payment_method' => $index % 2 === 0 ? 'bank_transfer' : 'cash',
                    'created_by' => $owner->id,
                    'notes' => 'Pembayaran bulan berjalan pilot Indonesia.',
                ]);
            });

        Payment::where('organization_id', $organization->id)
            ->where('due_date', '<', now()->toDateString())
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->orderBy('due_date')
            ->take(4)
            ->get()
            ->each(function (Payment $payment, int $index) use ($owner): void {
                $partial = $index === 0 ? round($payment->amount_due * 0.5, 2) : 0;

                $payment->update([
                    'amount_paid' => $partial,
                    'payment_date' => $partial > 0 ? now()->subDays(3) : null,
                    'status' => 'overdue',
                    'payment_method' => $partial > 0 ? 'cash' : null,
                    'created_by' => $owner->id,
                    'notes' => 'Saldo tertunggak untuk latihan tindak lanjut.',
                ]);
            });
    }

    private function expenses(Organization $organization, User $owner, $buildings, $units): void
    {
        foreach ([
            ['cleaning', 1250000, now()->startOfMonth()->addDays(1), 0, null, 'Kebersihan area kos Surabaya.'],
            ['electricity', 980000, now()->startOfMonth()->addDays(2), 0, null, 'Listrik area bersama.'],
            ['maintenance', 750000, now()->startOfMonth()->addDays(4), 1, 18, 'Perbaikan pintu kontrakan.'],
            ['security', 1500000, now()->startOfMonth()->addDays(5), 2, null, 'Keamanan ruko.'],
            ['water', 640000, now()->subDays(8), 0, null, 'Air bulanan kos.'],
            ['management', 2100000, now()->subDays(5), 3, null, 'Biaya operasional gudang.'],
        ] as [$category, $amount, $date, $buildingIndex, $unitIndex, $notes]) {
            Expense::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'notes' => $notes,
                ],
                [
                    'building_id' => $buildings[$buildingIndex]->id,
                    'unit_id' => $unitIndex ? $units[$unitIndex - 1]->id : null,
                    'category' => $category,
                    'amount' => $amount,
                    'expense_date' => $date,
                    'created_by' => $owner->id,
                ]
            );
        }
    }
}
