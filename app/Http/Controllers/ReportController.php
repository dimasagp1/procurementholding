<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PrItem;
use App\Models\Department;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PurchaseRequestExport;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view reports');

        $query = PurchaseRequest::with(['items', 'department', 'user']);

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->search_query) {
            if ($request->search_type === 'item_name') {
                $query->whereHas('items', function($q) use ($request) {
                    $q->where('item_name', 'like', '%' . $request->search_query . '%');
                });
            } else {
                $query->where('pr_number', 'like', '%' . $request->search_query . '%');
            }
        }

        $prs = $query->get();

        // Apply dynamic status filtering if needed
        if ($request->status && !in_array($request->status, ['draft', 'pending'])) {
            $prs = $prs->filter(function($p) use ($request) {
                return strtolower($p->approval_status) === strtolower($request->status) ||
                       ($request->status === 'approved_om' && $p->approval_status === 'Approved (OM)') ||
                       ($request->status === 'approved_gm' && $p->approval_status === 'Approved (GM)') ||
                       ($request->status === 'approved_proc' && $p->approval_status === 'Approved (Proc)') ||
                       ($request->status === 'rejected' && $p->approval_status === 'Revision Required');
            });
        }

        $stats = [
            'total_pr' => $prs->count(),
            'pending_pr' => $prs->filter(fn($p) => in_array($p->approval_status, ['Pending', 'Revision Required']))->count(),
            'approved_pr' => $prs->filter(fn($p) => in_array($p->approval_status, ['Approved (OM)', 'Approved (GM)', 'Approved (Proc)', 'Ordered', 'Delivered']))->count(),
            'completed_pr' => $prs->filter(fn($p) => $p->approval_status === 'Completed')->count(),
        ];

        $departments = Department::orderBy('name')->get();

        return view('reports.index', compact('stats', 'prs', 'departments'));
    }

    public function export(Request $request)
    {
        $this->authorize('view reports');
        return Excel::download(
            new PurchaseRequestExport(
                $request->status,
                $request->start_date,
                $request->end_date,
                $request->department_id,
                $request->search_type,
                $request->search_query
            ),
            'PR-Report-' . now()->format('YmdHi') . '.xlsx'
        );
    }
}
