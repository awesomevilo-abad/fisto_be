<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';
    protected $fillable = ['supplier_code', 'supplier_name', 'terms', 'supplier_type_id'];

    public function supplier_type()
    {
        return $this->hasOne(SupplierType::class);
    }
}
