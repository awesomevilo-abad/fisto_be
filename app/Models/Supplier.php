<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Supplier extends Model
{
  use HasFactory;
  use SoftDeletes;
  
  protected $table = 'suppliers';
  protected $fillable = ['supplier_code', 'supplier_name', 'terms', 'supplier_type_id', 'referrences'];
  protected $attributes = ['is_active' => 1];
  protected $hidden = ['pivot','is_active','supplier_type_id'];
  protected $cast = [
    "referrences" => 'array',
  ];


  public function getCreatedAtAttribute($value){
      $date = Carbon::parse($value);
      return $date->format('Y-m-d H:i');
  }

  public function getUpdatedAtAttribute($value){
      $date = Carbon::parse($value);
      return $date->format('Y-m-d H:i');
  }

  public function referrences()
  {
    return $this->belongsToMany(Referrence::class, 'supplier_referrences', 'supplier_id', 'referrence_id')->select(['referrences.id','referrences.referrence_type as type']);;
  }

  public function supplier_types()
  {
    return $this->hasOne(SupplierType::class,'id', 'supplier_type_id')->select(['id','type']);
  }
}
