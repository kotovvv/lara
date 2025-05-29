<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
  use HasFactory;
  protected $fillable = [
    'tel',
    'lid_id',
    'status_id',
    'user_id',
    'text',
    'last_log'
  ];
}
