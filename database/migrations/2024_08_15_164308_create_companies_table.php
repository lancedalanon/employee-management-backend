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
        Schema::create('companies', function (Blueprint $table) {
            $table->id('company_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('company_name')->unique();
            $table->string('company_registration_number')->unique();
            $table->string('company_tax_id')->nullable()->unique();
            $table->string('company_address')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_state')->nullable();
            $table->string('company_postal_code')->nullable();
            $table->string('company_country')->nullable();
            $table->string('company_phone_number')->nullable()->unique();
            $table->string('company_email')->nullable()->unique();
            $table->string('company_website')->nullable();
            $table->string('company_industry')->nullable();
            $table->date('company_founded_at')->nullable();
            $table->text('company_description')->nullable();
            $table->time('company_full_time_start_time')->nullable();
            $table->time('company_full_time_end_time')->nullable();            
            $table->time('company_part_time_start_time')->nullable();
            $table->time('company_part_time_end_time')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
