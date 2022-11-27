<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    protected $table ='cheques';

    protected $fillable = [
        'transaction_id',
        'treasury_id',
        'bank_id',
        'bank_name',
        'cheque_no',
        'cheque_date',
        'cheque_amount',
        'transaction_type',
        'entry_type'
    ];
}
