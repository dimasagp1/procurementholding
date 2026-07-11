<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('connect_odoo')->default(false)->after('is_active');
            $table->boolean('connect_finance')->default(false)->after('connect_odoo');
        });

        // Auto-set flags for existing companies that already have credentials
        DB::table('companies')->whereNotNull('odoo_url')->where('odoo_url', '!=', '')->update(['connect_odoo' => true]);
        DB::table('companies')->whereNotNull('finance_api_url')->where('finance_api_url', '!=', '')->update(['connect_finance' => true]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['connect_odoo', 'connect_finance']);
        });
    }
};
