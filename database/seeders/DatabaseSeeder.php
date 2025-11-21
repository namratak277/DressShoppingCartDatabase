<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

   //application database seeders
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
