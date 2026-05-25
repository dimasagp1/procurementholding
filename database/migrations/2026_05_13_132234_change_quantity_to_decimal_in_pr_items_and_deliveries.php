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
            $table->decimal('quantity', 10, 2)->change();
        });
        Schema::table('pr_item_deliveries', function (Blueprint $table) {
            $table->decimal('received_quantity', 10, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pr_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });
        Schema::table('pr_item_deliveries', function (Blueprint $table) {
            $table->integer('received_quantity')->change();
        });
    }
};
