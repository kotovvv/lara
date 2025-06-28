<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Status;

class Lid extends Model
{
  use HasFactory;
  protected $fillable = [
    'name',
    'tel',
    'email',
    'afilyator',
    'status_id',
    'provider_id',
    'user_id',
    'text',
    'active',
    'office_id',
    'address',
    'qtytel',
    'rd'
  ];


  public function status()
  {
    return $this->belongsTo(Status::class, 'status_id');
  }
}
