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
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $superadmin = Role::create(['name' => 'superadmin']);
        $operationalManager = Role::create(['name' => 'operational_manager']);
        $managerFat = Role::create(['name' => 'manager_fat']);
        $generalManager = Role::create(['name' => 'general_manager']);
        $procurement = Role::create(['name' => 'procurement']);
        $procurementHolding = Role::create(['name' => 'procurement_holding']);
        $userRole = Role::create(['name' => 'user']);

        // Assign permissions to roles
        $superadmin->givePermissionTo(Permission::all());

        $procurementHolding->givePermissionTo([
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'view dashboard',
            'view reports',
        ]);

        $operationalManager->givePermissionTo([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
        ]);

        $managerFat->givePermissionTo([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
        ]);

        $generalManager->givePermissionTo([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
        ]);

        $procurement->givePermissionTo([
            'create pr',
            'view pr',
            'edit pr',
            'approve pr',
            'reject pr',
            'export pr',
            'view dashboard',
            'view reports',
        ]);

        $userRole->givePermissionTo([
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
            Department::create($dept);
        }

        // Create superadmin user
        $superadminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@prsystem.com',
            'password' => Hash::make('password'),
            'employee_id' => 'SA001',
            'department_id' => 1,
            'phone' => '081234567890',
            'position' => 'System Administrator'
        ]);
        $superadminUser->assignRole($superadmin);

        // Create operational manager
        $omUser = User::create([
            'name' => 'Operational Manager',
            'email' => 'om@prsystem.com',
            'password' => Hash::make('password'),
            'employee_id' => 'OM001',
            'department_id' => 4,
            'phone' => '081234567891',
            'position' => 'Operational Manager'
        ]);
        $omUser->assignRole($operationalManager);

        // Create general manager
        $gmUser = User::create([
            'name' => 'General Manager',
            'email' => 'gm@prsystem.com',
            'password' => Hash::make('password'),
            'employee_id' => 'GM001',
            'department_id' => 1,
            'phone' => '081234567892',
            'position' => 'General Manager'
        ]);
        $gmUser->assignRole($generalManager);

        // Create procurement user
        $procUser = User::create([
            'name' => 'Procurement Staff',
            'email' => 'procurement@prsystem.com',
            'password' => Hash::make('password'),
            'employee_id' => 'PROC001',
            'department_id' => 3,
            'phone' => '081234567893',
            'position' => 'Procurement Staff'
        ]);
        $procUser->assignRole($procurement);

        // Create regular user
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@prsystem.com',
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