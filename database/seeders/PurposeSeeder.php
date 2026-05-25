<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purpose;

class PurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purposes = [
            'Biaya Pembelian Packaging Material',
            'Biaya Pembuatan Sampel Produk',
            'Biaya Pembuatan Sampel Kemasan',
            'Biaya Uji Eksternal NPD',
            'Biaya Registrasi Produk',
            'Biaya Panel Produk',
            'Biaya Pengujian Material',
            'Biaya Pengujian Packaging Material',
            'Biaya Pengujian Produk Jadi',
            'Biaya Perawatan Alat dan Instrumen QC',
            'Biaya Perbaikan Alat dan Instrumen QC',
            'Biaya Sewa Gedung, Mesin dan Kendaraan',
            'Biaya Renovasi & Instalasi Bangunan Sewa',
            'Biaya Perawatan Bangunan',
            'Biaya Perawatan Mesin dan Peralatan (supporting utility)',
            'Biaya Perbaikan Mesin dan Peralatan',
            'Biaya Kalibrasi',
            'Biaya Perawatan Kendaraan',
            'Biaya Bongkar Muat',
            'Biaya Pengiriman',
            'Sertifikasi dan Audit',
            'Biaya Telepon & Internet',
            'Biaya listrik dan Air',
            'Biaya Materai',
            'Beban Alat-alat dan Perlengkapan',
            'Biaya Administrasi Bank',
            'Biaya Transfer',
            'Biaya BBM, Parkir & Tol',
            'ATK dan P3K',
            'Iuran Keanggotaan',
            'Biaya Perjalanan Dinas',
            'Biaya Konsumsi',
            'Biaya software',
            'Biaya CSR',
            'Beban Operasional Rumah Tangga (Air minum, Gas, dll)',
            'Beban Alat-alat dan Perlengkapan Produksi',
            'Peralatan sanitasi Produksi',
            'Baju Produksi',
            'Sepatu Produksi',
            'Masker Produksi',
            'Hairnet Caps Produksi',
            'Sarung Tangan Produksi',
            'Pelatihan Karyawan',
            'Outbond',
        ];

        foreach ($purposes as $purpose) {
            Purpose::firstOrCreate(['name' => $purpose]);
        }
    }
}
