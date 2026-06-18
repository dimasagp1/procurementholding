<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model read-only untuk membaca data staging pengeluaran pagu dari database FAT.
 * Model ini TIDAK menulis data — hanya digunakan untuk menampilkan data di PROC.
 */
class ExpenseStaging extends Model
{
    protected $connection = 'fat_db';
    protected $table = 'expense_stagings';

    protected $casts = [
        'date' => 'date',
        'checked_at' => 'datetime',
        'qty' => 'float',
        'amount' => 'float',
    ];
}
