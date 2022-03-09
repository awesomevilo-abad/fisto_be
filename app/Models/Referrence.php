<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;


class Referrence extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'referrences';
    protected $fillable = ['type', 'description'];
    protected $hidden = ['pivot','created_at'];

    public function getCreatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }
    public function getUpdatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_referrences');
    }
}
