<?php

namespace App\Http\Controllers;

use App\Models\UtilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UtilityCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $is_active = $request->get('is_active');

        if ($is_active == 'true') {
            $utility_categories = DB::table('utility_categoriess')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $utility_categories = DB::table('utility_categoriess')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $utility_categories = DB::table('utility_categoriess')
                ->latest()
                ->paginate(10);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $utility_categories;

        if (!$utility_categories || $utility_categories->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $utility_categories;
        }

        return $this->result($code,$message,$data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $department = $request['department'];

        $fields = $request->validate([
            'code' => 'required|string|unique:departments,code',
            'is_active' => 'required',
            'department'=>'nullable',
            'company'=>'nullable'
        ]);

        $validate_department_company = DB::table('departments')
        ->where('department',$fields['department'])
        ->where('company',$fields['company'])->get();

        if(count($validate_department_company)>0){
            return $this->result(403,'Either department or department already exist',null);
        }

        $new_department = Department::create([
            'code' => $fields['code']
            , 'department' => $fields['department']
            , 'company' => $fields['company']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_department);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UtilityCategory  $utilityCategory
     * @return \Illuminate\Http\Response
     */
    public function show(UtilityCategory $utilityCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UtilityCategory  $utilityCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(UtilityCategory $utilityCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UtilityCategory  $utilityCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UtilityCategory $utilityCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UtilityCategory  $utilityCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(UtilityCategory $utilityCategory)
    {
        //
    }
}
