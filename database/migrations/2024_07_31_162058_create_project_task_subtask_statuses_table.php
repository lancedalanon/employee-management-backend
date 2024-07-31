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
        Schema::create('project_task_subtask_statuses', function (Blueprint $table) {
            $table->id('project_task_subtask_status_id');
            $table->text('project_task_subtask_status');
            $table->unsignedBigInteger('project_task_subtask_id');
            $table->binary('project_task_subtask_status_media_file')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_task_subtask_id')->references('project_task_subtask_id')->on('project_task_subtasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task_subtask_statuses');
    }
};
