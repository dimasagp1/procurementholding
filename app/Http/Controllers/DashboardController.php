<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Approval;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->hasRole('superadmin')) {
            $stats = $this->getSuperadminStats();
            $recentPRs = PurchaseRequest::with(['user', 'department', 'items'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            $chartData = $this->getSuperadminChartData();
        } elseif ($user->hasRole('user')) {
            $stats = $this->getUserStats($user);
            $recentPRs = PurchaseRequest::where('user_id', $user->id)
                ->with(['user', 'department', 'items'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            $chartData = $this->getUserChartData($user);
        } else {
            $stats = $this->getManagerStats($user);
            $query = PurchaseRequest::with(['user', 'department', 'items']);
            $query->where(function ($q) use ($user) {
                $q->orWhere('user_id', $user->id);
                
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
                    $q->orWhereRaw('1=1'); // Procurement sees all recent PRs
                }
                if ($user->hasRole('procurement_holding')) {
                    $q->orWhere(function ($subQ) {
                        $subQ->where('pr_type', 'operational')
                             ->whereHas('items', function ($itemQ) {
                                 $itemQ->whereIn('status', ['ordered', 'delivered', 'completed']);
                             });
                    });
                }
            });

            $recentPRs = $query->orderBy('created_at', 'desc')->limit(10)->get();
            $chartData = $this->getManagerChartData($user);
        }
        $ongoingItemsQuery = \App\Models\PrItem::with(['purchaseRequest.user', 'purchaseRequest.department', 'deliveryPlans', 'deliveries'])
        ->whereIn('status', ['approved_proc', 'ordered', 'delivered', 'completed'])
        ->orderBy('created_at', 'desc');

        // Filter based on role
        if ($user->hasRole('user')) {
            // User biasa: hanya PR milik sendiri
            $ongoingItemsQuery->whereHas('purchaseRequest', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->hasAnyRole(['operational_manager', 'manager_fat'])) {
            // Manager OM / FAT: PR milik sendiri ATAU item yang pernah dia approve
            $ongoingItemsQuery->where(function($q) use ($user) {
                // Kondisi 1: PR yang dibuat oleh manager itu sendiri
                $q->whereHas('purchaseRequest', function($subQ) use ($user) {
                    $subQ->where('user_id', $user->id);
                });
                // Kondisi 2: Item yang pernah diproses/approve oleh manager ini
                $q->orWhereHas('approvals', function($subQ) use ($user) {
                    $subQ->where('approver_id', $user->id)
                         ->where('status', 'approved');
                });
            });
        } elseif ($user->hasRole('procurement_holding')) {
            // Procurement Holding: hanya PR tipe operational
            $ongoingItemsQuery->whereHas('purchaseRequest', function($q) {
                $q->where('pr_type', 'operational');
            });
        }
        // Superadmin, General Manager, Procurement: melihat semua item (tidak ada filter tambahan)

        $ongoingItems = $ongoingItemsQuery->limit(50)->get();

        return view('dashboard', compact('stats', 'recentPRs', 'chartData', 'ongoingItems'));
    }

    private function getSuperadminChartData()
    {
        $prs = PurchaseRequest::all();
        return [
            'status_distribution' => $this->formatStatusDistribution($prs),
            'monthly_trends' => $this->getMonthlyTrends(),
        ];
    }

    public function exportOngoingItems()
    {
        $user = Auth::user();
        $ongoingItemsQuery = \App\Models\PrItem::with(['purchaseRequest.user', 'purchaseRequest.department', 'deliveryPlans', 'deliveries'])
        ->whereIn('status', ['approved_proc', 'ordered', 'delivered', 'completed'])
        ->orderBy('created_at', 'desc');

        // Filter based on role (sama dengan di index)
        if ($user->hasRole('user')) {
            $ongoingItemsQuery->whereHas('purchaseRequest', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->hasAnyRole(['operational_manager', 'manager_fat'])) {
            $ongoingItemsQuery->where(function($q) use ($user) {
                $q->whereHas('purchaseRequest', function($subQ) use ($user) {
                    $subQ->where('user_id', $user->id);
                });
                $q->orWhereHas('approvals', function($subQ) use ($user) {
                    $subQ->where('approver_id', $user->id)
                         ->where('status', 'approved');
                });
            });
        } elseif ($user->hasRole('procurement_holding')) {
            // Procurement Holding: hanya PR tipe operational
            $ongoingItemsQuery->whereHas('purchaseRequest', function($q) {
                $q->where('pr_type', 'operational');
            });
        }
        // Superadmin, General Manager, Procurement: export semua item

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\OngoingItemsExport($ongoingItemsQuery), 'monitoring_kedatangan_item_pr.xlsx');
    }

    private function getUserChartData($user)
    {
        $prs = PurchaseRequest::where('user_id', $user->id)->get();
        return [
            'status_distribution' => $this->formatStatusDistribution($prs),
            'monthly_trends' => $this->getMonthlyTrends($user->id),
        ];
    }

    private function getManagerChartData($user)
    {
        $prs = PurchaseRequest::all();
        return [
            'status_distribution' => $this->formatStatusDistribution($prs),
            'monthly_trends' => $this->getMonthlyTrends(),
        ];
    }

    private function formatStatusDistribution($prs)
    {
        $counts = [
            'Draft' => 0,
            'Pending' => 0,
            'Revision Required' => 0,
            'Processing' => 0,
            'Completed' => 0
        ];

        foreach($prs as $pr) {
            $status = $pr->approval_status;
            if ($status === 'Draft') $counts['Draft']++;
            elseif ($status === 'Pending') $counts['Pending']++;
            elseif ($status === 'Revision Required' || $status === 'Partial / Revision') $counts['Revision Required']++;
            elseif ($status === 'Completed') $counts['Completed']++;
            else $counts['Processing']++;
        }

        return [
            'labels' => array_keys($counts),
            'data' => array_values($counts)
        ];
    }

    private function getMonthlyTrends($userId = null)
    {
        $months = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M');
            
            $query = PurchaseRequest::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            $data[] = $query->count();
        }

        return [
            'labels' => $months,
            'data' => $data
        ];
    }

    private function getSuperadminStats()
    {
        $prs = PurchaseRequest::with('items')->get();
        return [
            'total_pr' => $prs->count(),
            'total_users' => User::count(),
            'total_departments' => Department::count(),
            'pending_pr' => $prs->filter(fn($p) => in_array($p->approval_status, ['Pending', 'Revision Required', 'Partial / Revision']))->count(),
            'approved_pr' => $prs->filter(fn($p) => !in_array($p->approval_status, ['Draft', 'Pending', 'Revision Required', 'Partial / Revision', 'Completed']))->count(),
            'rejected_pr' => $prs->filter(fn($p) => in_array($p->approval_status, ['Revision Required', 'Partial / Revision']))->count(),
        ];
    }

    private function getUserStats($user)
    {
        $prs = PurchaseRequest::where('user_id', $user->id)->with('items')->get();
        return [
            'my_pr' => $prs->count(),
            'pending_pr' => $prs->filter(fn($p) => in_array($p->approval_status, ['Pending', 'Revision Required', 'Partial / Revision']))->count(),
            'approved_pr' => $prs->filter(fn($p) => !in_array($p->approval_status, ['Draft', 'Pending', 'Revision Required', 'Partial / Revision', 'Completed']))->count(),
            'rejected_pr' => $prs->filter(fn($p) => in_array($p->approval_status, ['Revision Required', 'Partial / Revision']))->count(),
            'draft_pr' => $prs->filter(fn($p) => $p->approval_status === 'Draft')->count(),
        ];
    }

    private function getManagerStats($user)
    {
        $role = $user->getRoleNames()->first();
        $prs = PurchaseRequest::with('items')->get();

        if ($role === 'procurement_holding') {
            return [
                'pr_to_review' => 0,
                'total_pr' => $prs->where('pr_type', 'operational')->count(),
                'approved_today' => 0,
                'rejected_today' => 0,
            ];
        }
        
        // Items where current status matches what this manager should review
        $pendingTarget = match($role) {
            'operational_manager' => 'Pending',
            'manager_fat' => 'Pending',
            'general_manager' => 'Approved (OM)',
            'procurement' => 'Approved (GM)',
            default => 'Pending'
        };

        return [
            'pr_to_review' => $prs->filter(function($p) use ($pendingTarget, $role, $user) {
                // Filter by PR Type for Level 1 Managers
                if ($pendingTarget === 'Pending') {
                    if ($role === 'operational_manager') {
                        if ($p->pr_type !== 'operational') return false;
                    }
                    if ($role === 'manager_fat' && $p->pr_type !== 'non_operational') return false;
                }

                $itemTargetStatus = match($role) {
                    'operational_manager' => 'pending',
                    'manager_fat' => 'pending',
                    'general_manager' => 'approved_om',
                    'procurement' => 'approved_gm',
                    default => 'pending'
                };

                $prTargetStatus = $pendingTarget;
                if ($role === 'general_manager' && $p->pr_type === 'non_operational') {
                    $prTargetStatus = 'Approved (FAT)';
                }

                // For managers, we check if they have ANY item to review in this PR
                // even if the overall PR status is "Partial / Revision"
                return $p->approval_status === $prTargetStatus || 
                       ($p->approval_status === 'Partial / Revision' && $p->items->contains('status', $itemTargetStatus));
            })->count(),
            'total_pr' => $prs->count(),
            'approved_today' => Approval::where('approver_id', $user->id)
                ->whereDate('approved_at', today())
                ->count(),
            'rejected_today' => Approval::where('approver_id', $user->id)
                ->where('status', 'rejected')
                ->whereDate('approved_at', today())
                ->count(),
        ];
    }
}