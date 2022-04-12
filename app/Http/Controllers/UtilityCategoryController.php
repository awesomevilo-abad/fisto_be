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
      $rows =  (empty($request['rows']))?10:(int)$request['rows'];
      $search =  $request['search'];
      $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
      
      $utility_categories = UtilityCategory::withTrashed()
      ->where(function ($query) use ($status){
        ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })
      ->where('category', 'like', '%'.$search.'%')
      ->latest('updated_at');
      
    if ($paginate == 1){
      $utility_categories = $utility_categories
      ->paginate($rows);
    }else if ($paginate == 0){
      $utility_categories = $utility_categories
      ->get(['id','category as name']);
      if(count($utility_categories)==true){
          $utility_categories = array("utility_categories"=>$utility_categories);;
      }
    }

      if(count($utility_categories)==true){
        return $this->resultResponse('fetch','Utility Category',$utility_categories);
      }
      return $this->resultResponse('not-found','Utility Category',[]);
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
        return $this->resultResponse('save','Utility Category',$new_utility_category);
      }
      else {
        return $this->resultResponse('registered','Utility Category',[]);
      }
    }
    public function update(Request $request,$id)
    {
      $fields = $request->validate([
        'category' => ['required', 'string']
      ]);
      $specific_utility_category = UtilityCategory::find($id);
      if (!$specific_utility_category) {
        return $this->resultResponse('not-found','Utility Category',[]);
      }
      else {
        $utility_category_validateDuplicate = DB::table('utility_categories')
          ->where('id', '!=', $id)
          ->where('category', '=', $fields['category'])
          ->get();
        if (count($utility_category_validateDuplicate) > 0) {
          return $this->resultResponse('registered','Utility Category',[]);
        }
        else {
          $specific_utility_category->category = $request->get('category');
          return $this->validateIfNothingChangeThenSave($specific_utility_category,'Utility Category');
        }
      }
    }
    public function change_status(Request $request,$id){
      $status = $request['status'];
      $model = new UtilityCategory();
      return $this->change_masterlist_status($status,$model,$id,'Utility Category');
    }
}
