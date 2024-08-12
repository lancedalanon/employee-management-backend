<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTaskUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_task_users';

    protected $primaryKey = 'project_task_user_id';

    protected $fillable = [
        'project_task_id',
        'user_id',
    ];

    public function task() 
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_task_users', 'project_task_id', 'user_id')
            ->withTimestamps();
    }
}
