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
    protected $hidden = ['created_at'];

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
       return $this->belongsToMany(User::class,'company_users','company_id','user_id')->select(['users.id',DB::raw("CONCAT(users.first_name,' ',users.last_name)  AS fullname")]);
    }

    public function department()
    {
        
    }

    public function location()
    {

    }
    
}
