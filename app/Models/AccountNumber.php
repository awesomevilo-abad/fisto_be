<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;

class AccountNumber extends Model
{
  use HasFactory;
  use SoftDeletes;

  protected $table = 'account_numbers';
  protected $fillable = ['account_no', 'location_id', 'category_id', 'supplier_id'];

  public function getCreatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }
  public function getUpdatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }

  public function locations()
  {
    $this->belongsTo(UtilityLocation::class);
  }
}
