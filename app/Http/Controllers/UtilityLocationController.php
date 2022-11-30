<?php

namespace App\Http\Controllers;

use App\Models\UtilityLocation;
use Illuminate\Http\Request;
use App\Methods\GenericMethod;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FistoException;

class UtilityLocationController extends Controller
{
  public function index(Request $request)
  {

    $status =  $request['status'];
    $rows =  (empty($request['rows']))?10:(int)$request['rows'];
    $search =  $request['search'];
    $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
    $category = (isset($request['category']))? $request['category']:NULL;

   $utility_locations = DB::table('utility_locations')
   ->when($status, function ($query){
      $query->whereNull('deleted_at');
   }, function ($query){
      $query->whereNotNull('deleted_at');
   })
   ->where('location','like','%'.$search.'%');

   if($paginate == 0){
     $utility_Locations_ids = DB::table('credit_card_utility_locations')
      ->selectRaw("utility_location_id")
      ->leftJoin('credit_card_utility_categories','credit_card_utility_locations.credit_card_id','=','credit_card_utility_categories.credit_card_id')
      ->leftJoin('credit_cards','credit_card_utility_locations.credit_card_id','=','credit_cards.id')
      ->whereNull('credit_cards.deleted_at')
      ->where("credit_card_utility_categories.utility_category_id","=",$category)->pluck('utility_location_id');

     $utility_locations = DB::table('utility_locations')
     ->when($status, function ($query){
        $query->whereNull('utility_locations.deleted_at');
     }, function ($query){
        $query->whereNotNull('utility_locations.deleted_at');
     })
     ->where('location','like','%'.$search.'%')
     ->leftJoin('credit_card_utility_locations','utility_locations.id','=','credit_card_utility_locations.utility_location_id')
     ->leftJoin('credit_card_utility_categories','credit_card_utility_locations.credit_card_id','=','credit_card_utility_categories.credit_card_id')
     ->leftJoin('utility_categories','credit_card_utility_categories.utility_category_id','=','utility_categories.id')
     ->leftJoin('credit_cards','credit_card_utility_locations.credit_card_id','=','credit_cards.id')
     ->whereNotIn('utility_locations.id',$utility_Locations_ids)
     ->groupBy('utility_locations.id','utility_locations.location')
     ->get(['utility_locations.id','utility_locations.location as name']);
     
     $utility_locations = array("utility_locations"=>$utility_locations);
   }else{
    $utility_locations = $utility_locations->latest()
    ->paginate($rows);
   }
    
    if(count($utility_locations)==true){
      return $this->resultResponse('fetch','Utility Location',$utility_locations);
    }
    return $this->resultResponse('not-found','Utility Location',[]);
  }

  public function store(Request $request)
  {
    $fields = $request->validate([
      'location' => 'required|string'
    ]);

    $utility_location_validateDuplicate = DB::table('utility_locations')
      ->where('location', $fields['location'])
      ->get();
    
    if (count($utility_location_validateDuplicate) > 0) {
      return $this->resultResponse('registered','Utility Location',[]);
    }
    else {
      $new_utility_location = UtilityLocation::create([
        'location' => $fields['location']
      ]);
      return $this->resultResponse('save','Utility Location',$new_utility_location);
    }
  }

  public function update(Request $request,$id)
  {
    $fields = $request->validate([
      'location' => 'required|string'
    ]);

    $specific_utility_location = UtilityLocation::find($id);
    if (!$specific_utility_location) {
      return $this->resultResponse('not-found','Utility Location',[]);
    }
    else {
      $utility_location_validateDuplicate = DB::table('utility_locations')
        ->where('id', '!=', $id)
        ->where('location', '=', $fields['location'])
        ->get();

      if (count($utility_location_validateDuplicate) > 0) {
        return $this->resultResponse('registered','Utility Location',[]);
      }
      else {
        $specific_utility_location->location = $request->get('location');
        return $this->validateIfNothingChangeThenSave($specific_utility_location,'Utility Location');
      }
    }
  }
  public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new UtilityLocation();
    return $this->change_masterlist_status($status,$model,$id,'Utility Location');
  }
}