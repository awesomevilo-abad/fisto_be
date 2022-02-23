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
    public function index(Request $request)
    {
        $is_active = $request->get('is_active');

        if ($is_active == 'true') {
            $utility_categories = DB::table('utility_categories')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $utility_categories = DB::table('utility_categories')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $utility_categories = DB::table('utility_categories')
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
        $utility_categories = $request['utility_categories'];

        $fields = $request->validate([
            'category' => 'nullable',
            'is_active' => 'required',
        ]);

        $validate_utility_categories_company = DB::table('utility_categories')
        ->where('category',$fields['category'])->get();

        if(count($validate_utility_categories_company)>0){
            return $this->result(403,'Category already exist',null);
        }

        $new_utility_categories = UtilityCategory::create([
            'category' => $fields['category']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_utility_categories);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UtilityCategory  $utilityCategory
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $result = UtilityCategory::find($id);

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $result;

        if (!$result || $result->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $result;
        }

        return $this->result($code,$message,$data);
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
