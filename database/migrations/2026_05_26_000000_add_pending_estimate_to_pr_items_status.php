<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE pr_items MODIFY COLUMN status ENUM('pending_estimate', 'pending', 'approved_om', 'rejected_om', 'approved_gm', 'rejected_gm', 'approved_proc', 'rejected_proc', 'ordered', 'delivered', 'completed') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change any 'pending_estimate' items to 'pending' before reverting enum
        DB::table('pr_items')->where('status', 'pending_estimate')->update(['status' => 'pending']);
        
        DB::statement("ALTER TABLE pr_items MODIFY COLUMN status ENUM('pending', 'approved_om', 'rejected_om', 'approved_gm', 'rejected_gm', 'approved_proc', 'rejected_proc', 'ordered', 'delivered', 'completed') NOT NULL DEFAULT 'pending'");
    }
};
