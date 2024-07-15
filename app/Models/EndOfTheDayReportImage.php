<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EndOfTheDayReportImage extends Model
{
    use HasFactory;

    protected $primaryKey = 'end_of_day_report_image_id';

    protected $fillable = [
        'dtr_id',
        'end_of_the_day_image'
    ];

    public function dtr()
    {
        return $this->belongsTo(Dtr::class, 'dtr_id', 'dtr_id');
    }
}
