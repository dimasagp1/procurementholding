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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Odoo ERP credentials (optional for hybrid setup)
            $table->string('odoo_url')->nullable();
            $table->string('odoo_db')->nullable();
            $table->string('odoo_username')->nullable();
            $table->text('odoo_password')->nullable();
            $table->integer('odoo_company_id')->nullable();
            
            // Finance API credentials (optional for hybrid setup)
            $table->string('finance_api_url')->nullable();
            $table->string('finance_api_key')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
