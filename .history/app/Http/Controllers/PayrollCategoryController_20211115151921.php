<?php

namespace App\Http\Controllers;

use App\Models\PayrollCategory;
use Illuminate\Http\Request;
use Illuminate\Support\

class PayrollCategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $is_active = $request->get('is_active');

        if ($is_active =='true') {
            $payroll_categories = DB::table('payroll_categories')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $payroll_categories = DB::table('payroll_categories')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        }

        if(empty($is_active) == true){
            $payroll_categories = DB::table('payroll_categories')
            ->latest()
            ->paginate(10);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $payroll_categories;

        if (!$payroll_categories || $payroll_categories->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $payroll_categories;
        }

        return $this->result($code,$message,$data);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        $is_active = $request->get('is_active');

        if ($is_active =='true') {
            $payroll_categories = DB::table('payroll_categories')
                ->where('is_active', '=', 1)
                ->latest()
                ->get();

        } elseif ($is_active == 'false') {
            $payroll_categories = DB::table('payroll_categories')
                ->where('is_active', '=', 0)
                ->latest()
                ->get();

        }

        if(empty($is_active) == true){
            $payroll_categories = DB::table('payroll_categories')
            ->latest()
            ->get();
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $payroll_categories;

        if (!$payroll_categories || $payroll_categories->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $payroll_categories;
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
        $payroll_categories = $request['title'];

        $fields = $request->validate([
            'category' => 'nullable',
            'is_active' => 'required',
        ]);

        $validate_payroll_categories = DB::table('payroll_categories')
        ->where('category',$fields['category'])->get();

        if(count($validate_payroll_categories)>0){
            return $this->result(403,'Payroll Client Already exist',null);
        }

        $new_payroll_categories = PayrollCategory::create([
            'category' => $fields['category']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_payroll_categories);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PayrollCategory  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = PayrollCategory::find($id);

        if (empty($result)) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $result,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $result,
            ];
        }
        return response($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PayrollCategory  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function edit(PayrollCategory $accountTitle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PayrollCategory  $accountTitle
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $payroll_categories = $request['category'];
        $fields = $request->validate([
            'category' => ['unique:payroll_categories,category,' . $id]
        ]);

        $specific_payroll_categories = PayrollCategory::find($id);

        if (!$specific_payroll_categories) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_payroll_categories,
            ];
        } else {

            $specific_payroll_categories->category = $request->get('category');
            $specific_payroll_categories->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_payroll_categories,
            ];

        }
        return response($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PayrollCategory  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function destroy(PayrollCategory $accountTitle)
    {
        //
    }

    #_________________________SPECIAL CASE________________________________
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function archive(Request $request, $id)
    {
        $specific_payroll_categories = PayrollCategory::find($id);

        if (!$specific_payroll_categories) {

            $response = [
                "code" => 404,
                "message" => "Data Not Found",
                "data" => $specific_payroll_categories,
            ];
        } else {

            $specific_payroll_categories->is_active = 0;
            $specific_payroll_categories->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_payroll_categories,
            ];
        }

        return response($response);

    }

    public function restore(Request $request, $id)
    {
        $specific_payroll_categories = PayrollCategory::find($id);

        if(!$specific_payroll_categories){

            return $this->result(404,"Data Not Found", $specific_payroll_categories);
        }

            $specific_payroll_categories->is_active = 1;
            $specific_payroll_categories->save();

            return $this->result(200,"Succesfully Restored",$specific_payroll_categories);
    }

    public function search(Request $request)
    {
        $value = $request['value'];

        if (isset($request['is_active'])) {
            if ($request['is_active'] == true) {

                $is_active = 1;
            } else {

                $is_active = 0;
            }
        } else {
            $is_active = 1;
        }

        $result = PayrollCategory::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('category', 'like', '%' . $value . '%');
            })
            ->latest()
            ->get();

        if ($result->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $result,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $result,
            ];
        }
        return response($response);
    }
}
