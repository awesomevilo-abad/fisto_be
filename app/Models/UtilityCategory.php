<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;

class UtilityCategory extends Model
{
  use HasFactory;
  use SoftDeletes;

  protected $table = 'utility_categories';
  protected $fillable = ['category'];
  protected $hidden = ['pivot','created_at'];

  public function getCreatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }
  public function getUpdatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }
}
