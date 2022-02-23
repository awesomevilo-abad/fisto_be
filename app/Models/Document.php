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
        'document_type', 'document_description', 'categories', 'is_active',
    ];
    protected $hidden = ['pivot'];
    protected $attributes = ['is_active' => 1];
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
        return $this->belongsToMany(Category::class, 'document_categories','document_id','category_id');
    }

    public function document_categories()
    {
        return $this->belongsToMany(Category::class, 'user_document_category','document_id','category_id')->select('categories.id as category_id','categories.name as category_name');
    }
}
