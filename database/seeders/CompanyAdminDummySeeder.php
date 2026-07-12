<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CompanyAdminDummySeeder extends Seeder
{
    /**
     * Buat 1 dummy company_admin untuk PT Maju Konstruksi.
     * Jalankan: php artisan db:seed --class=CompanyAdminDummySeeder
     */
    public function run(): void
    {
        // Cari company yang sudah ada
        $company = Company::where('code', 'MJKONS')->first();

        if (!$company) {
            $this->command->error('Company MJKONS tidak ditemukan. Jalankan CompanyDummySeeder terlebih dahulu.');
            return;
        }

        // Pastikan role company_admin sudah ada
        $role = Role::firstOrCreate(['name' => 'company_admin']);

        // Buat user company_admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@konstruksi.com'],
            [
                'name'        => 'Admin Konstruksi',
                'password'    => Hash::make('password'),
                'employee_id' => 'ADM-MK001',
                'company_id'  => $company->id,
                'position'    => 'Company Administrator',
                'phone'       => '08122000001',
            ]
        );

        if (!$admin->hasRole('company_admin')) {
            $admin->syncRoles(['company_admin']);
        }

        $this->command->info('');
        $this->command->info('✅ Company Admin berhasil dibuat!');
        $this->command->info('══════════════════════════════════════');
        $this->command->info("  Nama     : {$admin->name}");
        $this->command->info("  Email    : {$admin->email}");
        $this->command->info("  Password : password");
        $this->command->info("  Role     : company_admin");
        $this->command->info("  Company  : {$company->name}");
        $this->command->info('══════════════════════════════════════');
        $this->command->info('');
        $this->command->info('Akses yang dimiliki:');
        $this->command->info('  ✅ Buat/edit/hapus users di PT Maju Konstruksi');
        $this->command->info('  ✅ Buat/edit/hapus departments di PT Maju Konstruksi');
        $this->command->info('  ❌ Tidak bisa akses company lain');
        $this->command->info('  ❌ Tidak bisa assign role superadmin/procurement_holding');
    }
}
