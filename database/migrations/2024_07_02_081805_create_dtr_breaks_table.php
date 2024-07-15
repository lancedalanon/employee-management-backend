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
        Schema::create('dtr_breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dtr_id');
            $table->foreign('dtr_id')->references('dtr_id')->on('dtrs')->onDelete('cascade');
            $table->dateTime('break_time');
            $table->dateTime('resume_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_breaks');
    }
};
