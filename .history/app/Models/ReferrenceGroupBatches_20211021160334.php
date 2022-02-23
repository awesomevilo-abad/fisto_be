<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferrenceGroupBatches extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'referrence_no',
        'referrence_total_amount'
    ];
}
