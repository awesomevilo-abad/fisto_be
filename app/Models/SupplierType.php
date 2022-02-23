<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SupplierType extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'supplier_types';
    protected $fillable = ['type', 'transaction_days'];
    protected $attributes = ['is_active'=>1];

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class);
    }
}
