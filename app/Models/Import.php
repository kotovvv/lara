<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
  use HasFactory;
  protected $fillable = [
    'start',
    'end',
    'provider_id',
    'user_id',
    'message',
  ];
  public function provider()
  {
    return $this->belongsTo(Provider::class, 'provider_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
