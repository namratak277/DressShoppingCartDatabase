<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Create an admin user: php artisan make:admin {email?} {password?}
Artisan::command('make:admin {email?} {password?}', function ($email = null, $password = null) {
    $email = $email ?: env('ADMIN_EMAIL', 'admin@example.com');
    $password = $password ?: env('ADMIN_PASSWORD', 'password');

    $user = User::updateOrCreate(
        ['email' => $email],
        ['name' => 'Administrator', 'password' => Hash::make($password)]
    );

    $this->info("Admin user {$user->email} created/updated.");
})->describe('Create or update an admin user');

