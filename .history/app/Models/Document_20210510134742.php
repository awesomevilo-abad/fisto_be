<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'document_type', 'document_description', 'is_active',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'document_categories');
    }
}
