<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Associate extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'tag_id',
        'date_received',
        'receipt_type',
        'percentage_tax',
        'witholding_tax',
        'net_amount',
        'approver_id',
        'approver_name',
        'status',
        'date_status',
        'reason_id',
        'remarks'
    ];

    public function account_title(){
        return $this->hasMany(VoucherAccountTitle::class,'associate_id','id')->select('id','associate_id','entry',
        'account_title_id','account_title_name','amount','remarks');
    }

}
