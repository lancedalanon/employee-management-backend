<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'project_id';

    public $timestamps = true;

    protected $fillable = [
        'project_name',
        'project_description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_users', 'project_id', 'user_id')
            ->withPivot('project_role')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_id', 'project_id');
    }
}
