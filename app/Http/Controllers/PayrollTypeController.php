<?php

namespace App\Http\Controllers;

use App\Models\PayrollType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollTypeController extends Controller
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
            $payroll_types = DB::table('payroll_types')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $payroll_types = DB::table('payroll_types')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        }

        if(empty($is_active) == true){
            $payroll_types = DB::table('payroll_types')
            ->latest()
            ->paginate(10);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $payroll_types;

        if (!$payroll_types || $payroll_types->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $payroll_types;
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
            $payroll_types = DB::table('payroll_types')
                ->where('is_active', '=', 1)
                ->latest()
                ->get();

        } elseif ($is_active == 'false') {
            $payroll_types = DB::table('payroll_types')
                ->where('is_active', '=', 0)
                ->latest()
                ->get();

        }

        if(empty($is_active) == true){
            $payroll_types = DB::table('payroll_types')
            ->latest()
            ->get();
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $payroll_types;

        if (!$payroll_types || $payroll_types->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $payroll_types;
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
        $payroll_types = $request['title'];

        $fields = $request->validate([
            'type' => 'nullable',
            'is_active' => 'required',
        ]);

        $validate_payroll_types = DB::table('payroll_types')
        ->where('type',$fields['type'])->get();

        if(count($validate_payroll_types)>0){
            return $this->result(403,'Payroll Client Already exist',null);
        }

        $new_payroll_types = PayrollType::create([
            'type' => $fields['type']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_payroll_types);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PayrollType  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = PayrollType::find($id);

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
     * @param  \App\Models\PayrollType  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function edit(PayrollType $accountTitle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PayrollType  $accountTitle
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $payroll_types = $request['type'];
        $fields = $request->validate([
            'type' => ['unique:payroll_types,type,' . $id]
        ]);

        $specific_payroll_types = PayrollType::find($id);

        if (!$specific_payroll_types) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_payroll_types,
            ];
        } else {

            $specific_payroll_types->type = $request->get('type');
            $specific_payroll_types->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_payroll_types,
            ];

        }
        return response($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PayrollType  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function destroy(PayrollType $accountTitle)
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
        $specific_payroll_types = PayrollType::find($id);

        if (!$specific_payroll_types) {

            $response = [
                "code" => 404,
                "message" => "Data Not Found",
                "data" => $specific_payroll_types,
            ];
        } else {

            $specific_payroll_types->is_active = 0;
            $specific_payroll_types->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_payroll_types,
            ];
        }

        return response($response);

    }

    public function restore(Request $request, $id)
    {
        $specific_payroll_types = PayrollType::find($id);

        if(!$specific_payroll_types){

            return $this->result(404,"Data Not Found", $specific_payroll_types);
        }

            $specific_payroll_types->is_active = 1;
            $specific_payroll_types->save();

            return $this->result(200,"Succesfully Restored",$specific_payroll_types);
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

        $result = PayrollType::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('type', 'like', '%' . $value . '%');
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
