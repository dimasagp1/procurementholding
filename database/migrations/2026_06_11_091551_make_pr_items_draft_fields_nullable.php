<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pr_items', function (Blueprint $table) {
            $table->string('item_name')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
            $table->string('uom')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pr_items', function (Blueprint $table) {
            $table->string('item_name')->nullable(false)->change();
            $table->integer('quantity')->nullable(false)->change();
            $table->string('uom')->nullable(false)->change();
        });
    }
};
