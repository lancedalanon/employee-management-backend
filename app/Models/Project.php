<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_id';

    public $timestamps = true;

    protected $fillable = [
        'project_name',
        'project_description'
    ];

    /**
     * The users that belong to the project.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')->withTimestamps();
    }
}
