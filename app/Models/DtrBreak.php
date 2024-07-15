<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrBreak extends Model
{
    use HasFactory;

    protected $primaryKey = 'dtr_break_id';

    protected $fillable = [
        'dtr_id',
        'break_time',
        'resume_time'
    ];

    public function dtr()
    {
        return $this->belongsTo(Dtr::class, 'dtr_id', 'dtr_id');
    }
}
