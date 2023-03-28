<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sedar extends Model
{
    use HasFactory;
    protected $connection = 'genus';
    protected $table = 'account';

    // public function orders(){
    //    return DB::connection('genus')->select('select * from `order`');
    // }
}
