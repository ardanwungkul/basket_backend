<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('coach_id');
            $table->uuid('training_schedule_id');
            $table->date('date');
            $table->time('time');
            $table->enum('method', ['qr', 'manual']);
            $table->enum('status', ['present', 'absent'])->default('present');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('coach_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('training_schedule_id')->references('id')->on('training_schedule')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
