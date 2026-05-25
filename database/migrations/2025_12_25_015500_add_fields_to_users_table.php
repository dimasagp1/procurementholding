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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->unique()->nullable(); // Making nullable initially or carefully handling data
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade'); // Make nullable to avoid issues with existing users if any, though fresh seed handles this
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['employee_id', 'department_id', 'phone', 'position']);
        });
    }
};
