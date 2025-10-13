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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('gender', ['Laki Laki', 'Perempuan']);
            $table->string('place_of_birth');
            $table->date('date_of_birth');
            $table->string('school');
            $table->string('school_grade');
            $table->string('disease')->nullable();
            $table->boolean('is_former_club')->default(false);
            $table->string('former_club')->nullable();
            $table->integer('former_club_year')->nullable();

            $table->string('parent_name');
            $table->string('parent_phone_number');
            $table->string('parent_email');
            $table->longText('parent_address');

            $table->unsignedBigInteger('parent_id');
            $table->foreign('parent_id')->references('id')->on('guardians')->onUpdate('cascade')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
