<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class POBatch extends Model
{
    use HasFactory;

    protected $table = 'p_o_batches';

    protected $fillable = [
        'request_id',
        'is_add',
        'is_editable',
        'previous_balance',
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


    public function transaction_ids()
    {
      return $this->belongsTo(Transaction::class,'request_id', 'request_id')
      ->where('transactions.payment_type','=','partial')
      ->where('transactions.state','!=','void')
      ->select(['transactions.id','request_id'])
      ->orderByDesc('transactions.updated_at');
    }
}
