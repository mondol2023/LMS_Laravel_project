<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'roll_number',
        'email',
        'phone',
        'passport_number',
        'institute',
        'reference',
    ];

    protected function casts(): array
    {
        return [];
    }
}
