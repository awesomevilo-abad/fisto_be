<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitoring extends Model
{
    use HasFactory;

    protected $table ="monitoring";

    protected $fillable = [
        'counter_id',
        'receipt_no',
        'status',
        'state',
        'receiver',
        'notice_count',
        'latest_notice',
        'reason_id',
        'reason',
        'reason_remarks'
    ];
}
