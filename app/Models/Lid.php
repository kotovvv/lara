<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lid extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'tel',
        'email',
        'status_id',
        'provider_id',
        'user_id',
        'text',
        'active'
    ];
}
