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
        Schema::create('leave_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('days', 5, 2);
            $table->enum('type', ['allocation', 'usage', 'reversal', 'adjustment']);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balance_transactions');
    }
};
