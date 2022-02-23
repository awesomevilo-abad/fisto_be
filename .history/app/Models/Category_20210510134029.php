<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',''
    ]

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_categories');
    }
}
