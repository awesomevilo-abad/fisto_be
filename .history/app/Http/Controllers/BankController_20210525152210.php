<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if ($is_active == 'active') {
            $bank = DB::table('banks')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'inactive') {
            $bank = DB::table('banks')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $bank = DB::table('banks')
                ->orderBy('id')
                ->paginate(10);
        }

        if (!$bank || $bank->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $bank,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $bank,
            ];

        }

        return response($response);
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
        $fields = $request->validate([
            'bank_code' => 'required|string|unique:banks,bank_code',
            'bank_name' => 'required|string|unique:banks,bank_name',
            'bank_account' => 'required|string|unique:banks,bank_account',
            'bank_location' => 'required|string',
            'is_active' => 'required',

        ]);

        $new_bank = Bank::create([
            'bank_code' => $fields['bank_code']
            , 'bank_name' => $fields['bank_name']
            , 'bank_account' => $fields['bank_account']
            , 'bank_location' => $fields['bank_location']
            , 'is_active' => $fields['is_active'],
        ]);

        return [
            $response = [
                "code" => 200,
                "message" => "Succefully Created",
                "data" => $new_bank,
            ],
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = Bank::find($id);

        if (!$result) {
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $specific_bank = Bank::find($id);

        if (!$specific_bank) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_bank,
            ];
        } else {
            $specific_bank->bank_code = $request->get('bank_code');
            $specific_bank->bank_name = $request->get('bank_name');
            $specific_bank->bank_account = $request->get('bank_account');
            $specific_bank->bank_location = $request->get('bank_location');
            $specific_bank->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_bank,
            ];

        }
        return response($response);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
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
        $specific_bank = Bank::find($id);

        if (!$specific_bank) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_bank,
            ];
        } else {

            $specific_bank->is_active = 0;
            $specific_bank->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_bank,
            ];
        }
        return response($response);
    }

    public function search(Request $request)
    {
        $value = $request['value'];

        $result = Bank::where('bank_code', 'like', '%' . $value . '%')
            ->orWhere('bank_name', 'like', '%' . $value . '%')
            ->orWhere('bank_account', 'like', '%' . $value . '%')
            ->orWhere('bank_location', 'like', '%' . $value . '%')
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
