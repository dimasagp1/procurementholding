<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // PR Management
            'create pr',
            'view pr',
            'edit pr',
            'delete pr',
            'approve pr',
            'reject pr',
            'export pr',
            
            // User Management
            'manage users',
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Department Management
            'manage departments',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            
            // Dashboard
            'view dashboard',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $operationalManager = Role::firstOrCreate(['name' => 'operational_manager', 'guard_name' => 'web']);
        $managerFat = Role::firstOrCreate(['name' => 'manager_fat', 'guard_name' => 'web']);
        $generalManager = Role::firstOrCreate(['name' => 'general_manager', 'guard_name' => 'web']);
        $procurement = Role::firstOrCreate(['name' => 'procurement', 'guard_name' => 'web']);
        $procurementHolding = Role::firstOrCreate(['name' => 'procurement_holding', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superadmin->syncPermissions(Permission::all());

        $procurementHolding->syncPermissions([
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'view dashboard',
            'view reports',
        ]);

        $operationalManager->syncPermissions([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
        ]);

        $managerFat->syncPermissions([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
        ]);

        $generalManager->syncPermissions([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
        ]);

        $procurement->syncPermissions([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
            'view reports',
        ]);

        $userRole->syncPermissions([
            'create pr',
            'view pr',
            'edit pr',
            'export pr',
            'view dashboard',
        ]);

        // Create departments
        $departments = [
            ['code' => 'IT', 'name' => 'Information Technology'],
            ['code' => 'HRD', 'name' => 'Human Resource Development'],
            ['code' => 'FIN', 'name' => 'Finance'],
            ['code' => 'PROD', 'name' => 'Production'],
            ['code' => 'MKT', 'name' => 'Marketing'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }

        // Create superadmin user
        $superadminUser = User::firstOrCreate([
            'email' => 'superadmin@prsystem.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'employee_id' => 'SA001',
            'department_id' => 1,
            'phone' => '081234567890',
            'position' => 'System Administrator'
        ]);
        $superadminUser->assignRole($superadmin);

        // Create operational manager
        $omUser = User::firstOrCreate([
            'email' => 'om@prsystem.com',
        ], [
            'name' => 'Operational Manager',
            'password' => Hash::make('password'),
            'employee_id' => 'OM001',
            'department_id' => 4,
            'phone' => '081234567891',
            'position' => 'Operational Manager'
        ]);
        $omUser->assignRole($operationalManager);

        // Create general manager
        $gmUser = User::firstOrCreate([
            'email' => 'gm@prsystem.com',
        ], [
            'name' => 'General Manager',
            'password' => Hash::make('password'),
            'employee_id' => 'GM001',
            'department_id' => 1,
            'phone' => '081234567892',
            'position' => 'General Manager'
        ]);
        $gmUser->assignRole($generalManager);

        // Create procurement user
        $procUser = User::firstOrCreate([
            'email' => 'procurement@prsystem.com',
        ], [
            'name' => 'Procurement Staff',
            'password' => Hash::make('password'),
            'employee_id' => 'PROC001',
            'department_id' => 3,
            'phone' => '081234567893',
            'position' => 'Procurement Staff'
        ]);
        $procUser->assignRole($procurement);

        // Create regular user
        $regularUser = User::firstOrCreate([
            'email' => 'user@prsystem.com',
        ], [
            'name' => 'Regular User',
            'password' => Hash::make('password'),
            'employee_id' => 'USR001',
            'department_id' => 2,
            'phone' => '081234567894',
            'position' => 'Staff'
        ]);
        $regularUser->assignRole($userRole);

        $this->call([
            PurposeSeeder::class,
        ]);
    }
}