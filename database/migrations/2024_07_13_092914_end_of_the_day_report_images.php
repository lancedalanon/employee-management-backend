<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('end_of_the_day_report_images', function (Blueprint $table) {
            $table->id('end_of_the_day_report_image_id');
            $table->unsignedBigInteger('dtr_id');
            $table->foreign('dtr_id')->references('dtr_id')->on('dtrs')->onDelete('cascade');
            $table->binary('end_of_the_day_report_image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('end_of_the_day_report_images');
    }
};
