<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Provider extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable;

  protected $guard = 'provs';

  protected $fillable = [
    'name',
    'tel',
    'active',
    'related_users_id',
    'office_id',
    'user_id',
    'dup',
    'weekdup',
    'top',
  ];

  protected $hidden = [
    'password',
    'remember_token'
  ];
}
