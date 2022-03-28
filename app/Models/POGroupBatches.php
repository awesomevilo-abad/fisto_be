<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POGroupBatches extends Model
{
    use HasFactory;
    protected $table = 'p_o_group_batches';


    protected $fillable = [
        'request_id',
        'po_no',
        'po_total_amount'
    ];
}
