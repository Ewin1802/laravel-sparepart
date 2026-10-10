<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'novi@admin.com'],
            [
                'name' => 'Novi Admin',
                'email' => 'novi@admin.com',
                'phone_number' => '081327201592',
                'password' => Hash::make('passnovi0810'),
                'role' => 'admin',
            ]
        );
        User::updateOrCreate(
            ['email' => 'e@admin.com'],
            [
                'name' => 'Ewin Kasir',
                'email' => 'e@admin.com',
                'phone_number' => '081340985993',
                'password' => Hash::make('password1413'),
                'role' => 'admin',
            ]
        );
        User::updateOrCreate(
            ['email' => 'fahniati@kasir.com'],
            [
                'name' => 'Fahniati Safari',
                'email' => 'fahniati@kasir.com',
                'phone_number' => '081234567898',
                'password' => Hash::make('password1413'),
                'role' => 'staff',
            ]
        );
    }
}
