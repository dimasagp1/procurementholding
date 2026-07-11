<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. approvals.approval_type ENUM alter
        DB::statement("ALTER TABLE approvals MODIFY COLUMN approval_type ENUM('om', 'gm', 'procurement', 'requester', 'fatm', 'holding') NOT NULL");

        // 2. pr_items.status ENUM alter to include rejected_holding
        DB::statement("ALTER TABLE pr_items MODIFY COLUMN status ENUM('pending_estimate', 'pending', 'approved_om', 'rejected_om', 'approved_gm', 'rejected_gm', 'approved_proc', 'rejected_proc', 'rejected_holding', 'ordered', 'delivered', 'completed') NOT NULL DEFAULT 'pending'");

        // 3. purchase_requests.status ENUM alter to include rejected_holding
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'pending', 'approved_om', 'rejected_om', 'approved_gm', 'rejected_gm', 'approved_proc', 'rejected_proc', 'rejected_holding', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'pending', 'approved_om', 'rejected_om', 'approved_gm', 'rejected_gm', 'approved_proc', 'rejected_proc', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE pr_items MODIFY COLUMN status ENUM('pending_estimate', 'pending', 'approved_om', 'rejected_om', 'approved_gm', 'rejected_gm', 'approved_proc', 'rejected_proc', 'ordered', 'delivered', 'completed') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE approvals MODIFY COLUMN approval_type ENUM('om', 'gm', 'procurement', 'requester', 'fatm') NOT NULL");
    }
};
