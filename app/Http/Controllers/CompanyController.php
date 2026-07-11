<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $this->authorize('view departments'); // We can reuse standard admin permissions or check role
        $companies = Company::paginate(10);
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
            'code' => 'required|string|max:10|unique:companies,code',
            'name' => 'required|string|max:255',
            'odoo_url' => 'nullable|url|max:255',
            'odoo_db' => 'nullable|string|max:255',
            'odoo_username' => 'nullable|string|max:255',
            'odoo_password' => 'nullable|string|max:255',
            'odoo_company_id' => 'nullable|integer',
            'finance_api_url' => 'nullable|url|max:255',
            'finance_api_key' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['connect_odoo'] = $request->boolean('connect_odoo', false);
        $validated['connect_finance'] = $request->boolean('connect_finance', false);

        Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company created successfully.');
    }

    public function edit(Company $company)
    {
        $this->authorize('edit departments');
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorize('edit departments');

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:companies,code,' . $company->id,
            'name' => 'required|string|max:255',
            'odoo_url' => 'nullable|url|max:255',
            'odoo_db' => 'nullable|string|max:255',
            'odoo_username' => 'nullable|string|max:255',
            'odoo_password' => 'nullable|string|max:255',
            'odoo_company_id' => 'nullable|integer',
            'finance_api_url' => 'nullable|url|max:255',
            'finance_api_key' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['connect_odoo'] = $request->boolean('connect_odoo', false);
        $validated['connect_finance'] = $request->boolean('connect_finance', false);

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        $this->authorize('delete departments');
        
        // Prevent deletion if there are associated users or departments
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
        // Require user to be superadmin or holding
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
        $url = $request->input('odoo_url');
        $db = $request->input('odoo_db');
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
                    'method' => 'call',
                    'params' => [
                        'service' => 'common',
                        'method' => 'login',
                        'args' => [$db, $username, $password]
                    ],
                    'id' => rand(1, 1000)
                ]);

            $latencyMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['result']) && $json['result'] !== false) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Koneksi Berhasil! Terotentikasi di Odoo dengan User ID: ' . $json['result'],
                        'latency_ms' => $latencyMs
                    ]);
                }
                
                $errorMsg = 'Koneksi gagal. Periksa kembali username/password atau nama database.';
                if (isset($json['error'])) {
                    $errorMsg .= ' Detail: ' . json_encode($json['error']);
                }
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'latency_ms' => $latencyMs
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'HTTP Error ' . $response->status() . ': ' . $response->body(),
                'latency_ms' => $latencyMs
            ]);
        } catch (\Exception $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success' => false,
                'message' => 'Kesalahan koneksi: ' . $e->getMessage(),
                'latency_ms' => $latencyMs
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
        $startTime = microtime(true);

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->get($categoriesUrl);

            $latencyMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['status']) && $body['status'] === 'success') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Koneksi Berhasil! Finance API terhubung dan data kategori berhasil dimuat.',
                        'latency_ms' => $latencyMs
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Koneksi gagal (HTTP ' . $response->status() . '). ' . $response->body(),
                'latency_ms' => $latencyMs
            ]);
        } catch (\Exception $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success' => false,
                'message' => 'Kesalahan koneksi: ' . $e->getMessage(),
                'latency_ms' => $latencyMs
            ]);
        }
    }
}
