<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Exceptions\FistoException;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $status =  $request['status'];
        $rows =  (empty($request['rows']))?10:(int)$request['rows'];
        $search =  $request['search'];
        
        $categories = Category::withTrashed()
        ->where(function ($query) use ($status){
          return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($search){
            return (isset($search))?$query->where('name', 'like', '%' . $search . '%'):$query;
        })
        ->latest('updated_at')
        ->paginate($rows);
        
        if(count($categories)==true){
          return $this->result(200,"Category has been fetched.",$categories);
        }
        throw new FistoException("No records found.", 404, NULL, []);
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
            throw new FistoException("Category already registered.", 409, NULL, []);
        } elseif ($duplicate_category_inactive->count()) {
            throw new FistoException("Category already registered but inactive.", 409, NULL, []);
        }else{
            $new_category = Category::create([
                'name' => $fields['name']
            ]);
            return $this->result(201,"New category has been saved.",$new_category);
        }
    }

    public function show($id)
    {
        $result = Category::find($id);
        if (!$result) {
            $code = 404;
            $message = "Data Not Found";
            $data = [];
        }else{
            $code = 200;
            $message = "Succefully Retrieved";
            $data = $result;
        }

        return $this->result($code,$message,$data);
    }

    public function update(Request $request, $id)
    {
        $specific_category = Category::find($id);

        $fields = $request->validate([
            'name' => ['unique:categories,name,' . $id],
        ]);

        if (!$specific_category) {
            throw new FistoException("No records found.", 404, NULL, []);
        } else {
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
