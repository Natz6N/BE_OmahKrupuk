<?php

// database/seeders/UserSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin (Ibu pemilik toko)
        User::create([
            'name' => 'Admin Omah Krupuk',
            'email' => 'admin@omahkrupuk.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Kasir
        User::create([
            'name' => 'Kasir Omah Krupuk',
            'email' => 'kasir@omahkrupuk.com',
            'password' => Hash::make('kasir123'),
            'role' => 'kasir',
            'is_active' => true,
        ]);
    }
}
