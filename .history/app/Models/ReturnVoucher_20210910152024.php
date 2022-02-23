<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'date_received',
        'distributed_to',
        'status',
        'date_status',
        'reason_id',
        'remarks'

    ];
}
