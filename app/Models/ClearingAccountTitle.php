<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearingAccountTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        "clear_id",
        "entry",
        "account_title_id",
        "account_title_name",
        "amount",
        "remarks",
        "transaction_type"
    ];
}
