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
        Schema::create('project_task_subtask_users', function (Blueprint $table) {
            $table->id('project_task_subtask_user_id');
            $table->foreign('project_task_subtask_id')->references('project_task_subtask_id')->on('project_task_subtasks')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('project_task_subtask_id');
            $table->unsignedBigInteger('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task_subtask_users');
    }
};
