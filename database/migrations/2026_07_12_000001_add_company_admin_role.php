<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan permissions yang dibutuhkan company_admin sudah ada
        $needed = [
            'view users', 'create users', 'edit users', 'delete users',
            'view departments', 'create departments', 'edit departments', 'delete departments',
            'view pr', 'view dashboard',
        ];

        foreach ($needed as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Buat role company_admin jika belum ada
        $companyAdmin = Role::firstOrCreate(['name' => 'company_admin']);

        // Assign permissions ke company_admin
        $companyAdmin->syncPermissions($needed);
    }

    public function down(): void
    {
        $role = Role::where('name', 'company_admin')->first();
        if ($role) {
            $role->delete();
        }
    }
};
