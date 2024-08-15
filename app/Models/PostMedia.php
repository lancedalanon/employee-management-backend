<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'post_media_id';

    protected $fillable = [
        'post_media',
        'post_media_type',
        'post_id',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'post_id');
    }
}
