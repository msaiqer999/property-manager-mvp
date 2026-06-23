<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ResetPilotOwnerPassword extends Command
{
    protected $signature = 'pilot:reset-owner-password {email}';

    protected $description = 'Reset a pilot owner password from a trusted server console.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        $identityValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($identityValidator->fails()) {
            $this->error('Unable to reset the owner password.');

            return self::FAILURE;
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || $user->role !== Role::Owner) {
            $this->error('Unable to reset the owner password.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('New password');
        $passwordConfirmation = (string) $this->secret('Confirm new password');

        $passwordValidator = Validator::make([
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password' => [
                'required',
                'confirmed',
                'min:12',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);

        if ($passwordValidator->fails()) {
            $this->error('Unable to reset the owner password.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($user, $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                Log::notice('pilot_owner_password_reset', [
                    'user_id' => $user->id,
                    'organization_id' => $user->organization_id,
                ]);
            });
        } catch (Throwable) {
            $this->error('Unable to reset the owner password.');

            return self::FAILURE;
        }

        $this->info('Pilot owner password reset for '.$email.'.');

        return self::SUCCESS;
    }
}
