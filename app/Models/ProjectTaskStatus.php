<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTaskStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_task_status_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_id',
        'project_task_status',
    ];

    /**
     * Get the task that owns the status.
     */
    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id', 'project_task_id');
    }

    /**
     * Get the media for the status.
     */
    public function media()
    {
        return $this->hasMany(ProjectTaskStatus::class, 'project_task_status_id', 'project_task_status_id');
    }
}
