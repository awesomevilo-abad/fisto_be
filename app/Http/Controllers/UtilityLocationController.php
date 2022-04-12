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
    
    $utility_locations= UtilityLocation::withTrashed()
    ->where(function ($query) use ($status){
      ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('location', 'like', '%'.$search.'%')
    ->latest('updated_at');
    
    if ($paginate == 1){
      $utility_locations = $utility_locations
      ->paginate($rows);
    }else if ($paginate == 0){
        $utility_locations = $utility_locations
        ->get(['id','location as name']);
        $utility_locations = array("utility_locations"=>$utility_locations);
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