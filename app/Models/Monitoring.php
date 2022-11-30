<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitoring extends Model
{
    use HasFactory;

    protected $table ="monitoring";

    protected $fillable = [
        'counter_receipt_id',
        'date_countered',
        'date_transaction',
        'counter_receipt_no',
        'receipt_type',
        'receipt_no',
        'supplier_id',
        'supplier',
        'department_id',
        'department',
        'amount',
        'status',
        'state',
        'receiver',
        'remarks',
    ];
}
