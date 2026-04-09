<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'ffigueroa@boveda-creativa.com'],
            [
                'name' => 'Administrador Hacienda 5',
                'password' => Hash::make('Fobox1992ffr'),
                'phone' => '3331285406',
                'is_active' => true,
            ]
        );

        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }
    }
}