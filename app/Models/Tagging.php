<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagging extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'description',
        'status',
        'date_status',
        'reason_id',
        'remarks',
        'distributed_id',
        'distributed_name'

    ];

    public function voucher(){
        return $this->hasMany(Associate::class,'transaction_id','transaction_id')->select('transaction_id','tag_id','id',
        'receipt_type','percentage_tax','witholding_tax','net_amount','approver_id','approver_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }

    public function cheque(){
        return $this->hasMany(Treasury::class,'transaction_id','transaction_id')->select('transaction_id','tag_id','id',
        'date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }

    public function approve(){
        return $this->hasMany(Approver::class,'transaction_id','transaction_id')->select('transaction_id','tag_id','id',
        'distributed_id','distributed_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }
    
    public function transmit(){
        return $this->hasMany(Transmit::class,'transaction_id','transaction_id')->select('transaction_id','tag_id','id',
        'date_status as date','status')->latest()->limit(1);
    }
    
    public function release(){
        return $this->hasMany(Release::class,'transaction_id','transaction_id')->select('transaction_id','id',
        'distributed_id','distributed_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }

    public function file(){
        return $this->hasMany(File::class,'transaction_id','transaction_id')->select('transaction_id','tag_id','id',
        'receipt_type','percentage_tax','witholding_tax','net_amount','approver_id','approver_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }
}
