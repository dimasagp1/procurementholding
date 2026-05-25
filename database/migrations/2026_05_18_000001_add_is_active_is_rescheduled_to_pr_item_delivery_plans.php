<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pr_item_delivery_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pr_item_delivery_plans', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('attachment_path');
            }
            if (!Schema::hasColumn('pr_item_delivery_plans', 'is_rescheduled')) {
                $table->boolean('is_rescheduled')->default(false)->after('is_active');
            }
        });

        // Fix data lama: set is_active = true untuk plan yang belum punya nilai
        \DB::table('pr_item_delivery_plans')
            ->whereNull('is_active')
            ->update(['is_active' => true, 'is_rescheduled' => false]);
    }

    public function down(): void
    {
        Schema::table('pr_item_delivery_plans', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_rescheduled']);
        });
    }
};
