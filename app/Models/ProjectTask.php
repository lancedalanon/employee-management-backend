<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_task_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_name',
        'project_task_description',
        'project_id',
        'project_task_progress',
        'project_task_priority_level',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    /**
     * Get the statuses for the task.
     */
    public function statuses()
    {
        return $this->hasMany(ProjectTaskStatus::class, 'project_task_id', 'project_task_id');
    }
}
