<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filing extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'distributed_to',
        'date_received',
        'status',
        'date_status',
        'reason_id',
        'remarks'

    ];
}
