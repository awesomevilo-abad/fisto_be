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

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $department;

        if (!$department || $department->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $department;
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
        return $request;
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
