<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PrItem;
use App\Models\Approval;
use App\Models\Department;
use App\Models\Uom;
use App\Models\Purpose;
use App\Models\Setting;
use App\Models\PrItemDelivery;
use Illuminate\Http\Request;
use App\Services\OdooService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PurchaseRequestExport;
use App\Notifications\ItemDeliveredNotification;
use App\Notifications\PrSubmittedNotification;
use App\Notifications\PrStatusUpdatedNotification;
use App\Notifications\PrActionRequiredNotification;
use App\Models\User;
use App\Notifications\QueuedMailWrapper;
use Illuminate\Support\Facades\Notification;


class PurchaseRequestController extends Controller
{
    private function getPurposesFromFinance()
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (!$apiUrl || !$apiKey) {
            return Purpose::all();
        }

        $categoriesUrl = str_replace('/check', '/categories', $apiUrl);

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(3)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 2,
                        CURLOPT_TIMEOUT => 3,
                    ],
                ])
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
            \Log::warning('Failed to fetch purposes from Finance API: ' . $e->getMessage());
        }

        return Purpose::all();
    }

    private function getResponsibleDepartmentForPurpose($purpose, $fallbackDeptId = null, $fallbackDeptName = null)
    {
        $purposes = $this->getPurposesFromFinance();
        
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

    private function validateBudgetWithFinance($purpose, $requestDate, $requestedAmount = 0, $departmentId = null, $departmentName = null, $reference = null)
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (!$apiUrl || !$apiKey) {
            return ['status' => 'success', 'is_allowed' => true];
        }

        $fallbackId = $departmentId ?? (Auth::check() ? Auth::user()->department_id : null);
        $fallbackName = $departmentName ?? (Auth::check() ? Auth::user()->department?->name : null);

        $resolved = $this->getResponsibleDepartmentForPurpose($purpose, $fallbackId, $fallbackName);
        $deptId = $resolved['id'];
        $deptName = $resolved['name'];

        try {
            $headers = [
                'Accept' => 'application/json',
                'X-API-KEY' => $apiKey,
            ];

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders($headers)
                ->timeout(8)
                ->withOptions([
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT => 8,
                    ],
                ])
                ->post($apiUrl, [
                    'department_id' => $deptId,
                    'department_name' => $deptName,
                    'category_name' => $purpose,
                    'month' => date('Y-m', strtotime($requestDate)),
                    'requested_amount' => $requestedAmount,
                    'reference' => $reference,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $errorMessage = 'Gagal memvalidasi anggaran ke sistem Finance. HTTP Code: ' . $response->status();
            $jsonResponse = $response->json();
            if ($jsonResponse && isset($jsonResponse['message'])) {
                $errorMessage = 'Finance API: ' . $jsonResponse['message'];
            }

            return [
                'status' => 'error',
                'is_allowed' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'is_allowed' => false,
                'message' => 'Terjadi kesalahan koneksi API Finance: ' . $e->getMessage()
            ];
        }
    }

    public function checkBudget(Request $request)
    {
        $request->validate([
            'request_date' => 'required|date',
            'department_id' => 'nullable|integer',
            'purpose' => 'nullable|string',
            'requested_amount' => 'nullable|numeric',
            'reference' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.purpose' => 'required_with:items|string',
            'items.*.amount' => 'required_with:items|numeric',
        ]);

        if (!Setting::get('finance_api_url', env('FINANCE_API_URL')) || !Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Konfigurasi API Finance belum lengkap.',
                'is_allowed' => true
            ], 200);
        }

        $departmentId = $request->department_id;
        $departmentName = null;
        if ($departmentId) {
            $dept = Department::find($departmentId);
            if ($dept) {
                $departmentName = $dept->name;
            }
        }

        if ($request->has('items') && is_array($request->items)) {
            $purposeAmounts = [];
            foreach ($request->items as $item) {
                $p = $item['purpose'];
                $amt = (float) $item['amount'];
                if (empty($p))
                    continue;
                $purposeAmounts[$p] = ($purposeAmounts[$p] ?? 0) + $amt;
            }

            $results = [];
            $allAllowed = true;
            $firstErrorMessage = null;

            foreach ($purposeAmounts as $purpose => $amount) {
                $res = $this->validateBudgetWithFinance($purpose, $request->request_date, $amount, $departmentId, $departmentName, $request->reference);
                $results[$purpose] = [
                    'is_allowed' => $res['is_allowed'] ?? true,
                    'remaining_budget' => $res['remaining_budget'] ?? null,
                    'budget_limit' => $res['budget_limit'] ?? null,
                    'current_usage' => $res['current_usage'] ?? null,
                    'recorded_expense_amount' => $res['recorded_expense_amount'] ?? null,
                    'message' => $res['message'] ?? null,
                ];
                if (!($res['is_allowed'] ?? true)) {
                    $allAllowed = false;
                    if (!$firstErrorMessage) {
                        $firstErrorMessage = $res['message'] ?? "Anggaran tidak mencukupi untuk kategori {$purpose}.";
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'is_allowed' => $allAllowed,
                'message' => $firstErrorMessage,
                'results' => $results
            ]);
        }

        $result = $this->validateBudgetWithFinance(
            $request->purpose,
            $request->request_date,
            $request->requested_amount ?? 0,
            $departmentId,
            $departmentName
        );

        return response()->json($result);
    }

    public function index(Request $request)
    {
        $this->authorize('view pr');
        $user = Auth::user();
        $query = PurchaseRequest::with(['user', 'department', 'items'])
            ->where('status', '!=', 'draft');


        if ($user->hasRole('user') && !$user->hasAnyRole(['operational_manager', 'manager_fat', 'general_manager', 'procurement', 'superadmin'])) {
            $query->where('user_id', $user->id);
        } else {
            if (!$request->boolean('awaiting_approval')) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id); // always can see their own

                    if ($user->hasRole('superadmin') || $user->hasRole('procurement') || $user->hasRole('general_manager')) {
                        $q->orWhereRaw('1=1'); // can see all
                    } else {
                        if ($user->hasRole('operational_manager')) {
                            $q->orWhere('pr_type', 'operational');
                        }
                        if ($user->hasRole('manager_fat')) {
                            $q->orWhere('pr_type', 'non_operational');
                        }
                    }
                });
            }
        }

        // Search
        $this->applySearchFilter($query, $request->search);

        // Awaiting Approval Filter
        if ($request->boolean('awaiting_approval')) {
            if ($user->hasRole('superadmin')) {
                $query->whereIn('status', ['pending', 'approved_om', 'approved_gm']);
            } else {
                $query->where(function ($q) use ($user) {
                    if ($user->hasRole('operational_manager')) {
                        $q->orWhere(function ($subQ) use ($user) {
                            $subQ->where('status', 'pending')
                                ->where('pr_type', 'operational');
                        });
                    }
                    if ($user->hasRole('manager_fat')) {
                        $q->orWhere(function ($subQ) {
                            $subQ->where('status', 'pending')
                                ->where('pr_type', 'non_operational');
                        });
                    }
                    if ($user->hasRole('general_manager')) {
                        $q->orWhere('status', 'approved_om');
                    }
                    if ($user->hasRole('procurement')) {
                        $q->orWhere('status', 'approved_gm');
                    }
                });
            }
        }


        // Department Filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchaseRequests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $departments = Department::all();

        return view('purchase_requests.index', compact('purchaseRequests', 'departments'));
    }


    public function create()
    {
        if (!Auth::user()->department_id) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda belum terhubung dengan Departemen apa pun. Silakan hubungi admin.');
        }
        $departments = Department::where('is_active', true)->get();
        $uoms = Uom::all();
        $purposes = $this->getPurposesFromFinance();
        $masterItems = \App\Models\MasterItem::orderBy('name')->get();
        return view('purchase_requests.create', compact('departments', 'uoms', 'purposes', 'masterItems'));
    }


    public function store(Request $request)
    {
        \Log::info('PR Store attempt', $request->all());

        if (!Auth::user()->department_id) {
            return redirect()->back()->with('error', 'Akun Anda belum terhubung dengan Departemen apa pun.')->withInput();
        }

        $purposeAmounts = [];
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $p = $item['purpose'] ?? '';
                $amt = (float) ($item['quantity'] ?? 0) * (float) ($item['estimated_price'] ?? 0);
                $purposeAmounts[$p] = ($purposeAmounts[$p] ?? 0) + $amt;
            }
        }

        $isDraft = $request->action === 'draft';
        // No budget blocking check during user initial submission since estimated prices are 0.

        \DB::beginTransaction();
        try {
            if ($isDraft) {
                $request->validate([
                    'request_date' => 'required|date',
                    'pr_type' => 'required|in:operational,non_operational',
                    'items' => 'required|array|min:1',
                    'items.*.purpose' => 'nullable|string|max:255',
                    'items.*.item_name' => 'nullable|string|max:255',
                    'items.*.manual_item_name' => 'nullable|string|max:255',
                    'items.*.quantity' => 'nullable|numeric|min:0.01',
                    'items.*.uom' => 'nullable|string|max:50',
                    'items.*.manual_uom' => 'nullable|string|max:50',
                    'items.*.due_date' => 'nullable|string|max:255',
                    'items.*.description' => 'nullable|string',
                    'items.*.attachment' => 'nullable|file|max:10240',
                ]);
            } else {
                $request->validate([
                    'request_date' => 'required|date',
                    'pr_type' => 'required|in:operational,non_operational',
                    'items' => 'required|array|min:1',
                    'items.*.purpose' => 'required|string|max:255',
                    'items.*.item_name' => 'required|string|max:255',
                    'items.*.manual_item_name' => 'nullable|string|max:255',
                    'items.*.quantity' => 'required|numeric|min:0.01',
                    'items.*.uom' => 'required|string|max:50',
                    'items.*.manual_uom' => 'nullable|string|max:50',
                    'items.*.due_date' => 'nullable|string|max:255',
                    'items.*.description' => 'nullable|string',
                    'items.*.attachment' => 'nullable|file|max:10240',
                ]);
            }

            $isDraft = $request->action === 'draft';

            $uniquePurposes = collect($request->items)->pluck('purpose')->filter()->unique()->implode(', ');

            $purchaseRequest = PurchaseRequest::create([
                'user_id' => Auth::id(),
                'department_id' => Auth::user()->department_id,
                'request_date' => $request->request_date,
                'purpose' => $uniquePurposes ?: 'Multi-purpose',
                'pr_type' => $request->pr_type,
                'status' => $isDraft ? 'draft' : 'pending',
                'total_amount' => 0,
            ]);


            $totalAmount = 0;
            foreach ($request->items as $item) {
                $attachmentPath = null;
                if (isset($item['attachment']) && $item['attachment']->isValid()) {
                    $attachmentPath = $item['attachment']->store('pr-attachments', 'public');
                }

                $qty = (float) ($item['quantity'] ?? 0);
                $itemTotal = ($item['estimated_price'] ?? 0) * $qty;
                $totalAmount += $itemTotal;

                $finalItemName = ($item['item_name'] ?? '') === 'other' ? ($item['manual_item_name'] ?? null) : ($item['item_name'] ?? null);
                $finalUom = ($item['uom'] ?? '') === 'other' ? ($item['manual_uom'] ?? null) : ($item['uom'] ?? null);

                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_name' => $finalItemName,
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty ?: null,
                    'uom' => $finalUom,
                    'estimated_price' => $item['estimated_price'] ?? 0,
                    'total_price' => $itemTotal,
                    'due_date' => $item['due_date'] ?? null,
                    'attachment' => $attachmentPath,
                    'status' => $isDraft ? 'pending' : 'pending_estimate',
                    'purpose' => $item['purpose'] ?? null,
                    'rekap_po_odoo' => $request->pr_type === 'operational',
                    'is_incoming' => $request->pr_type === 'operational',
                ]);
            }

            $purchaseRequest->update(['total_amount' => $totalAmount]);

            \DB::commit();

            if (!$isDraft) {
                // Notify Procurement and Superadmin that a new PR is waiting for estimate input
                $procurements = User::role(['procurement', 'superadmin'])->get();
                $filtered = $procurements->reject(function ($user) {
                    return $user->id == auth()->id();
                });

                if ($filtered->isNotEmpty()) {
                    Notification::send($filtered, new PrSubmittedNotification($purchaseRequest));
                    Notification::send($filtered, new QueuedMailWrapper(new PrSubmittedNotification($purchaseRequest)));
                }
            }

            return redirect()->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase Request created successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PR Store failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan PR: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $this->authorize('view pr', $purchaseRequest);

        $purchaseRequest->load(['items.deliveries', 'approvals', 'user', 'department']);
        $purposes = $this->getPurposesFromFinance();
        return view('purchase_requests.show', compact('purchaseRequest', 'purposes'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        if (Auth::user()->hasRole('procurement_holding')) {
            abort(403, 'Unauthorized action.');
        }

        $this->authorize('edit pr', $purchaseRequest);

        if (!$purchaseRequest->isEditable()) {
            return redirect()->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'This PR is not in an editable state.');
        }

        $departments = Department::where('is_active', true)->get();
        $uoms = Uom::all();
        $purposes = $this->getPurposesFromFinance();
        $masterItems = \App\Models\MasterItem::orderBy('name')->get();
        $isPending = $purchaseRequest->status === 'pending';
        return view('purchase_requests.edit', compact('purchaseRequest', 'departments', 'uoms', 'purposes', 'isPending', 'masterItems'));
    }



    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (Auth::user()->hasRole('procurement_holding')) {
            abort(403, 'Unauthorized action.');
        }

        \Log::info('PR Update attempt', ['id' => $purchaseRequest->id, 'data' => $request->all()]);
        $this->authorize('edit pr', $purchaseRequest);

        $isPrDraft = $purchaseRequest->status === 'draft';

        \DB::beginTransaction();
        try {
            if ($isPrDraft) {
                // If it is a draft, fully editable as before
                $isDraftSubmit = $request->action === 'draft';
                if ($isDraftSubmit) {
                    $request->validate([
                        'request_date' => 'required|date',
                        'pr_type' => 'required|in:operational,non_operational',
                        'items' => 'required|array|min:1',
                        'items.*.purpose' => 'nullable|string|max:255',
                        'items.*.item_name' => 'nullable|string|max:255',
                        'items.*.manual_item_name' => 'nullable|string|max:255',
                        'items.*.quantity' => 'nullable|numeric|min:0.01',
                        'items.*.uom' => 'nullable|string|max:50',
                        'items.*.manual_uom' => 'nullable|string|max:50',
                        'items.*.due_date' => 'nullable|string|max:255',
                        'items.*.description' => 'nullable|string',
                        'items.*.attachment' => 'nullable|file|max:10240',
                    ]);
                } else {
                    $request->validate([
                        'request_date' => 'required|date',
                        'pr_type' => 'required|in:operational,non_operational',
                        'items' => 'required|array|min:1',
                        'items.*.purpose' => 'required|string|max:255',
                        'items.*.item_name' => 'required|string|max:255',
                        'items.*.manual_item_name' => 'nullable|string|max:255',
                        'items.*.quantity' => 'required|numeric|min:0.01',
                        'items.*.uom' => 'required|string|max:50',
                        'items.*.manual_uom' => 'nullable|string|max:50',
                        'items.*.due_date' => 'nullable|string|max:255',
                        'items.*.description' => 'nullable|string',
                        'items.*.attachment' => 'nullable|file|max:10240',
                    ]);
                }

                $totalAmount = 0;
                $submittedItemIds = [];

                foreach ($request->items as $itemData) {
                    $attachmentPath = null;
                    if (isset($itemData['attachment']) && $itemData['attachment']->isValid()) {
                        $attachmentPath = $itemData['attachment']->store('pr-attachments', 'public');
                    }

                    if (isset($itemData['id'])) {
                        $item = PrItem::where('id', $itemData['id'])
                            ->where('purchase_request_id', $purchaseRequest->id)
                            ->first();

                        if ($item) {
                            $submittedItemIds[] = $item->id;
                            $finalItemName = $itemData['item_name'] === 'other' ? $itemData['manual_item_name'] : $itemData['item_name'];
                            $finalUom = $itemData['uom'] === 'other' ? $itemData['manual_uom'] : $itemData['uom'];
                            $itemTotal = ($itemData['estimated_price'] ?? 0) * $itemData['quantity'];
                            $totalAmount += $itemTotal;

                            $item->update([
                                'item_name' => $finalItemName,
                                'description' => $itemData['description'] ?? null,
                                'quantity' => $itemData['quantity'],
                                'uom' => $finalUom,
                                'estimated_price' => $itemData['estimated_price'] ?? 0,
                                'total_price' => $itemTotal,
                                'due_date' => $itemData['due_date'] ?? null,
                                'attachment' => $attachmentPath ?? $item->attachment,
                                'status' => 'pending',
                                'purpose' => $itemData['purpose'] ?? null,
                            ]);
                        }
                    } else {
                        $finalItemName = $itemData['item_name'] === 'other' ? $itemData['manual_item_name'] : $itemData['item_name'];
                        $finalUom = $itemData['uom'] === 'other' ? $itemData['manual_uom'] : $itemData['uom'];
                        $itemTotal = ($itemData['estimated_price'] ?? 0) * $itemData['quantity'];
                        $totalAmount += $itemTotal;

                        $newItem = PrItem::create([
                            'purchase_request_id' => $purchaseRequest->id,
                            'item_name' => $finalItemName,
                            'description' => $itemData['description'] ?? null,
                            'quantity' => $itemData['quantity'],
                            'uom' => $finalUom,
                            'estimated_price' => $itemData['estimated_price'] ?? 0,
                            'total_price' => $itemTotal,
                            'due_date' => $itemData['due_date'] ?? null,
                            'attachment' => $attachmentPath,
                            'status' => 'pending',
                            'purpose' => $itemData['purpose'] ?? null,
                            'rekap_po_odoo' => $purchaseRequest->pr_type === 'operational',
                            'is_incoming' => $purchaseRequest->pr_type === 'operational',
                        ]);
                        $submittedItemIds[] = $newItem->id;
                    }
                }

                // Delete removed items
                $purchaseRequest->items()->whereNotIn('id', $submittedItemIds)->delete();

                $isDraftSubmit = $request->action === 'draft';
                $uniquePurposes = collect($request->items)->pluck('purpose')->filter()->unique()->implode(', ');
                $purchaseRequest->update([
                    'request_date' => $request->request_date,
                    'purpose' => $uniquePurposes ?: 'Multi-purpose',
                    'pr_type' => $request->pr_type,
                    'total_amount' => $totalAmount,
                    'status' => $isDraftSubmit ? 'draft' : 'pending',
                ]);

                if (!$isDraftSubmit) {
                    // Changing draft to submitted -> set items to pending_estimate
                    $purchaseRequest->items()->update(['status' => 'pending_estimate']);
                    
                    // Notify Procurement
                    $procurements = User::role(['procurement', 'superadmin'])->get();
                    $filtered = $procurements->reject(fn($u) => $u->id == auth()->id());
                    if ($filtered->isNotEmpty()) {
                        Notification::send($filtered, new PrSubmittedNotification($purchaseRequest));
                        Notification::send($filtered, new QueuedMailWrapper(new PrSubmittedNotification($purchaseRequest)));
                    }
                }

            } else {
                // If it is NOT a draft (submitted PR in estimate/pending/revision stage), restricted editing!
                $request->validate([
                    'request_date' => 'required|date',
                    'items' => 'required|array|min:1',
                    'items.*.id' => 'required|exists:pr_items,id',
                    'items.*.item_name' => 'required|string|max:255',
                    'items.*.manual_item_name' => 'nullable|string|max:255',
                    'items.*.purpose' => 'required|string|max:255',
                    'items.*.quantity' => 'required|numeric|min:0.01',
                    'items.*.uom' => 'required|string|max:50',
                    'items.*.manual_uom' => 'nullable|string|max:50',
                    'items.*.due_date' => 'nullable|string|max:255',
                    'items.*.description' => 'nullable|string',
                    'items.*.attachment' => 'nullable|file|max:10240',
                ]);

                // Delete editable items that were removed
                $submittedItemIds = collect($request->items)->pluck('id')->filter()->toArray();
                $editableItemIds = $purchaseRequest->items()
                    ->where(function($q) {
                        $q->where('status', 'pending')
                          ->orWhere('status', 'pending_estimate')
                          ->orWhere('status', 'like', 'rejected_%');
                    })
                    ->pluck('id')
                    ->toArray();

                $itemsToDeleteIds = array_diff($editableItemIds, $submittedItemIds);

                if (!empty($itemsToDeleteIds)) {
                    PrItem::whereIn('id', $itemsToDeleteIds)->delete();
                }

                $totalAmount = 0;
                $hasRevisedItems = false;

                foreach ($request->items as $itemData) {
                    $item = PrItem::where('id', $itemData['id'])
                        ->where('purchase_request_id', $purchaseRequest->id)
                        ->first();

                    if ($item) {
                        $isRejected = str_starts_with($item->status, 'rejected');
                        $isPendingEstimate = $item->status === 'pending_estimate';
                        $isPending = $item->status === 'pending';

                        $canEdit = $isRejected || $isPendingEstimate || $isPending;

                        if ($canEdit) {
                            $hasRevisedItems = true;
                            $finalItemName = $itemData['item_name'] === 'other' ? $itemData['manual_item_name'] : $itemData['item_name'];
                            $finalUom = $itemData['uom'] === 'other' ? $itemData['manual_uom'] : $itemData['uom'];
                            $newTotalPrice = (float) $item->estimated_price * (float) $itemData['quantity'];
                            $totalAmount += $newTotalPrice;

                            $attachmentPath = null;
                            if (isset($itemData['attachment']) && $itemData['attachment']->isValid()) {
                                $attachmentPath = $itemData['attachment']->store('pr-attachments', 'public');
                            }

                            $item->update([
                                'item_name' => $finalItemName,
                                'purpose' => $itemData['purpose'] ?? null,
                                'description' => $itemData['description'] ?? null,
                                'quantity' => $itemData['quantity'],
                                'uom' => $finalUom,
                                'total_price' => $newTotalPrice,
                                'due_date' => $itemData['due_date'] ?? null,
                                'attachment' => $attachmentPath ?? $item->attachment,
                                'status' => 'pending_estimate', // Reset back to pending_estimate so procurement checks new total/budget
                                'revision_count' => $item->revision_count + ($isRejected ? 1 : 0),
                                'reject_reason' => null,
                                'rejected_by' => null,
                                'rejected_at' => null,
                            ]);
                        } else {
                            $totalAmount += $item->total_price;
                        }
                    }
                }

                $purchaseRequest->update([
                    'request_date' => $request->request_date,
                    'total_amount' => $totalAmount,
                ]);

                if ($hasRevisedItems) {
                    // Reset approval records since we went back to pending_estimate
                    Approval::where('purchase_request_id', $purchaseRequest->id)->delete();

                    // Notify Procurement of the update
                    $procurements = User::role(['procurement', 'superadmin'])->get();
                    $filtered = $procurements->reject(fn($u) => $u->id == auth()->id());
                    if ($filtered->isNotEmpty()) {
                        Notification::send($filtered, new PrSubmittedNotification($purchaseRequest));
                        Notification::send($filtered, new QueuedMailWrapper(new PrSubmittedNotification($purchaseRequest)));
                    }
                } else {
                    // Recalculate status since rejected items were removed and only approved/locked items remain
                    $purchaseRequest->update(['status' => 'pending']);
                    $this->checkAndAdvancePrStatus($purchaseRequest);
                }
            }

            \DB::commit();
            return redirect()->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase Request updated successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PR Update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal update PR: ' . $e->getMessage())->withInput();
        }
    }

    public function approveItem(Request $request, PrItem $item)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $pr = $item->purchaseRequest;

        $isLevel1Approver = false;
        if ($pr->pr_type === 'non_operational') {
            $isLevel1Approver = $user->hasRole('manager_fat');
        } else {
            $isLevel1Approver = $user->hasRole('operational_manager');
        }

        if (($isLevel1Approver || $user->hasRole('superadmin')) && $item->status === 'pending') {
            $item->update(['status' => 'approved_om']);

            $approverRole = $pr->pr_type === 'non_operational' ? 'manager_fat' : 'operational_manager';
            $approvalType = $pr->pr_type === 'non_operational' ? 'fatm' : 'om';

            Approval::create([
                'purchase_request_id' => $item->purchase_request_id,
                'pr_item_id' => $item->id,
                'approver_id' => $user->id,
                'approver_role' => $approverRole,
                'approval_type' => $approvalType,
                'status' => 'approved',
                'notes' => $request->notes,
                'approved_at' => now(),
            ]);

            // Evaluate PR advancement
            $this->checkAndAdvancePrStatus($item->purchaseRequest);

            // Notify requester + Superadmins + Level 1 Manager
            $recipients = $this->getSharedRecipients($item->purchaseRequest, $item->purchaseRequest->user);
            $managerTitle = $pr->pr_type === 'non_operational' ? 'Manager FAT' : 'Operational Manager';
            $message = "Item '{$item->item_name}' telah disetujui oleh {$managerTitle}.";
            if ($request->filled('notes')) {
                $message .= " Catatan: {$request->notes}";
            }
            Notification::send($recipients, new PrStatusUpdatedNotification($item->purchaseRequest, $message));
            Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($item->purchaseRequest, $message)));


        } elseif (($user->hasRole('general_manager') || $user->hasRole('superadmin')) && $item->status === 'approved_om') {
            $item->update(['status' => 'approved_gm']);

            Approval::create([
                'purchase_request_id' => $item->purchase_request_id,
                'pr_item_id' => $item->id,
                'approver_id' => $user->id,
                'approver_role' => 'general_manager',
                'approval_type' => 'gm',
                'status' => 'approved',
                'notes' => $request->notes,
                'approved_at' => now(),
            ]);

            // Evaluate PR advancement
            $this->checkAndAdvancePrStatus($item->purchaseRequest);


        } elseif (($user->hasRole('procurement') || $user->hasRole('superadmin')) && $item->status === 'approved_gm') {
            $item->update(['status' => 'approved_proc']);

            // Notify requester + Superadmins + Level 1 Manager
            $recipients = $this->getSharedRecipients($item->purchaseRequest, $item->purchaseRequest->user);
            $message = "Item '{$item->item_name}' telah disetujui oleh Procurement.";
            if ($request->filled('notes')) {
                $message .= " Catatan: {$request->notes}";
            }
            Notification::send($recipients, new PrStatusUpdatedNotification($item->purchaseRequest, $message));
            Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($item->purchaseRequest, $message)));

            Approval::create([

                'purchase_request_id' => $item->purchase_request_id,
                'pr_item_id' => $item->id,
                'approver_id' => $user->id,
                'approver_role' => 'procurement',
                'approval_type' => 'procurement',
                'status' => 'approved',
                'notes' => $request->notes,
                'approved_at' => now(),
            ]);

            // Evaluate PR advancement
            $this->checkAndAdvancePrStatus($item->purchaseRequest);
        } else {
            return redirect()->back()->with('error', 'Status item tidak dapat disetujui saat ini (Invalid approval action).');
        }

        return redirect()->back()->with('success', 'Item approved successfully.');
    }

    public function rejectItem(Request $request, PrItem $item)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $pr = $item->purchaseRequest;

        $isLevel1Approver = false;
        if ($pr->pr_type === 'non_operational') {
            $isLevel1Approver = $user->hasRole('manager_fat');
        } else {
            $isLevel1Approver = $user->hasRole('operational_manager');
        }

        if (($isLevel1Approver || $user->hasRole('superadmin')) && $item->status === 'pending') {
            $status = 'rejected_om';
            $approverRole = $pr->pr_type === 'non_operational' ? 'manager_fat' : 'operational_manager';
        } elseif (($user->hasRole('general_manager') || $user->hasRole('superadmin')) && $item->status === 'approved_om') {
            $status = 'rejected_gm';
            $approverRole = 'general_manager';
        } elseif (($user->hasRole('procurement') || $user->hasRole('superadmin')) && $item->status === 'approved_gm') {
            $status = 'rejected_proc';
            $approverRole = 'procurement';
        } else {
            return redirect()->back()->with('error', 'Invalid rejection action.');
        }

        $item->update([
            'status' => $status,
            'reject_reason' => $request->reject_reason,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ]);

        if ($approverRole === 'operational_manager' && $item->purchaseRequest->pr_type === 'non_operational') {
            $approverRole = 'manager_fat';
        }

        // Notify requester + Superadmins + Level 1 Manager
        $recipients = $this->getSharedRecipients($item->purchaseRequest, $item->purchaseRequest->user);
        Notification::send($recipients, new PrStatusUpdatedNotification($item->purchaseRequest, "Item '{$item->item_name}' ditolak. Catatan validasi: " . $request->reject_reason));
        Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($item->purchaseRequest, "Item '{$item->item_name}' ditolak. Catatan validasi: " . $request->reject_reason)));



        $approvalType = $item->purchaseRequest->pr_type === 'non_operational' && $approverRole === 'manager_fat' ? 'fatm' : str_replace('rejected_', '', $status);
        if ($approvalType === 'proc') {
            $approvalType = 'procurement';
        }

        Approval::create([
            'purchase_request_id' => $item->purchase_request_id,
            'pr_item_id' => $item->id,
            'approver_id' => $user->id,
            'approver_role' => $approverRole,
            'approval_type' => $approvalType,
            'status' => 'rejected',
            'notes' => $request->reject_reason,
            'approved_at' => now(),
        ]);

        // Evaluate PR advancement
        $this->checkAndAdvancePrStatus($item->purchaseRequest);

        return redirect()->back()->with('success', 'Item rejected successfully.');
    }

    public function approveAll(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $eligibleItems = $purchaseRequest->getEligibleItemsForUser($user, 'approve');

        if ($eligibleItems->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada item yang dapat disetujui saat ini.');
        }

        \DB::beginTransaction();
        try {
            $isOm = $user->hasRole('operational_manager');
            $isFat = $user->hasRole('manager_fat');
            $isGm = $user->hasRole('general_manager');
            $isProc = $user->hasRole('procurement');
            $isSuperadmin = $user->hasRole('superadmin');

            foreach ($eligibleItems as $item) {
                $isLevel1Approver = ($purchaseRequest->pr_type === 'non_operational') ? $isFat : $isOm;

                if (($isLevel1Approver || $isSuperadmin) && $item->status === 'pending') {
                    $item->update(['status' => 'approved_om']);
                    $approverRole = $purchaseRequest->pr_type === 'non_operational' ? 'manager_fat' : 'operational_manager';
                    $approvalType = $purchaseRequest->pr_type === 'non_operational' ? 'fatm' : 'om';

                    Approval::create([
                        'purchase_request_id' => $item->purchase_request_id,
                        'pr_item_id' => $item->id,
                        'approver_id' => $user->id,
                        'approver_role' => $approverRole,
                        'approval_type' => $approvalType,
                        'status' => 'approved',
                        'notes' => $request->notes,
                        'approved_at' => now(),
                    ]);

                    // Send notification
                    $recipients = $this->getSharedRecipients($purchaseRequest, $purchaseRequest->user);
                    $managerTitle = $purchaseRequest->pr_type === 'non_operational' ? 'Manager FAT' : 'Operational Manager';
                    $message = "Item '{$item->item_name}' telah disetujui secara massal oleh {$managerTitle}.";
                    if ($request->filled('notes')) {
                        $message .= " Catatan: {$request->notes}";
                    }
                    Notification::send($recipients, new PrStatusUpdatedNotification($purchaseRequest, $message));
                    Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($purchaseRequest, $message)));

                } elseif (($isGm || $isSuperadmin) && $item->status === 'approved_om') {
                    $item->update(['status' => 'approved_gm']);

                    Approval::create([
                        'purchase_request_id' => $item->purchase_request_id,
                        'pr_item_id' => $item->id,
                        'approver_id' => $user->id,
                        'approver_role' => 'general_manager',
                        'approval_type' => 'gm',
                        'status' => 'approved',
                        'notes' => $request->notes,
                        'approved_at' => now(),
                    ]);

                } elseif (($isProc || $isSuperadmin) && $item->status === 'approved_gm') {
                    $item->update(['status' => 'approved_proc']);

                    // Send notification
                    $recipients = $this->getSharedRecipients($purchaseRequest, $purchaseRequest->user);
                    $message = "Item '{$item->item_name}' telah disetujui secara massal oleh Procurement.";
                    if ($request->filled('notes')) {
                        $message .= " Catatan: {$request->notes}";
                    }
                    Notification::send($recipients, new PrStatusUpdatedNotification($purchaseRequest, $message));
                    Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($purchaseRequest, $message)));

                    Approval::create([
                        'purchase_request_id' => $item->purchase_request_id,
                        'pr_item_id' => $item->id,
                        'approver_id' => $user->id,
                        'approver_role' => 'procurement',
                        'approval_type' => 'procurement',
                        'status' => 'approved',
                        'notes' => $request->notes,
                        'approved_at' => now(),
                    ]);
                }
            }

            // Evaluate PR advancement
            $this->checkAndAdvancePrStatus($purchaseRequest);

            \DB::commit();
            return redirect()->back()->with('success', count($eligibleItems) . ' item berhasil disetujui.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PR approveAll failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyetujui semua item: ' . $e->getMessage());
        }
    }

    public function rejectAll(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $eligibleItems = $purchaseRequest->getEligibleItemsForUser($user, 'reject');

        if ($eligibleItems->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada item yang dapat ditolak saat ini.');
        }

        \DB::beginTransaction();
        try {
            $isOm = $user->hasRole('operational_manager');
            $isFat = $user->hasRole('manager_fat');
            $isGm = $user->hasRole('general_manager');
            $isProc = $user->hasRole('procurement');
            $isSuperadmin = $user->hasRole('superadmin');

            foreach ($eligibleItems as $item) {
                $isLevel1Approver = ($purchaseRequest->pr_type === 'non_operational') ? $isFat : $isOm;

                if (($isLevel1Approver || $isSuperadmin) && $item->status === 'pending') {
                    $status = 'rejected_om';
                    $approverRole = $purchaseRequest->pr_type === 'non_operational' ? 'manager_fat' : 'operational_manager';
                } elseif (($isGm || $isSuperadmin) && $item->status === 'approved_om') {
                    $status = 'rejected_gm';
                    $approverRole = 'general_manager';
                } elseif (($isProc || $isSuperadmin) && $item->status === 'approved_gm') {
                    $status = 'rejected_proc';
                    $approverRole = 'procurement';
                } else {
                    continue;
                }

                $item->update([
                    'status' => $status,
                    'reject_reason' => $request->reject_reason,
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                ]);

                $approvalType = $purchaseRequest->pr_type === 'non_operational' && $approverRole === 'manager_fat' ? 'fatm' : str_replace('rejected_', '', $status);
                if ($approvalType === 'proc') {
                    $approvalType = 'procurement';
                }

                Approval::create([
                    'purchase_request_id' => $item->purchase_request_id,
                    'pr_item_id' => $item->id,
                    'approver_id' => $user->id,
                    'approver_role' => $approverRole,
                    'approval_type' => $approvalType,
                    'status' => 'rejected',
                    'notes' => $request->reject_reason,
                    'approved_at' => now(),
                ]);

                // Send notification
                $recipients = $this->getSharedRecipients($purchaseRequest, $purchaseRequest->user);
                $msg = "Item '{$item->item_name}' ditolak secara massal. Catatan validasi: " . $request->reject_reason;
                Notification::send($recipients, new PrStatusUpdatedNotification($purchaseRequest, $msg));
                Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($purchaseRequest, $msg)));
            }

            // Evaluate PR advancement
            $this->checkAndAdvancePrStatus($purchaseRequest);

            \DB::commit();
            return redirect()->back()->with('success', count($eligibleItems) . ' item berhasil ditolak.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PR rejectAll failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menolak semua item: ' . $e->getMessage());
        }
    }

    public function sendNoteAll(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $eligibleItems = $purchaseRequest->getEligibleItemsForUser($user, 'note');

        if ($eligibleItems->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada item untuk dikirimi catatan.');
        }

        \DB::beginTransaction();
        try {
            $roleName = $user->getRoleNames()->first();

            foreach ($eligibleItems as $item) {
                $role = $roleName;
                $approvalType = 'unknown';

                if ($purchaseRequest->user_id == $user->id) {
                    $approvalType = 'requester';
                } elseif (($role === 'procurement' || $role === 'superadmin') && $item->status === 'pending_estimate') {
                    $approvalType = 'procurement';
                    $role = 'procurement';
                } elseif ($role === 'superadmin' && $item->status === 'pending') {
                    $approvalType = $purchaseRequest->pr_type === 'non_operational' ? 'fatm' : 'om';
                    $role = $purchaseRequest->pr_type === 'non_operational' ? 'manager_fat' : 'operational_manager';
                } elseif ($role === 'superadmin' && $item->status === 'approved_om') {
                    $approvalType = 'gm';
                    $role = 'general_manager';
                } elseif ($role === 'superadmin' && $item->status === 'approved_gm') {
                    $approvalType = 'procurement';
                    $role = 'procurement';
                } elseif ($role === 'operational_manager' && $item->status === 'pending' && $purchaseRequest->pr_type === 'operational') {
                    $approvalType = 'om';
                } elseif ($role === 'manager_fat' && $item->status === 'pending' && $purchaseRequest->pr_type === 'non_operational') {
                    $approvalType = 'fatm';
                } elseif ($role === 'general_manager' && $item->status === 'approved_om') {
                    $approvalType = 'gm';
                } elseif ($role === 'procurement' && $item->status === 'approved_gm') {
                    $approvalType = 'procurement';
                } else {
                    continue;
                }

                Approval::create([
                    'purchase_request_id' => $item->purchase_request_id,
                    'pr_item_id' => $item->id,
                    'approver_id' => $user->id,
                    'approver_role' => $role,
                    'approval_type' => $approvalType,
                    'status' => 'pending',
                    'notes' => $request->notes,
                    'approved_at' => now(),
                ]);

                $recipients = $this->getSharedRecipients($purchaseRequest, $purchaseRequest->user);
                $senderName = $approvalType === 'requester' ? "Requester ({$user->name})" : strtoupper(str_replace('_', ' ', $role));
                
                Notification::send($recipients, new PrStatusUpdatedNotification(
                    $purchaseRequest,
                    "Catatan massal untuk item '{$item->item_name}' dari " . $senderName . ": {$request->notes}"
                ));
                Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification(
                    $purchaseRequest,
                    "Catatan massal untuk item '{$item->item_name}' dari " . $senderName . ": {$request->notes}"
                )));
            }

            \DB::commit();
            return redirect()->back()->with('success', 'Catatan massal berhasil dikirim.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PR sendNoteAll failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengirim catatan massal: ' . $e->getMessage());
        }
    }

    public function sendValidationNote(Request $request, PrItem $item)
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $role = $user->getRoleNames()->first();

        $canSend = false;
        $approvalType = 'unknown';

        if ($item->purchaseRequest->user_id == $user->id) {
            $canSend = true;
            $approvalType = 'requester';
        } elseif (($role === 'procurement' || $role === 'superadmin') && $item->status === 'pending_estimate') {
            $canSend = true;
            $approvalType = 'procurement';
            $role = 'procurement';
        } elseif ($role === 'superadmin' && $item->status === 'pending') {
            $canSend = true;
            $approvalType = $item->purchaseRequest->pr_type === 'non_operational' ? 'fatm' : 'om';
            $role = $item->purchaseRequest->pr_type === 'non_operational' ? 'manager_fat' : 'operational_manager';
        } elseif ($role === 'superadmin' && $item->status === 'approved_om') {
            $canSend = true;
            $approvalType = 'gm';
            $role = 'general_manager';
        } elseif ($role === 'superadmin' && $item->status === 'approved_gm') {
            $canSend = true;
            $approvalType = 'procurement';
            $role = 'procurement';
        } elseif ($role === 'operational_manager' && $item->status === 'pending' && $item->purchaseRequest->pr_type === 'operational') {
            $canSend = true;
            $approvalType = 'om';
        } elseif ($role === 'manager_fat' && $item->status === 'pending' && $item->purchaseRequest->pr_type === 'non_operational') {
            $canSend = true;
            $approvalType = 'fatm';
        } elseif ($role === 'general_manager' && $item->status === 'approved_om') {
            $canSend = true;
            $approvalType = 'gm';
        } elseif ($role === 'procurement' && $item->status === 'approved_gm') {
            $canSend = true;
            $approvalType = 'procurement';
        }

        if (!$canSend) {
            return redirect()->back()->with('error', 'Anda tidak dapat mengirim catatan pada status item ini.');
        }

        Approval::create([
            'purchase_request_id' => $item->purchase_request_id,
            'pr_item_id' => $item->id,
            'approver_id' => $user->id,
            'approver_role' => $role,
            'approval_type' => $approvalType,
            'status' => 'pending',
            'notes' => $request->notes,
            'approved_at' => now(),
        ]);

        $recipients = $this->getSharedRecipients($item->purchaseRequest, $item->purchaseRequest->user);

        $senderName = $approvalType === 'requester' ? "Requester ({$user->name})" : strtoupper(str_replace('_', ' ', $role));

        Notification::send(
            $recipients,
            new PrStatusUpdatedNotification(
                $item->purchaseRequest,
                "Catatan untuk item '{$item->item_name}' dari " . $senderName . ": {$request->notes}"
            )
        );
        Notification::send(
            $recipients,
            new QueuedMailWrapper(new PrStatusUpdatedNotification(
                $item->purchaseRequest,
                "Catatan untuk item '{$item->item_name}' dari " . $senderName . ": {$request->notes}"
            ))
        );

        return redirect()->back()->with('success', 'Catatan berhasil dikirim.');
    }

    public function reviseItem(Request $request, PrItem $item)
    {
        $user = Auth::user();
        $oldPr = $item->purchaseRequest;

        // Only the owner or superadmin can revise
        if ($user->id !== $oldPr->user_id && !$user->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Get all rejected items for this PR
        $rejectedItems = $oldPr->items()
            ->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc'])
            ->get();

        if ($rejectedItems->isEmpty()) {
            return redirect()->back()->with('error', 'No rejected items found to revise.');
        }

        \DB::beginTransaction();
        try {
            // 1. Create NEW Purchase Request
            $newPr = PurchaseRequest::create([
                'user_id' => $oldPr->user_id,
                'department_id' => $oldPr->department_id,
                'request_date' => now(),
                'purpose' => $oldPr->purpose,
                'pr_type' => $oldPr->pr_type,
                'status' => 'draft', // Set to draft so it is editable
                'notes' => 'Bulk Revision from ' . $oldPr->pr_number,

                'total_amount' => 0,
            ]);


            $movedItemsNames = [];
            foreach ($rejectedItems as $rejectedItem) {
                // 2. Move Item to New PR and Reset Status
                $rejectedItem->update([
                    'purchase_request_id' => $newPr->id,
                    'status' => 'pending',
                    'reject_reason' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'revision_count' => $rejectedItem->revision_count + 1,
                ]);
                $movedItemsNames[] = $rejectedItem->item_name;
            }

            // 3. Recalculate Totals
            // Since prices were removed/set to 0 by user request earlier, total_amount remains 0
            // but we follow the logic if needed
            $newPrTotal = $newPr->items()->sum('total_price');
            $newPr->update(['total_amount' => $newPrTotal]);

            // Old PR Logic
            $oldPrRemainingTotal = $oldPr->items()->sum('total_price');
            $revisionNote = "\n[System] Items (" . implode(', ', $movedItemsNames) . ") revised to {$newPr->pr_number}.";

            $updateData = [
                'total_amount' => $oldPrRemainingTotal,
                'notes' => $oldPr->notes . $revisionNote
            ];

            // Check if Old PR is empty and update status if needed
            if ($oldPr->items()->count() == 0) {
                $updateData['status'] = 'cancelled';
                $updateData['notes'] .= "\n[System] PR Cancelled (All items revised).";
            }

            // Notify Management about the new revision PR
            $managers = $this->getSharedRecipients($newPr);
            Notification::send($managers, new PrSubmittedNotification($newPr));
            Notification::send($managers, new QueuedMailWrapper(new PrSubmittedNotification($newPr)));

            // Notify requester about revision + Superadmins
            $requesterAndAdmins = User::role('superadmin')->get()->push($oldPr->user);
            Notification::send($requesterAndAdmins, new PrStatusUpdatedNotification($newPr, "Item yang ditolak dari {$oldPr->pr_number} telah dipindahkan ke PR baru {$newPr->pr_number} untuk direvisi."));
            Notification::send($requesterAndAdmins, new QueuedMailWrapper(new PrStatusUpdatedNotification($newPr, "Item yang ditolak dari {$oldPr->pr_number} telah dipindahkan ke PR baru {$newPr->pr_number} untuk direvisi.")));



            // Update or delete old PR
            if ($oldPr->items()->count() == 0) {
                $oldPr->delete();
            } else {
                $oldPr->update($updateData);
            }

            \DB::commit();

            return redirect()->route('purchase-requests.edit', $newPr)->with('success', count($rejectedItems) . ' items have been moved to a new PR. Please review and update details.');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Bulk Revision failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to revise items: ' . $e->getMessage());
        }
    }

    public function deleteRejectedItem(PrItem $item)
    {
        $user = Auth::user();
        $pr = $item->purchaseRequest;

        // Only the owner of the PR or superadmin can delete the rejected item
        if ($user->id !== $pr->user_id && !$user->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Only rejected items can be deleted this way
        if (!str_starts_with($item->status, 'rejected')) {
            return redirect()->back()->with('error', 'Only rejected items can be deleted.');
        }

        \DB::beginTransaction();
        try {
            // Delete the item
            $item->delete();

            // Check if PR has no items left
            if ($pr->items()->count() === 0) {
                $pr->delete();
                \DB::commit();
                return redirect()->route('purchase-requests.index')->with('success', 'Purchase Request has been deleted since all items were removed.');
            }

            // Recalculate total amount
            $totalAmount = $pr->items()->sum('total_price');
            $pr->update(['total_amount' => $totalAmount]);

            // Recalculate PR status
            $this->checkAndAdvancePrStatus($pr);

            \DB::commit();
            return redirect()->back()->with('success', 'Rejected item deleted successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Failed to delete rejected item: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete item: ' . $e->getMessage());
        }
    }


    public function destroy(PurchaseRequest $purchaseRequest)
    {
        // Check ownership/permission
        if (auth()->id() !== $purchaseRequest->user_id && !auth()->user()->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Check deletability using model logic
        if ($purchaseRequest->isDeletable()) {
            $this->removePrExpensesFromFinance($purchaseRequest);
            $purchaseRequest->delete();
            return redirect()->route('purchase-requests.index')->with('success', 'Purchase Request has been deleted.');
        }

        return redirect()->back()->with('error', 'This PR cannot be deleted (must be Draft or truly Pending without approvals).');
    }

    public function preview(PurchaseRequest $purchaseRequest)
    {
        // Load items, excluding rejected ones (Show Pending, Approved, etc.)
        $purchaseRequest->load([
            'user',
            'department',
            'items' => function ($query) {
                $query->whereNotIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc', 'cancelled']);
            }
        ]);

        // Removed strict check for empty items to allow viewing Draft/Pending PRs
        // if ($purchaseRequest->items->isEmpty()) ...

        return view('purchase_requests.export', [
            'purchaseRequest' => $purchaseRequest
        ]);
    }

    public function export(PurchaseRequest $purchaseRequest)
    {
        // Load items, excluding rejected ones
        $purchaseRequest->load([
            'user',
            'department',
            'items' => function ($query) {
                $query->whereNotIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc', 'cancelled']);
            }
        ]);

        // Removed strict check

        $pdf = PDF::loadView('purchase_requests.export', [
            'purchaseRequest' => $purchaseRequest
        ]);

        return $pdf->download("PR-{$purchaseRequest->pr_number}.pdf");
    }

    public function exportExcel()
    {
        return Excel::download(new PurchaseRequestExport, 'purchase-requests.xlsx');
    }

    public function updateItemStatus(Request $request, PrItem $item, OdooService $odooService)
    {
        $this->authorize('edit pr', $item->purchaseRequest);
        $user = Auth::user();

        $request->validate([
            'status' => 'required|in:approved_proc,ordered,delivered,completed'
        ]);

        $updateData = ['status' => $request->status];
        if ($request->status === 'ordered') {
            $request->validate([
                'po_number' => 'nullable|string|max:255',
                'actual_price' => 'required|numeric|min:0',
                'planned_dates' => 'nullable|array',
                'planned_dates.*' => 'required_with:planned_dates|date',
                'planned_quantities' => 'required_with:planned_dates|array',
                'planned_quantities.*' => 'required_with:planned_dates|numeric|min:0.01',
                'planned_notes' => 'nullable|array',
                'planned_notes.*' => 'nullable|string',
                'planned_attachments' => 'nullable|array',
                'planned_attachments.*' => 'nullable|file|max:5120',
                'vendor_name' => 'nullable|string|max:255',
                'group_items' => 'nullable|array',
                'group_items.*' => 'integer',
                'group_actual_prices' => 'nullable|array',
                'purpose' => 'nullable|string|max:255',
            ]);

            if ($request->filled('planned_quantities')) {
                $totalPlanned = array_sum($request->planned_quantities);
                if ($totalPlanned > $item->quantity) {
                    return redirect()->back()->with('error', 'Total rencana kedatangan (' . $totalPlanned . ') tidak boleh melebihi jumlah pesanan (' . $item->quantity . ').');
                }
            }

            if ($request->filled('purpose')) {
                $item->update(['purpose' => $request->purpose]);
            }

            // Kirim data ke Odoo dan buat PO otomatis jika bertipe Operational
            $purchaseRequest = $item->purchaseRequest;
            $poNumber = null;

            // Collect all items to sync/group
            $itemsToSync = [$item];
            $groupedItems = [];

            if ($request->has('group_items')) {
                foreach ($request->group_items as $groupedId) {
                    $gItem = PrItem::where('purchase_request_id', $purchaseRequest->id)
                        ->where('status', 'approved_proc')
                        ->find($groupedId);
                    if ($gItem) {
                        $gPrice = $request->input("group_actual_prices.{$groupedId}", $gItem->estimated_price) ?: 0;
                        $gItem->actual_price = $gPrice;
                        $itemsToSync[] = $gItem;
                        $groupedItems[] = [
                            'model' => $gItem,
                            'actual_price' => $gPrice
                        ];
                    }
                }
            }

            if ($item->rekap_po_odoo) {
                // Set temporary actual_price agar service menggunakan harga terbaru
                $item->actual_price = $request->actual_price;
                
                $odooPo = $odooService->createPurchaseOrder(
                    $purchaseRequest, 
                    $itemsToSync, 
                    $request->vendor_name ?? 'Default Vendor'
                );

                if ($odooPo && isset($odooPo['name'])) {
                    $poNumber = $odooPo['name']; // Menggunakan nomor PO dari Odoo
                } else {
                    $poNumber = $request->po_number ?: 'PO-PENDING'; // Fallback manual
                    \Log::warning("Gagal membuat PO di Odoo API, menggunakan fallback nomor PO manual.");
                }
            } else {
                // Non-operational / no odoo sync: langsung gunakan nomor PO manual atau generate fallback
                $poNumber = $request->po_number ?: 'PO-NONOP-' . date('Ymd') . '-' . $item->id;
            }

            $updateData['po_number'] = $poNumber;
            $updateData['actual_price'] = $request->actual_price;
            $updateData['actual_total_price'] = $item->quantity * $request->actual_price;
            $updateData['ordered_at'] = now();
            
            if (!$item->is_incoming) {
                $updateData['status'] = 'completed';
                $updateData['completed_at'] = now();
            } else {
                $updateData['status'] = 'ordered';
            }

            // Update all grouped items as well
            foreach ($groupedItems as $gData) {
                $gModel = $gData['model'];
                $gPrice = $gData['actual_price'];
                $gUpdateData = [
                    'po_number' => $poNumber,
                    'actual_price' => $gPrice,
                    'actual_total_price' => $gModel->quantity * $gPrice,
                    'ordered_at' => now(),
                ];
                if (!$gModel->is_incoming) {
                    $gUpdateData['status'] = 'completed';
                    $gUpdateData['completed_at'] = now();
                } else {
                    $gUpdateData['status'] = 'ordered';
                }
                $gModel->update($gUpdateData);
            }

            // Construct notification message listing all processed items
            $processedItemsNames = [$item->item_name];
            foreach ($groupedItems as $gData) {
                $processedItemsNames[] = $gData['model']->item_name;
            }
            $itemsStr = implode(', ', array_map(fn($name) => "'{$name}'", $processedItemsNames));
            $msg = "Item {$itemsStr} telah dipesan dengan PO: {$poNumber}.";

        } elseif ($request->status === 'approved_proc') {
            $updateData['processed_at'] = now();
            $msg = "Item '{$item->item_name}' telah disetujui (Approved).";
        } elseif ($request->status === 'delivered') {
            $updateData['delivered_at'] = now();
            $msg = "Item '{$item->item_name}' telah dikirim (Delivered).";
            $item->purchaseRequest->user->notify(new ItemDeliveredNotification($item));
        } elseif ($request->status === 'completed') {
            $updateData['completed_at'] = now();
            $msg = "Item '{$item->item_name}' telah selesai diproses (Completed).";
        }

        $item->update($updateData);

        if ($request->status === 'ordered' && $request->filled('planned_dates') && ($user->hasRole('procurement_holding') || $user->hasRole('superadmin'))) {
            // Save Delivery Plans
            foreach ($request->planned_dates as $index => $date) {
                $attachmentPath = null;
                if ($request->hasFile("planned_attachments.{$index}")) {
                    $attachmentPath = $request->file("planned_attachments.{$index}")->store('delivery_plans', 'public');
                }

                $item->deliveryPlans()->create([
                    'planned_date' => $date,
                    'planned_quantity' => $request->planned_quantities[$index],
                    'notes' => $request->planned_notes[$index] ?? null,
                    'attachment_path' => $attachmentPath,
                    'is_active' => true,
                    'is_rescheduled' => false,
                ]);
            }
        }

        // Notify requester + Superadmins + Level 1 Manager
        $recipients = $this->getSharedRecipients($item->purchaseRequest, $item->purchaseRequest->user);

        if ($request->status === 'delivered') {
            $requesterId = $item->purchaseRequest->user->id;
            $recipients = $recipients->reject(fn($user) => $user->id === $requesterId);
        }

        Notification::send($recipients, new PrStatusUpdatedNotification($item->purchaseRequest, $msg));
        Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($item->purchaseRequest, $msg)));

        // Sync expenses to Finance Application when item is ordered/delivered/completed
        $this->syncPrExpensesWithFinance($item->purchaseRequest);

        return redirect()->back()->with('success', 'Item status updated successfully.');
    }

    public function updateItemQuantity(Request $request, PrItem $item)
    {
        $this->authorize('edit pr', $item->purchaseRequest);

        $request->validate([
            'quantity' => 'required|numeric|min:0.01'
        ]);

        $item->update([
            'quantity' => $request->quantity,
            'total_price' => $request->quantity * ($item->estimated_price ?: 0)
        ]);

        // Perbarui total_amount dari PR induk
        $pr = $item->purchaseRequest;
        $totalAmount = $pr->items()->sum('total_price');
        $pr->update(['total_amount' => $totalAmount]);

        return redirect()->back()->with('success', 'Kuantitas item berhasil diperbarui.');
    }

    public function syncItemToOdoo(Request $request, PrItem $item, OdooService $odooService)
    {
        $this->authorize('edit pr', $item->purchaseRequest);
        $purchaseRequest = $item->purchaseRequest;

        if (!$item->rekap_po_odoo) {
            return redirect()->back()->with('error', 'Item ini tidak ditandai untuk disinkronkan ke Odoo.');
        }

        $request->validate([
            'vendor_name' => 'required|string|max:255'
        ]);

        // Kirim data ke Odoo dan buat PO otomatis
        $odooPo = $odooService->createPurchaseOrder(
            $purchaseRequest, 
            [$item], 
            $request->vendor_name
        );

        if ($odooPo && isset($odooPo['name'])) {
            $item->update([
                'po_number' => $odooPo['name']
            ]);

            // Sync expenses to Finance Application when item is updated
            $this->syncPrExpensesWithFinance($purchaseRequest);

            return redirect()->back()->with('success', 'Item berhasil dikirim ke Odoo! PO Baru: ' . $odooPo['name']);
        }

        return redirect()->back()->with('error', 'Gagal mengirim ke Odoo. Silakan periksa kembali konfigurasi Odoo Anda atau log server.');
    }

    public function getOdooVendors(OdooService $odooService)
    {
        try {
            $vendors = $odooService->getVendors();
            return response()->json([
                'success' => true,
                'data' => $vendors
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch vendors from Odoo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data vendor dari Odoo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDeliveryPlans(Request $request, PrItem $item)
    {
        $user = Auth::user();
        $isProc = $user->hasRole('procurement');
        $isProcHolding = $user->hasRole('procurement_holding');
        $isSuperadmin = $user->hasRole('superadmin');

        if (!$isProc && !$isProcHolding && !$isSuperadmin) {
            abort(403, 'Unauthorized action. You are not allowed to update delivery plans.');
        }

        if (!$item->is_incoming) {
            return redirect()->back()->with('error', 'Item ini tidak ditandai memiliki kedatangan barang.');
        }

        $this->authorize('edit pr', $item->purchaseRequest);

        $request->validate([
            'planned_dates' => 'required|array|min:1',
            'planned_dates.*' => 'required|date',
            'planned_quantities' => 'required|array|min:1',
            'planned_quantities.*' => 'required|numeric|min:0.01',
            'planned_notes' => 'nullable|array',
            'planned_notes.*' => 'nullable|string',
            'planned_attachments' => 'nullable|array',
            'planned_attachments.*' => 'nullable|file|max:5120'
        ]);

        $totalPlanned = array_sum($request->planned_quantities);
        if ($totalPlanned > $item->quantity) {
            return redirect()->back()->with('error', 'Total rencana kedatangan (' . $totalPlanned . ') tidak boleh melebihi jumlah pesanan (' . $item->quantity . ').');
        }

        $activePlans = $item->deliveryPlans()->where('is_active', true)->get();
        $hasExistingActivePlans = $activePlans->isNotEmpty();
        $submittedPlans = [];

        foreach ($request->planned_dates as $index => $date) {
            $submittedPlans[] = [
                'date' => \Carbon\Carbon::parse($date)->format('Y-m-d'),
                'qty' => (float) $request->planned_quantities[$index],
                'notes' => $request->planned_notes[$index] ?? null,
                'attachment' => $request->file("planned_attachments.{$index}") ?? null,
                'is_matched' => false
            ];
        }

        foreach ($activePlans as $plan) {
            $planDate = $plan->planned_date->format('Y-m-d');
            $planQty = (float) $plan->planned_quantity;
            $matched = false;

            // Use array keys to update by reference
            foreach ($submittedPlans as $key => $submitted) {
                if (!$submitted['is_matched'] && $submitted['date'] === $planDate && $submitted['qty'] === $planQty) {
                    $submittedPlans[$key]['is_matched'] = true;
                    $matched = true;

                    $updateData = [];
                    // Update notes if provided
                    if (isset($submitted['notes']) && $submitted['notes'] !== $plan->notes) {
                        $updateData['notes'] = $submitted['notes'];
                    }
                    // Update attachment if provided
                    if ($submitted['attachment']) {
                        if ($plan->attachment_path) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($plan->attachment_path);
                        }
                        $updateData['attachment_path'] = $submitted['attachment']->store('delivery_plans', 'public');
                    }
                    if (!empty($updateData)) {
                        $plan->update($updateData);
                    }
                    break;
                }
            }

            if (!$matched) {
                // Old plan was changed/deleted
                $plan->update(['is_active' => false]);
            }
        }

        foreach ($submittedPlans as $submitted) {
            if (!$submitted['is_matched']) {
                $attachmentPath = null;
                if ($submitted['attachment']) {
                    $attachmentPath = $submitted['attachment']->store('delivery_plans', 'public');
                }

                $item->deliveryPlans()->create([
                    'planned_date' => $submitted['date'],
                    'planned_quantity' => $submitted['qty'],
                    'notes' => $submitted['notes'],
                    'attachment_path' => $attachmentPath,
                    'is_rescheduled' => $hasExistingActivePlans,
                    'is_active' => true
                ]);
            }
        }

        $message = $hasExistingActivePlans ? 'Rencana kedatangan berhasil di-reschedule.' : 'Rencana kedatangan berhasil disimpan.';
        return redirect()->back()->with('success', $message);
    }

    public function storeDelivery(Request $request, PrItem $item)
    {
        // Only procurement (non-operational only), procurement_holding (operational only) or superadmin can add delivery
        $user = Auth::user();
        $isProc = $user->hasRole('procurement');
        $isProcHolding = $user->hasRole('procurement_holding');
        $isSuperadmin = $user->hasRole('superadmin');

        if (!$isProc && !$isProcHolding && !$isSuperadmin) {
            abort(403, 'Unauthorized action.');
        }

        if (!$item->is_incoming) {
            return redirect()->back()->with('error', 'Item ini tidak ditandai memiliki kedatangan barang.');
        }

        $receivedSoFar = $item->received_quantity;
        $maxAllowed = $item->quantity - $receivedSoFar;

        $request->validate([
            'received_quantity' => 'required|numeric|min:0|max:' . $maxAllowed,
            'rejected_quantity' => 'required|numeric|min:0',
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:500',
            'delivery_attachment' => 'nullable|file|max:5120'
        ]);

        if ($request->received_quantity + $request->rejected_quantity <= 0) {
            return redirect()->back()->withErrors(['received_quantity' => 'Total kuantitas (Diterima + Ditolak) harus lebih besar dari 0.']);
        }

        if ($request->rejected_quantity > 0 && empty($request->rejection_reason)) {
            return redirect()->back()->withErrors(['rejection_reason' => 'Alasan penolakan wajib diisi jika ada barang yang ditolak.']);
        }

        $attachmentPath = null;
        if ($request->hasFile('delivery_attachment')) {
            $attachmentPath = $request->file('delivery_attachment')->store('deliveries', 'public');
        }

        $item->deliveries()->create([
            'received_quantity' => $request->received_quantity,
            'rejected_quantity' => $request->rejected_quantity,
            'delivery_date' => $request->delivery_date,
            'notes' => $request->notes,
            'rejection_reason' => $request->rejected_quantity > 0 ? $request->rejection_reason : null,
            'attachment_path' => $attachmentPath,
            'received_by' => Auth::id()
        ]);

        // Check if fully delivered
        $newTotal = $receivedSoFar + $request->received_quantity;
        if ($newTotal >= $item->quantity && !in_array($item->status, ['completed', 'delivered'])) {
            $item->update(['status' => 'delivered', 'delivered_at' => now()]);
            // Notify requester
            $item->purchaseRequest->user->notify(new ItemDeliveredNotification($item));
            $this->checkAndAdvancePrStatus($item->purchaseRequest);
        }

        return redirect()->back()->with('success', 'Riwayat kedatangan berhasil dicatat.');
    }

    public function updateDelivery(Request $request, PrItemDelivery $delivery)
    {
        $user = Auth::user();
        $item = $delivery->prItem;
        $isProc = $user->hasRole('procurement');
        $isProcHolding = $user->hasRole('procurement_holding');
        $isSuperadmin = $user->hasRole('superadmin');

        if (!$isProc && !$isProcHolding && !$isSuperadmin) {
            abort(403, 'Unauthorized action.');
        }

        if ($delivery->isReturReceipt()) {
            $otherReturTotal = $delivery->returForDelivery->returReceipts()->where('id', '!=', $delivery->id)->sum('received_quantity');
            $maxAllowed = $delivery->returForDelivery->rejected_quantity - $otherReturTotal;
        } else {
            $otherDeliveriesTotal = $item->deliveries()->where('id', '!=', $delivery->id)->sum('received_quantity');
            $maxAllowed = $item->quantity - $otherDeliveriesTotal;
        }

        $request->validate([
            'received_quantity' => 'required|numeric|min:0|max:' . $maxAllowed,
            'rejected_quantity' => 'required|numeric|min:0',
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:500',
            'delivery_attachment' => 'nullable|file|max:5120'
        ]);

        if ($request->received_quantity + $request->rejected_quantity <= 0) {
            return redirect()->back()->withErrors(['received_quantity' => 'Total kuantitas (Diterima + Ditolak) harus lebih besar dari 0.']);
        }

        if ($request->rejected_quantity > 0 && empty($request->rejection_reason)) {
            return redirect()->back()->withErrors(['rejection_reason' => 'Alasan penolakan wajib diisi jika ada barang yang ditolak.']);
        }

        $updateData = [
            'received_quantity' => $request->received_quantity,
            'rejected_quantity' => $request->rejected_quantity,
            'delivery_date' => $request->delivery_date,
            'notes' => $request->notes,
            'rejection_reason' => $request->rejected_quantity > 0 ? $request->rejection_reason : null,
        ];

        if ($request->hasFile('delivery_attachment')) {
            if ($delivery->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($delivery->attachment_path);
            }
            $updateData['attachment_path'] = $request->file('delivery_attachment')->store('deliveries', 'public');
        }

        $delivery->update($updateData);

        $totalReceived = $item->deliveries()->sum('received_quantity');

        // Revert status to ordered if it was delivered but total is now less
        if ($totalReceived < $item->quantity && $item->status === 'delivered') {
            $item->update(['status' => 'ordered', 'delivered_at' => null]);
        }
        // Advance to delivered if it reaches total and currently ordered
        elseif ($totalReceived >= $item->quantity && $item->status === 'ordered') {
            $item->update(['status' => 'delivered', 'delivered_at' => now()]);
            $item->purchaseRequest->user->notify(new ItemDeliveredNotification($item));
            $this->checkAndAdvancePrStatus($item->purchaseRequest);
        }

        return redirect()->back()->with('success', 'Riwayat kedatangan berhasil diperbarui.');
    }

    public function destroyDelivery(PrItemDelivery $delivery)
    {
        $user = Auth::user();
        $item = $delivery->prItem;
        $isProc = $user->hasRole('procurement');
        $isProcHolding = $user->hasRole('procurement_holding');
        $isSuperadmin = $user->hasRole('superadmin');

        if (!$isProc && !$isProcHolding && !$isSuperadmin) {
            abort(403, 'Unauthorized action.');
        }

        $delivery->delete();

        $newTotal = $item->deliveries()->sum('received_quantity');
        if ($newTotal < $item->quantity && $item->status === 'delivered') {
            $item->update(['status' => 'ordered', 'delivered_at' => null]);
        }

        return redirect()->back()->with('success', 'Riwayat kedatangan berhasil dihapus.');
    }

    public function rejected(Request $request)
    {
        $this->authorize('view pr');
        $user = Auth::user();

        $query = PurchaseRequest::with(['user', 'department', 'items'])
            ->whereHas('items', function ($q) {
                $q->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc']);
            });

        if (!$user->hasAnyRole(['superadmin', 'operational_manager', 'manager_fat', 'general_manager', 'procurement'])) {
            $query->where('user_id', $user->id);
        }

        $this->applySearchFilter($query, $request->search);

        $purchaseRequests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $departments = Department::all();
        $title = "Rejected Purchase Requests";

        return view('purchase_requests.index', compact('purchaseRequests', 'departments', 'title'));
    }

    public function drafts(Request $request)
    {
        $this->authorize('view pr');
        $user = Auth::user();

        $query = PurchaseRequest::with(['user', 'department', 'items'])
            ->where('status', 'draft');

        if (!$user->hasAnyRole(['superadmin'])) {
            $query->where('user_id', $user->id);
        }

        $this->applySearchFilter($query, $request->search);

        $purchaseRequests = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();
        $departments = Department::all();
        $title = "Draft Purchase Requests";

        return view('purchase_requests.index', compact('purchaseRequests', 'departments', 'title'));
    }

    public function submitDraft(PurchaseRequest $purchaseRequest)
    {
        $this->authorize('edit pr', $purchaseRequest);

        if ($purchaseRequest->status !== 'draft') {
            return redirect()->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'PR ini sudah diajukan atau tidak dalam status draft.');
        }

        // Validate that all items are complete
        foreach ($purchaseRequest->items as $item) {
            if (empty($item->item_name) || empty($item->quantity) || empty($item->uom) || empty($item->purpose)) {
                return redirect()->route('purchase-requests.edit', $purchaseRequest)
                    ->with('error', 'Draft tidak dapat diajukan karena data item belum lengkap. Silakan lengkapi data item terlebih dahulu.');
            }
        }

        \DB::beginTransaction();
        try {
            // Update PR status to pending
            $purchaseRequest->update(['status' => 'pending']);

            // Update items status to pending_estimate
            $purchaseRequest->items()->update(['status' => 'pending_estimate']);

            \DB::commit();

            // Notify Procurement
            $procurements = User::role(['procurement', 'superadmin'])->get();
            $filtered = $procurements->reject(fn($u) => $u->id == auth()->id());
            if ($filtered->isNotEmpty()) {
                Notification::send($filtered, new PrSubmittedNotification($purchaseRequest));
                Notification::send($filtered, new QueuedMailWrapper(new PrSubmittedNotification($purchaseRequest)));
            }

            return redirect()->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase Request berhasil diajukan.');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PR submit draft failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengajukan PR: ' . $e->getMessage());
        }
    }

    public function approvalQueue(Request $request)
    {
        $this->authorize('view pr');
        $user = Auth::user();

        $query = PurchaseRequest::with(['user', 'department', 'items'])
            ->where('status', '!=', 'draft');

        if ($user->hasRole('superadmin')) {
            $query->whereIn('status', ['pending', 'approved_om', 'approved_gm']);
        } else {
            $hasApprovalRole = false;
            $query->where(function ($q) use ($user, &$hasApprovalRole) {
                if ($user->hasRole('operational_manager')) {
                    $hasApprovalRole = true;
                    $q->orWhere(function ($subQ) use ($user) {
                        $subQ->where('status', 'pending')
                            ->where('pr_type', 'operational');
                    });
                }
                if ($user->hasRole('manager_fat')) {
                    $hasApprovalRole = true;
                    $q->orWhere(function ($subQ) {
                        $subQ->where('status', 'pending')
                            ->where('pr_type', 'non_operational');
                    });
                }
                if ($user->hasRole('general_manager')) {
                    $hasApprovalRole = true;
                    $q->orWhere('status', 'approved_om');
                }
                if ($user->hasRole('procurement')) {
                    $hasApprovalRole = true;
                    $q->orWhere('status', 'approved_gm');
                }
            });

            if (!$hasApprovalRole) {
                abort(403);
            }
        }

        $this->applySearchFilter($query, $request->search);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $purchaseRequests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $departments = Department::all();
        $title = 'Approval Queue (OM/GM)';
        $hideCreateButton = true;

        return view('purchase_requests.index', compact('purchaseRequests', 'departments', 'title', 'hideCreateButton'));
    }

    public function checkNotifications()
    {
        $user = auth()->user();
        if (!$user)
            return response()->json(['unread_count' => 0, 'latest' => null]);

        $unreadCount = $user->unreadNotifications->count();
        $latestNotification = $user->unreadNotifications->first();

        return response()->json([
            'unread_count' => $unreadCount,
            'latest' => $latestNotification ? [
                'id' => $latestNotification->id,
                'message' => $latestNotification->data['message'],
                'url' => route('notifications.mark-as-read', $latestNotification->id)
            ] : null
        ]);
    }

    public function saveEstimates(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole('procurement') && !$user->hasRole('procurement_holding') && !$user->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'estimates' => 'required|array',
            'estimates.*.estimated_price' => 'required|numeric|min:0',
            'estimates.*.rekap_po_odoo' => 'nullable|boolean',
            'estimates.*.is_incoming' => 'nullable|boolean',
        ]);

        // Calculate purpose amounts for budget check
        $purposeAmounts = [];
        $itemsToUpdate = [];
        foreach ($request->estimates as $itemId => $itemData) {
            $item = PrItem::find($itemId);
            if ($item && $item->purchase_request_id === $purchaseRequest->id) {
                $purpose = $item->purpose;
                $requestedAmount = (float) $itemData['estimated_price'] * (float) $item->quantity;
                $purposeAmounts[$purpose] = ($purposeAmounts[$purpose] ?? 0) + $requestedAmount;
                $itemsToUpdate[] = [
                    'item' => $item,
                    'estimated_price' => (float) $itemData['estimated_price'],
                    'total_price' => $requestedAmount,
                    'rekap_po_odoo' => isset($itemData['rekap_po_odoo']),
                    'is_incoming' => isset($itemData['is_incoming'])
                ];
            }
        }

        // Validate budget against Finance system
        foreach ($purposeAmounts as $purpose => $requestedAmount) {
            if (empty($purpose)) continue;
            $budgetCheck = $this->validateBudgetWithFinance(
                $purpose, 
                $purchaseRequest->request_date, 
                $requestedAmount,
                $purchaseRequest->department_id,
                $purchaseRequest->department?->name,
                $purchaseRequest->reference
            );
            if (isset($budgetCheck['is_allowed']) && $budgetCheck['is_allowed'] === false) {
                return redirect()->back()->with('error', $budgetCheck['message'] ?? "Anggaran tidak mencukupi untuk kategori {$purpose}.");
            }
        }

        \DB::beginTransaction();
        try {
            $totalAmount = 0;
            foreach ($itemsToUpdate as $data) {
                $item = $data['item'];
                $item->update([
                    'estimated_price' => $data['estimated_price'],
                    'total_price' => $data['total_price'],
                    'rekap_po_odoo' => $data['rekap_po_odoo'],
                    'is_incoming' => $data['is_incoming'],
                    'status' => 'pending' // Update to pending to start manager approval
                ]);
                $totalAmount += $data['total_price'];
            }

            $purchaseRequest->update(['total_amount' => $totalAmount]);

            // Create initial manager approval record
            $approverRole = $purchaseRequest->pr_type === 'non_operational' ? 'manager_fat' : 'operational_manager';
            $approvalType = $purchaseRequest->pr_type === 'non_operational' ? 'fatm' : 'om';

            // Delete only pending approvals without notes (preserve note history)
            Approval::where('purchase_request_id', $purchaseRequest->id)
                ->where('status', 'pending')
                ->whereNull('notes')
                ->delete();

            Approval::create([
                'purchase_request_id' => $purchaseRequest->id,
                'approver_id' => null,
                'approver_role' => $approverRole,
                'approval_type' => $approvalType,
                'status' => 'pending',
            ]);

            \DB::commit();

            // Notify Management
            $managers = $this->getSharedRecipients($purchaseRequest);
            if ($managers->isNotEmpty()) {
                Notification::send($managers, new PrSubmittedNotification($purchaseRequest));
                Notification::send($managers, new QueuedMailWrapper(new PrSubmittedNotification($purchaseRequest)));
            }

            return redirect()->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Estimasi harga berhasil disimpan dan diajukan ke Manager.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PR saveEstimates failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan estimasi: ' . $e->getMessage());
        }
    }

    private function getSharedRecipients($purchaseRequest, $requester = null)
    {
        $superadmins = User::role('superadmin')->get();

        if ($purchaseRequest->pr_type === 'non_operational') {
            // Level 1 for Non-Operational is Manager FAT
            $level1Managers = User::role('manager_fat')->get();
        } else {
            // Level 1 for Operational is Department OM
            $level1Managers = User::role('operational_manager')
                ->where('department_id', $purchaseRequest->department_id)
                ->get();
        }

        $recipients = $superadmins->merge($level1Managers);

        if ($requester) {
            $recipients = $recipients->push($requester);
        }

        $authId = auth()->id();
        \Log::info('getSharedRecipients Check', [
            'auth_id' => $authId,
            'dept_id' => $purchaseRequest->department_id,
            'pr_type' => $purchaseRequest->pr_type,
            'requester_id' => $requester ? $requester->id : 'null',
            'initial_count' => $recipients->count(),
            'initial_ids' => $recipients->pluck('id')->toArray()
        ]);

        $filtered = $recipients->unique('id')->reject(function ($user) use ($authId) {
            // Use loose comparison to handle string/int mismatches
            return $user->id == $authId;
        });

        \Log::info('getSharedRecipients Result', [
            'final_count' => $filtered->count(),
            'final_ids' => $filtered->pluck('id')->toArray()
        ]);

        return $filtered;
    }

    private function applySearchFilter($query, $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('pr_number', 'like', "%{$search}%")
                ->orWhere('purpose', 'like', "%{$search}%")
                ->orWhereHas('user', function ($qu) use ($search) {
                    $qu->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('department', function ($qd) use ($search) {
                    $qd->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhereHas('items', function ($qi) use ($search) {
                    $qi->where('item_name', 'like', "%{$search}%")
                        ->orWhere('uom', 'like', "%{$search}%");
                });
        });
    }

    private function checkAndAdvancePrStatus(PurchaseRequest $pr)
    {
        // 1. Check if OM stage is done
        $omDone = $pr->items()->where('status', 'pending')->doesntExist();
        if ($omDone && $pr->status === 'pending') {
            if ($pr->items()->where('status', 'approved_om')->exists()) {
                $pr->update(['status' => 'approved_om']);
                $gms = User::role(['general_manager', 'superadmin'])->get();
                Notification::send($gms, new PrActionRequiredNotification($pr, "PR {$pr->pr_number} menunggu persetujuan General Manager."));
                Notification::send($gms, new QueuedMailWrapper(new PrActionRequiredNotification($pr, "PR {$pr->pr_number} menunggu persetujuan General Manager.")));
            } elseif ($pr->items()->where('status', 'rejected_om')->count() === $pr->items()->count()) {
                $pr->update(['status' => 'rejected_om']);
            }
        }

        // 2. Check if GM stage is done
        $gmDone = $pr->items()->whereIn('status', ['pending', 'approved_om'])->doesntExist();
        if ($gmDone && in_array($pr->status, ['pending', 'approved_om'])) {
            if ($pr->items()->where('status', 'approved_gm')->exists()) {
                $pr->update(['status' => 'approved_gm']);
                $proc = User::role(['procurement', 'superadmin'])->get();
                Notification::send($proc, new PrActionRequiredNotification($pr, "PR {$pr->pr_number} menunggu proses Procurement."));
                Notification::send($proc, new QueuedMailWrapper(new PrActionRequiredNotification($pr, "PR {$pr->pr_number} menunggu proses Procurement.")));
            } elseif ($pr->items()->whereIn('status', ['rejected_om', 'rejected_gm'])->count() === $pr->items()->count()) {
                $pr->update(['status' => 'rejected_gm']);
            }
        }

        // 3. Check if Procurement stage is done
        $procDone = $pr->items()->whereIn('status', ['pending', 'approved_om', 'approved_gm'])->doesntExist();
        if ($procDone && in_array($pr->status, ['pending', 'approved_om', 'approved_gm'])) {
            if ($pr->items()->where('status', 'approved_proc')->exists()) {
                $pr->update(['status' => 'approved_proc']);
            } elseif ($pr->items()->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc'])->count() === $pr->items()->count()) {
                $pr->update(['status' => 'rejected_proc']);
            }
        }

        // Sync expenses to Finance Application
        $this->syncPrExpensesWithFinance($pr);
    }

    private function syncPrExpensesWithFinance(PurchaseRequest $pr)
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

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
                \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders($headers)
                    ->timeout(8)
                    ->post($removeUrl, [
                        'reference' => $pr->pr_number,
                    ]);
            } catch (\Exception $e) {
                \Log::error("Failed to remove expense from Finance API for PR {$pr->pr_number}: " . $e->getMessage());
            }
            return;
        }

        $grouped = [];
        foreach ($committedItems as $item) {
            if (empty($item->purpose))
                continue;
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
                $resolved = $this->getResponsibleDepartmentForPurpose($purpose, $pr->department_id, $pr->department?->name);

                $itemsSummary = implode(', ', $data['items_detail']);
                $description = "Realisasi PR {$pr->pr_number} ({$itemsSummary}) - Kategori {$purpose}";

                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders($headers)
                    ->timeout(8)
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
                    \Log::warning("Finance API record expense returned error code {$response->status()} for PR {$pr->pr_number}, category {$purpose}: " . $response->body());
                }
            } catch (\Exception $e) {
                \Log::error("Failed to record expense to Finance API for PR {$pr->pr_number}, category {$purpose}: " . $e->getMessage());
            }
        }
    }

    private function removePrExpensesFromFinance(PurchaseRequest $pr)
    {
        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (!$apiUrl || !$apiKey) {
            return;
        }

        $baseApiUrl = dirname($apiUrl);
        $removeUrl = $baseApiUrl . '/remove-expense';

        $headers = [
            'Accept' => 'application/json',
            'X-API-KEY' => $apiKey,
        ];

        try {
            \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders($headers)
                ->timeout(8)
                ->post($removeUrl, [
                    'reference' => $pr->pr_number,
                ]);
        } catch (\Exception $e) {
            \Log::error("Failed to remove expense from Finance API for PR {$pr->pr_number}: " . $e->getMessage());
        }
    }

    /**
     * [MAINTENANCE] Manually re-sync a specific PR's expenses to the Finance (pagu) system.
     * This is used for old PRs that were created before the Finance API was configured.
     * Only accessible by superadmin.
     */
    public function syncExpenseToFinance(PurchaseRequest $purchaseRequest)
    {
        if (!Auth::user()->hasRole('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (!$apiUrl || !$apiKey) {
            return redirect()->back()->with('error', 'Konfigurasi API Finance belum lengkap di Settings.');
        }

        // Only PRs that have committed items (ordered/delivered/completed) can be synced
        $committedItems = $purchaseRequest->items()->whereIn('status', ['ordered', 'delivered', 'completed'])->get();

        if ($committedItems->isEmpty()) {
            return redirect()->back()->with('error', "PR #{$purchaseRequest->pr_number} tidak memiliki item yang sudah di-order/deliver/complete. Tidak ada yang perlu di-sync ke pagu.");
        }

        // Check items without purpose
        $noPurposeItems = $committedItems->filter(fn($i) => empty($i->purpose));
        if ($noPurposeItems->count() === $committedItems->count()) {
            return redirect()->back()->with('error', "Semua item pada PR #{$purchaseRequest->pr_number} tidak memiliki kategori (purpose). Harap isi kategori item terlebih dahulu.");
        }

        $purchaseRequest->load('department');
        $this->syncPrExpensesWithFinance($purchaseRequest);

        \Log::info("[MANUAL SYNC] Superadmin " . Auth::user()->name . " triggered expense sync for PR {$purchaseRequest->pr_number}");

        return redirect()->back()->with('success', "Sync expense PR #{$purchaseRequest->pr_number} ke sistem pagu Finance berhasil dikirim. Cek halaman Staging Pagu untuk konfirmasi.");
    }

    /**
     * [MAINTENANCE] Bulk re-sync all PRs with committed items that may not be recorded in Finance.
     * Only accessible by superadmin.
     */
    public function bulkSyncExpensesToFinance(Request $request)
    {
        if (!Auth::user()->hasRole('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $apiUrl = Setting::get('finance_api_url', env('FINANCE_API_URL'));
        $apiKey = Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (!$apiUrl || !$apiKey) {
            return redirect()->back()->with('error', 'Konfigurasi API Finance belum lengkap di Settings.');
        }

        // Find all PRs that have at least one committed item
        $prs = PurchaseRequest::with(['items', 'department'])
            ->whereHas('items', function ($q) {
                $q->whereIn('status', ['ordered', 'delivered', 'completed']);
            })
            ->get();

        if ($prs->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada PR dengan item yang sudah di-order/deliver/complete.');
        }

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($prs as $pr) {
            try {
                $hasCommittedWithPurpose = $pr->items->filter(fn($i) =>
                    in_array($i->status, ['ordered', 'delivered', 'completed']) && !empty($i->purpose)
                )->isNotEmpty();

                if (!$hasCommittedWithPurpose) {
                    $skipped++;
                    continue;
                }

                $this->syncPrExpensesWithFinance($pr);
                $synced++;
                \Log::info("[BULK SYNC] Synced expense for PR {$pr->pr_number}");
            } catch (\Exception $e) {
                $errors[] = "PR #{$pr->pr_number}: " . $e->getMessage();
                \Log::error("[BULK SYNC] Failed for PR {$pr->pr_number}: " . $e->getMessage());
            }
        }

        \Log::info("[BULK SYNC] Superadmin " . Auth::user()->name . " ran bulk expense sync. Synced: {$synced}, Skipped: {$skipped}, Errors: " . count($errors));

        $msg = "Bulk sync selesai. {$synced} PR berhasil di-sync, {$skipped} PR dilewati (tidak ada kategori).";
        if (!empty($errors)) {
            $msg .= " Gagal: " . implode(' | ', array_slice($errors, 0, 3));
        }

        return redirect()->back()->with('success', $msg);
    }

    public function resendNotification(PurchaseRequest $purchaseRequest)

    {
        $recipients = collect();
        $notification = null;
        $subject = "";

        if ($purchaseRequest->status === 'pending_estimate') {
            $recipients = User::role(['procurement', 'superadmin'])->get();
            $notification = new PrSubmittedNotification($purchaseRequest);
            $subject = "Menunggu Estimasi Harga";
        } elseif ($purchaseRequest->status === 'pending') {
            $recipients = $this->getSharedRecipients($purchaseRequest);
            $notification = new PrSubmittedNotification($purchaseRequest);
            $subject = "Menunggu Persetujuan Manager";
        } elseif ($purchaseRequest->status === 'approved_om') {
            $recipients = User::role('general_manager')->get();
            $notification = new PrActionRequiredNotification($purchaseRequest, "PR {$purchaseRequest->pr_number} menunggu persetujuan General Manager.");
            $subject = "Menunggu Persetujuan GM";
        } elseif (in_array($purchaseRequest->status, ['approved_gm', 'approved_proc'])) {
            $recipients = User::role('procurement')->get();
            $notification = new PrActionRequiredNotification($purchaseRequest, "PR {$purchaseRequest->pr_number} menunggu proses Procurement.");
            $subject = "Menunggu Proses Order";
        } else {
            $recipients = collect([$purchaseRequest->user])->concat(User::role('superadmin')->get())->unique('id');
            $notification = new PrStatusUpdatedNotification($purchaseRequest, "Notifikasi kirim ulang untuk PR {$purchaseRequest->pr_number} dengan status: " . strtoupper($purchaseRequest->status));
            $subject = "Pembaruan Status PR";
        }

        $recipients = $recipients->filter(function($user) {
            return !empty($user->email);
        });

        if ($recipients->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada penerima email yang valid ditemukan untuk status PR saat ini.');
        }

        $sentToNames = [];
        $errors = [];

        foreach ($recipients as $recipient) {
            try {
                Notification::sendNow($recipient, new SyncMailWrapper($notification));
                $sentToNames[] = "{$recipient->name} ({$recipient->email})";
            } catch (\Exception $e) {
                \Log::error("Manual resend failed for {$recipient->email}: " . $e->getMessage());
                $errors[] = "{$recipient->name}: " . $e->getMessage();
            }
        }

        if (count($sentToNames) > 0) {
            $msg = "Email notifikasi [{$subject}] berhasil dikirim ulang ke: " . implode(', ', $sentToNames);
            if (count($errors) > 0) {
                $msg .= ". Namun gagal kirim ke: " . implode(', ', $errors);
            }
            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back()->with('error', 'Gagal mengirim email: ' . implode(', ', $errors));
    }

    public function rejectedDeliveries(Request $request)
    {
        $this->authorize('view pr');
        $user = Auth::user();

        // Get deliveries with rejected_quantity > 0 and retur_for_delivery_id IS NULL (original rejections)
        $query = PrItemDelivery::with(['prItem.purchaseRequest.user', 'prItem.purchaseRequest.department', 'receiver'])
            ->where('rejected_quantity', '>', 0)
            ->whereNull('retur_for_delivery_id');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('prItem.purchaseRequest', function($subQ) use ($search) {
                    $subQ->where('pr_number', 'like', "%{$search}%");
                })
                ->orWhereHas('prItem', function($subQ) use ($search) {
                    $subQ->where('item_name', 'like', "%{$search}%")
                         ->orWhere('po_number', 'like', "%{$search}%");
                });
            });
        }

        // Filter based on user roles if not superadmin/procurement
        if (!$user->hasAnyRole(['superadmin', 'procurement', 'procurement_holding'])) {
            $query->whereHas('prItem.purchaseRequest', function($subQ) use ($user) {
                $subQ->where('user_id', $user->id);
            });
        }

        $deliveries = $query->orderBy('delivery_date', 'desc')->paginate(10)->withQueryString();
        $title = "Rejected Deliveries / Retur Vendor";

        return view('purchase_requests.rejected_deliveries', compact('deliveries', 'title'));
    }

    public function storeReturReceipt(Request $request, PrItemDelivery $delivery)
    {
        $user = Auth::user();
        $item = $delivery->prItem;

        $isProc = $user->hasRole('procurement');
        $isProcHolding = $user->hasRole('procurement_holding');
        $isSuperadmin = $user->hasRole('superadmin');

        if (!$isProc && !$isProcHolding && !$isSuperadmin) {
            abort(403, 'Unauthorized action.');
        }

        $unresolved = $delivery->unresolved_rejected_quantity;

        $request->validate([
            'received_quantity' => 'required|numeric|min:0|max:' . $unresolved,
            'rejected_quantity' => 'required|numeric|min:0',
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:500',
            'delivery_attachment' => 'nullable|file|max:5120'
        ]);

        if ($request->received_quantity + $request->rejected_quantity <= 0) {
            return redirect()->back()->withErrors(['received_quantity' => 'Total kuantitas (Diterima + Ditolak) harus lebih besar dari 0.']);
        }

        if ($request->rejected_quantity > 0 && empty($request->rejection_reason)) {
            return redirect()->back()->withErrors(['rejection_reason' => 'Alasan penolakan wajib diisi jika ada barang yang ditolak.']);
        }

        $attachmentPath = null;
        if ($request->hasFile('delivery_attachment')) {
            $attachmentPath = $request->file('delivery_attachment')->store('deliveries', 'public');
        }

        $item->deliveries()->create([
            'received_quantity' => $request->received_quantity,
            'rejected_quantity' => $request->rejected_quantity,
            'delivery_date' => $request->delivery_date,
            'notes' => $request->notes,
            'rejection_reason' => $request->rejected_quantity > 0 ? $request->rejection_reason : null,
            'attachment_path' => $attachmentPath,
            'received_by' => Auth::id(),
            'retur_for_delivery_id' => $delivery->id
        ]);

        // Check if item is now fully delivered
        $totalReceived = $item->deliveries()->sum('received_quantity');
        if ($totalReceived >= $item->quantity && !in_array($item->status, ['completed', 'delivered'])) {
            $item->update(['status' => 'delivered', 'delivered_at' => now()]);
            // Notify requester
            $item->purchaseRequest->user->notify(new ItemDeliveredNotification($item));
            $this->checkAndAdvancePrStatus($item->purchaseRequest);
        }

        return redirect()->back()->with('success', 'Penerimaan barang retur berhasil dicatat.');
    }

    public function updateItemPurpose(Request $request, PrItem $item)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['procurement', 'superadmin'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'purpose' => 'required|string|max:255'
        ]);

        $item->update([
            'purpose' => $request->purpose
        ]);

        // Re-sync expenses to Finance Application if this item is already committed
        if (in_array($item->status, ['ordered', 'delivered', 'completed'])) {
            $this->syncPrExpensesWithFinance($item->purchaseRequest);
        }

        return redirect()->back()->with('success', 'Kategori anggaran (purpose) berhasil diperbarui.');
    }

    public function toggleItemFlags(Request $request, PrItem $item)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['procurement', 'procurement_holding', 'superadmin'])) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $item->update([
            'rekap_po_odoo' => $request->boolean('rekap_po_odoo'),
            'is_incoming' => $request->boolean('is_incoming'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tanda PO/Incoming berhasil diperbarui.',
                'rekap_po_odoo' => $item->rekap_po_odoo,
                'is_incoming' => $item->is_incoming
            ]);
        }

        return redirect()->back()->with('success', 'Tanda PO/Incoming berhasil diperbarui.');
    }
}

class SyncMailWrapper extends \Illuminate\Notifications\Notification
{
    public function __construct(private \Illuminate\Notifications\Notification $notification) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable)
    {
        return $this->notification->toMail($notifiable);
    }
}
