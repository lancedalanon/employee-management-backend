<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EndOfTheDayReportImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'dtr_id',
        'end_of_the_day_image'
    ];

    public function dtr()
    {
        return $this->belongsTo(Dtr::class);
    }
}
