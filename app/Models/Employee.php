<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'provider',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'position',
        'department',
        'start_date',
        'status',
        'tracktik_id',
        'provider_data',
    ];

    protected $casts = [
        'provider_data' => 'array',
        'start_date' => 'date',
    ];
}
