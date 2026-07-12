<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CompanyDummySeeder extends Seeder
{
    /**
     * Seeder untuk membuat 1 dummy company beserta department dan users-nya.
     * Jalankan dengan: php artisan db:seed --class=CompanyDummySeeder
     */
    public function run(): void
    {
        // ─── 1. Buat Company ─────────────────────────────────────────────────────
        $company = Company::firstOrCreate(
            ['code' => 'MJKONS'],
            [
                'name'        => 'PT Maju Konstruksi',
                'description' => 'Anak perusahaan bidang konstruksi - dummy data',
                'is_active'   => true,
                'connect_odoo'    => false,
                'connect_finance' => false,
            ]
        );

        $this->command->info("✅ Company created: {$company->name} (ID: {$company->id})");

        // ─── 2. Buat Departments ──────────────────────────────────────────────────
        $deptData = [
            ['code' => 'TEKNIK',  'name' => 'Teknik',     'manager' => 'Rizky Pratama'],
            ['code' => 'KEU',     'name' => 'Keuangan',   'manager' => 'Siti Rahayu'],
            ['code' => 'PROCMK',  'name' => 'Procurement','manager' => 'Agus Santoso'],
            ['code' => 'HRD-MK',  'name' => 'HRD',        'manager' => 'Dewi Lestari'],
            ['code' => 'IT-MK',   'name' => 'IT',         'manager' => 'Budi Wijaya'],
        ];

        $departments = [];
        foreach ($deptData as $d) {
            $dept = Department::firstOrCreate(
                ['code' => $d['code']],
                [
                    'name'       => $d['name'],
                    'manager'    => $d['manager'],
                    'is_active'  => true,
                    'company_id' => $company->id,
                ]
            );
            $departments[$d['code']] = $dept;
            $this->command->info("  📂 Department: {$dept->name} (ID: {$dept->id})");
        }

        // ─── 3. Pastikan roles sudah ada ─────────────────────────────────────────
        $roles = [
            'superadmin', 'procurement_holding', 'operational_manager',
            'general_manager', 'manager_fat', 'procurement', 'user',
        ];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // ─── 4. Buat Users untuk Company ─────────────────────────────────────────
        $usersData = [
            [
                'name'          => 'Andi Wijaya',
                'email'         => 'andi@konstruksi.com',
                'employee_id'   => 'MK-GM001',
                'role'          => 'general_manager',
                'department'    => 'TEKNIK',
                'position'      => 'General Manager',
                'phone'         => '08111000001',
            ],
            [
                'name'          => 'Rizky Pratama',
                'email'         => 'rizky@konstruksi.com',
                'employee_id'   => 'MK-OM001',
                'role'          => 'operational_manager',
                'department'    => 'TEKNIK',
                'position'      => 'Operational Manager',
                'phone'         => '08111000002',
            ],
            [
                'name'          => 'Agus Santoso',
                'email'         => 'agus@konstruksi.com',
                'employee_id'   => 'MK-PROC001',
                'role'          => 'procurement',
                'department'    => 'PROCMK',
                'position'      => 'Procurement Staff',
                'phone'         => '08111000003',
            ],
            [
                'name'          => 'Dewi Lestari',
                'email'         => 'dewi@konstruksi.com',
                'employee_id'   => 'MK-USR001',
                'role'          => 'user',
                'department'    => 'HRD-MK',
                'position'      => 'Staff HRD',
                'phone'         => '08111000004',
            ],
            [
                'name'          => 'Siti Rahayu',
                'email'         => 'siti@konstruksi.com',
                'employee_id'   => 'MK-FAT001',
                'role'          => 'manager_fat',
                'department'    => 'KEU',
                'position'      => 'Manager Finance & Accounting',
                'phone'         => '08111000005',
            ],
        ];

        foreach ($usersData as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'          => $u['name'],
                    'password'      => Hash::make('password'),
                    'employee_id'   => $u['employee_id'],
                    'department_id' => $departments[$u['department']]->id,
                    'company_id'    => $company->id,
                    'position'      => $u['position'],
                    'phone'         => $u['phone'],
                ]
            );

            // Assign role jika belum punya
            if (!$user->hasRole($u['role'])) {
                $user->syncRoles([$u['role']]);
            }

            $this->command->info("  👤 User: {$user->name} | role: {$u['role']} | email: {$user->email}");
        }

        // ─── 5. Update departments existing (tanpa company_id) ───────────────────
        // Pastikan departments lama di-assign ke company ini jika belum ada company_id
        Department::whereNull('company_id')->update(['company_id' => $company->id]);

        // ─── 6. Update users existing (tanpa company_id, bukan holding role) ─────
        $holdingRoles = ['superadmin', 'procurement_holding'];
        $usersWithoutCompany = User::whereNull('company_id')
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', $holdingRoles))
            ->get();

        foreach ($usersWithoutCompany as $u) {
            $u->update(['company_id' => $company->id]);
            $this->command->info("  🔗 Assigned existing user '{$u->name}' to company {$company->name}");
        }

        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════');
        $this->command->info("✅ Selesai! Dummy company & users berhasil dibuat.");
        $this->command->info("   Login tersedia (password: 'password'):");
        $this->command->info("   - andi@konstruksi.com (general_manager)");
        $this->command->info("   - rizky@konstruksi.com (operational_manager)");
        $this->command->info("   - agus@konstruksi.com (procurement)");
        $this->command->info("   - dewi@konstruksi.com (user)");
        $this->command->info("   - siti@konstruksi.com (manager_fat)");
        $this->command->info('══════════════════════════════════════════');
    }
}
