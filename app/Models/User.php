<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'place_of_birth',
        'date_of_birth',
        'gender',
        'username',
        'email',
        'recovery_email',
        'phone_number',
        'emergency_contact_name',
        'emergency_contact_number',
        'password',
        'api_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->getRoleNames(),
            'user' => [
                'id' => $this->id,
                'username' => $this->username,
                'email' => $this->email,
            ],
        ];
    }

    public function dtr()
    {
        return $this->hasMany(Dtr::class, 'user_id', 'user_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_users', 'user_id', 'project_id')
            ->withPivot('project_role')
            ->withTimestamps();
    }

    public function getFullNameAttribute()
    {
        $full_name = "{$this->first_name} ";

        if ($this->middle_name) {
            $full_name .= "{$this->middle_name} ";
        }

        $full_name .= "{$this->last_name} ";

        if ($this->suffix) {
            $full_name .= "{$this->suffix}";
        }

        return trim($full_name);
    }
}
