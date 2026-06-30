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
            $table->decimal('rejected_quantity', 15, 2)->default(0)->after('received_quantity');
            $table->text('rejection_reason')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pr_item_deliveries', function (Blueprint $table) {
            $table->dropColumn(['rejected_quantity', 'rejection_reason']);
        });
    }
};
