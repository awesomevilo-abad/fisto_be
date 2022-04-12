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
  protected $hidden = ['location_id', 'category_id','supplier_id','created_at'];
  public function getCreatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }
  public function getUpdatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }

  public function location()
  {
    return $this->hasOne(UtilityLocation::class,'id', 'location_id')->select(['id','location']);
  }
  public function category()
  {
    return $this->hasOne(UtilityCategory::class,'id', 'category_id')->select(['id','category']);
  }
  public function supplier()
  {
    return $this->hasOne(Supplier::class,'id', 'supplier_id')->select(['id','name']);
  }
}
