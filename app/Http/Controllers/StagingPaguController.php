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
            $apiUrl = \App\Models\Setting::get('finance_api_url', env('FINANCE_API_URL'));
            $apiKey = \App\Models\Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

            if (!$apiUrl || !$apiKey) {
                throw new \Exception('Konfigurasi API Finance belum lengkap di Settings.');
            }

            // Ganti /check dengan /stagings untuk mengakses API data staging
            $stagingsUrl = str_replace('/check', '/stagings', $apiUrl);

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Accept'    => 'application/json',
                    'X-API-KEY' => $apiKey,
                ])
                ->timeout(8)
                ->get($stagingsUrl, [
                    'search'     => $request->input('search'),
                    'department' => $request->input('department'),
                    'status'     => $request->input('status'),
                    'page'       => $request->input('page', 1),
                ]);

            if (!$response->successful()) {
                throw new \Exception('Gagal menghubungi API Finance (HTTP ' . $response->status() . ')');
            }

            $body = $response->json();
            if (($body['status'] ?? 'error') !== 'success' || !isset($body['data'])) {
                throw new \Exception($body['message'] ?? 'API merespons dengan format tidak valid.');
            }

            $apiData = $body['data'];
            $apiStagings = $apiData['stagings'];

            // Ubah array item menjadi stdClass object agar sesuai dengan yang diharapkan oleh blade view
            $items = array_map(function($item) {
                return (object) $item;
            }, $apiStagings['data'] ?? []);

            $stagings = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $apiStagings['total'] ?? 0,
                $apiStagings['per_page'] ?? 20,
                $apiStagings['current_page'] ?? 1,
                [
                    'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                ]
            );
            $stagings->withQueryString();

            // Ringkasan (Summary)
            $summary = isset($apiData['summary']) ? (object) $apiData['summary'] : null;

            // Daftar department untuk filter dropdown
            $departments = collect($apiData['departments'] ?? []);

            return view('staging.index', compact('stagings', 'summary', 'departments'));

        } catch (\Exception $e) {
            return view('staging.index', [
                'stagings' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'summary' => null,
                'departments' => collect(),
                'error' => 'Gagal memuat data dari API Finance: ' . $e->getMessage(),
            ]);
        }
    }
}
