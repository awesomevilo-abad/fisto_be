<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id','document_id'];

    
    public function document_categories()
    {
        return $this->belongsToMany(Category::class, 'user_document_category','document_id','category_id')->select('categories.id as category_id','categories.name as category_name');
    }
}
