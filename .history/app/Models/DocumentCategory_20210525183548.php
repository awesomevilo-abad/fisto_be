<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    use HasFactory;

    protected $table = 'document_categories';
    
    protected $fillable = [
        'document_type', 'document_description', 'categories', 'is_active',
    ];
}
