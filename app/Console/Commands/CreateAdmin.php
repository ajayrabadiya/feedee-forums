<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('app:create-admin {--username= : Username for the new administrator} {--email= : Email address for the new administrator}')]
#[Description('Create an administrator account (admins can only be created from the command line)')]
class CreateAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = [
            'username' => trim((string) ($this->option('username') ?? $this->ask('Username'))),
            'email' => Str::lower(trim((string) ($this->option('email') ?? $this->ask('Email address')))),
            'password' => (string) $this->secret('Password (min. 8 characters)'),
            'password_confirmation' => (string) $this->secret('Confirm password'),
        ];

        $validator = Validator::make($data, RegisterRequest::accountRules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->name = $data['username'];
        $user->role = UserRole::Admin;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Administrator \"{$user->username}\" created. Log in at ".route('login'));

        return self::SUCCESS;
    }
}
