<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referrence extends Model
{
    use HasFactory;

    protected $table = 'referrences';
    protected $fillable = ['referrence_type', 'referrence_description', 'is_active'];

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_referrences');
    }
}
