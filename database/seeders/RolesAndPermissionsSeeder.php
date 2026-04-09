<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view dashboard',
            'manage users',
            'manage clients',
            'manage services',
            'manage events',
            'manage quotations',
            'manage payments',
            'manage documents',
            'view calendar',
            'access client portal',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $ventas = Role::firstOrCreate(['name' => 'ventas']);
        $operaciones = Role::firstOrCreate(['name' => 'operaciones']);
        $cliente = Role::firstOrCreate(['name' => 'cliente']);

        $superAdmin->givePermissionTo($permissions);
        $admin->givePermissionTo($permissions);
        $ventas->givePermissionTo([
            'view dashboard',
            'manage clients',
            'manage quotations',
            'manage payments',
            'view calendar',
        ]);
        $operaciones->givePermissionTo([
            'view dashboard',
            'manage events',
            'manage documents',
            'view calendar',
        ]);
        $cliente->givePermissionTo([
            'access client portal',
        ]);
    }
}
