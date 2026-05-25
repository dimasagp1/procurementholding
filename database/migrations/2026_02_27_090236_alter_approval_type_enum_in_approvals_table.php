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
        Schema::table('approvals', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE approvals MODIFY COLUMN approval_type ENUM('om', 'gm', 'procurement', 'requester') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE approvals MODIFY COLUMN approval_type ENUM('om', 'gm', 'procurement') NOT NULL");
        });
    }
};
