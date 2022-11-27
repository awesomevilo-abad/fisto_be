<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;

class UtilityLocation extends Model
{
  use HasFactory;
  use SoftDeletes;

  protected $table = 'utility_locations';
  protected $dates = ['deleted_at'];
  protected $fillable = ['location'];
  protected $hidden = ['pivot','created_at'];

  public function getCreatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }
  public function getUpdatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }
  public function account_numbers()
  {
      return $this->belongsTo(AccountNumber::class);
  }

  
  public function document_categories()
  {
      return $this->belongsToMany(Category::class, 'user_document_category','document_id','category_id')->select('categories.id as category_id','categories.name as category_name');
  }

  public function credit_cards()
  {
    return $this->belongsToMany(CreditCard::class, 'credit_card_utility_locations','utility_location_id','credit_card_id');
  }
}
