<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        "process"
        ,"transaction_id"
        ,"tag_id"
        ,"from_distributed_id"
        ,"from_distributed_name"
        ,"to_distributed_id"
        ,"to_distributed_name"
    ];
}
