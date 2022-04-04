<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Exceptions\FistoException;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $status =  $request['status'];
        $rows =  (empty($request['rows']))?10:(int)$request['rows'];
        $search =  $request['search'];
        $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
        
        $categories = Category::withTrashed()
        ->where(function ($query) use ($status){
          return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($search){
            return (isset($search))?$query->where('name', 'like', '%' . $search . '%'):$query;
        })
        ->latest('updated_at');
        
         if ($paginate == 1){
        $categories = $categories
        ->paginate($rows);
        }else if ($paginate == 0){
          $categories = $categories->get(['id','name']);
        }
        
        if(count($categories)==true){
            return $this->resultResponse('fetch','Category',$categories);
        }
        return $this->resultResponse('not-found','Category',[]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string'
        ]);

        $duplicate_category = DB::table('categories')
            ->where('name', $fields['name'])
            ->whereNull('deleted_at')
            ->get();

        $duplicate_category_inactive = DB::table('categories')
            ->where('name', $fields['name'])
            ->whereNotNull('deleted_at')
            ->get();

        if ($duplicate_category->count()) {
            return $this->resultResponse('registered','Category',[]);
        } elseif ($duplicate_category_inactive->count()) {
            return $this->resultResponse('registered-inactive','Category',[]);
        }else{
            $new_category = Category::create([
                'name' => $fields['name']
            ]);
            return $this->resultResponse('save','Category',$new_category);
        }
    }

    public function update(Request $request, $id)
    {
        $specific_category = Category::find($id);

        $fields = $request->validate([
            'name' => ['required'],
        ]);
        

        if (!$specific_category) {
            return $this->resultResponse('not-found','Category',[]);
        } else {
            
            $model = new Category();
            $this->isUnique($model,'Category',['name'],[$fields['name']],$id);
            
            $specific_category->name = $request->get('name');
            return $this->validateIfNothingChangeThenSave($specific_category,'Category');
        }
    }

    public function change_status(Request $request,$id){
        $status = $request['status'];
        $model = new Category();
        return $this->change_masterlist_status($status,$model,$id,'Category');
    }

}
