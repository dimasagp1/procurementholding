<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetPrData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pr:reset {--force : Force the operation to run without prompting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus semua data Purchase Request beserta relasi dan lampirannya';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('PERINGATAN: Perintah ini akan menghapus SEMUA data PR, detail item, riwayat approval, dan file lampiran. Lanjutkan?')) {
            $this->info('Operasi dibatalkan.');
            return;
        }

        $this->info('Memulai penghapusan data PR...');

        // Matikan pengecekan Foreign Key untuk mencegah error constraint
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        try {
            // Kosongkan tabel berelasi dari yang terbawah
            \Illuminate\Support\Facades\DB::table('pr_item_deliveries')->truncate();
            \Illuminate\Support\Facades\DB::table('approvals')->truncate();
            \Illuminate\Support\Facades\DB::table('pr_items')->truncate();
            \Illuminate\Support\Facades\DB::table('purchase_requests')->truncate();

            $this->info('Semua data PR berhasil dikosongkan.');

            // Hapus file attachment dari storage
            $prAttachmentPath = storage_path('app/public/pr-attachments');
            $deliveryAttachmentPath = storage_path('app/public/deliveries');

            if (\Illuminate\Support\Facades\File::exists($prAttachmentPath)) {
                \Illuminate\Support\Facades\File::cleanDirectory($prAttachmentPath);
                $this->info('File lampiran PR berhasil dibersihkan.');
            }

            if (\Illuminate\Support\Facades\File::exists($deliveryAttachmentPath)) {
                \Illuminate\Support\Facades\File::cleanDirectory($deliveryAttachmentPath);
                $this->info('File lampiran Kedatangan berhasil dibersihkan.');
            }

            $this->info('PROSES SELESAI: Database PR Anda kini bersih dan siap digunakan!');
        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
        } finally {
            // Nyalakan kembali pengecekan Foreign Key
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }
    }
}
