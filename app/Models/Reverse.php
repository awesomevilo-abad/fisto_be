<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reverse extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'transaction_id',
        'tag_id',
        'user_role',
        'user_id',
        'user_name',
        'status',
        'date_status',
        'reason_id',
        'remarks',
        'distributed_id',
        'distributed_name'
    ];
}
