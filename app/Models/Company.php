<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $primaryKey = 'company_id';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_registration_number',
        'company_tax_id',
        'company_address',
        'company_city',
        'company_state',
        'company_postal_code',
        'company_country',
        'company_phone_number',
        'company_email',
        'company_website',
        'company_industry',
        'company_founded_at',
        'company_description',
        'deactivated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function invites()
    {
        return $this->hasMany(InviteToken::class, 'company_id', 'company_id');
    }
}