<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RRBatch extends Model
{
    use HasFactory;

    protected $table = ''

    protected $fillable = [
        'po_batch_no',
        'rr_code'
    ];
}
