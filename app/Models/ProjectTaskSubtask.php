<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTaskSubtask extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_task_subtask_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_id',
        'project_task_subtask_name',
        'project_task_subtask_description',
        'project_task_subtask_progress',
        'project_task_subtask_priority_level',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id', 'project_task_id');
    }
}
