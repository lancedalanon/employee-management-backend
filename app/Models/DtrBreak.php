<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'dtr_id',
        'break_time',
        'resume_time'
    ];

    public function dtr()
    {
        return $this->belongsTo(Dtr::class);
    }
}
