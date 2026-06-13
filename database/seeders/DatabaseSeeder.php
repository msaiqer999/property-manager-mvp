<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\PaymentSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create(['name' => 'Riyadh Small Properties']);

        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Nasser Al Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'Hassan Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'Mona Accountant',
            'email' => 'accountant@example.com',
            'password' => Hash::make('password'),
            'role' => 'accountant',
        ]);

        User::create([
            'organization_id' => $organization->id,
            'name' => 'Fahad Caretaker',
            'email' => 'caretaker@example.com',
            'password' => Hash::make('password'),
            'role' => 'caretaker',
        ]);

        $buildings = collect([
            Building::create([
                'organization_id' => $organization->id,
                'name' => 'Al Noor Residence',
                'location' => 'Olaya, Riyadh',
                'description' => 'Residential apartment building with ground-floor services.',
            ]),
            Building::create([
                'organization_id' => $organization->id,
                'name' => 'Al Yasmin Plaza',
                'location' => 'Al Yasmin, Riyadh',
                'description' => 'Mixed-use property with apartments and small offices.',
            ]),
        ]);

        $units = collect();

        foreach ($buildings as $index => $building) {
            for ($i = 1; $i <= 10; $i++) {
                $unitNumber = ($index + 1).str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $isOffice = $building->name === 'Al Yasmin Plaza' && $i > 7;

                $units->push(Unit::create([
                    'building_id' => $building->id,
                    'unit_number' => $unitNumber,
                    'type' => $isOffice ? 'office' : 'apartment',
                    'size' => $isOffice ? 65 + ($i * 3) : 80 + ($i * 5),
                    'rooms' => $isOffice ? 2 : (($i % 3) + 2),
                    'status' => $i === 10 ? 'maintenance' : 'vacant',
                    'rent_amount' => $isOffice ? 4200 + ($i * 150) : 2800 + ($i * 250),
                    'notes' => $isOffice ? 'Suitable for small business office.' : null,
                ]));
            }
        }

        $tenantNames = [
            'Ahmed Saleh', 'Sara Khalid', 'Omar Hassan', 'Layla Nasser',
            'Yousef Ali', 'Reem Abdullah', 'Khaled Mansour', 'Huda Faisal',
            'Faisal Hamad', 'Noura Saad', 'Majed Ibrahim', 'Dana Adel',
        ];

        $tenants = collect($tenantNames)->map(fn (string $name, int $index) => Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => $name,
            'phone' => '+9665'.str_pad((string) (50000000 + $index * 13791), 8, '0', STR_PAD_LEFT),
            'email' => 'tenant'.($index + 1).'@example.com',
            'id_number' => '10'.str_pad((string) (10000000 + $index * 9137), 8, '0', STR_PAD_LEFT),
            'nationality' => $index % 4 === 0 ? 'Saudi' : 'Resident',
            'notes' => $index % 3 === 0 ? 'Prefers WhatsApp communication.' : null,
        ]));

        $contractSpecs = [
            ['frequency' => 'monthly', 'start' => now()->subMonths(5)->startOfMonth(), 'months' => 12],
            ['frequency' => 'monthly', 'start' => now()->subMonths(4)->startOfMonth(), 'months' => 12],
            ['frequency' => 'monthly', 'start' => now()->subMonths(3)->startOfMonth(), 'months' => 12],
            ['frequency' => 'monthly', 'start' => now()->subMonths(2)->startOfMonth(), 'months' => 12],
            ['frequency' => 'monthly', 'start' => now()->subMonth()->startOfMonth(), 'months' => 12],
            ['frequency' => 'monthly', 'start' => now()->startOfMonth(), 'months' => 12],
            ['frequency' => 'quarterly', 'start' => now()->subMonths(6)->startOfMonth(), 'months' => 12],
            ['frequency' => 'quarterly', 'start' => now()->subMonths(3)->startOfMonth(), 'months' => 12],
            ['frequency' => 'quarterly', 'start' => now()->startOfMonth(), 'months' => 12],
            ['frequency' => 'annual', 'start' => now()->subMonths(10)->startOfMonth(), 'months' => 12],
            ['frequency' => 'annual', 'start' => now()->subMonths(2)->startOfMonth(), 'months' => 12],
            ['frequency' => 'annual', 'start' => now()->startOfMonth(), 'months' => 12],
        ];

        $rentedUnits = $units->where('status', 'vacant')->take(12)->values();

        foreach ($contractSpecs as $index => $spec) {
            $unit = $rentedUnits[$index];
            $start = $spec['start']->copy();
            $end = $start->copy()->addMonths($spec['months'])->subDay();

            $contract = Contract::create([
                'organization_id' => $organization->id,
                'unit_id' => $unit->id,
                'tenant_id' => $tenants[$index]->id,
                'contract_number' => 'CN-2026-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'start_date' => $start,
                'end_date' => $end,
                'rent_amount' => $unit->rent_amount,
                'payment_frequency' => $spec['frequency'],
                'deposit_amount' => $unit->rent_amount,
                'status' => 'active',
                'notes' => 'Demo active contract with '.$spec['frequency'].' payment schedule.',
            ]);

            PaymentSchedule::createFor($contract);
            $unit->update(['status' => 'rented']);
        }

        $this->seedPayments($organization->id, $owner->id);
        $this->seedExpenses($organization->id, $owner->id, $buildings, $units);
    }

    private function seedPayments(int $organizationId, int $ownerId): void
    {
        Payment::where('organization_id', $organizationId)
            ->where('due_date', '<', now()->startOfMonth())
            ->orderBy('due_date')
            ->take(8)
            ->get()
            ->each(function (Payment $payment) use ($ownerId) {
                $payment->update([
                    'amount_paid' => $payment->amount_due,
                    'payment_date' => $payment->due_date->copy()->addDays(2),
                    'status' => 'paid',
                    'payment_method' => 'bank_transfer',
                    'created_by' => $ownerId,
                    'notes' => 'Historical demo payment.',
                ]);
            });

        Payment::where('organization_id', $organizationId)
            ->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderBy('amount_due', 'desc')
            ->take(6)
            ->get()
            ->each(function (Payment $payment, int $index) use ($ownerId) {
                $payment->update([
                    'amount_paid' => $payment->amount_due,
                    'payment_date' => now()->startOfMonth()->addDays($index + 1),
                    'status' => 'paid',
                    'payment_method' => $index % 2 === 0 ? 'bank_transfer' : 'cash',
                    'created_by' => $ownerId,
                    'notes' => 'Current month demo income.',
                ]);
            });

        Payment::where('organization_id', $organizationId)
            ->where('due_date', '<', now()->toDateString())
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->each(function (Payment $payment, int $index) use ($ownerId) {
                $partial = $index === 0 ? round($payment->amount_due * 0.4, 2) : 0;

                $payment->update([
                    'amount_paid' => $partial,
                    'payment_date' => $partial > 0 ? now()->subDays(3) : null,
                    'status' => 'overdue',
                    'payment_method' => $partial > 0 ? 'cash' : null,
                    'created_by' => $ownerId,
                    'notes' => 'Demo overdue balance for dashboard and reports.',
                ]);
            });
    }

    private function seedExpenses(int $organizationId, int $ownerId, $buildings, $units): void
    {
        $expenses = [
            ['maintenance', 1450, now()->startOfMonth()->addDays(1), 0, null, 'Elevator preventive maintenance.'],
            ['cleaning', 900, now()->startOfMonth()->addDays(2), 0, null, 'Monthly common area cleaning.'],
            ['security', 2200, now()->startOfMonth()->addDays(3), 1, null, 'Security guard monthly fee.'],
            ['electricity', 1180, now()->startOfMonth()->addDays(4), 1, null, 'Common area electricity.'],
            ['water', 760, now()->startOfMonth()->addDays(5), 0, null, 'Water bill.'],
            ['maintenance', 650, now()->subDays(10), 0, 2, 'AC service for unit.'],
            ['management', 2500, now()->subDays(8), 1, null, 'Property management admin cost.'],
            ['cleaning', 480, now()->subDays(6), 1, 12, 'Post-maintenance cleaning.'],
            ['electricity', 1320, now()->subMonth()->startOfMonth()->addDays(8), 0, null, 'Previous month electricity.'],
            ['other', 350, now()->subMonth()->startOfMonth()->addDays(12), 1, null, 'Small supplies and keys.'],
        ];

        foreach ($expenses as [$category, $amount, $date, $buildingIndex, $unitIndex, $notes]) {
            Expense::create([
                'organization_id' => $organizationId,
                'building_id' => $buildings[$buildingIndex]->id,
                'unit_id' => $unitIndex ? $units[$unitIndex - 1]->id : null,
                'category' => $category,
                'amount' => $amount,
                'expense_date' => $date,
                'notes' => $notes,
                'created_by' => $ownerId,
            ]);
        }
    }
}
