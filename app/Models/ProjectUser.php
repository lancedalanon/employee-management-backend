<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_users';

    protected $primaryKey = 'project_user_id';

    protected $fillable = [
        'project_id',
        'user_id',
        'project_role'
    ];

    /**
     * Set the project role attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setProjectRoleAttribute($value)
    {
        $validRoles = config('constants.project_roles');

        if (!in_array($value, $validRoles)) {
            throw new \InvalidArgumentException("Invalid project role: $value");
        }

        $this->attributes['project_role'] = $value;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
