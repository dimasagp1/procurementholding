<?php
// database/migrations/xxxx_create_approvals_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('pr_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->string('approver_role');
            $table->enum('approval_type', ['om', 'gm', 'procurement']);
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};