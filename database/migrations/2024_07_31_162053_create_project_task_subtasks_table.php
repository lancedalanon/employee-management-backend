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
        Schema::create('project_task_subtasks', function (Blueprint $table) {
            $table->id('project_task_subtask_id');
            $table->string('project_task_subtask_name');
            $table->text('project_task_subtask_description');
            $table->string('project_task_subtask_progress');
            $table->string('project_task_subtask_priority_level');
            $table->unsignedBigInteger('project_task_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_task_id')->references('project_task_id')->on('project_tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task_subtasks');
    }
};
