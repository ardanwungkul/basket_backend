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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('parent_id');
            $table->foreign('parent_id')->references('id')->on('guardians')->onUpdate('cascade')->onDelete('cascade');
            $table->date('payment_date')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_method', ['transfer', 'qris']);
            $table->string('reference_code');
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
