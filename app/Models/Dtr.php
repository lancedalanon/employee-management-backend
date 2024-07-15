<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dtr extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'time_in',
        'time_out',
        'end_of_the_day_report',
    ];

    protected $dates = [
        'time_in',
        'time_out'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function breaks()
    {
        return $this->hasMany(DtrBreak::class);
    }

    public function endOfTheDayReportImages()
    {
        return $this->hasMany(EndOfTheDayReportImage::class);
    }
}
