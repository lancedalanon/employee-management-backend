<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Dtr extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'dtr_id';

    protected $fillable = [
        'user_id',
        'dtr_time_in',
        'dtr_time_out',
        'dtr_end_of_the_day_report',
        'dtr_time_in_image',
        'dtr_time_out_image',
        'dtr_is_overtime',
        'dtr_absence_date',
        'dtr_absence_reason',
        'dtr_absence_approved_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function breaks()
    {
        return $this->hasMany(DtrBreak::class, 'dtr_id', 'dtr_id');
    }
}
