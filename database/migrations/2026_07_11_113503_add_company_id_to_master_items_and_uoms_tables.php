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
        Schema::table('master_items', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('uoms', function (Blueprint $table) {
            // Drop unique index on name
            $table->dropUnique('uoms_name_unique');
            
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['name', 'company_id']);
        });

        // Set default company for existing items & uoms
        $defaultCompany = DB::table('companies')->first();
        if ($defaultCompany) {
            DB::table('master_items')->whereNull('company_id')->update(['company_id' => $defaultCompany->id]);
            DB::table('uoms')->whereNull('company_id')->update(['company_id' => $defaultCompany->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uoms', function (Blueprint $table) {
            $table->dropUnique(['name', 'company_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->unique('name');
        });

        Schema::table('master_items', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
