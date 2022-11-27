<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditCard extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name','account_no'];
    protected $hidden = ['pivot','created_at'];

    public function utility_categories()
    {
        return $this->belongsToMany(UtilityCategory::class,'credit_card_utility_categories','credit_card_id','utility_category_id')->select('utility_categories.id','category');
    }

    public function utility_locations()
    {
        return $this->belongsToMany(UtilityLocation::class,'credit_card_utility_locations','credit_card_id','utility_location_id')->select('utility_locations.id','location');
    }
}
