<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionClient extends Model
{
    use HasFactory;

    protected $table = 'transaction_client';

    protected $fillable = [
        'request_id',
        'client_id',
        'client_name'
    ];
}
