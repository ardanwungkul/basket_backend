<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'password' => Hash::make(12345678),
            'role' => 'admin'
        ]);
        User::create([
            'name' => 'Coach',
            'email' => 'coach@gmail.com',
            'username' => 'coach',
            'password' => Hash::make(12345678),
            'role' => 'coach'
        ]);
    }
}
