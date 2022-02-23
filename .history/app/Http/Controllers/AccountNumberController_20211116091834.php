<?php

namespace App\Http\Controllers;

use App\Models\AccountNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountNumberController extends Controller
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
            $account_numbers = DB::table('account_numbers')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $account_numbers = DB::table('account_numbers')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        }

        if(empty($is_active) == true){
            $account_numbers = DB::table('account_numbers')
            ->latest()
            ->paginate(10);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $account_numbers;

        if (!$account_numbers || $account_numbers->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $account_numbers;
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
            $account_numbers = DB::table('account_numbers')
                ->where('is_active', '=', 1)
                ->latest()
                ->get();

        } elseif ($is_active == 'false') {
            $account_numbers = DB::table('account_numbers')
                ->where('is_active', '=', 0)
                ->latest()
                ->get();

        }

        if(empty($is_active) == true){
            $account_numbers = DB::table('account_numbers')
            ->latest()
            ->get();
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $account_numbers;

        if (!$account_numbers || $account_numbers->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $account_numbers;
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
        $account_numbers = $request['title'];

        $fields = $request->validate([
            'location_id' => 'nullable',
            'supplier_id' => 'nullable',
            'category' => 'nullable',
            'account_no' => 'nullable',
            'is_active' => 'required',
        ]);

        $validate_account_numbers = DB::table('account_numbers')
        ->where('account_no',$fields['account_no'])
        ->orWhere(function ($query) use ($fields){
            $query->where('location_id',$fields['location_id'])
            ->where('supplier_id',$fields['supplier_id'])
            ->where('category',$fields['category']);
        })->get();

        if(count($validate_account_numbers)>0){
            return $this->result(403,'Account Number Already exist',null);
        }

        $new_account_numbers = AccountNumber::create([
            'location_id' => $fields['location_id']
            ,'supplier_id' => $fields['supplier_id']
            ,'category' => $fields['category']
            ,'account_no' => $fields['account_no']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_account_numbers);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AccountNumber  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = AccountNumber::find($id);

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
     * @param  \App\Models\AccountNumber  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function edit(AccountNumber $accountTitle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AccountNumber  $accountTitle
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $account_numbers = $request['title'];

        $fields = $request->validate([
            'location_id' => 'nullable',Rule::unique('account_numbers')->where(function ($query) use ($request){
                $query->where('location_id',$request['location_id'])
                ->where('supplier_id',$request['supplier_id'])
                ->where('id', '!=', $id);
            }),

            'supplier_id' => 'nullable',Rule::unique('account_numbers')->where(function ($query) use ($request){
                $query->where('location_id',$request['location_id'])
                ->where('id', '!=', $id);
            }),

            'category' => 'nullable',Rule::unique('account_numbers')->where(function ($query) use ($request){
                $query->where('location_id',$request['location_id'])
                ->where('supplier_id',$request['supplier_id'])
                ->where('id', '!=', $id);
            }),
            'account_no' => ['unique:account_numbers,account_no,' . $id]
        ]);

        $specific_account_numbers = AccountNumber::find($id);

        if (!$specific_account_numbers) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_account_numbers,
            ];
        } else {

            $specific_account_numbers->location_id = $request->get('location_id');
            $specific_account_numbers->supplier_id = $request->get('supplier_id');
            $specific_account_numbers->category = $request->get('category');
            $specific_account_numbers->account_no = $request->get('account_no');
            $specific_account_numbers->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_account_numbers,
            ];

        }
        return response($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AccountNumber  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function destroy(AccountNumber $accountTitle)
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
        $specific_account_numbers = AccountNumber::find($id);

        if (!$specific_account_numbers) {

            $response = [
                "code" => 404,
                "message" => "Data Not Found",
                "data" => $specific_account_numbers,
            ];
        } else {

            $specific_account_numbers->is_active = 0;
            $specific_account_numbers->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_account_numbers,
            ];
        }

        return response($response);

    }

    public function restore(Request $request, $id)
    {
        $specific_account_numbers = AccountNumber::find($id);

        if(!$specific_account_numbers){

            return $this->result(404,"Data Not Found", $specific_account_numbers);
        }

            $specific_account_numbers->is_active = 1;
            $specific_account_numbers->save();

            return $this->result(200,"Succesfully Restored",$specific_account_numbers);
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

        $result = AccountNumber::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('location_id', 'like', '%' . $value . '%')
                ->orWhere('supplier_id', 'like', '%' . $value . '%')
                ->orWhere('category', 'like', '%' . $value . '%')
                ->orWhere('account_no', 'like', '%' . $value . '%');
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
