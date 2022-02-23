<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierType extends Model
{
    use HasFactory;

    protected $table = 'supplier_types';
    protected $fillable = ['type','transaction_days','is_active'];

    public function supplier()
}
