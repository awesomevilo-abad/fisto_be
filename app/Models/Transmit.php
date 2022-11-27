<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transmit extends Model
{
    use HasFactory;

    
    protected $table = 'transmit';
    
    protected $fillable = [
        'transaction_id',
        'tag_id',
        'date_received',
        'status',
        'date_status',
        'transaction_type'
    ];
}
