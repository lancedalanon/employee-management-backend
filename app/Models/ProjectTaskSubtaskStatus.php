<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTaskSubtaskStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_task_subtask_status_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_subtask_id',
        'project_task_subtask_status',
        'project_task_subtask_status_media_file',
    ];

    public function subtask()
    {
        return $this->belongsTo(ProjectTaskSubtask::class, 'project_task_subtask_id', 'project_task_subtask_id');
    }
}
