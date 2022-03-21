<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Document extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'type', 'description', 'categories',
    ];
    protected $hidden = ['pivot','created_at'];
    protected $casts = [
        'categories' => 'array',
    ];

    public function getCreatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }
    public function getUpdatedAtAttribute($value){
        $date = Carbon::parse($value);
        return $date->format('Y-m-d H:i');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'document_categories','document_id','category_id')->select('categories.id','categories.name');
    }

    public function document_categories()
    {
        return $this->belongsToMany(Category::class, 'user_document_category','document_id','category_id')->select('categories.id','categories.name');
    }
}
