<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    protected $table = 'category';

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'category_document');
    }
}
