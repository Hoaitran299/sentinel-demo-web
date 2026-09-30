<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('demo:user {--email=demo@example.com} {--name=Demo User} {--phone=0900000000} {--address=}')]
#[Description('Create or update the staging demo user using a hidden password prompt')]
class ProvisionDemoUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->environment(['local', 'staging'])) {
            $this->error('The demo user can only be provisioned in local or staging environments.');

            return self::FAILURE;
        }

        $attributes = [
            'email' => Str::lower(trim((string) $this->option('email'))),
            'name' => trim((string) $this->option('name')),
            'phone' => trim((string) $this->option('phone')),
            'address' => $this->normalizedAddress(),
        ];

        $profileValidator = Validator::make($attributes, [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if ($profileValidator->fails()) {
            foreach ($profileValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        $passwordValidator = Validator::make([
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'phone' => $attributes['phone'],
                'address' => $attributes['address'],
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        $action = $user->wasRecentlyCreated ? 'created' : 'updated';

        $this->info("Demo user {$action}: {$user->email}");

        return self::SUCCESS;
    }

    private function normalizedAddress(): ?string
    {
        $address = trim((string) $this->option('address'));

        return $address === '' ? null : $address;
    }
}
