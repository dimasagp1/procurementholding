<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function financeBudget()
    {
        return view('settings.finance-budget');
    }

    public function getFinanceBudgetStatus()
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        \Illuminate\Support\Facades\Log::info("getFinanceBudgetStatus called from Web. apiUrl: {$apiUrl}");

        if (!$apiUrl || !$apiKey) {
            \Illuminate\Support\Facades\Log::error("API config missing");
            return response()->json(['success' => false, 'message' => 'Konfigurasi API Finance belum lengkap. Silakan simpan kredensial terlebih dahulu.']);
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

    public function generateFinanceBudget()
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

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

    public function getFinanceBudgetData()
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

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

    public function getFinanceBudgetDetail(Request $request)
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

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

            // Now, we compute the PROC usage for each category returned by FAT
            $departments = $responseBody['data'];
            $detailedData = [];

            foreach ($departments as $dept) {
                $categories = [];
                foreach ($dept['categories'] as $cat) {
                    $categoryName = $cat['category_name'];
                    
                    // Sum the PR items in PROC for this department, category, and month
                    $penggunaan = \App\Models\PrItem::where('purpose', $categoryName)
                        ->where('status', 'not like', 'rejected%')
                        ->whereHas('purchaseRequest', function($q) use ($year, $month, $dept) {
                            $q->where('status', '!=', 'draft')
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

    public function updateFinanceCredentials(Request $request)
    {
        $request->validate([
            'finance_api_url' => 'required|url|max:255',
            'procurement_api_key' => 'required|string|max:255',
        ]);

        Setting::set('finance_api_url', $request->finance_api_url);
        Setting::set('procurement_api_key', $request->procurement_api_key);

        return response()->json([
            'status' => 'success',
            'message' => 'Kredensial API Finance berhasil disimpan.',
        ]);
    }

    public function syncDepartments()
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

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
            
            // Fallback if the dedicated /departments endpoint is not available or errors
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
                    ['name' => $dept['name']],
                    [
                        'code' => $code,
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
}
