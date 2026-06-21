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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->unsignedInteger('total_minutes')->nullable();
            $table->enum('status', [
                'present',
                'late',
                'absent',
                'half_day',
                'leave',
                'holiday',
                'work_from_home',
            ])->default('present');
            $table->text('remarks')->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->text('device_info')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
            $table->index('attendance_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
