<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeInfo extends Model
{
    use HasFactory;

    protected $fillable = [

        'cheque_number',
        'bank_id',
        'status',
        'date_status',
        'reason_id',
        'remarks'

    ];
}
