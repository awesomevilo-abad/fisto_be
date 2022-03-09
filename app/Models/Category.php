<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $date = ['deleted_at'];
    protected $table = "categories";
    protected $fillable = ['name'];
    protected $hidden = ['pivot','created_at'];
    public function getCreatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }
    public function getUpdatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }
    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_categories')->select('categories.id');
    }
}
