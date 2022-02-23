<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagging extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'description',
        'date_received',
        'status',
        'date_status',
        'reason_id',
        'remarks'

    ];
}
