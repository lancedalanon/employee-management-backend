<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dtr extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
