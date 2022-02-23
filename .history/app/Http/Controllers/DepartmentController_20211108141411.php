<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Models\Department;

class DepartmentController extends Controller
{
    public function index(){
        return Department::all()
    }

    public function store(Request $request){

    }
}
