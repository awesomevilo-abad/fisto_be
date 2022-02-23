<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Models\Department;

class DepartmentController extends Controller
{
    public function index(Request $request){
        $is_active = $request->get('is_active');

        if ($is_active == 'true') {
            $department = DB::table('departments')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $department = DB::table('departments')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $department = DB::table('departments')
                ->latest()
                ->paginate(10);
        }

        if (!$department || $department->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $department,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $department,
            ];

        }
        return response($response);
    }

    public function store(Request $request){

        $fields = $request->validate([
            'code' => 'required|string|unique:departments,code',
            'department' => 'required|string|unique:departments,department',
            'is_active' => 'required',

        ]);

        $new_department = Department::create([
            'code' => $fields['company_code']
            , 'department' => $fields['company_description']
            , 'is_active' => $fields['is_active'],
        ]);

        return [
            $response = [
                "code" => 200,
                "message" => "Succefully Created",
                "data" => $new_department,
            ],
        ];
    }
}
