<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferrenceBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrence_type',
        'referrence_no',
        'referrence_no',
        'referrence_amount',
        'referrence_qty'

    ];
}
