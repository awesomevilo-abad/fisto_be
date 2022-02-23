<?php

namespace App\Http\Controllers;

use App\Models\UtilityLocation;
use Illuminate\Http\Request;
use App\Methods\GenericMethod;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FistoException;

class UtilityLocationController extends Controller
{
  public function index(Request $request,bool $status,int $rows)
  {
    $utility_location = UtilityLocation::where(function ($query) use ($status){
      ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->select(['id', 'location', 'updated_at', 'deleted_at'])
    ->latest()
    ->paginate($rows);

    if (count($utility_location)>0)
    {
      return $this->result(200,"Utility locations has been fetched.",$utility_location);
    }
    else
    {
      throw new FistoException("No records found.",404,NULL,[]);
    }
  }
  public function all(Request $request,$status)
  {

    if ($status == 1) {
      $utility_location = DB::table('utility_locations')
        ->select(['id','location'])
        ->whereNull('deleted_at')
        ->latest()
        ->get();

    }
    
    if ($status == 0) {
      $utility_location = DB::table('utility_locations')
        ->select(['id','location'])
        ->whereNotNull('deleted_at')
        ->latest()
        ->get();

    }

    $code = 200;
    $message = "Succefully Retrieved";
    $data = $utility_location;

    if (!$utility_location || $utility_location->isEmpty()) {
      $code = 404;
      $message = "Data Not Found!";
      $data = $utility_location;
    }

    return $this->result($code,$message,$data);
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
  public function search(Request $request,bool $status,int $rows)
  {
    $value = $request['value'];
    $utility_location = UtilityLocation::where(function ($query) use ($status){
      ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('location', 'like', '%'.$value.'%')
    ->select(['id', 'location', 'updated_at', 'deleted_at'])
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($utility_location) == true)
      return $this->result(200,'Utility locations has been fetched.',$utility_location);
    else
      throw new FistoException("No records found.",404,NULL,[]);
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
        $specific_utility_location->save();
        return $this->result(200,"Utility location has been updated.",$specific_utility_location);
      }
    }
  }
  public function archive(Request $request,$id)
  {
    $softDeleteUtilityLocation = UtilityLocation::where('id', $id)->delete();
    if ($softDeleteUtilityLocation == true) {
      return $this->result(200,"Utility location has been archived.",[]);
    }
    else {
      throw new FistoException("No records found.",404,NULL,[]);
    }
  }
  public function restore(Request $request,$id)
  {
    $softRestoreSoftDelete = UtilityLocation::onlyTrashed()->where('id', $id)->restore();
    if ($softRestoreSoftDelete == true) {
      return $this->result(200,"Utility location has been restored.",[]);
    }
    else {
      throw new FistoException("No records found.",404,NULL,[]);
    }
  }
}