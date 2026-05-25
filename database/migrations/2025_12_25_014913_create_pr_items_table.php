<?php
// database/migrations/xxxx_create_pr_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pr_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->onDelete('cascade');
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->string('uom');
            $table->decimal('estimated_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending', 'approved_om', 'rejected_om', 'approved_gm', 'rejected_gm', 'approved_proc', 'rejected_proc', 'ordered', 'delivered', 'completed'])->default('pending');
            $table->text('reject_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->integer('revision_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_items');
    }
};