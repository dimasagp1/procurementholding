<?php

namespace App\Traits;

use App\Models\PurchaseRequest;
use Illuminate\Support\Str;

trait GeneratesPrNumber
{
    public static function bootGeneratesPrNumber()
    {
        static::creating(function ($model) {
            if (!$model->pr_number) {
                $departmentCode = $model->department->code ?? 'GEN';
                $date = now()->format('Ymd');
                
                // Get the last PR number for this department today to increment
                $lastPr = PurchaseRequest::where('department_id', $model->department_id)
                    ->whereDate('created_at', today())
                    ->latest()
                    ->first();
                
                $sequence = 1;
                // Regex updated to handle optional PR- prefix or no prefix for backward compatibility
                if ($lastPr && preg_match('/(?:PR-)?\w+-(\d+)-/', $lastPr->pr_number, $matches)) {
                    $sequence = intval($matches[1]) + 1;
                }
                
                $sequencePadded = str_pad($sequence, 3, '0', STR_PAD_LEFT);
                
                // Format: PR-DEPT-001-20231225
                $model->pr_number = strtoupper("PR-{$departmentCode}-{$sequencePadded}-{$date}");
            }
        });
    }
}
