<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request,$status,$tableRows)
    {
        $tableRows = (int)$tableRows;
        $is_active = $status;

        if ($is_active == 1) {
            $categories = DB::table('categories')
                ->select(['id', 'name', 'updated_at', 'deleted_at'])
                ->whereNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->paginate($tableRows);
        } elseif ($is_active == 0) {
            $categories = DB::table('categories')
                ->select(['id', 'name', 'updated_at', 'deleted_at'])
                ->whereNotNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->paginate($tableRows);
        } else {
            $categories = DB::table('categories')
                ->orderBy('updated_at','desc')
                ->paginate($tableRows);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $categories;

        if (!$categories || $categories->isEmpty()) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        }

        return $this->result($code,$message,$data);
    }

    public function all(Request $request,$status)
    {
        $is_active = $status;

        if ($is_active == 1) {
            $categories = DB::table('categories')
                ->select(['id','name'])
                ->whereNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->get();

        } elseif ($is_active == 0) {
            $categories = DB::table('categories')
                ->select(['id','name'])
                ->whereNotNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->get();

        }else{
            $categories = DB::table('categories')
            ->orderBy('updated_at','desc')
            ->get();
        }

        if (!$categories || $categories->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        }else{
            $code =    200;
            $message = "Succefully Retrieved";
            $data = $categories;
        }

        return $this->result($code,$message,$data);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string'
        ]);

        return $fields->errors();

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

    public function archive(Request $request, $id)
    {
        $softDeletePayrollCategory = Category::where('id',$id)->delete();


        if ($softDeletePayrollCategory == 0) {
            $code = 403;
            $data = [];
            $message = "Data Not Found";
        }else{
            $code = 200;
            $data = [];
            $message = "Succefully Archived";
        }
        return $this->result($code,$message,$data);
    }

    public function restore(Request $request, $id)
    {
        $restoreSoftDelete = Category::onlyTrashed()->find($id)->restore();
        if ($restoreSoftDelete == 1) {
            $code = 200;
            $data = [];
            $message = "Succefully Restored";
        }else{
            $code = 403;
            $data = [];
            $message = "Data Not Found";
        }
        return $this->result($code,$message,$data);
    }

    public function search(Request $request,$status,$tableRows)
    {

        $tableRows = (int)$tableRows;
        $value = $request['value'];

        if($status == 1){
            $result = DB::table('categories')
            ->select(['id', 'name', 'updated_at', 'deleted_at'])
            ->where('name', 'like', '%' . $value . '%')
            ->whereNull('deleted_at')
            ->orderBy('updated_at','desc')
            ->paginate($tableRows);
        }else{
            $result = DB::table('categories')
            ->select(['id', 'name', 'updated_at', 'deleted_at'])
            ->where('name', 'like', '%' . $value . '%')
            ->whereNotNull('deleted_at')
            ->orderBy('updated_at','desc')
            ->paginate($tableRows);
        }

        if ($result->isEmpty()) {
            $code = 404;
            $message = "Data Not Found";
            $data = [];
        } else {
            $code = 200;
            $message = "Succefully Retrieved";
            $data = $result;
        }
        return $this->result($code,$message,$data);
    }
}
