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
    protected $fillable = ['referrence_type', 'referrence_description', 'is_active'];
    protected $attributes = ['is_active' => 1];
    protected $hidden = ['pivot'];
    

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
