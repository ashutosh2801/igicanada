<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('admin:create {email : Administrator email address} {--name= : Administrator display name}')]
#[Description('Securely create the first approved Filament administrator')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) ($this->option('name') ?: $this->ask('Administrator name')));

        if (User::where('email', $email)->exists()) {
            $this->error('A user with this email already exists. Existing accounts are not promoted automatically.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->info('Administrator created. Sign in at /admin.');

        return self::SUCCESS;
    }
}
