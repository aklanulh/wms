<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 2 Super Admin users
        User::create([
            'name' => 'Rani',
            'email' => 'superadminrani@msa.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        User::create([
            'name' => 'Pak Ebet',
            'email' => 'superadminebet@msa.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        // Create 2 Regular Admin users
        User::create([
            'name' => 'Pak Jo',
            'email' => 'adminjo@msa.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Mba Ria',
            'email' => 'adminria@msa.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}
