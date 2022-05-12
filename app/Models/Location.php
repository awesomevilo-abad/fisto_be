<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Location extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'locations';
    protected $fillable = ['code','location','departments','created_at','updated_at','deleted_at'];
    protected $hidden = ['pivot','created_at'];
    protected $cast = [
        "department" => 'array',
      ];

    public function getCreatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }
    
    public function getUpdatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }

    public function departments(){
        return $this->belongsToMany(Department::class,'location_departments')->select('departments.id','departments.department as name');
    }
}
 