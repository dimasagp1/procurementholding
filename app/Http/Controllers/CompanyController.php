<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CompanyController extends Controller
{
    public function index()
    {
        $this->authorize('view departments');
        $companies = Company::withCount(['users', 'departments'])->paginate(10);
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        $this->authorize('create departments');
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create departments');

        $validated = $request->validate([
            // Company
            'code'             => 'required|string|max:10|unique:companies,code',
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'odoo_url'         => 'nullable|url|max:255',
            'odoo_db'          => 'nullable|string|max:255',
            'odoo_username'    => 'nullable|string|max:255',
            'odoo_password'    => 'nullable|string|max:255',
            'odoo_company_id'  => 'nullable|integer',
            'finance_api_url'  => 'nullable|url|max:255',
            'finance_api_key'  => 'nullable|string|max:255',

            // Departments (array opsional)
            'departments'              => ['nullable', 'array'],
            'departments.*.code'       => ['required_with:departments', 'string', 'max:10'],
            'departments.*.name'       => ['required_with:departments', 'string', 'max:255'],
            'departments.*.manager'    => ['nullable', 'string', 'max:255'],

            // Company Admin (opsional)
            'admin_name'        => ['nullable', 'string', 'max:255'],
            'admin_email'       => ['nullable', 'email', 'unique:users,email'],
            'admin_employee_id' => ['nullable', 'string', 'unique:users,employee_id'],
            'admin_password'    => ['nullable', 'string', 'min:8'],
            'admin_position'    => ['nullable', 'string', 'max:255'],
            'admin_phone'       => ['nullable', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            // ── 1. Buat Company ────────────────────────────────────────────────
            $company = Company::create([
                'code'            => $validated['code'],
                'name'            => $validated['name'],
                'description'     => $validated['description'] ?? null,
                'is_active'       => $request->boolean('is_active', true),
                'connect_odoo'    => $request->boolean('connect_odoo', false),
                'connect_finance' => $request->boolean('connect_finance', false),
                'odoo_url'        => $validated['odoo_url'] ?? null,
                'odoo_db'         => $validated['odoo_db'] ?? null,
                'odoo_username'   => $validated['odoo_username'] ?? null,
                'odoo_password'   => $validated['odoo_password'] ?? null,
                'odoo_company_id' => $validated['odoo_company_id'] ?? null,
                'finance_api_url' => $validated['finance_api_url'] ?? null,
                'finance_api_key' => $validated['finance_api_key'] ?? null,
            ]);

            // ── 2. Buat Departments ────────────────────────────────────────────
            if (!empty($validated['departments'])) {
                foreach ($validated['departments'] as $dept) {
                    if (!empty($dept['code']) && !empty($dept['name'])) {
                        Department::create([
                            'code'       => $dept['code'],
                            'name'       => $dept['name'],
                            'manager'    => $dept['manager'] ?? null,
                            'is_active'  => true,
                            'company_id' => $company->id,
                        ]);
                    }
                }
            }

            // ── 3. Buat Company Admin ──────────────────────────────────────────
            if (!empty($validated['admin_email']) && !empty($validated['admin_name'])) {
                $adminRole = Role::firstOrCreate(['name' => 'company_admin']);

                $admin = User::create([
                    'name'        => $validated['admin_name'],
                    'email'       => $validated['admin_email'],
                    'password'    => Hash::make($validated['admin_password'] ?? 'password'),
                    'employee_id' => $validated['admin_employee_id'] ?? 'ADM-' . strtoupper($validated['code']),
                    'company_id'  => $company->id,
                    'position'    => $validated['admin_position'] ?? 'Company Administrator',
                    'phone'       => $validated['admin_phone'] ?? null,
                ]);

                $admin->assignRole($adminRole);
            }
        });

        return redirect()->route('companies.index')
            ->with('success', 'Company berhasil dibuat beserta departments dan admin.');
    }

    public function edit(Company $company)
    {
        $this->authorize('edit departments');
        $departments = $company->departments()->orderBy('name')->get();
        return view('companies.edit', compact('company', 'departments'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorize('edit departments');

        $validated = $request->validate([
            'code'            => 'required|string|max:10|unique:companies,code,' . $company->id,
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'odoo_url'        => 'nullable|url|max:255',
            'odoo_db'         => 'nullable|string|max:255',
            'odoo_username'   => 'nullable|string|max:255',
            'odoo_password'   => 'nullable|string|max:255',
            'odoo_company_id' => 'nullable|integer',
            'finance_api_url' => 'nullable|url|max:255',
            'finance_api_key' => 'nullable|string|max:255',
        ]);

        $validated['is_active']       = $request->boolean('is_active', true);
        $validated['connect_odoo']    = $request->boolean('connect_odoo', false);
        $validated['connect_finance'] = $request->boolean('connect_finance', false);

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        $this->authorize('delete departments');

        if ($company->users()->count() > 0 || $company->departments()->count() > 0) {
            return redirect()->route('companies.index')
                ->with('error', 'Cannot delete company with associated users or departments.');
        }

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    public function switchCompany(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['superadmin', 'procurement_holding'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($request->company_id) {
            session(['active_company_id' => $request->company_id]);
        } else {
            session()->forget('active_company_id');
        }

        return redirect()->back()->with('success', 'Active company switched successfully.');
    }

    public function testOdooConnection(Request $request)
    {
        $url      = $request->input('odoo_url');
        $db       = $request->input('odoo_db');
        $username = $request->input('odoo_username');
        $password = $request->input('odoo_password');

        if (!$url || !$db || !$username || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi URL, Database, Username, dan Password Odoo terlebih dahulu.'
            ]);
        }

        $startTime = microtime(true);
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(10)
                ->post("{$url}/jsonrpc", [
                    'jsonrpc' => '2.0',
                    'method'  => 'call',
                    'params'  => [
                        'service' => 'common',
                        'method'  => 'login',
                        'args'    => [$db, $username, $password]
                    ],
                    'id' => rand(1, 1000)
                ]);

            $latencyMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['result']) && $json['result'] !== false) {
                    return response()->json([
                        'success'    => true,
                        'message'    => 'Koneksi Berhasil! User ID: ' . $json['result'],
                        'latency_ms' => $latencyMs
                    ]);
                }

                return response()->json([
                    'success'    => false,
                    'message'    => 'Koneksi gagal. Periksa username/password atau nama database.',
                    'latency_ms' => $latencyMs
                ]);
            }

            return response()->json([
                'success'    => false,
                'message'    => 'HTTP Error ' . $response->status(),
                'latency_ms' => $latencyMs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'    => false,
                'message'    => 'Kesalahan koneksi: ' . $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000)
            ]);
        }
    }

    public function testFinanceConnection(Request $request)
    {
        $apiUrl = $request->input('finance_api_url');
        $apiKey = $request->input('finance_api_key');

        if (!$apiUrl || !$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi URL Base dan API Key terlebih dahulu.'
            ]);
        }

        $categoriesUrl = str_replace('/check', '/categories', $apiUrl);
        $startTime     = microtime(true);

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->get($categoriesUrl);

            $latencyMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['status']) && $body['status'] === 'success') {
                    return response()->json([
                        'success'    => true,
                        'message'    => 'Koneksi Berhasil! Finance API terhubung.',
                        'latency_ms' => $latencyMs
                    ]);
                }
            }

            return response()->json([
                'success'    => false,
                'message'    => 'Koneksi gagal (HTTP ' . $response->status() . ').',
                'latency_ms' => $latencyMs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'    => false,
                'message'    => 'Kesalahan koneksi: ' . $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000)
            ]);
        }
    }
}
