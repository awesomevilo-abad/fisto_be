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
        'due_date',
        'cheque_amount',
        'date_released',
        'date_prepared',
        'date_cleared',
        'reason_id',
        'remarks'

    ];
}
