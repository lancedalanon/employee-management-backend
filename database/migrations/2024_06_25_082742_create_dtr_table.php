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
        Schema::create('dtrs', function (Blueprint $table) {
            $table->id('dtr_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('dtr_time_in')->nullable();
            $table->timestamp('dtr_time_out')->nullable();
            $table->string('dtr_time_in_image')->nullable();
            $table->string('dtr_time_out_image')->nullable();
            $table->string('dtr_reason_of_late_entry')->nullable();
            $table->text('dtr_end_of_the_day_report')->nullable();
            $table->boolean('dtr_is_overtime')->default(0);
            $table->date('dtr_absence_date')->nullable();
            $table->string('dtr_absence_reason')->nullable();
            $table->timestamp('dtr_absence_approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtrs');
    }
};
