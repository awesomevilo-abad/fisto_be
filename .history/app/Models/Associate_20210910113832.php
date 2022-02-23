<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Associate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'date_received',
        'month_in',
        'voucher_no',
        'status',
        'date_status',
        'reason_id',
        'remarks'

    ];
}
