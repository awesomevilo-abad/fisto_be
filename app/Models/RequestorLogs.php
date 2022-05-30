<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestorLogs extends Model
{
    use HasFactory;

    protected $table = 'requestor_logs';
    protected $fillable = ['transaction_id','transaction_no','description','status','date_status','user_id','reason_id','reason_description','reason_remarks'];
}
