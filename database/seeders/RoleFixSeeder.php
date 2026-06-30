<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleFixSeeder extends Seeder
{
    /**
     * Insert roles & permissions yang belum ada di database hosting.
     * Aman dijalankan berkali-kali (menggunakan firstOrCreate).
     */
    public function run(): void
    {
        // Pastikan semua role ada
        $roles = [
            'superadmin',
            'operational_manager',
            'manager_fat',
            'general_manager',
            'procurement',
            'procurement_holding',
            'user',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Pastikan semua permission ada
        $permissions = [
            'create pr', 'view pr', 'edit pr', 'delete pr',
            'approve pr', 'reject pr', 'export pr',
            'manage users', 'view users', 'create users', 'edit users', 'delete users',
            'manage departments', 'view departments', 'create departments', 'edit departments', 'delete departments',
            'view dashboard', 'view reports',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Assign permissions ke role manager_fat (sama seperti operational_manager)
        $managerFat = Role::findByName('manager_fat');
        $managerFat->syncPermissions([
            'create pr', 'view pr', 'edit pr', 'approve pr', 'reject pr', 'export pr', 'view dashboard',
        ]);

        // Assign permissions ke role procurement_holding
        $procurementHolding = Role::findByName('procurement_holding');
        $procurementHolding->syncPermissions([
            'view pr', 'edit pr', 'view dashboard', 'view reports',
        ]);

        // Pastikan superadmin punya semua permission
        $superadmin = Role::findByName('superadmin');
        $superadmin->syncPermissions(Permission::all());

        $this->command->info('✅ Roles & permissions berhasil diperbaiki.');
    }
}

