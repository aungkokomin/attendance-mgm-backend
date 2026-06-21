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
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('action', ['approved', 'rejected']);
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at');

            $table->index('action');
            $table->index('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
