<?php

namespace App\Http\Controllers;

use App\Models\ExpenseStaging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StagingPaguController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DB::connection('fat_db')
                ->table('expense_stagings as es')
                ->leftJoin('departments as d', 'es.department_id', '=', 'd.id')
                ->leftJoin('budget_categories as bc', 'es.budget_category_id', '=', 'bc.id')
                ->select(
                    'es.id',
                    'es.reference',
                    'es.date',
                    'es.description',
                    'es.qty',
                    'es.amount',
                    'es.status',
                    'es.checked_at',
                    'es.created_at',
                    'd.name as department_name',
                    'bc.name as category_name',
                    'bc.code as category_code'
                );

            // Filter pencarian
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('es.reference', 'like', "%{$search}%")
                      ->orWhere('es.description', 'like', "%{$search}%");
                });
            }

            // Filter department (berdasarkan nama, karena ID bisa berbeda)
            if ($request->filled('department')) {
                $query->where('d.name', 'like', '%' . $request->input('department') . '%');
            }

            // Filter status
            if ($request->filled('status')) {
                $query->where('es.status', $request->input('status'));
            }

            // Default sort: pending dulu, lalu terbaru
            $query->orderByRaw("FIELD(es.status, 'pending', 'bon', 'ignored') ASC")
                  ->orderBy('es.date', 'desc');

            $stagings = $query->paginate(20)->withQueryString();

            // Hitung ringkasan
            $summary = DB::connection('fat_db')
                ->table('expense_stagings')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'bon' THEN 1 ELSE 0 END) as bon_count,
                    SUM(CASE WHEN status = 'ignored' THEN 1 ELSE 0 END) as ignored_count,
                    SUM(CASE WHEN status IN ('pending','bon') THEN amount ELSE 0 END) as total_amount
                ")->first();

            // Daftar department untuk filter dropdown
            $departments = DB::connection('fat_db')
                ->table('departments')
                ->orderBy('name')
                ->pluck('name');

            return view('staging.index', compact('stagings', 'summary', 'departments'));

        } catch (\Exception $e) {
            return view('staging.index', [
                'stagings' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'summary' => null,
                'departments' => collect(),
                'error' => 'Tidak dapat terhubung ke database Finance: ' . $e->getMessage(),
            ]);
        }
    }
}
