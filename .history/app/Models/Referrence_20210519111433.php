<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referrence extends Model
{
    use HasFactory;

    protected $table = 'referrences';
    protected $fillable = ['referrence_type', 'referrence_description', 'document_id', 'is_active'];

    public function supliers(){
        return 
    }
}
