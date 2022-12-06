<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        "date_countered",
        "date_transaction",
        "counter_receipt_no",
        "receipt_type_id",
        "receipt_type",
        "receipt_no",
        "supplier_id",
        "supplier",
        "department_id",
        "department",
        "amount",
        "status",
        "state",
        "receiver",
        "remarks",
        "counter_receipt_status"
    ];
    
}
