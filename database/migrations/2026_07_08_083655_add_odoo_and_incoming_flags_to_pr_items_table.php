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
        Schema::table('pr_items', function (Blueprint $table) {
            $table->boolean('rekap_po_odoo')->default(true)->after('purpose');
            $table->boolean('is_incoming')->default(true)->after('rekap_po_odoo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pr_items', function (Blueprint $table) {
            $table->dropColumn(['rekap_po_odoo', 'is_incoming']);
        });
    }
};
