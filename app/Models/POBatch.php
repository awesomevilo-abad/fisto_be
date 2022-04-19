<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POBatch extends Model
{
    use HasFactory;

    protected $table = 'p_o_batches';

    protected $fillable = [
        'request_id',
        'po_no',
        'po_amount',
        'po_qty',
        'rr_code',
        'po_group_no',
        'rr_group',
        'po_total_amount'
    ];

    protected $casts = [
        'rr_group' => 'array'
    ];

}
