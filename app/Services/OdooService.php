<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Setting;

class OdooService
{
    protected $url;
    protected $db;
    protected $username;
    protected $password;
    protected $uid;
    protected $odoo_company_id;

    public function __construct(\App\Models\Company $company = null)
    {
        $this->url = $company && $company->odoo_url ? $company->odoo_url : Setting::get('odoo_url', env('ODOO_URL'));
        $this->db = $company && $company->odoo_db ? $company->odoo_db : Setting::get('odoo_db', env('ODOO_DB'));
        $this->username = $company && $company->odoo_username ? $company->odoo_username : Setting::get('odoo_username', env('ODOO_USERNAME'));
        $this->password = $company && $company->odoo_password ? $company->odoo_password : Setting::get('odoo_password', env('ODOO_PASSWORD'));
        $this->odoo_company_id = $company ? $company->odoo_company_id : null;
    }

    /**
     * Melakukan autentikasi ke Odoo untuk mendapatkan User ID (uid)
     */
    public function authenticate()
    {
        if ($this->uid) {
            return $this->uid;
        }

        if (!$this->url || !$this->db || !$this->username || !$this->password) {
            Log::error('Odoo configuration missing in .env');
            return null;
        }

        try {
            $response = Http::withoutVerifying()->post("{$this->url}/jsonrpc", [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => 'common',
                    'method' => 'login',
                    'args' => [$this->db, $this->username, $this->password]
                ],
                'id' => rand(1, 1000)
            ]);

            if ($response->successful() && isset($response->json()['result'])) {
                $this->uid = $response->json()['result'];
                return $this->uid;
            }

            Log::error('Odoo Authentication failed', ['response' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Odoo Auth connection error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Memanggil method dari model Odoo menggunakan execute_kw
     */
    public function execute($model, $method, array $args = [], array $kwargs = [])
    {
        $uid = $this->authenticate();
        if (!$uid) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()->post("{$this->url}/jsonrpc", [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => 'object',
                    'method' => 'execute_kw',
                    'args' => [
                        $this->db,
                        $uid,
                        $this->password,
                        $model,
                        $method,
                        $args,
                        $kwargs
                    ]
                ],
                'id' => rand(1, 1000)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['error'])) {
                    Log::error("Odoo error in {$model}::{$method}", ['error' => $data['error']]);
                    return null;
                }
                return $data['result'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Odoo execution failed on {$model}::{$method}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mendapatkan atau membuat Vendor (res.partner) di Odoo berdasarkan nama
     */
    public function getOrCreatePartner($name)
    {
        $cacheKey = 'odoo_partner_' . md5($name);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(7), function () use ($name) {
            $partnerIds = $this->execute('res.partner', 'search', [
                [['name', '=', $name], ['supplier_rank', '>', 0]]
            ]);

            if (!empty($partnerIds)) {
                return $partnerIds[0];
            }

            // Buat vendor baru jika belum ada
            return $this->execute('res.partner', 'create', [[
                'name' => $name,
                'supplier_rank' => 1,
                'is_company' => true
            ]]);
        });
    }

    /**
     * Mendapatkan atau membuat Product (product.product) di Odoo berdasarkan nama
     */
    public function getOrCreateProduct($name)
    {
        $cacheKey = 'odoo_product_' . md5($name);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(7), function () use ($name) {
            $productIds = $this->execute('product.product', 'search', [
                [['name', '=', $name]]
            ]);

            if (!empty($productIds)) {
                return $productIds[0];
            }

            // Buat produk baru bertipe consumable
            return $this->execute('product.product', 'create', [[
                'name' => $name,
                'type' => 'consu',
                'purchase_ok' => true
            ]]);
        });
    }

    /**
     * Mengirim data Purchase Order ke Odoo
     */
    public function createPurchaseOrder($purchaseRequest, $items, $vendorName = 'Default Vendor')
    {
        $partnerId = $this->getOrCreatePartner($vendorName);
        if (!$partnerId) {
            Log::error('Failed to resolve Partner in Odoo');
            return null;
        }

        // Siapkan order lines dengan format Command Odoo: [0, 0, {values}]
        $orderLines = [];
        foreach ($items as $item) {
            $productId = $this->getOrCreateProduct($item->item_name);

            // Dapatkan default UoM dari produk di Odoo
            $uomId = \Illuminate\Support\Facades\Cache::remember('odoo_uom_product_' . $productId, now()->addDays(7), function () use ($productId) {
                $productData = $this->execute('product.product', 'read', [
                    [$productId], ['uom_id']
                ]);
                return $productData[0]['uom_id'][0] ?? null;
            });

            $datePlanned = now()->addDays(7)->format('Y-m-d');
            if ($item->due_date) {
                try {
                    $datePlanned = Carbon::parse($item->due_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::warning('Failed to parse item due_date, using fallback date planned', ['error' => $e->getMessage()]);
                }
            }

            $orderLines[] = [0, 0, [
                'product_id' => $productId,
                'name' => $item->item_name,
                'product_qty' => (float) $item->quantity,
                'price_unit' => (float) ($item->actual_price ?: $item->estimated_price),
                'date_planned' => $datePlanned,
                'product_uom' => $uomId,
            ]];
        }

        $poData = [
            'partner_id' => $partnerId,
            'origin' => $purchaseRequest->pr_number, // Referensi PR lokal
            'date_order' => now()->format('Y-m-d H:i:s'),
            'order_line' => $orderLines
        ];

        if ($this->odoo_company_id) {
            $poData['company_id'] = $this->odoo_company_id;
        }

        // Buat Purchase Order di Odoo
        $odooPoId = $this->execute('purchase.order', 'create', [$poData]);

        if ($odooPoId) {
            // Dapatkan nomor PO resmi yang dibuat oleh Odoo
            $poDetails = $this->execute('purchase.order', 'read', [
                [$odooPoId], ['name']
            ]);

            return [
                'id' => $odooPoId,
                'name' => $poDetails[0]['name'] ?? null
            ];
        }

        return null;
    }

    /**
     * Mengambil daftar vendor (partner) dari Odoo
     */
    public function getVendors()
    {
        $uid = $this->authenticate();
        if (!$uid) {
            return [];
        }

        $partners = $this->execute('res.partner', 'search_read', [
            [['supplier_rank', '>', 0]],
            ['id', 'name']
        ]);

        if (empty($partners)) {
            $partners = $this->execute('res.partner', 'search_read', [
                [['is_company', '=', true]],
                ['id', 'name']
            ]);
        }

        return $partners ?? [];
    }

    /**
     * Mengambil daftar vendor (partner) dari Odoo dengan detail lengkap
     */
    public function getVendorsDetailed()
    {
        $uid = $this->authenticate();
        if (!$uid) {
            return [];
        }

        $partners = $this->execute('res.partner', 'search_read', [
            [['supplier_rank', '>', 0]],
            ['id', 'name', 'email', 'phone', 'street', 'city', 'vat', 'website']
        ]);

        if (empty($partners)) {
            $partners = $this->execute('res.partner', 'search_read', [
                [['is_company', '=', true]],
                ['id', 'name', 'email', 'phone', 'street', 'city', 'vat', 'website']
            ]);
        }

        if (is_array($partners)) {
            foreach ($partners as &$partner) {
                $partner['mobile'] = null; // map mobile to null since it's not supported by this Odoo schema
            }
        }

        return $partners ?? [];
    }

    /**
     * Membuat Vendor baru di Odoo
     */
    public function createVendor(array $data)
    {
        return $this->execute('res.partner', 'create', [[
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? $data['mobile'] ?? null,
            'street' => $data['street'] ?? null,
            'city' => $data['city'] ?? null,
            'vat' => $data['vat'] ?? null,
            'website' => $data['website'] ?? null,
            'supplier_rank' => 1,
            'is_company' => true
        ]]);
    }
}
