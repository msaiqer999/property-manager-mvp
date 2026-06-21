<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CreatePilotOwner extends Command
{
    protected $signature = 'pilot:create-owner';

    protected $description = 'Create the first pilot organization and owner on a clean database.';

    public function handle(): int
    {
        $organizationName = trim((string) $this->ask('Organization name'));
        $ownerName = trim((string) $this->ask('Owner name'));
        $email = strtolower(trim((string) $this->ask('Owner email')));

        $identityValidator = Validator::make([
            'organization_name' => $organizationName,
            'name' => $ownerName,
            'email' => $email,
        ], [
            'organization_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($identityValidator->fails()) {
            foreach ($identityValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->error('A user with this email already exists.');

            return self::FAILURE;
        }

        if (Organization::whereRaw('LOWER(name) = ?', [strtolower($organizationName)])->exists()) {
            $this->error('An organization with this name already exists.');

            return self::FAILURE;
        }

        if (Organization::exists() || User::exists()) {
            $this->error('The pilot owner can only be created on a clean database.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        $passwordValidator = Validator::make([
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($organizationName, $ownerName, $email, $password): void {
                $organization = Organization::create([
                    'name' => $organizationName,
                ]);

                User::create([
                    'organization_id' => $organization->id,
                    'name' => $ownerName,
                    'email' => $email,
                    'password' => $password,
                    'role' => Role::Owner,
                    'is_active' => true,
                ]);
            });
        } catch (Throwable) {
            $this->error('Unable to create the pilot owner. No records were kept.');

            return self::FAILURE;
        }

        $this->info('Pilot organization and owner created successfully.');
        $this->line('Organization: '.$organizationName);
        $this->line('Owner email: '.$email);

        return self::SUCCESS;
    }
}
