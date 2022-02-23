<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Models\Department;

class DepartmentController extends Controller
{
    public function index(Request $request){
        
        return Department::all();
    }

    public function store(Request $request){
        return $request;
    }
}
