<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'post_id';

    protected $fillable = [
        'post_title',
        'post_content',
        'user_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->post_slug = Str::slug($post->post_title);
        });

        static::updating(function ($post) {
            $post->post_slug = Str::slug($post->post_title);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function tags()
    {
        return $this->hasMany(PostTag::class, 'post_id', 'post_id');
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class, 'post_id', 'post_id');
    }
}
