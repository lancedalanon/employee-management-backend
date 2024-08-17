<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InviteToken extends Model
{
    use HasFactory;

    protected $primaryKey = 'invite_token_id';

    protected $fillable = [
        'company_id',
        'email',
        'token',
        'expires_at',
        'used_at',
    ];

    // Generate a unique token
    public static function generateToken()
    {
        return Str::random(60);
    }

    public function company() 
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
