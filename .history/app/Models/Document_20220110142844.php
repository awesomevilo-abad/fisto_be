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
        return $this->belongsToMany(Category::class, 'document_categories');
    }
}
