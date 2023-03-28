<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitBatch extends Model
{
    use HasFactory;

    protected $fillable = [
       "request_id"
        ,"pn_no"
        ,"interest_from"
        ,"interest_to"
        ,"outstanding_amount"
        ,"interest_rate"
        ,"no_of_days"
        ,"principal_amount"
        ,"interest_due"
        ,"cwt"
        ,"dst"
    ];
}
