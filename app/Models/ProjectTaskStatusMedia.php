<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTaskStatusMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_task_status_media_id';

    public $timestamps = true;

    protected $fillable = [
        'project_task_status_media_file',
    ];

    /**
     * Get the task that owns the status.
     */
    public function status()
    {
        return $this->belongsTo(ProjectTaskStatus::class, 'project_task_status_id', 'project_task_status_id');
    }
}
