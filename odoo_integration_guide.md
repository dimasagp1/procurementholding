# Panduan Integrasi Purchase Order (PO) Laravel ke Odoo API

Panduan ini menjelaskan langkah-langkah untuk mengirim data Purchase Order (PO) dari aplikasi Procurement Laravel ke ERP Odoo menggunakan **JSON-RPC API** secara modular dan portable.

---

## 1. Konfigurasi Environment (`.env`)
Tambahkan kredensial koneksi Odoo ke file `.env` di aplikasi Laravel Anda:

```env
ODOO_URL=https://your-odoo-domain.com
ODOO_DB=nama_database_odoo
ODOO_USERNAME=email_atau_username_admin
ODOO_PASSWORD=api_key_odoo_anda
```

> **Catatan Keamanan:** Gunakan **API Key** yang dibuat di profil User Odoo Anda (*Preferences -> Account Security -> Developer Tools*) sebagai pengganti password asli demi alasan keamanan.

---

## 2. Membuat Service Odoo (`app/Services/OdooService.php`)
Buat file class Service di `app/Services/OdooService.php` untuk menangani komunikasi dengan Odoo. Service ini otomatis mencocokkan/membuat Vendor (*res.partner*) dan Produk (*product.product*), lalu membuat Purchase Order beserta baris barangnya (*order_line*) dalam satu request.

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooService
{
    protected $url;
    protected $db;
    protected $username;
    protected $password;
    protected $uid;

    public function __construct()
    {
        $this->url = env('ODOO_URL');
        $this->db = env('ODOO_DB');
        $this->username = env('ODOO_USERNAME');
        $this->password = env('ODOO_PASSWORD');
    }

    /**
     * Melakukan autentikasi ke Odoo untuk mendapatkan User ID (uid)
     */
    public function authenticate()
    {
        if ($this->uid) {
            return $this->uid;
        }

        try {
            $response = Http::post("{$this->url}/jsonrpc", [
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
            $response = Http::post("{$this->url}/jsonrpc", [
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
    }

    /**
     * Mendapatkan atau membuat Product (product.product) di Odoo berdasarkan nama
     */
    public function getOrCreateProduct($name)
    {
        $productIds = $this->execute('product.product', 'search', [
            [['name', '=', $name]]
        ]);

        if (!empty($productIds)) {
            return $productIds[0];
        }

        // Buat produk baru bertipe consumable
        return $this->execute('product.product', 'create', [[
            'name' => $name,
            'detailed_type' => 'consu',
            'purchase_ok' => true
        ]]);
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

            $orderLines[] = [0, 0, [
                'product_id' => $productId,
                'name' => $item->description ?: $item->item_name,
                'product_qty' => (float) $item->quantity,
                'price_unit' => (float) ($item->actual_price ?: $item->estimated_price),
                'date_planned' => $item->due_date ?: now()->addDays(7)->format('Y-m-d'),
            ]];
        }

        $poData = [
            'partner_id' => $partnerId,
            'origin' => $purchaseRequest->pr_number, // Referensi PR lokal
            'date_order' => now()->format('Y-m-d H:i:s'),
            'order_line' => $orderLines
        ];

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
}
```

---

## 3. Integrasi pada Controller (`PurchaseRequestController.php`)
Di file `app/Http/Controllers/PurchaseRequestController.php`, panggil OdooService saat status item diperbarui menjadi `ordered`.

### Langkah-langkah Integrasi:

1. Import service di bagian paling atas:
   ```php
   use App\Services\OdooService;
   ```

2. Ubah signature method `updateItemStatus` agar menginjeksikan `OdooService`:
   ```php
   public function updateItemStatus(Request $request, PrItem $item, OdooService $odooService)
   ```

3. Modifikasi kode di dalam blok `if ($request->status === 'ordered')` menjadi seperti berikut:
   ```php
   if ($request->status === 'ordered') {
       $request->validate([
           'po_number' => 'nullable|string|max:255', // opsional jika ingin otomatis generate dari Odoo
           'actual_price' => 'required|numeric|min:0',
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
           return redirect()->back()->with('error', 'Total rencana kedatangan tidak boleh melebihi jumlah pesanan.');
       }

       // Kirim data ke Odoo dan buat PO otomatis
       $purchaseRequest = $item->purchaseRequest;
       $odooPo = $odooService->createPurchaseOrder(
           $purchaseRequest, 
           [$item], 
           $request->vendor_name ?? 'Default Vendor'
       );

       if ($odooPo && isset($odooPo['name'])) {
           $poNumber = $odooPo['name']; // Menggunakan nomor PO dari Odoo
       } else {
           $poNumber = $request->po_number ?: 'PO-PENDING'; // Fallback manual
           \Log::warning("Gagal membuat PO di Odoo API, menggunakan fallback nomor PO manual.");
       }

       $updateData['po_number'] = $poNumber;
       $updateData['actual_price'] = $request->actual_price;
       $updateData['actual_total_price'] = $item->quantity * $request->actual_price;
       $updateData['ordered_at'] = now();
       
       $msg = "Item '{$item->item_name}' sedang dalam proses pemesanan (Ordered) dengan PO: {$poNumber}.";
   }
   ```

---

## 4. Keuntungan Arsitektur Ini
* **Hemat Payload / Performa Tinggi**: Menggunakan format Command List Odoo `[0, 0, {values}]` agar pembuatan *Purchase Order* dan *Order Lines* selesai dalam **satu kali HTTP Request**.
* **Zero External Dependencies**: Berjalan murni menggunakan HTTP Client Laravel bawaan (`Http::post`), tidak memerlukan ekstensi XML-RPC yang terkadang belum terpasang di Laragon/Server.
* **Auto-healing Master Data**: Jika Vendor atau Produk belum terdaftar di Odoo, service otomatis mendaftarkannya terlebih dahulu sehingga API tidak mengalami *crash*.
