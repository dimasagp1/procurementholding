<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function general()
    {
        $settings = [
            'app_name' => Setting::get('app_name', config('app.name')),
            'app_logo' => Setting::get('app_logo'),
            'export_logo' => Setting::get('export_logo'),
            'app_favicon' => Setting::get('app_favicon'),
            'signature_om' => Setting::get('signature_om'),
            'signature_gm' => Setting::get('signature_gm'),
            'signature_proc' => Setting::get('signature_proc'),
            'odoo_url' => Setting::get('odoo_url', env('ODOO_URL')),
            'odoo_db' => Setting::get('odoo_db', env('ODOO_DB')),
            'odoo_username' => Setting::get('odoo_username', env('ODOO_USERNAME')),
            'odoo_password' => Setting::get('odoo_password', env('ODOO_PASSWORD')),
        ];
        
        return view('settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|max:2048',
            'export_logo' => 'nullable|image|max:2048',
            'app_favicon' => 'nullable|image|max:1024',
            'signature_om' => 'nullable|image|max:1024',
            'signature_gm' => 'nullable|image|max:1024',
            'signature_proc' => 'nullable|image|max:1024',
        ]);

        Setting::set('app_name', $request->app_name);

        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('settings', 'public');
            Setting::set('app_logo', $path);
        }

        if ($request->hasFile('export_logo')) {
            $path = $request->file('export_logo')->store('settings', 'public');
            Setting::set('export_logo', $path);
        }

        if ($request->hasFile('app_favicon')) {
            $path = $request->file('app_favicon')->store('settings', 'public');
            Setting::set('app_favicon', $path);
        }

        if ($request->hasFile('signature_om')) {
            $path = $request->file('signature_om')->store('settings', 'public');
            Setting::set('signature_om', $path);
        }

        if ($request->hasFile('signature_gm')) {
            $path = $request->file('signature_gm')->store('settings', 'public');
            Setting::set('signature_gm', $path);
        }

        if ($request->hasFile('signature_proc')) {
            $path = $request->file('signature_proc')->store('settings', 'public');
            Setting::set('signature_proc', $path);
        }

        return redirect()->back()->with('success', 'General settings updated successfully.');
    }

    public function testFinanceApi(Request $request)
    {
        $apiUrl  = $request->input('finance_api_url') ?? Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey  = $request->input('procurement_api_key') ?? Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));
        $apiHost = env('FINANCE_API_HOST');

        if (!$apiUrl) {
            return response()->json(['success' => false, 'message' => 'Finance API URL belum dikonfigurasi.']);
        }
        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'Procurement API Key belum dikonfigurasi.']);
        }

        $headers = [
            'Accept'    => 'application/json',
            'X-API-KEY' => $apiKey,
        ];

        $startTime = microtime(true);
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders($headers)
                ->timeout(8)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 8,
                    ],
                ])
                ->post($apiUrl, [
                    'department_id'    => 1,
                    'category_name'    => 'TEST_CONNECTION',
                    'month'            => date('Y-m'),
                    'requested_amount' => 0,
                ]);

            $latencyMs   = round((microtime(true) - $startTime) * 1000);
            $responseBody = $response->json();
            $httpStatus  = $response->status();

            // HTTP 301/302 berarti ada redirect (biasanya HTTP→HTTPS)
            if (in_array($httpStatus, [301, 302, 307, 308])) {
                return response()->json([
                    'success'     => false,
                    'message'     => "Server merespons dengan redirect HTTP {$httpStatus}. URL target: " . ($response->header('Location') ?? '-') . ". Coba ganti FINANCE_API_URL ke HTTPS atau sesuaikan konfigurasi.",
                    'http_status' => $httpStatus,
                    'latency_ms'  => $latencyMs,
                    'url_tried'   => $apiUrl,
                ]);
            }

            if ($response->successful()) {
                return response()->json([
                    'success'          => true,
                    'http_status'      => $httpStatus,
                    'latency_ms'       => $latencyMs,
                    'response_preview' => $responseBody,
                    'url_tried'        => $apiUrl,
                ]);
            }

            return response()->json([
                'success'          => false,
                'message'          => "HTTP {$httpStatus}: " . ($responseBody['message'] ?? $response->body()),
                'http_status'      => $httpStatus,
                'latency_ms'       => $latencyMs,
                'response_preview' => $responseBody,
                'url_tried'        => $apiUrl,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success'    => false,
                'message'    => 'Koneksi gagal (Connection timeout/refused): ' . $e->getMessage(),
                'latency_ms' => $latencyMs,
                'url_tried'  => $apiUrl,
            ]);
        } catch (\Exception $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success'    => false,
                'message'    => $e->getMessage(),
                'latency_ms' => $latencyMs,
                'url_tried'  => $apiUrl,
            ]);
        }
    }

    public function companyFinanceBudget(\App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }
        return view('settings.finance-budget', compact('company'));
    }

    private function resolveCompanyFinanceApiConfig(\App\Models\Company $company)
    {
        $apiUrl = $company->finance_api_url ? $company->finance_api_url : Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = $company->finance_api_key ? $company->finance_api_key : Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        return [$apiUrl, $apiKey];
    }

    public function getCompanyFinanceBudgetStatus(\App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        list($apiUrl, $apiKey) = $this->resolveCompanyFinanceApiConfig($company);

        \Illuminate\Support\Facades\Log::info("getCompanyFinanceBudgetStatus called from Web. apiUrl: {$apiUrl}");

        if (!$apiUrl || !$apiKey) {
            \Illuminate\Support\Facades\Log::error("API config missing");
            return response()->json(['success' => false, 'message' => 'Konfigurasi API Finance belum lengkap.']);
        }

        $statusUrl = str_replace('/check', '/monthly-status', $apiUrl);

        try {
            \Illuminate\Support\Facades\Log::info("Requesting status to: {$statusUrl}");
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 8,
                    ],
                ])
                ->get($statusUrl);

            $responseBody = $response->json();
            \Illuminate\Support\Facades\Log::info("Response received: HTTP " . $response->status() . " Body: " . json_encode($responseBody));
            
            if (!$response->successful() || !$responseBody) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'API Finance merespons error (HTTP ' . $response->status() . '). Mungkin URL tidak sesuai.'
                ], 500);
            }

            return response()->json($responseBody);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Exception in getFinanceBudgetStatus: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghubungi API Finance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateCompanyFinanceBudget(\App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        list($apiUrl, $apiKey) = $this->resolveCompanyFinanceApiConfig($company);

        if (!$apiUrl || !$apiKey) {
            return response()->json(['success' => false, 'message' => 'Konfigurasi API Finance belum lengkap.']);
        }

        $generateUrl = str_replace('/check', '/generate-monthly', $apiUrl);

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 8,
                    ],
                ])
                ->post($generateUrl);

            $responseBody = $response->json();
            
            if (!$response->successful() || !$responseBody) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'API Finance merespons error (HTTP ' . $response->status() . '). Mungkin URL tidak sesuai.'
                ], 500);
            }

            return response()->json($responseBody);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghubungi API Finance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCompanyFinanceBudgetData(\App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        list($apiUrl, $apiKey) = $this->resolveCompanyFinanceApiConfig($company);

        if (!$apiUrl || !$apiKey) {
            return response()->json(['success' => false, 'message' => 'Konfigurasi API Finance belum lengkap.']);
        }

        $listUrl = str_replace('/check', '/list-monthly', $apiUrl);

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 8,
                    ],
                ])
                ->get($listUrl);

            $responseBody = $response->json();
            
            if (!$response->successful() || !$responseBody) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'API Finance merespons error (HTTP ' . $response->status() . ').'
                ], 500);
            }

            return response()->json($responseBody);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghubungi API Finance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCompanyFinanceBudgetDetail(Request $request, \App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        list($apiUrl, $apiKey) = $this->resolveCompanyFinanceApiConfig($company);

        if (!$apiUrl || !$apiKey) {
            return response()->json(['success' => false, 'message' => 'Konfigurasi API Finance di .env belum lengkap.']);
        }

        $monthParam = $request->query('month', date('Y-m'));
        $year  = (int) date('Y', strtotime($monthParam));
        $month = (int) date('m', strtotime($monthParam));

        $detailUrl = str_replace('/check', '/detail-monthly', $apiUrl) . '?month=' . $monthParam;

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 8,
                    ],
                ])
                ->get($detailUrl);

            $responseBody = $response->json();
            
            if (!$response->successful() || !$responseBody || $responseBody['status'] !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'API Finance merespons error atau tidak valid.'
                ], 500);
            }

            $departments = $responseBody['data'];
            $detailedData = [];

            foreach ($departments as $dept) {
                $categories = [];
                foreach ($dept['categories'] as $cat) {
                    $categoryName = $cat['category_name'];
                    
                    $penggunaan = \App\Models\PrItem::where('purpose', $categoryName)
                        ->where('status', 'not like', 'rejected%')
                        ->whereHas('purchaseRequest', function($q) use ($year, $month, $dept, $company) {
                            $q->where('status', '!=', 'draft')
                              ->where('company_id', $company->id)
                              ->whereYear('request_date', $year)
                              ->whereMonth('request_date', $month)
                              ->whereHas('department', function($dq) use ($dept) {
                                  $dq->where('name', $dept['department_name']);
                              });
                        })
                        ->sum('total_price');

                    $pagu = $cat['pagu'];
                    $realisasi = $cat['realisasi'];
                    $sisa = $pagu - $realisasi - $penggunaan;

                    $categories[] = [
                        'category_id' => $cat['category_id'],
                        'category_name' => $categoryName,
                        'pagu' => $pagu,
                        'realisasi' => $realisasi,
                        'penggunaan' => (float) $penggunaan,
                        'sisa' => $sisa
                    ];
                }

                $detailedData[] = [
                    'department_id' => $dept['department_id'],
                    'department_name' => $dept['department_name'],
                    'categories' => $categories
                ];
            }

            return response()->json([
                'status' => 'success',
                'month' => $monthParam,
                'data' => $detailedData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghubungi API Finance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncCompanyDepartments(\App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        list($apiUrl, $apiKey) = $this->resolveCompanyFinanceApiConfig($company);

        if (!$apiUrl || !$apiKey) {
            return response()->json(['status' => 'error', 'message' => 'Konfigurasi API Finance belum lengkap.']);
        }

        $departmentsUrl = str_replace('/check', '/departments', $apiUrl);

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 8,
                    ],
                ])
                ->get($departmentsUrl);

            $responseBody = $response->json();
            
            if (!$response->successful() || !$responseBody || !isset($responseBody['data']) || $responseBody['status'] !== 'success') {
                $detailUrl = str_replace('/check', '/detail-monthly', $apiUrl);
                $detailResponse = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders([
                        'Accept'    => 'application/json',
                        'X-API-KEY' => $apiKey,
                    ])
                    ->timeout(8)
                    ->get($detailUrl);
                
                $detailBody = $detailResponse->json();
                if ($detailResponse->successful() && $detailBody && isset($detailBody['data'])) {
                    $responseBody = [
                        'status' => 'success',
                        'data' => array_map(function($d) {
                            return [
                                'id' => $d['department_id'],
                                'name' => $d['department_name'],
                                'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $d['department_name']), 0, 10)),
                                'head_name' => null,
                                'description' => null,
                                'is_active' => true
                            ];
                        }, $detailBody['data'])
                    ];
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'API Finance merespons error atau tidak memiliki daftar departemen.'
                    ], 500);
                }
            }

            $syncedCount = 0;
            foreach ($responseBody['data'] as $dept) {
                $code = !empty($dept['code']) ? $dept['code'] : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $dept['name']), 0, 10));
                
                \App\Models\Department::updateOrCreate(
                    [
                        'code' => $code,
                        'company_id' => $company->id
                    ],
                    [
                        'name' => $dept['name'],
                        'manager' => $dept['head_name'] ?? ($dept['manager'] ?? null),
                        'description' => $dept['description'] ?? null,
                        'is_active' => $dept['is_active'] ?? true
                    ]
                );
                $syncedCount++;
            }

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil menyinkronkan {$syncedCount} departemen dari Finance."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghubungi API Finance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function companyOdooVendors(\App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $vendors = [];
        $errorMessage = null;

        $odooService = new \App\Services\OdooService($company);

        try {
            $vendors = $odooService->getVendorsDetailed();
        } catch (\Exception $e) {
            $errorMessage = 'Gagal mengambil data dari Odoo: ' . $e->getMessage();
        }

        return view('settings.odoo_vendors', compact('vendors', 'errorMessage', 'company'));
    }

    public function storeCompanyOdooVendor(Request $request, \App\Models\Company $company)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding']) && $user->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'vat' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
        ]);

        $odooService = new \App\Services\OdooService($company);

        try {
            $partnerId = $odooService->createVendor($request->only([
                'name', 'email', 'phone', 'mobile', 'street', 'city', 'vat', 'website'
            ]));

            if ($partnerId) {
                return redirect()->back()->with('success', 'Vendor baru berhasil didaftarkan ke Odoo!');
            }
            return redirect()->back()->with('error', 'Gagal membuat vendor di Odoo.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateOdooCredentials(Request $request)
    {
        $request->validate([
            'odoo_url' => 'required|url|max:255',
            'odoo_db' => 'required|string|max:255',
            'odoo_username' => 'required|string|max:255',
            'odoo_password' => 'required|string|max:255',
        ]);

        Setting::set('odoo_url', $request->odoo_url);
        Setting::set('odoo_db', $request->odoo_db);
        Setting::set('odoo_username', $request->odoo_username);
        Setting::set('odoo_password', $request->odoo_password);

        return redirect()->back()->with('success', 'Kredensial Odoo berhasil diperbarui.');
    }

    public function testOdooApi(Request $request)
    {
        $url = $request->input('odoo_url') ?? Setting::get('odoo_url', env('ODOO_URL'));
        $db = $request->input('odoo_db') ?? Setting::get('odoo_db', env('ODOO_DB'));
        $username = $request->input('odoo_username') ?? Setting::get('odoo_username', env('ODOO_USERNAME'));
        $password = $request->input('odoo_password') ?? Setting::get('odoo_password', env('ODOO_PASSWORD'));

        if (!$url || !$db || !$username || !$password) {
            return response()->json(['success' => false, 'message' => 'Konfigurasi Odoo belum lengkap.']);
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
                
                $errorMsg = $json['error']['message'] ?? 'Autentikasi gagal. Periksa kembali username/password/API Key.';
                return response()->json([
                    'success' => false,
                    'message' => 'Odoo API Error: ' . $errorMsg,
                    'latency_ms' => $latencyMs
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server Odoo. HTTP Status: ' . $response->status(),
                'latency_ms' => $latencyMs
            ]);

        } catch (\Exception $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success' => false,
                'message' => 'Error koneksi Odoo: ' . $e->getMessage(),
                'latency_ms' => $latencyMs
            ]);
        }
    }

    public function myCompanySettings()
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            abort(404, 'Anda tidak terikat ke perusahaan manapun.');
        }

        return view('settings.my-company', compact('company'));
    }

    public function updateMyCompanySettings(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            abort(404, 'Anda tidak terikat ke perusahaan manapun.');
        }

        $validated = $request->validate([
            'connect_odoo'    => 'required|boolean',
            'connect_finance' => 'required|boolean',
            'odoo_url'         => 'nullable|url|max:255',
            'odoo_db'          => 'nullable|string|max:255',
            'odoo_username'    => 'nullable|string|max:255',
            'odoo_password'    => 'nullable|string|max:255',
            'odoo_company_id'  => 'nullable|integer',
            'finance_api_url'  => 'nullable|url|max:255',
            'finance_api_key'  => 'nullable|string|max:255',
        ]);

        $company->update([
            'connect_odoo'    => $request->boolean('connect_odoo', false),
            'connect_finance' => $request->boolean('connect_finance', false),
            'odoo_url'        => $validated['odoo_url'] ?? null,
            'odoo_db'         => $validated['odoo_db'] ?? null,
            'odoo_username'   => $validated['odoo_username'] ?? null,
            'odoo_company_id' => $validated['odoo_company_id'] ?? null,
            'finance_api_url' => $validated['finance_api_url'] ?? null,
            'finance_api_key' => $validated['finance_api_key'] ?? null,
        ]);

        if (!empty($validated['odoo_password'])) {
            $company->update(['odoo_password' => $validated['odoo_password']]);
        }

        return redirect()->back()->with('success', 'Pengaturan integrasi perusahaan berhasil diperbarui.');
    }

}

