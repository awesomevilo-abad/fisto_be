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
    
    $utility_locations= UtilityLocation::withTrashed()
    ->where(function ($query) use ($status){
      ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('location', 'like', '%'.$search.'%')
    ->latest('updated_at')
    ->paginate($rows);

    if(count($utility_locations) == true)
      return $this->result(200,'Utility locations has been fetched.',$utility_locations);
    else
      throw new FistoException("No records found.",404,NULL,[]);
  }
  public function show($id)
  {
    $result = UtilityLocation::find($id);
    if (!$result) {
      throw new FistoException("No records found.",404,NULL,[]);
    }
    else {
      return $this->result(200,"Utility locations has been fetched.",$result);
    }
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
      throw new FistoException("Utility location already registered.",409,NULL,[]);
    }
    else {
      $new_utility_location = UtilityLocation::create([
        'location' => $fields['location']
      ]);
      return $this->result(200,"New utility location has been saved.",$new_utility_location);
    }
  }
  public function update(Request $request,$id)
  {
    $fields = $request->validate([
      'location' => 'required|string'
    ]);

    $specific_utility_location = UtilityLocation::find($id);
    if (!$specific_utility_location) {
      throw new FistoException("No records found.",404,NULL,[]);
    }
    else {
      $utility_location_validateDuplicate = DB::table('utility_locations')
        ->where('id', '!=', $id)
        ->where('location', '=', $fields['location'])
        ->get();

      if (count($utility_location_validateDuplicate) > 0) {
        throw new FistoException("Utility location already registered.",409,NULL,[]);
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