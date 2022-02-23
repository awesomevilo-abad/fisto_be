<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gas extends Model
{
    use HasFactory;

    protected $fillable = [
        "tag_id"=>$transaction_id,
        "receipt_type"=>$receipt_type,
        "date_received"=>$date_received,
        "status"=>$status,
        "date_status"=>$date_status,
        "witholding_tax"=>$date_status,
        "percentage_tax"=>$date_status,
        "reason_id"=>$reason_id,
        "remarks"=>$remarks
    ]
}
