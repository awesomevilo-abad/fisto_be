<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\UtilityCategory;
use Illuminate\Http\Request;
use App\Methods\GenericMethod;
use Illuminate\Support\Facades\DB;

class UtilityCategoryController extends Controller
{
    public function index(Request $request)
    {
      $status =  $request['status'];
      $rows =  (empty($request['rows']))?10:$request['rows'];
      $search =  $request['search'];
      
      $utility_categories = UtilityCategory::withTrashed()
      ->where(function ($query) use ($status){
        ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })
      ->where('category', 'like', '%'.$search.'%')
      ->latest('updated_at')
      ->paginate($rows);

      if(count($utility_categories) == true)
        return $this->result(200,'Utility categories has been fetched.',$utility_categories);
      else
        throw new FistoException("No records found.",404,NULL,[]);
    }
    public function show($id)
    {
      $result = UtilityCategory::find($id);
      if (!empty($result)) {
        return $this->result(200,"Utility category has been fetched",$result);
      }
      else {
        throw new FistoException("No records found.",404,NULL,[]);
      }
    }
    public function store(Request $request)
    {
      $fields = $request->validate([
        'category' => 'required|string'
      ]);

      $utility_category_validateDuplicate = DB::table('utility_categories')
        ->where('category', $fields['category'])
        ->get();
      
      if (!count($utility_category_validateDuplicate) > 0) {
        $new_utility_category = UtilityCategory::create([
          'category' => $fields['category']
        ]);
        return $this->result(201,"New utility category has been saved.",$new_utility_category);
      }
      else {
        throw new FistoException("Utility category already registered.",409,NULL,[]);
      }
    }
    public function update(Request $request,$id)
    {
      $fields = $request->validate([
        'category' => ['required', 'string']
      ]);
      $specific_utility_category = UtilityCategory::find($id);
      if (!$specific_utility_category) {
        throw new FistoException("No records found.",404,NULL,[]);
      }
      else {
        $utility_category_validateDuplicate = DB::table('utility_categories')
          ->where('id', '!=', $id)
          ->where('category', '=', $fields['category'])
          ->get();
        if (count($utility_category_validateDuplicate) > 0) {
          throw new FistoException("Utility category already registered.",403,NULL,[]);
        }
        else {
          $specific_utility_category->category = $request->get('category');
          $specific_utility_category->save();
          return $this->result(200,"Utility category has been updated.",$specific_utility_category);
        }
      }
    }
    public function change_status(Request $request,$id){
      $status = $request['status'];
      $model = new UtilityCategory();
      return $this->change_masterlist_status($status,$model,$id);
    }
}
