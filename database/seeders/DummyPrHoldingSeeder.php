<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\User;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PrItem;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

/**
 * Seeder: 4 companies x 4 PRs each, all items stuck at 'approved_proc'
 * (waiting for procurement_holding approval at holding level).
 *
 * Run: php artisan db:seed --class=DummyPrHoldingSeeder
 */
class DummyPrHoldingSeeder extends Seeder
{
    public function run(): void
    {
        // Disable permission cache so role-assign works
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────────
        // 1. COMPANIES (create 2 new, use 2 existing)
        // ─────────────────────────────────────────────
        $companyData = [
            // Use existing
            ['id' => 1, 'name' => 'PT. Herbatech Innopharma Industry', 'code' => 'HERB'],
            ['id' => 2, 'name' => 'Abhimata Emas Juara',               'code' => 'AEJ'],
            // Create new
            ['name' => 'PT. Nusantara Teknologi Mandiri', 'code' => 'NTM'],
            ['name' => 'PT. Sinar Abadi Sejahtera',       'code' => 'SAS'],
        ];

        $companies = [];
        foreach ($companyData as $cd) {
            if (isset($cd['id'])) {
                $companies[] = Company::find($cd['id']);
            } else {
                $companies[] = Company::firstOrCreate(
                    ['code' => $cd['code']],
                    ['name' => $cd['name'], 'is_active' => true, 'connect_odoo' => false, 'connect_finance' => false]
                );
            }
        }

        // ─────────────────────────────────────────────
        // 2. ROLES (ensure all needed roles exist)
        // ─────────────────────────────────────────────
        $roles = [];
        foreach (['operational_manager', 'general_manager', 'procurement', 'user'] as $r) {
            $roles[$r] = Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // ─────────────────────────────────────────────
        // 3. PER-COMPANY: departments + users + PRs
        // ─────────────────────────────────────────────
        $prItems = [
            [
                'item_name'       => 'Laptop Dell Inspiron 15',
                'description'     => 'Laptop untuk kebutuhan tim operasional',
                'quantity'        => 2,
                'uom'             => 'Unit',
                'estimated_price' => 14500000,
                'total_price'     => 29000000,
            ],
            [
                'item_name'       => 'Printer Multifungsi HP LaserJet',
                'description'     => 'Printer untuk kantor departemen',
                'quantity'        => 1,
                'uom'             => 'Unit',
                'estimated_price' => 5200000,
                'total_price'     => 5200000,
            ],
            [
                'item_name'       => 'Meja Kerja Ergonomis',
                'description'     => 'Meja kerja adjustable height',
                'quantity'        => 5,
                'uom'             => 'Unit',
                'estimated_price' => 2800000,
                'total_price'     => 14000000,
            ],
            [
                'item_name'       => 'UPS 1500VA APC',
                'description'     => 'UPS untuk server room',
                'quantity'        => 3,
                'uom'             => 'Unit',
                'estimated_price' => 3750000,
                'total_price'     => 11250000,
            ],
            [
                'item_name'       => 'Kursi Ergonomis Herman Miller',
                'description'     => 'Kursi kantor ergonomis',
                'quantity'        => 4,
                'uom'             => 'Unit',
                'estimated_price' => 6500000,
                'total_price'     => 26000000,
            ],
            [
                'item_name'       => 'Switch Network 24 Port',
                'description'     => 'Switch jaringan Cisco Catalyst',
                'quantity'        => 2,
                'uom'             => 'Unit',
                'estimated_price' => 8900000,
                'total_price'     => 17800000,
            ],
            [
                'item_name'       => 'AC Split 2 PK',
                'description'     => 'AC untuk ruang server',
                'quantity'        => 1,
                'uom'             => 'Unit',
                'estimated_price' => 7200000,
                'total_price'     => 7200000,
            ],
            [
                'item_name'       => 'Scanner Dokumen A3',
                'description'     => 'Scanner untuk arsip dokumen',
                'quantity'        => 1,
                'uom'             => 'Unit',
                'estimated_price' => 4350000,
                'total_price'     => 4350000,
            ],
            [
                'item_name'       => 'Server Rack 42U',
                'description'     => 'Rack server untuk data center',
                'quantity'        => 1,
                'uom'             => 'Unit',
                'estimated_price' => 18500000,
                'total_price'     => 18500000,
            ],
            [
                'item_name'       => 'Proyektor Full HD Epson',
                'description'     => 'Proyektor untuk ruang meeting',
                'quantity'        => 2,
                'uom'             => 'Unit',
                'estimated_price' => 11200000,
                'total_price'     => 22400000,
            ],
            [
                'item_name'       => 'CCTV IP Camera 4MP',
                'description'     => 'Kamera keamanan IP 4MP outdoor',
                'quantity'        => 8,
                'uom'             => 'Unit',
                'estimated_price' => 950000,
                'total_price'     => 7600000,
            ],
            [
                'item_name'       => 'Tinta Epson L3110 (Set 4 warna)',
                'description'     => 'Tinta original refill untuk printer',
                'quantity'        => 10,
                'uom'             => 'Set',
                'estimated_price' => 185000,
                'total_price'     => 1850000,
            ],
            [
                'item_name'       => 'Kertas HVS A4 80gsm',
                'description'     => 'Kertas fotocopy A4',
                'quantity'        => 50,
                'uom'             => 'Rim',
                'estimated_price' => 65000,
                'total_price'     => 3250000,
            ],
            [
                'item_name'       => 'Spanduk Banner Acara',
                'description'     => 'Banner promosi kegiatan perusahaan',
                'quantity'        => 3,
                'uom'             => 'Pcs',
                'estimated_price' => 550000,
                'total_price'     => 1650000,
            ],
            [
                'item_name'       => 'Software Antivirus (Lisensi 1 tahun)',
                'description'     => 'Lisensi antivirus untuk 20 PC',
                'quantity'        => 20,
                'uom'             => 'Lisensi',
                'estimated_price' => 350000,
                'total_price'     => 7000000,
            ],
            [
                'item_name'       => 'Bahan Kimia Pembersih Industri',
                'description'     => 'Cairan pembersih mesin produksi',
                'quantity'        => 100,
                'uom'             => 'Liter',
                'estimated_price' => 45000,
                'total_price'     => 4500000,
            ],
        ];

        $deptNames = [
            ['name' => 'Information Technology',  'code' => 'IT'],
            ['name' => 'Human Resource & GA',      'code' => 'HRD'],
            ['name' => 'Finance & Accounting',     'code' => 'FIN'],
            ['name' => 'Production & Operations',  'code' => 'PROD'],
        ];

        $purposes = [
            'Kebutuhan Operasional Kantor',
            'Pengadaan Peralatan Produksi',
            'Pemeliharaan Infrastruktur IT',
            'Keperluan Acara & Promosi',
        ];

        $itemIndex = 0;

        foreach ($companies as $ci => $company) {
            $this->command->info("Processing company: {$company->name} ({$company->code})");

            // ── 3a. Departments ──────────────────────────────
            $depts = [];
            foreach ($deptNames as $dn) {
                $scopedCode = $dn['code'] . '-' . $company->code;
                $depts[] = Department::firstOrCreate(
                    ['code' => $scopedCode],
                    ['name' => $dn['name'], 'code' => $scopedCode, 'company_id' => $company->id, 'is_active' => true]
                );
            }

            // ── 3b. Users ──────────────────────────────────
            $emailSuffix = strtolower($company->code) . '.local';

            $om = User::firstOrCreate(
                ['email' => 'om@' . $emailSuffix],
                [
                    'name'          => 'Manajer Operasional ' . $company->code,
                    'employee_id'   => 'EMP-OM-' . $company->code,
                    'password'      => Hash::make('password'),
                    'company_id'    => $company->id,
                    'department_id' => $depts[0]->id,
                    'position'      => 'Operational Manager',
                ]
            );
            $om->syncRoles(['operational_manager']);

            $gm = User::firstOrCreate(
                ['email' => 'gm@' . $emailSuffix],
                [
                    'name'          => 'General Manager ' . $company->code,
                    'employee_id'   => 'EMP-GM-' . $company->code,
                    'password'      => Hash::make('password'),
                    'company_id'    => $company->id,
                    'department_id' => $depts[0]->id,
                    'position'      => 'General Manager',
                ]
            );
            $gm->syncRoles(['general_manager']);

            $proc = User::firstOrCreate(
                ['email' => 'proc@' . $emailSuffix],
                [
                    'name'          => 'Procurement ' . $company->code,
                    'employee_id'   => 'EMP-PROC-' . $company->code,
                    'password'      => Hash::make('password'),
                    'company_id'    => $company->id,
                    'department_id' => $depts[2]->id,
                    'position'      => 'Procurement Staff',
                ]
            );
            $proc->syncRoles(['procurement']);

            $regularUser = User::firstOrCreate(
                ['email' => 'user@' . $emailSuffix],
                [
                    'name'          => 'Staff ' . $company->code,
                    'employee_id'   => 'EMP-USR-' . $company->code,
                    'password'      => Hash::make('password'),
                    'company_id'    => $company->id,
                    'department_id' => $depts[1]->id,
                    'position'      => 'Staff',
                ]
            );
            $regularUser->syncRoles(['user']);

            // ── 3c. 4 PRs per company, items = approved_proc ─
            for ($prIdx = 0; $prIdx < 4; $prIdx++) {
                $dept       = $depts[$prIdx % count($depts)];
                $requestDate = Carbon::now()->subDays(rand(5, 25));
                $prType     = ($prIdx % 2 === 0) ? 'operational' : 'non_operational';

                // Generate PR number
                $year      = $requestDate->year;
                $month     = str_pad($requestDate->month, 2, '0', STR_PAD_LEFT);
                $seq       = PurchaseRequest::where('company_id', $company->id)
                                ->whereYear('created_at', $year)->count() + $prIdx + 1;
                $prNumber  = sprintf('PR/%s/%s/%s/%04d', $company->code, $year, $month, $seq);

                $item1  = $prItems[$itemIndex % count($prItems)];
                $item2  = $prItems[($itemIndex + 1) % count($prItems)];
                $total  = $item1['total_price'] + $item2['total_price'];

                $pr = PurchaseRequest::create([
                    'pr_number'    => $prNumber,
                    'user_id'      => $regularUser->id,
                    'department_id'=> $dept->id,
                    'company_id'   => $company->id,
                    'request_date' => $requestDate->toDateString(),
                    'purpose'      => $purposes[$prIdx % count($purposes)],
                    'status'       => 'pending',
                    'pr_type'      => $prType,
                    'total_amount' => $total,
                    'notes'        => 'PR dummy — menunggu persetujuan Procurement Holding.',
                    'created_at'   => $requestDate,
                    'updated_at'   => $requestDate->copy()->addDays(rand(1, 3)),
                ]);

                // 2 items per PR, both at approved_proc (stuck waiting holding)
                foreach ([$item1, $item2] as $itemData) {
                    PrItem::create([
                        'purchase_request_id' => $pr->id,
                        'item_name'           => $itemData['item_name'],
                        'description'         => $itemData['description'],
                        'quantity'            => $itemData['quantity'],
                        'uom'                 => $itemData['uom'],
                        'estimated_price'     => $itemData['estimated_price'],
                        'total_price'         => $itemData['total_price'],
                        'status'              => 'approved_proc', // ← stuck at holding
                        'processed_at'        => $requestDate->copy()->addDays(2),
                        'created_at'          => $requestDate,
                        'updated_at'          => $requestDate->copy()->addDays(2),
                    ]);
                }

                $this->command->line("  → {$prNumber} [{$prType}] ({$item1['item_name']} + {$item2['item_name']}) — approved_proc");
                $itemIndex += 2;
            }
        }

        $this->command->info('');
        $this->command->info('✅ Done! ' . count($companies) . ' companies × 4 PRs = ' . (count($companies) * 4) . ' PRs created.');
        $this->command->info('   All items status: approved_proc (waiting procurement_holding approval).');
        $this->command->info('');
        $this->command->info('📋 Login accounts (password: password):');
        foreach ($companies as $company) {
            $suffix = strtolower($company->code) . '.local';
            $this->command->line("   [{$company->code}] user@{$suffix} | om@{$suffix} | gm@{$suffix} | proc@{$suffix}");
        }
    }
}
