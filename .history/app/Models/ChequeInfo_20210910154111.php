<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeInfo extends Model
{
    use HasFactory;

    protected $fillable = [

        'cheque_bn',
        'date_received',
        'status',
        'date_status',
        'reason_id',
        'remarks'

    ];
}
