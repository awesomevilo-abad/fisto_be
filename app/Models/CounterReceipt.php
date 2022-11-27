<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "date_countered",
        "date_transaction",
        "counter_receipt_no",
        "receipt_type",
        "receipt_no",
        "supplier_id",
        "supplier",
        "department_id",
        "department",
        "amount",
        "status",
        "receiver",
    ];
}
