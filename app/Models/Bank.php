<?php

namespace App\Models;

use App\Models\AccountTitle;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;

class Bank extends Model
{
  use HasFactory;
  use SoftDeletes;

  protected $table = 'banks';
  protected $fillable = ['code', 'name', 'branch', 'account_no', 'location', 'account_title_1', 'account_title_2'];
  protected $hidden = ['account_title_1','account_title_2','created_at'];
 
  public function getCreatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }

  public function getUpdatedAtAttribute($value){
    $date = Carbon::parse($value);
    return $date->format('Y-m-d H:i');
  }

  public function AccountTitleOne() {
    return $this->hasOne(AccountTitle::class, 'id', 'account_title_1')->select('id','title as name');
  }
  
  public function AccountTitleTwo() {
    return $this->hasOne(AccountTitle::class, 'id', 'account_title_2')->select('id','title as name');
  }
  
}
