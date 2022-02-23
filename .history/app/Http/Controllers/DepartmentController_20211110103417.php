<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Models\Department;

class DepartmentController extends Controller
{
    public function index(Request $request){
        return $request;
        return Department::all();
    }

    public function store(Request $request){


        $is_active = $request->get('is_active');

        if ($is_active == 'true') {
            $company = DB::table('companies')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $company = DB::table('companies')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $company = DB::table('companies')
                ->latest()
                ->paginate(10);
        }

        if (!$company || $company->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $company,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $company,
            ];

        }
        return response($response);
    }
}
