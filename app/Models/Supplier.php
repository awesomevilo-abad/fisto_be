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
  protected $fillable = ['code', 'name', 'terms', 'supplier_type_id', 'referrences'];
  protected $hidden = ['pivot','supplier_type_id','created_at'];
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

  public function references()
  {
    return $this->belongsToMany(Referrence::class, 'supplier_referrences', 'supplier_id', 'referrence_id')->select(['referrences.id','referrences.type']);
  }

  public function supplier_type()
  {
    return $this->hasOne(SupplierType::class,'id', 'supplier_type_id')->select(['id','type']);
  }
}
