<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ProjectTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'project_task_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_name',
        'project_task_description',
        'project_id',
        'project_task_progress',
        'project_task_priority_level',
        'user_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function subtasks()
    {
        return $this->hasMany(ProjectTaskSubtask::class, 'project_task_id', 'project_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
