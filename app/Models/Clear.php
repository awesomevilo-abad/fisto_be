<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clear extends Model
{
    use HasFactory;
    
    protected $fillable = [
        "tag_id",
        "date_received",
        "status",
        "date_status",
        "date_cleared"
    ];

    public function account_title(){
        return $this->hasMany(ClearingAccountTitle::class,'clear_id','id');
    }
}
