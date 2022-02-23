<?php

namespace App\Http\Controllers;

use App\Models\PayrollClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollClientController extends Controller
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
            $payroll_clients = DB::table('payroll_clients')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $payroll_clients = DB::table('payroll_clients')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        }

        if(empty($is_active) == true){
            $payroll_clients = DB::table('payroll_clients')
            ->latest()
            ->paginate(10);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $payroll_clients;

        if (!$payroll_clients || $payroll_clients->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $payroll_clients;
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
            $payroll_clients = DB::table('payroll_clients')
                ->where('is_active', '=', 1)
                ->latest()
                ->get();

        } elseif ($is_active == 'false') {
            $payroll_clients = DB::table('payroll_clients')
                ->where('is_active', '=', 0)
                ->latest()
                ->get();

        }

        if(empty($is_active) == true){
            $payroll_clients = DB::table('payroll_clients')
            ->latest()
            ->get();
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $payroll_clients;

        if (!$payroll_clients || $payroll_clients->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $payroll_clients;
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
        $payroll_clients = $request['title'];

        $fields = $request->validate([
            'client' => 'nullable',
            'is_active' => 'required',
        ]);

        $validate_payroll_clients = DB::table('payroll_clients')
        ->where('client',$fields['client'])->get();

        if(count($validate_payroll_clients)>0){
            return $this->result(403,'Payroll Client Already exist',null);
        }

        $new_payroll_clients = PayrollClient::create([
            'client' => $fields['client']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_payroll_clients);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PayrollClient  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = PayrollClient::find($id);

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
     * @param  \App\Models\PayrollClient  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function edit(PayrollClient $accountTitle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PayrollClient  $accountTitle
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $payroll_clients = $request['client'];
        $fields = $request->validate([
            'client' => ['unique:payroll_clients,client,' . $id]

        ]);

        $specific_payroll_clients = PayrollClient::find($id);

        if (!$specific_payroll_clients) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_payroll_clients,
            ];
        } else {

            $specific_payroll_clients->code = $request->get('code');
            $specific_payroll_clients->title = $request->get('title');
            $specific_payroll_clients->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_payroll_clients,
            ];

        }
        return response($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PayrollClient  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function destroy(PayrollClient $accountTitle)
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
        $specific_payroll_clients = PayrollClient::find($id);

        if (!$specific_payroll_clients) {

            $response = [
                "code" => 404,
                "message" => "Data Not Found",
                "data" => $specific_payroll_clients,
            ];
        } else {

            $specific_payroll_clients->is_active = 0;
            $specific_payroll_clients->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_payroll_clients,
            ];
        }

        return response($response);

    }

    public function restore(Request $request, $id)
    {
        $specific_payroll_clients = PayrollClient::find($id);

        if(!$specific_payroll_clients){

            return $this->result(404,"Data Not Found", $specific_payroll_clients);
        }

            $specific_payroll_clients->is_active = 1;
            $specific_payroll_clients->save();

            return $this->result(200,"Succesfully Restored",$specific_payroll_clients);
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

        $result = PayrollClient::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('code', 'like', '%' . $value . '%')
                ->orWhere('title', 'like', '%' . $value . '%');
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
