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

        $fields = $request->validate([
            'company_code' => 'required|string|unique:companies,company_code',
            'company_description' => 'required|string|unique:companies,company_description',
            'is_active' => 'required',

        ]);

        $new_company = Company::create([
            'company_code' => $fields['company_code']
            , 'company_description' => $fields['company_description']
            , 'is_active' => $fields['is_active'],
        ]);

        return [
            $response = [
                "code" => 200,
                "message" => "Succefully Created",
                "data" => $new_company,
            ],
        ];
    }
}
