<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Accountant = 'accountant';
    case Caretaker = 'caretaker';

    public function can(string $ability): bool
    {
        return match ($this) {
            self::Owner => true,
            self::Manager => in_array($ability, [
                'manage-properties', 'manage-tenants', 'manage-contracts',
                'view-payments', 'record-payment', 'manage-payments',
                'view-expenses', 'manage-expenses', 'view-reports',
            ], true),
            self::Accountant => in_array($ability, [
                'view-payments', 'manage-payments', 'view-expenses',
                'record-payment', 'view-reports', 'export-reports',
            ], true),
            self::Caretaker => in_array($ability, [
                'view-payments', 'record-payment', 'record-maintenance-note',
            ], true),
        };
    }
}
