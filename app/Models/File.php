<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
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
}
