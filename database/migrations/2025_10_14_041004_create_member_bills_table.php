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
        Schema::create('member_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('member_id');
            $table->foreign('member_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('bill_type', ['registration', 'monthly']);
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->enum('status', ['PAID', 'UNPAID']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_bills');
    }
};
