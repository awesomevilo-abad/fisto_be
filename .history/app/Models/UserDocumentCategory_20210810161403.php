<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocumentCategory extends Model
{
    use HasFactory;
    protected $table = 'user_document_categories';

    protected $fillable = ['user_id','category_id','document_id'];
}
