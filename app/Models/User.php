<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    use SoftDeletes;
    use LogsActivity;

   
    protected $fillable = [
        'id_prefix'
        , 'id_no'
        , 'role'
        , 'first_name'
        , 'middle_name'
        , 'last_name'
        , 'suffix'
        , 'department'
        , 'position'
        , 'permissions'
        , 'document_types'
        , 'username'
        , 'password'
    ];

    protected $hidden = [
        'password', 'remember_token','pivot','created_at'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions' => 'array',
        'document_types' => 'array',
        'department' => 'array',
    ];

    protected static $logAttributes = ['id_no', 'role','first_name', 'middle_name','last_name', 'suffix','department', 'position', 'permissions','document_types', 'username','password'];
  
    protected static $logName = 'User';

    protected static $logOnlyDirty = true;
    
    public function setUsernameAttribute($value)
    {
        $this->attributes['username']=strtolower($value);
    }


    public function documents()
    {
       return $this->belongsToMany(Document::class,'user_documents','user_id','document_id')->select('documents.id','documents.id as document_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class,'user_permission','user_id','permission_id');
    }
    public function getDescriptionForEvent(string $eventName): string
    {
        return "User has been {$eventName}.";
    }

    public function companies(){
        return $this->belongsToMany(Company::class,'company_users','user_id','company_id')->select('companies.id','companies.company as name');
    }

}
