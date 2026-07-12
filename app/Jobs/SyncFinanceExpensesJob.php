<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PurchaseRequest;
use App\Models\Setting;
use App\Models\Department;
use App\Models\Purpose;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFinanceExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $purchaseRequestId;

    /**
     * Create a new job instance.
     */
    public function __construct($purchaseRequestId)
    {
        $this->purchaseRequestId = $purchaseRequestId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pr = PurchaseRequest::with(['company', 'items', 'department'])->find($this->purchaseRequestId);
        if (!$pr) {
            return;
        }

        $company = $pr->company;

        // Guard: only sync if company has Finance integration enabled
        if (!$company || !$company->connect_finance) {
            return;
        }

        $apiUrl = $company->finance_api_url ?: Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = $company->finance_api_key ?: Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (!$apiUrl || !$apiKey) {
            return;
        }

        $baseApiUrl = dirname($apiUrl);
        $recordUrl = $baseApiUrl . '/record-expense';
        $removeUrl = $baseApiUrl . '/remove-expense';

        $headers = [
            'Accept' => 'application/json',
            'X-API-KEY' => $apiKey,
        ];

        // Group items that are actually committed/ordered (ordered, delivered, completed)
        $committedItems = $pr->items()->whereIn('status', ['ordered', 'delivered', 'completed'])->get();

        if ($committedItems->isEmpty()) {
            try {
                Http::withoutVerifying()
                    ->withHeaders($headers)
                    ->timeout(10)
                    ->post($removeUrl, [
                        'reference' => $pr->pr_number,
                    ]);
            } catch (\Exception $e) {
                Log::error("Failed to remove expense from Finance API for PR {$pr->pr_number} in Job: " . $e->getMessage());
            }
            return;
        }

        $grouped = [];
        foreach ($committedItems as $item) {
            if (empty($item->purpose)) {
                continue;
            }
            $price = $item->actual_price !== null ? (float) $item->actual_price : (float) $item->estimated_price;
            $amt = (float) ($item->quantity * $price);
            if (!isset($grouped[$item->purpose])) {
                $grouped[$item->purpose] = [
                    'amount' => 0,
                    'qty' => 0,
                    'items_detail' => []
                ];
            }
            $grouped[$item->purpose]['amount'] += $amt;
            $grouped[$item->purpose]['qty'] += (float) $item->quantity;
            $grouped[$item->purpose]['items_detail'][] = ($item->item_name ?: 'Item') . ' (x' . (int)$item->quantity . ')';
        }

        foreach ($grouped as $purpose => $data) {
            try {
                $resolved = $this->getResponsibleDepartmentForPurpose($purpose, $pr->department_id, $pr->department?->name, $company);

                $itemsSummary = implode(', ', $data['items_detail']);
                $description = "Realisasi PR {$pr->pr_number} ({$itemsSummary}) - Kategori {$purpose}";

                $response = Http::withoutVerifying()
                    ->withHeaders($headers)
                    ->timeout(10)
                    ->post($recordUrl, [
                        'department_id' => $resolved['id'],
                        'department_name' => $resolved['name'],
                        'category_name' => $purpose,
                        'amount' => $data['amount'],
                        'qty' => $data['qty'],
                        'date' => $pr->request_date->format('Y-m-d'),
                        'reference' => $pr->pr_number,
                        'description' => $description,
                    ]);

                if (!$response->successful()) {
                    Log::warning("Finance API record expense returned error code {$response->status()} for PR {$pr->pr_number}, category {$purpose} in Job: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Failed to record expense to Finance API for PR {$pr->pr_number}, category {$purpose} in Job: " . $e->getMessage());
            }
        }
    }

    private function getResponsibleDepartmentForPurpose($purpose, $fallbackDeptId = null, $fallbackDeptName = null, $company = null)
    {
        $purposes = $this->getPurposesFromFinance($company);
        
        $matched = null;
        if (!empty($fallbackDeptName)) {
            // Try exact match within requesting department first
            $matched = collect($purposes)->first(function ($item) use ($purpose, $fallbackDeptName) {
                return strcasecmp($item->name ?? '', $purpose) === 0
                    && strcasecmp($item->department_name ?? '', $fallbackDeptName) === 0;
            });

            // Try loose/contained match within requesting department
            if (!$matched) {
                $matched = collect($purposes)->first(function ($item) use ($purpose, $fallbackDeptName) {
                    if (strcasecmp($item->name ?? '', $purpose) !== 0) {
                        return false;
                    }
                    $n1 = strtolower($item->department_name ?? '');
                    $n2 = strtolower($fallbackDeptName);
                    return str_contains($n1, $n2) || str_contains($n2, $n1)
                        || (str_contains($n1, 'human') && str_contains($n2, 'human'))
                        || (str_contains($n1, 'finance') && str_contains($n2, 'finance'));
                });
            }
        }

        // If no match in the requesting department, find first matching category globally
        if (!$matched) {
            $matched = collect($purposes)->first(function ($item) use ($purpose) {
                return strcasecmp($item->name ?? '', $purpose) === 0;
            });
        }

        if ($matched && isset($matched->department_name) && !empty($matched->department_name)) {
            $deptName = $matched->department_name;
            $localDept = Department::where('name', $deptName)->first();
            if (!$localDept) {
                $localDept = Department::all()->first(function($d) use ($deptName) {
                    $n1 = strtolower($d->name);
                    $n2 = strtolower($deptName);
                    return str_contains($n1, $n2) || str_contains($n2, $n1)
                        || (str_contains($n1, 'human') && str_contains($n2, 'human'))
                        || (str_contains($n1, 'finance') && str_contains($n2, 'finance'));
                });
            }
            return [
                'id' => $localDept ? $localDept->id : null,
                'name' => $deptName
            ];
        }

        return [
            'id' => $fallbackDeptId,
            'name' => $fallbackDeptName
        ];
    }

    private function getPurposesFromFinance($company = null)
    {
        $apiUrl = $company && $company->finance_api_url ? $company->finance_api_url : Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = $company && $company->finance_api_key ? $company->finance_api_key : Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (!$apiUrl || !$apiKey) {
            return Purpose::all();
        }

        $baseApiUrl = dirname($apiUrl);
        $categoriesUrl = $baseApiUrl . '/categories';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(5)
                ->get($categoriesUrl);

            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['status']) && $body['status'] === 'success' && isset($body['data'])) {
                    return collect($body['data'])->map(function ($item) {
                        if (is_array($item)) {
                            return (object) [
                                'name' => $item['name'] ?? '',
                                'department_name' => $item['department_name'] ?? 'Lainnya'
                            ];
                        }
                        return (object) [
                            'name' => $item,
                            'department_name' => 'Lainnya'
                        ];
                    });
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch purposes from Finance API in Job: ' . $e->getMessage());
        }

        return Purpose::all();
    }
}
