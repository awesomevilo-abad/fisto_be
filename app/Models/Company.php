<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'companies';
    protected $fillable = ['code', 'company'];
    protected $hidden = ['created_at','pivot'];

    public function getCreatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }
    
    public function getUpdatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }

    public function associates()
    {
       return $this->belongsToMany(User::class,'company_users','company_id','user_id')->select(['users.id',DB::raw("CONCAT(users.first_name,' ',users.last_name)  AS name")]);
    }

    public function charging()
    { return $this->belongsToMany(Department::class,'departments','company','id')->select();
    }

    public function location()
    {

    }

    public function departments(){
        return $this->hasMany(Department::class,'company','id') 
        ->select('id','code','department as name','company')
        ->with('locations');
    }
    
}
