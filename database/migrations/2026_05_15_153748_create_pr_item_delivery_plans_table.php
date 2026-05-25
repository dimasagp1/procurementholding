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
        Schema::create('pr_item_delivery_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pr_item_id')->constrained()->onDelete('cascade');
            $table->date('planned_date');
            $table->decimal('planned_quantity', 10, 2);
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_item_delivery_plans');
    }
};
