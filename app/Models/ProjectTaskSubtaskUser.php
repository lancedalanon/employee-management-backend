<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTaskSubtaskUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_task_subtask_users';

    protected $primaryKey = 'project_task_subtask_user_id';

    protected $fillable = [
        'project_task_subtask_id',
        'user_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_task_subtask_users', 'project_task_subtask_id', 'user_id')
            ->withTimestamps();
    }
}
