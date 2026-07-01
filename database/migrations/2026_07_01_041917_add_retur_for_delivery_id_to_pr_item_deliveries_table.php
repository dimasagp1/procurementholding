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
        Schema::table('pr_item_deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('retur_for_delivery_id')->nullable()->after('pr_item_id');
            $table->foreign('retur_for_delivery_id')
                  ->references('id')
                  ->on('pr_item_deliveries')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pr_item_deliveries', function (Blueprint $table) {
            $table->dropForeign(['retur_for_delivery_id']);
            $table->dropColumn('retur_for_delivery_id');
        });
    }
};
