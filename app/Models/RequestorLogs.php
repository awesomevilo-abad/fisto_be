<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestorLogs extends Model
{
    use HasFactory;

    protected $table = 'requestor_logs';
    protected $fillable = ['transaction_id','transaction_no','description','status','date_status','user_id'
    ,'reason_id','reason_description','reason_remarks','changes'];


    protected $casts = [
      "changes"=>'array'
    ];
    

    public function transaction()
    {
      return $this->hasOne(Transaction::class,'id', 'transaction_id')
      ->select([
          'users_id','first_name','middle_name','last_name','suffix',
          'id','transaction_id as transaction_no'
          ,'remarks as description','reason_id'
          ,'reason','reason_remarks']);
    }
  }
