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
        $rows =  (empty($request['rows']))?10:$request['rows'];
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
            $code = 403;
            $message = "Category already registered.";
            $data = [];
        } elseif ($duplicate_category_inactive->count()) {
            $code = 403;
            $message = "Category already registered but inactive.";
            $data = [];
        }else{
            $new_category = Category::create([
                'name' => $fields['name']
            ]);

            $code = 200;
            $message = "New category has been saved.";
            $data =$new_category;
        }
        return $this->result($code,$message,$data);
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
            $code = 404;
            $data = [];
            $message = "Data Not Found!";
        } else {
            $specific_category->name = $request->get('name');
            $specific_category->save();
            $code = 200;
            $data = $specific_category;
            $message = "Succefully Updated";
        }
        return $this->result($code,$message,$data);
    }

    public function change_status(Request $request,$id){
        $status = $request['status'];
        $model = new Category();
        return $this->change_masterlist_status($status,$model,$id);
    }

}
