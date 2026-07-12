<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('department_id');
            $table->index('company_id');
        });

        Schema::table('pr_items', function (Blueprint $table) {
            $table->index('purchase_request_id');
            $table->index('status');
            $table->index('po_number');
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->index('purchase_request_id');
            $table->index('pr_item_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['company_id']);
        });

        Schema::table('pr_items', function (Blueprint $table) {
            $table->dropIndex(['purchase_request_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['po_number']);
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->dropIndex(['purchase_request_id']);
            $table->dropIndex(['pr_item_id']);
            $table->dropIndex(['status']);
        });
    }
};
